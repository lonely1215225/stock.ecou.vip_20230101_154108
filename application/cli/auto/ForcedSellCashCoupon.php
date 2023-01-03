<?php

namespace app\cli\auto;

use app\cli\client\WsClient;
use app\cli\sims\logic\OrderSell;
use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\Calc;
use app\index\logic\ForcedSellLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use util\OrderRedis;
use util\RedisUtil;
use util\ScriptRedis;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 强平脚本
 * 规则：
 * 时间：交易日 09:30 ~ 11:30 及 13:00 ~ 15:00
 *
 * Redis缓存:
 * -- 交易日 09:00 ~ 09:10 检测并缓存 策略金 及 持仓
 * -- KEY : user_strategy_{$userID} => 策略金（买入委托、买入委托失败、成交、追加保证金时，更新）
 * -- KEY : position_{$positionID} => 持仓缓存（成交、追加保证金时，更新）
 * -- KEY : forced_sell_checklist => 强平检测队列（存储所有需要检测的$positionID）
 */
class ForcedSellCashCoupon extends Command
{

    // 强平检测队列
    protected $queue = 'forced_sell_list';

    /** @var WsClient $localWsClient 本地WS服务的客户端 */
    protected $localWsClient;
    protected $localWsHost = '127.0.0.1';
    protected $localWsPort = 9502;
    protected $localWsName = '本地客户端';
    protected $localWsLinkDebug = true;

    protected function configure()
    {
        $this->setName('forced_sell_cash_coupon')->setDescription('代金券强平脚本');
    }

    /**
     * 入口
     *
     * @param Input $input
     * @param Output $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(5000, function () {
            try {
                $tradingDate = TradingUtil::currentTradingDate();
                $nowHi = intval(date('Hi'));
                if (TradingRedis::isTradingDate($tradingDate)) {
                    // 检测缓存
                    if ($nowHi >= 900 && $nowHi <= 910) {
                        // 缓存用户策略金（不含冻结资金）
                        if (ScriptRedis::isNotCachePosition()) {
                            OrderRedis::cachePositionUserStrategy();
                        }

                        // 缓存所有未完结持仓，初始化强平队列
                        if (ScriptRedis::isNotCacheUserStrategy()) {
                            OrderRedis::cacheAllPosition();
                        }
                    }

                    //代金券强制平仓检测
                    if ($nowHi >= 930 && $nowHi <= 1500) {
                        //代金券到期强平检测
                        $userAccountWhere = [
                            ['cash_coupon_time', '>', 1572364800],
                            ['cash_coupon_uptime', '>', 1572364800],
                        ];

                        $uAccount = UserAccount::where($userAccountWhere)->column('user_id,cash_coupon,cash_coupon_time,cash_coupon_frozen,cash_coupon_uptime', 'user_id');

                        foreach ($uAccount as $val) {
                            $isValid = isBuyCashCoupon($val['user_id']);
                            if ($isValid['forcedSell'] == true) {
                                $userID = $val['user_id'];
                                $list = OrderPosition::where(['user_id' => $userID, 'is_cash_coupon' => true, 'is_finished' => false])->field('id,user_id,market,stock_code,stock_id,volume_position,volume_for_sell,stop_loss_price,position_price,is_suspended,sum_deposit,is_monthly,is_cash_coupon')->select();

                                foreach ($list as $v) {
                                    $positionID = $v['id'];
                                    $market = $v['market'];
                                    $stockCode = $v['stock_code'];
                                    $volumePosition =  $v['volume_position'];
                                    // 如果持仓为0，不检测
                                    if ($volumePosition == 0) return null;

                                    // 取最新价
                                    $quotation = RedisUtil::getQuotationData($stockCode, $market);
                                    $nowPrice = $quotation['Price'];

                                    // 当前市值
                                    $nowStockValue = Calc::calcStockValue($nowPrice, $volumePosition);

                                    $strategy = bcsub($uAccount[$userID]['cash_coupon'], $uAccount[$userID]['cash_coupon_frozen'], 2);

                                    // 触发强平时的用户账户信息
                                    $userAccount = [
                                        'user_id' => $val['user_id'],
                                        'cash_coupon' => $uAccount[$userID]['cash_coupon'],
                                        'cash_coupon_frozen' => $uAccount[$userID]['cash_coupon_frozen'],
                                        'strategy' => $strategy,
                                        'additional_deposit' => 0,
                                    ];

                                    // 触发源持仓信息
                                    $originPosition = [
                                        'position_id' => $v['id'],
                                        'is_cash_coupon' => true,
                                        'stock' => $v['market'] . $v['stock_code'],
                                        'stock_value' => $nowStockValue,
                                        'volume_position' => $v['volume_position'],
                                        'volume_for_sell' => $v['volume_for_sell'],
                                        'price' => $nowPrice,
                                        'stop_loss_price' => $v['stop_loss_price'],
                                        'is_suspended' => $v['is_suspended'],
                                    ];

                                    // 执行强平
                                    $this->executeForcedSell($positionID, $originPosition, $userAccount, FORCED_SELL_TYPE_CASH_COUPON);
                                }
                            }
                        }
                    }

                }
            } catch (\Exception $e) {
                $this->output->writeln($e->getFile());
                $this->output->writeln($e->getLine());
                $this->output->writeln($e->getMessage());
                $this->output->writeln($e->getTraceAsString());
            }
        });

        // 启动强平客户端
        //$this->startSellClient();
    }

    /**
     * 启动本地WS客户端
     *
     * @throws \Exception
     */
    protected function startSellClient()
    {
        // 本地客户端，自动重连，定时心跳
        $this->localWsClient = new WsClient($this->localWsHost, $this->localWsPort, $this->localWsName, $this->localWsLinkDebug);
        $this->localWsClient->setReconnect();
        $this->localWsClient->setHeartbeat(['Key' => 'Heartbeat']);

        // onMessage
        $this->localWsClient->on('message', function (\Swoole\Http\Client $client, \swoole_websocket_frame $frame) {
        });

        $this->localWsClient->connect();
    }

    /**
     * 向上游发起强平委托
     *
     * @param int $positionID
     * @param array $originPosition
     * @param array $userAccount
     * @param string $type 平仓类型
     */
    protected function executeForcedSell($positionID, $originPosition, $userAccount, $type = FORCED_SELL_TYPE_REALTIME)
    {
        $this->output->writeln(date('Y-m-d H:i:s') . '准备向上游发起强平');
        $data = [
            'Key' => 'ForcedSell',
            'Token' => LOCAL_TRADING_TOKEN,
            'Data' => [
                'position_id' => $positionID,
                'type' => $type,
                'positionData' => $originPosition,
                'uAccountData' => $userAccount,
            ],
        ];
        $this->forcedSell($data);
    }
    /**
     * 解析强平数据，并发起强平
     *
     * @param array $recMsg
     * @param int   $clientID
     */
    public function forcedSell($recMsg)
    {
        go(function () use ($recMsg) {
            // 取触发源持仓ID
            $positionID       = $recMsg['Data']['position_id'] ?? '';
            $originPositionID = $positionID;
            if (!$positionID) return false;

            // 触发前的持仓数据，用户账户数据
            $forcedSellType     = $recMsg['Data']['type'];
            $originPositionData = $recMsg['Data']['positionData'];
            $uAccountData       = $recMsg['Data']['uAccountData'];

            // 获取持仓信息
            $position = OrderPosition::where('id', $positionID)
                ->where('is_finished', false)
                ->field('id,user_id,market,stock_code,stock_id,primary_account,volume_position,volume_for_sell,sum_buy_value_cost,sum_sell_value,sum_deposit,sum_deposit,stop_loss_price,is_finished,is_suspended,is_monthly,is_cash_coupon')
                ->find();

            $userID        = $position['user_id'];
            $volumeForSell = $position['volume_for_sell'];
            $isSuspended   = $position['is_suspended'];

            // 本持仓有可卖数量，卖掉本持仓的所有可卖数量，停牌股票不能卖出
            if ($volumeForSell > 0 && $isSuspended == false) {
                // 创建强平委托单，并向上游发起强平委托
                $isSystem = false;
                $result   = OrderSell::create($positionID, $userID, $volumeForSell, $isSystem);
            } else {
                // 本持仓没有可卖数量，执行顺序平仓
                $position = OrderPosition::where('user_id', $userID)
                    ->where('is_finished', false)
                    ->where('is_suspended', false)
                    ->where('volume_for_sell', '>', 0)
                    ->field('id,user_id,market,stock_code,stock_id,primary_account,volume_position,volume_for_sell,sum_buy_value_cost,sum_sell_value,sum_deposit,sum_deposit,stop_loss_price,is_finished,is_suspended,is_monthly,is_cash_coupon')
                    ->order('id', 'ASC')
                    ->limit(1)
                    ->find();

                if ($position) {
                    $positionID    = $position['id'];
                    $volumeForSell = $position['volume_for_sell'];
                    $isSystem      = true;
                    $result        = OrderSell::create($positionID, $userID, $volumeForSell, $isSystem);
                }
            }

            // 如果已经创建了委托单
            if (isset($result) && is_array($result)) {
                // 解析委托单结果
                list ($orderID, $stockID, $market, $stockCode, $volume) = $result;

                // 记录强平日志
                try {
                    // 被强平的持仓数据
                    $targetPositionData = [
                        'target_position_id' => $positionID,
                        'target_stock'       => $position['market'] . $position['stock_code'],
                        'sell_volume'        => $volume,
                        'sell_order'         => $positionID == $originPositionID ? FORCED_SELL_ORDER_SELF : FORCED_SELL_ORDER_IN_ORDER,
                        'order_id'           => $orderID,
                    ];

                    ForcedSellLogic::create($forcedSellType, $uAccountData, $originPositionData, $targetPositionData);
                    //file_put_contents("/data/wwwroot/trade", "强平forceSell{$forceSell}".PHP_EOL, FILE_APPEND);
                } catch (\Exception $e) {
                    $this->output->writeln($e->getFile());
                    $this->output->writeln($e->getLine());
                    $this->output->writeln($e->getMessage());
                    $this->output->writeln($e->getTraceAsString());
                }

                // 成交
                $direction = TRADE_DIRECTION_SELL;
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            }

            return true;
        });
    }

}