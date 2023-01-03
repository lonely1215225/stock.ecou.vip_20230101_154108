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
use util\RedisUtil;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 月管理费到期强平脚本
 * 规则：
 * -- 9:30 ~ 10:00 月持仓中 月管理费到期日期 < 当前日期的 订单进行顺序平仓
 * -- 14:50 ~ 15:00 月持仓中 月管理费到期日期 = 当前日期的 订单进行顺序平仓
 *
 * 时间：交易日 09:30 ~ 10:00 及 14:50 ~ 15:00
 *
 */
class ForcedSellMonthly extends Command
{
    /** @var WsClient $localWsClient 本地WS服务的客户端 */
    protected $localWsClient;
    protected $localWsHost = '127.0.0.1';
    protected $localWsPort = 9502;
    protected $localWsName = '本地客户端';
    protected $localWsLinkDebug = true;

    protected function configure()
    {
        $this->setName('forced_sell_monthly')->setDescription('月管理费强平脚本');
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

                if (TradingRedis::isTradingDate($tradingDate)) {
                    $nowHi = intval(date('Hi'));

                    // 按月收取管理费强平检测
                    if ($nowHi >= 930 && $nowHi <= 1000) {
                        $this->checkMonthly(true);
                    }

                    // 按月收取管理费强平检测
                    if ($nowHi >= 1450 && $nowHi <= 1500) {
                        $this->checkMonthly();
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
     * 遍历月管理费的持仓订单
     * 条件：
     *      is_finished = false 未完成的
     *      is_monthly = true   按月收取管理费
     *      volume_for_sell > 0 可买数量大于0
     *      monthly_expire_date >= date('Y-m-d')
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkMonthly($date_type = false)
    {
        $date_now = date('Y-m-d');
        $where[] = $date_type ? ['monthly_expire_date', '< time', $date_now] : ['monthly_expire_date', '=', $date_now];
        $orderPosition = OrderPosition:: where($where)
            ->where('is_finished', false)
            ->where('is_monthly', true)
            ->where('volume_for_sell', '>', 0)
            ->column('id,user_id,market,stock_id,stock_code,volume_position,volume_for_sell,stop_loss_price,is_suspended,is_monthly,monthly_expire_date', 'id');

        foreach ($orderPosition as $positionData) {
            $positionID = $positionData['id'];
            $userID = $positionData['user_id'];
            $market = $positionData['market'];
            $stockCode = $positionData['stock_code'];
            $volumePosition = $positionData['volume_position'];
            $volumeForSell = $positionData['volume_for_sell'];
            $stopLossPrice = $positionData['stop_loss_price'];
            $isSuspended = $positionData['is_suspended'];

            // 取最新价
            $quotation = RedisUtil::getQuotationData($stockCode, $market);
            $nowPrice = $quotation['Price'];

            // 当前市值
            $nowStockValue = Calc::calcStockValue($nowPrice, $volumePosition);

            // 获取用户账户信息
            $uAccount = UserAccount::where('user_id', $userID)->field('strategy_balance,frozen')->find();
            if (!$uAccount) return false;

            $strategyBalance = $uAccount['strategy_balance'];
            $frozen = $uAccount['frozen'];
            $strategy = bcsub($strategyBalance, $frozen, 2);

            // 触发强平时的用户账户信息
            $userAccount = [
                'user_id' => $userID,
                'strategy_balance' => $uAccount['strategy_balance'],
                'frozen' => $uAccount['frozen'],
                'strategy' => $strategy,
                'additional_deposit' => 0,
            ];

            // 触发源持仓信息
            $originPosition = [
                'position_id' => $positionID,
                'stock' => $market . $stockCode,
                'stock_value' => $nowStockValue,
                'volume_position' => $volumePosition,
                'volume_for_sell' => $volumeForSell,
                'price' => $nowPrice,
                'stop_loss_price' => $stopLossPrice,
                'is_suspended' => $isSuspended,
            ];

            // 执行强平
            $this->executeForcedSell($positionID, $originPosition, $userAccount);
        }

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
     */
    protected function executeForcedSell($positionID, $originPosition, $userAccount)
    {
        $this->output->writeln(date('Y-m-d H:i:s') . '准备向上游发起强平');
        $data = [
            'Key' => 'ForcedSellMonthly',
            'Token' => LOCAL_TRADING_TOKEN,
            'Data' => [
                'position_id' => $positionID,
                'type' => FORCED_SELL_TYPE_MONTHLY,
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
                $price    = 0;
                $isSystem = false;
                $result   = OrderSell::create($positionID, $userID, $price, $volumeForSell, $isSystem);
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
                    $price         = 0;
                    $volumeForSell = $position['volume_for_sell'];
                    $isSystem      = true;
                    $result        = OrderSell::create($positionID, $userID, $price, $volumeForSell, $isSystem);
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