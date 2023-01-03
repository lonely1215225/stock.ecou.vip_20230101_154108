<?php
namespace app\cli\auto;

use app\cli\sims\logic\OrderBuy;
use app\cli\sims\logic\OrderSell;
use app\cli\client\CoRedisPool;
//use app\cli\client\WsClient;
use app\common\model\Condition;
use app\common\model\OrderPosition;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\Order;
use app\index\logic\AccountLog;
use app\index\logic\AccountLogic;
use app\index\logic\Calc;
use app\index\logic\ConditionLogic;
use app\index\logic\ForcedSellLogic;
use sms\SmsUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use util\BasicData;
use util\QuotationRedis;
use util\ConditionRedis;
use util\OrderRedis;
use util\RedisUtil;
use util\ScriptRedis;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 获取持仓股票的行情数据
 * -- 命令 quotation
 *
 * @package app\cli\command
 */
class AtuoCondition extends Command
{
    /** @var WsClient $localWsClient 本地WS服务的客户端 */
    protected $localWsClient;
    protected $localWsHost      = '127.0.0.1';
    protected $localWsPort      = 9502;
    protected $localWsName      = '本地客户端';
    protected $localWsLinkDebug = true;

    /** @var CoRedisPool $redisPool Redis连接池 */
    protected $redisPool;
    public static $i = 0;

    protected function configure()
    {
        $this->setName('condition')->setDescription('追加保证金（强平）、条件单');
    }

    /**
     * 入口
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $this->startAutoClient();
        //$this->startLocalClient();
    }
    public function startAutoClient()
    {
        swoole_timer_tick(5000, function () {
            // 检测追加保证金
            $this->checkAdditionalDeposit();
            // 检测条件单
             $this->checkCondition();
        });
    }
    
    /**
     * 启动本地WS客户端
     *
     * @throws \Exception
     */
    public function startLocalClient()
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
     * 基于行情的成交触发
     *
     * @param $market
     * @param $stockCode
     */
    public function triggerDeal($market, $stockCode)
    {
        try {
            $data = [
                'Key'   => 'Deal',
                'Token' => LOCAL_TRADING_TOKEN,
                'Data'  => [
                    'market'     => $market,
                    'stock_code' => $stockCode,
                ],
            ];
            $this->localWsClient->send(json_encode($data));
        } catch (\Exception $e) {
        }
    }

    /**
     * 检测并执行追加保证金
     * -- 交易日 09:26 ~ 11:30 及 13:00 ~ 15:00
     */
    private function checkAdditionalDeposit()
    {
        //交易时间检测
        if($this->checkTradeTime() == false) return false;
        $list = OrderPosition::where('is_finished', false)
            ->where('market', 'NOT NULL')
            ->where('stock_code', 'NOT NULL')
            ->field('*')
            ->order('id', 'ASC')
            ->select()
            ->toArray();
        if(!$list)return $this->output->writeln('没有持仓数据');
        foreach ($list as $positionData) {
            // 行情数据
            $quotation = RedisUtil::getQuotationData($positionData['stock_code'], $positionData['market']);
            // 当前价
            $price = $quotation['Price'];
            //print_r($positionData);echo "当前价格：".$price;
            // 持仓均价
            $positionPrice = $positionData['position_price'];
            if ($positionPrice <= 0) continue;
            // 止损价
            $stopLossPrice = $positionData['stop_loss_price'];
            // 如果当前价【小于等于】止损价，追加1%保证金
            if ($price <= $stopLossPrice) {
                if (ScriptRedis::isAdditionalRunAble($positionData['id'])) {
                    $this->additionalDeposit($positionData, $price);
                }
            } elseif (($price - $positionPrice) / $positionPrice <= -0.05) {
                // 亏损5%短信警告
                try {
                    // 一票一天内只警告一次
                    $key = 'warning_' . $positionData['id'];
                    if (!RedisUtil::redis()->exists($key)) {
                        // 没发送过警告短信，发送警告短信
                        RedisUtil::redis()->set($key, 'yes');
                        RedisUtil::redis()->expireAt($key, RedisUtil::midnight());

                        $mobile   = User::where('id', $positionData['user_id'])->value('mobile');
                        $position = OrderPosition::where('id', $positionID)
                            ->field('id,volume_position,volume_for_sell,sum_buy_value_cost,sum_sell_value,sum_deposit,sum_deposit,stop_loss_price')
                            ->find();
                        $loss     = abs(($price - $positionPrice) * $position['volume_position']);
                        SmsUtil::sendWarningSms($mobile, $market, $stockCode, $position['sum_deposit'], $loss);
                    }
                } catch (\Exception $e) {
                    
                }
            }
        }

        return true;
    }

    /**
     * 执行追加保证金 或 强平
     *
     * @param array $positionData 缓存中持仓数据
     * @param float $nowPrice 现价
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function additionalDeposit($positionData, $nowPrice)
    {
        $userID     = $positionData['user_id'];
        $positionID = $positionData['id'];
        $market     = $positionData['market'];
        $stockCode  = $positionData['stock_code'];
        $stockID    = $positionData['stock_id'];

        // 获取持仓信息
        $position = OrderPosition::where('id', $positionID)
            ->field('id,user_id,market,stock_code,stock_id,primary_account,volume_position,volume_for_sell,sum_buy_value_cost,sum_sell_value,sum_deposit,sum_deposit,stop_loss_price,is_finished,is_suspended,is_cash_coupon')
            ->find();

        // 没有持仓，或者持仓已完结，将本持仓从检测列表中移除
        if (!$position || $position['volume_position'] <= 0 || $position['is_finished'] == true) {
            $key = "{$market}{$stockCode}_position_set";
            RedisUtil::redis()->sRem($key, $positionID);

            return false;
        }

        $volumePosition  = $position['volume_position'];
        $sumBuyValueCost = $position['sum_buy_value_cost'];
        $sumSellValue    = $position['sum_sell_value'];
        $sumDeposit      = $position['sum_deposit'];

        // 用户账户
        $uAccount = UserAccount::where('user_id', $userID)->field('strategy_balance,frozen')->find();
        if (!$uAccount) return false;

        // 策略金变动前
        $beforeStrategy = $uAccount['strategy_balance'];
        $frozen         = $uAccount['frozen'];

        // 当前市值
        $stockValue = Calc::calcStockValue($nowPrice, $volumePosition);

        // 1%保证金
        $addDeposit = bcmul($stockValue, 0.01, 2);

        // 策略金（扣除冻结资金）
        $strategy = bcsub($beforeStrategy, $frozen, 2);

        // 调试信息
        $this->output->writeln(date('Y-m-d H:i:s'));
        $this->output->writeln('当前用户:' . $userID);
        $this->output->writeln('用户账户信息:' . json_encode($uAccount));
        $this->output->writeln('当前持仓编号:' . $positionID);
        $this->output->writeln('当前市值:' . $stockValue);
        $this->output->writeln('当前策略金:' . $strategy);
        $this->output->writeln('需要追加的保证金:' . $addDeposit);

        if (bccomp($strategy, $addDeposit, 2) >= 0) {
            $this->output->writeln(date('Y-m-d H:i:s') . ' 策略金充足，开始追加');
            // 策略金（不含冻结资金）足够追加保证金，执行追加保证金操作
            Db::startTrans();
            try {
                // 用户账户：扣保证金
                $uAccount['strategy_balance'] = Db::raw("strategy_balance-{$addDeposit}");
                // 保存账户
                $uaRet = $uAccount->save();

                // 新的持仓保证金
                $sumDepositAfter = bcadd($sumDeposit, $addDeposit, 2);

                // 计算新的止损价
                $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDepositAfter, $volumePosition);

                // 写入策略金变动日志（追加保证金）
                $slRet = AccountLog::strategyAdditionalDeposit($userID, $market, $stockID, $stockCode, $positionID, $stopLossPrice, $volumePosition, $addDeposit, $beforeStrategy);

                // 持仓：增加保证金，更新止损价
                $position['sum_deposit']     = $sumDepositAfter;
                $position['stop_loss_price'] = $stopLossPrice;
                // 保存持仓
                $position->save();
                $opRows = $position->getNumRows();
                
                if ($uaRet && $slRet && $opRows) {
                    Db::commit();

                    // 更新持仓数据缓存
                    OrderRedis::cachePosition($positionID);

                    // 更新用户的总保证金
                    AccountLogic::updateTotalDeposit($userID);

                    // 更新用户的策略金缓存
                    OrderRedis::cacheUserStrategy($userID);
                    
                    try {
                        // 追加保证金短信通知
                        $mobile = User::where('id', $positionData['user_id'])->value('mobile');
                        SmsUtil::sendAddDeposit($mobile, $positionID, $market, $stockCode, $addDeposit, $volumePosition, $stopLossPrice);
                    } catch (\Exception $e) {
                        $this->output->writeln($e);
                    }
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
                $this->output->writeln($e);
            }
        } else {
            // 策略金不足，发起强平，不能强平停牌股票
            //$this->output->writeln(date('Y-m-d H:i:s') . ' 策略金不足，发起强平'.json_encode($position));

            // 触发强平时的用户账户信息
            $userAccount = [
                'user_id'            => $userID,
                'strategy_balance'   => $beforeStrategy,
                'frozen'             => $frozen,
                'strategy'           => $strategy,
                'additional_deposit' => $addDeposit,
            ];

            // 触发源持仓信息
            $originPosition = [
                'position_id'     => $position['id'],
                'stock'           => $position['market'] . $position['stock_code'],
                'stock_value'     => $stockValue,
                'volume_position' => $position['volume_position'],
                'volume_for_sell' => $position['volume_for_sell'],
                'price'           => $nowPrice,
                'stop_loss_price' => $position['stop_loss_price'],
                'is_suspended'    => $position['is_suspended'],
                'is_cash_coupon'  => $position['is_cash_coupon']
            ];
            //$this->output->writeln(date('Y-m-d H:i:s') . ' 策略金不足，发起强平'.'账户'.json_encode($userAccount).'持仓'.json_encode($originPosition));
            $this->executeForcedSell($positionID, $originPosition, $userAccount);
        }

        return true;
    }

    /**
     * 向上游发起强平委托
     *
     * @param int   $positionID
     * @param array $originPosition
     * @param array $userAccount
     */
    protected function executeForcedSell($positionID, $originPosition, $userAccount)
    {
        $this->output->writeln(date('Y-m-d H:i:s') . ' 准备向上游发起强平');
        $data = [
            'Key'   => 'ForcedSell',
            'Token' => LOCAL_TRADING_TOKEN,
            'Data'  => [
                'position_id'  => $positionID,
                'type'         => FORCED_SELL_TYPE_QUOTATION,
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
    /**
     * 检测执行条件单
     *
     * @param $quotation
     *
     * @return bool
     */
    protected function checkCondition()
    {
        try {
            //交易时间检测
            if($this->checkTradeTime() == false) return false;//$this->output->writeln('不在交易时间')
            $list = Condition::where('trading_date', TradingUtil::currentTradingDate())
                ->where('state', CONDITION_STATE_ING)
                ->field('*')
                ->order('id', 'DESC')
                ->select()
                ->toArray();
            if (count($list)) {
                foreach ($list as $id => $item) {
                    // 行情数据
                    $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);
                    // 当前价
                    $price = $quotation['Price'];
                    //print_r($item);echo "当前价格：".$price;
                    $triggerCompare = $item['trigger_compare'];
                    $triggerPrice   = $item['trigger_price'];
                    if (($triggerCompare == CONDITION_COMPARE_EGT && $price >= $triggerPrice) || $triggerCompare == CONDITION_COMPARE_ELT && $price <= $triggerPrice) {
                        // 标注条件单已触发
                        ConditionLogic::setConditionEnd($item['id'], $item['market'], $item['stock_code']);
                        // 创建委托单
                        //print_r($item['direction']);
                        if($item['direction'] == TRADE_DIRECTION_BUY) {
                            //$this->output->writeln($item['id']." ".$item['stock_code']." ".$item['direction']." ".$item['volume']);
                            $this->conditionBuy($item);
                        }
                        if($item['direction'] == TRADE_DIRECTION_SELL){
                            $this->conditionSell($item);
                        }
                    }
                    
                }
            }
        } catch (\Exception $e) {
            //$this->output->writeTrace($e);
        }

        return false;
    }
    // 条件单：委买
    public function conditionBuy($recMsg)
    {
        try {
            $isSystem    = false;
            $isCondition = true;
            $conditionID = $recMsg['id'];
            $userID      = $recMsg['user_id'];
            $stockID     = $recMsg['stock_id'];
            $stockCode   = $recMsg['stock_code'];
            $market      = $recMsg['market'];
            $volume      = $recMsg['volume'];
            $direction   = TRADE_DIRECTION_BUY;
            $price       = QuotationRedis::getStallsPrice($market, $stockCode, -1, 'only');
            $result      = OrderBuy::create($userID, $stockID, $stockCode, $market, $volume, $price, $isSystem, $isCondition);
            // 标注条件单已触发
            //ConditionLogic::setConditionEnd($conditionID, $market, $stockCode);
            // 客户端反馈：条件单已触发
            $this->output->writeln(date('Y-m-d H:i:s') ." ".$conditionID." ".$stockCode. ' 买入条件单已触发');
            
            if (is_array($result)) {
                list ($orderID, $stockID, $market, $stockCode, $volume) = $result;
                // （创建委托单成功）设置条件单触发执行状态
                ConditionLogic::setConditionExecResult($conditionID, $orderID);

                // 成交
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            } else {
                // （创建委托单失败）设置条件单触发执行状态
                ConditionLogic::setConditionExecResult($conditionID, 0, $result);
            }
        } catch (\Exception $e) {
            $this->output->writeTrace($e);
        }
    }
     // 条件单，委卖
    public function conditionSell($recMsg)
    {
        try {
            $isCondition = true;
            $conditionID = $recMsg['id'];
            $positionID  = $recMsg['position_id'];
            $userID      = $recMsg['user_id'];
            $volume      = $recMsg['volume'];
            $isSystem    = false;
            $direction   = TRADE_DIRECTION_SELL;
            $result      = OrderSell::create($positionID, $userID, $volume, $isSystem, $isCondition);
            // 标注条件单已触发
            //ConditionLogic::setConditionEnd($conditionID, $market, $stockCode);
            // 客户端反馈：条件单已触发
            //$this->sendToClient($clientID, 'Condition', 1, '条件单已触发');
            $this->output->writeln(date('Y-m-d H:i:s') ." ".$conditionID." ".$stockCode. ' 卖出条件单已触发');
            
            if (is_array($result)) {
                list ($orderID, $stockID, $market, $stockCode, $volume) = $result;
                // （创建委托单成功）设置条件单触发执行状态
                ConditionLogic::setConditionExecResult($conditionID, $orderID);
                // 成交
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            } else {
                // （创建委托单失败）设置条件单触发执行状态
                ConditionLogic::setConditionExecResult($conditionID, 0, $result);
            }
        } catch (\Exception $e) {
            $this->output->writeTrace($e);
        }
    }
    /**
     * 加入待成交列表
     *
     * @param int    $orderID
     * @param int    $volume
     * @param string $direction
     * @param string $market
     * @param string $stockCode
     * @param bool   $delay
     */
    public function addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode, $delay = true)
    {
        go(function () use ($orderID, $volume, $direction, $market, $stockCode, $delay) {
            // 加入持仓订阅列表
            QuotationRedis::addPositionSubscribe($market, $stockCode);

            // 是否延迟成交
            if ($delay) \Co::sleep(mt_rand(800, 2000) / 1000);

            // 加入待成交列表
            $key   = 'waiting_deal_' . $market . $stockCode;

            $value = implode(',', [$orderID, $volume, $direction, $market, $stockCode]);
            RedisUtil::redis()->rPush($key, $value);
            RedisUtil::redis()->expireAt($key, RedisUtil::midnight());
        });
    }
    /**
     * 发起条件单委买
     *
     * @param $condition
     */
    protected function executeConditionBuy($condition)
    {
        //$this->output->writeln(date('Y-m-d H:i:s') . ' 发起条件单委买');
        $data = [
            'Key'   => 'ConditionBuy',
            'Token' => LOCAL_TRADING_TOKEN,
            'Data'  => [
                'condition_id' => $condition['id'],
                'user_id'      => $condition['user_id'],
                'stock_id'     => $condition['stock_id'],
                'stock_code'   => $condition['stock_code'],
                'market'       => $condition['market'],
                'volume'       => $condition['volume'],
            ],
        ];
        $this->localWsClient->send(json_encode($data));
    }

    /**
     * 发起条件单委卖
     *
     * @param $condition
     */
    protected function executeConditionSell($condition)
    {
        //$this->output->writeln(date('Y-m-d H:i:s') . ' 发起条件单委卖');
        $data = [
            'Key'   => 'ConditionSell',
            'Token' => LOCAL_TRADING_TOKEN,
            'Data'  => [
                'condition_id' => $condition['id'],
                'position_id'  => $condition['order_position_id'],
                'user_id'      => $condition['user_id'],
                'volume'       => $condition['volume'],
            ],
        ];
        $this->localWsClient->send(json_encode($data));
    }

    /**
     * 检查是否在交易时间内
     * @return bool
     */
    public function checkTradeTime(){
        // 仅交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return false;

        // 仅在 09:26 ~ 11:30 及 13:00 ~ 15:00 缓存行情
        $nowHi = intval(date('Hi'));
        if (!(($nowHi >= 926 && $nowHi <= 1130) || ($nowHi >= 1300 && $nowHi <= 1500))) return false;

        return true;
    }
}
