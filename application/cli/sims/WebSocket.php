<?php
namespace app\cli\sims;
use app\cli\sims\logic\OrderBuy;
use app\cli\sims\logic\OrderCancel;
use app\cli\sims\logic\OrderCancelMsg;
use app\cli\sims\logic\OrderSell;
use app\cli\sims\logic\TradedMsg;
use app\common\model\OrderPosition;
use app\index\logic\ConditionLogic;
use app\index\logic\ForcedSellLogic;
use swooldy\server\WebSocketServer;
use think\console\Input;
use think\console\Output;
use util\MonthlyNoticeRedis;
use util\NoticeRedis;
use util\QuotationRedis;
use util\RedisUtil;
use util\WsServerRedis;

/**
 * 模拟股票交易服务器
 */
class WebSocket extends WebSocketServer
{

    protected $host = '0.0.0.0';
    protected $port = 30200;

    protected function configure()
    {
        parent::configure();

        $this->setName('ws:sims')
            ->setDescription('模拟交易服务器(WebSocket)');
    }

    protected function execute(Input $input, Output $output)
    {
        parent::execute($input, $output);
    }

    // Server On Start
    public function onStart(\Swoole\Websocket\Server $server)
    {
    }

    // Server On WorkerStart
    public function onWorkerStart(\Swoole\Websocket\Server $server, int $workerID)
    {
        // 实时公告
        $this->notice();
        // $this->monthly_position_notice();
        error_reporting(E_ALL & ~E_NOTICE);
        // 清空所有客户端关系缓存数据
        RedisUtil::redis()->del(RedisUtil::redis()->keys('ws_client_*'));
    }

    // Server On Client Open
    public function onOpen(\Swoole\Websocket\Server $server, \Swoole\Http\Request $request)
    {
        
    }

    // Server On Client Message
    public function onMessage(\Swoole\Websocket\Server $server, \Swoole\Websocket\Frame $frame)
    {
        // 客户端ID
        $clientID = $frame->fd;
        // 解析客户端数据
        $recMsg = $this->parseClientData($frame->data, $clientID);
        $token  = $recMsg['Token'] ?? '';
        switch ($recMsg['Key']) {
            // 委托订单
            case 'Buy':
                $this->buy($recMsg, $clientID);
                break;

            case 'Sell':
                $this->sell($recMsg, $clientID);
                break;

            case 'Cancel':
                $this->cancel($recMsg, $clientID);
                break;

            case 'Heartbeat':
                // 心跳
                $this->sendToClient($clientID, 'Heartbeat', 1);
                break;

            case 'NoticeRead':
                // 设置用户已读公告
                $this->setNoticeRead($clientID, $recMsg);
                break;

            case 'MonthlyNoticeRead':
                // 设置用户已读月管理费到期公告
                $this->setMonthlyNoticeRead($clientID, $recMsg);
                break;

            case 'ForcedSell':
                // 发起强平，该入口不对外开放
                if ($token != LOCAL_TRADING_TOKEN) return false;
                $this->forcedSell($recMsg, $clientID);
                break;

            case 'ForcedSellMonthly':
                // 发起月管理费到期强平，该入口不对外开放
                if ($token != LOCAL_TRADING_TOKEN) return false;
                $this->forcedMonthlySell($recMsg, $clientID);
                break;

            case 'ConditionBuy':
                // 条件单委买，该入口不对外开放
                $this->sendToClient($clientID,'ConditionBuy', 1, '条件单委买入口',$recMsg);
                if ($token != LOCAL_TRADING_TOKEN) return false;
                $this->conditionBuy($clientID, $recMsg);
                break;

            case 'ConditionSell':
                // 条件单委卖，该入口不对外开放
                if ($token != LOCAL_TRADING_TOKEN) return false;
                $this->conditionSell($clientID, $recMsg);
                break;

            case 'Deal':
                // 行情信号，该入口不对外开放
                // 基于行情触发成交
                if ($token != LOCAL_TRADING_TOKEN) return false;
                $this->triggerDeal($recMsg);
                break;
            case 'Subscribe':
                //股票行情
                $this->sendToClient($clientID,'MarketData', 1, '实时行情获取成功',$recMsg);
                break;
            default:
                $this->sendToClient($clientID, 'Error', 0, '无效的参数');
        }
    }

    // Server On Client Close
    public function onClose(\Swoole\WebSocket\Server $server, int $fd)
    {
    }

    /**
     * 解析客户端数据，只接受json格式的数据
     *
     * @param string $data 客户端数据
     * @param int    $clientID 客户端ID
     *
     * @return array|mixed
     */
    protected function parseClientData($data, $clientID)
    {
        // 解析数据
        try {
            $recMsg = json_decode($data, true);
        } catch (\Exception $e) {
            $recMsg['Key'] = 'Error';
        }
        
        // 用户请求合法性判断
        $avlKeys = array('Buy', 'Sell', 'Cancel', 'Heartbeat', 'ForcedSell', 'ForcedSellMonthly', 'NoticeRead', 'MonthlyNotice', 'ConditionBuy', 'ConditionSell', 'Deal', 'Subscribe');
        if (!isset($recMsg['Key']) || !in_array($recMsg['Key'], $avlKeys)) {
            $recMsg['Key'] = 'Error';
        }
        //var_dump($recMsg['Key']);
        // 缓存token与$clientID之间的关系
        $token = $recMsg['Token'] ?? '';
        if ($token) {
            $userData = $token ? RedisUtil::getToken($token) : [];
            $userID   = $userData['user_id'] ?? 0;

            // 缓存 $userID 与 $clientID 之间的关系
            WsServerRedis::cacheWsClient($userID, $clientID);
        }

        return $recMsg;
    }

    /**
     * 检查行情数据是否合理
     * -- 是否有行情
     * -- 行情时间是否合理
     *
     * @param $market
     * @param $stockCode
     *
     * @return bool
     */
    protected function checkQuotation($market, $stockCode)
    {
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        $lastTime  = $quotation['last_time'] ?? 0;
        if (empty($quotation) || time() - $lastTime >= 10) {
            // 加入合约股票订阅列表
            QuotationRedis::addActiveSubscribe($market, $stockCode);

            return false;
        }

        return true;
    }
    /**
     * 委托买入
     *
     * @param array $recMsg
     * @param int   $clientID
     */
    public function buy($recMsg, $clientID)
    {
        go(function () use ($recMsg, $clientID) {
            // 检查行情
            /*if (isset($recMsg['Data']['market']) && isset($recMsg['Data']['stock_code'])) {
                $market    = $recMsg['Data']['market'];
                $stockCode = $recMsg['Data']['stock_code'];
                if (RedisUtil::isStockExist($stockCode, $market)) {
                    $ret = $this->checkQuotation($market, $stockCode);
                    // 行情数据不合理，休眠0.5秒后继续执行
                    if (!$ret) \Co::sleep(0.5);
                }
            }*/

            // 创建委托单
            $result = OrderBuy::execute($recMsg);
            //$this->sendToClient($clientID, 'OKOKOKOKOK', 1, $result);
            if (is_array($result)) {
                // 委托成功
                $this->sendToClient($clientID, 'Buy', 1, '委托成功');

                // 成交
                list($orderID, $stockID, $market, $stockCode, $volume) = $result;
                $direction = TRADE_DIRECTION_BUY;
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            } else {
                // 系统处理失败
                $this->sendToClient($clientID, 'Buy', 0, $result);
            }
        });
    }

    /**
     * 委托卖出
     *
     * @param $recMsg
     * @param $clientID
     */
    public function sell($recMsg, $clientID)
    {
        go(function () use ($recMsg, $clientID) {
            // 创建委托单
            $result = OrderSell::execute($recMsg);

            if (is_array($result)) {
                // 委托成功
                $this->sendToClient($clientID, 'Sell', 1, '委托成功');

                // 成交
                list($orderID, $stockID, $market, $stockCode, $volume) = $result;
                $direction = TRADE_DIRECTION_SELL;
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            } else {
                // 系统处理失败
                $this->sendToClient($clientID, 'Sell', 0, $result);
            }
        });
    }

    public function cancel($recMsg, $clientID)
    {
        go(function () use ($recMsg, $clientID) {
            // 撤单系统处理
            $result = OrderCancel::execute($recMsg);
            if (is_array($result)) {
                list ($orderID) = $result;
                $cancelRet = OrderCancelMsg::execute($orderID);
                if (is_array($cancelRet)) {
                    list ($userID, $code, $msg) = $cancelRet;
                    $this->sendToClient($clientID, 'Cancel', 1, $msg);
                }
            } else {
                // 系统处理失败
                $this->sendToClient($clientID, 'Cancel', 403, $result);
            }
        });
    }

    /**
     * 解析强平数据，并发起强平
     *
     * @param array $recMsg
     * @param int   $clientID
     */
    public function forcedSell($recMsg, $clientID)
    {
        go(function () use ($recMsg, $clientID) {
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

    /**
     * 解析月费到期强平数据，并发起强平
     *
     * @param array $recMsg
     * @param int   $clientID
     */
    public function forcedMonthlySell($recMsg, $clientID)
    {
        go(function () use ($recMsg, $clientID) {
            // 取触发源持仓ID
            $positionID         = $recMsg['Data']['position_id'] ?? '';
            $uAccountData       = $recMsg['Data']['uAccountData'];
            $userID             = $uAccountData['user_id'];
            $forcedSellType     = $recMsg['Data']['type'];
            $originPositionData = $recMsg['Data']['positionData'];
            $uAccountData       = $recMsg['Data']['uAccountData'];

            $isSystem = true;
            $result   = OrderSell::create($positionID, $userID, 0, $isSystem);

            // 如果已经创建了委托单
            if (is_array($result)) {
                // 解析委托单结果
                list ($orderID, $stockID, $market, $stockCode, $volume) = $result;
                // 记录强平日志
                try {
                    // 被强平的持仓数据
                    $targetPositionData = [
                        'target_position_id' => $positionID,
                        'target_stock'       => $market . $stockCode,
                        'sell_volume'        => $volume,
                        'sell_order'         => FORCED_SELL_ORDER_SELF,
                        'order_id'           => $orderID,
                    ];
                    ForcedSellLogic::create($forcedSellType, $uAccountData, $originPositionData, $targetPositionData);
                } catch (\Exception $e) {
                    $this->writeTrace($e);
                }

                // 1-2后成交
                \Co::sleep(mt_rand(1, 2));

                // 成交
                $direction = TRADE_DIRECTION_SELL;
                $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            }

            return true;
        });
    }

    // 实时公告
    public function notice()
    {
        try {
            swoole_timer_tick(1000, function () {
                // 客户端列表
                $clientList = $this->server->connection_list(0);
                if (is_array($clientList) && count($clientList)) {
                    foreach ($clientList as $fd) {
                        $userID      = WsServerRedis::getWsUserID($fd);
                        $notPushList = NoticeRedis::getNotPushList($userID);
                        if (count($notPushList)) {
                            $this->sendToClient($fd, 'Notice', 1, '', $notPushList);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    // 每个十分钟推送一次月管理费到期公告
    public function monthly_position_notice()
    {
        try {
            swoole_timer_tick(600000, function () {
                // 客户端列表
                $clientList = $this->server->connection_list(0);
                if (is_array($clientList) && count($clientList)) {
                    foreach ($clientList as $fd) {
                        $userID = WsServerRedis::getWsUserID($fd);
                        MonthlyNoticeRedis::catchPositionAllMonthly($userID);
                        $notPushList = MonthlyNoticeRedis::getNotPushList($userID);
                        if (count($notPushList)) {
                            $this->sendToClient($fd, 'Notice', 1, '', $notPushList);
                        }
                    }
                }
            });

        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    // 设置公告已读
    public function setNoticeRead($clientID, $recMsg)
    {
        $noticeID = $recMsg['Data']['notice_id'] ?? 0;
        $userID   = WsServerRedis::getWsUserID($clientID);
        NoticeRedis::setNoticeRead($userID, $noticeID);
    }

    // 设置月管理费公告已读
    public function setMonthlyNoticeRead($clientID, $recMsg)
    {
        $noticeID = $recMsg['Data']['position_id'] ?? 0;
        $userID   = WsServerRedis::getWsUserID($clientID);
        MonthlyNoticeRedis::setNoticeRead($userID, $noticeID);
    }

    // 条件单：委买
    public function conditionBuy($clientID, $recMsg)
    {
        try {
            $isSystem    = false;
            $isCondition = true;
            $conditionID = $recMsg['Data']['condition_id'];
            $userID      = $recMsg['Data']['user_id'];
            $stockID     = $recMsg['Data']['stock_id'];
            $stockCode   = $recMsg['Data']['stock_code'];
            $market      = $recMsg['Data']['market'];
            $volume      = $recMsg['Data']['volume'];
            $direction   = TRADE_DIRECTION_BUY;
            $price       = QuotationRedis::getStallsPrice($market, $stockCode, -1, 'only');
            $result      = OrderBuy::create($userID, $stockID, $stockCode, $market, $volume, $price, $isSystem, $isCondition);
            
            // 客户端反馈：条件单已触发
            $this->sendToClient($clientID, 'Condition', 1, '条件单已触发');
            
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
            $this->writeTrace($e);
        }
    }

    // 条件单，委卖
    public function conditionSell($clientID, $recMsg)
    {
        try {
            $isCondition = true;
            $conditionID = $recMsg['Data']['condition_id'];
            $positionID  = $recMsg['Data']['position_id'];
            $userID      = $recMsg['Data']['user_id'];
            $volume      = $recMsg['Data']['volume'];
            $isSystem    = false;
            $direction   = TRADE_DIRECTION_SELL;
            $result      = OrderSell::create($positionID, $userID, $volume, $isSystem, $isCondition);

            // 客户端反馈：条件单已触发
            $this->sendToClient($clientID, 'Condition', 1, '条件单已触发');

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
            $this->writeTrace($e);
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
     * 行情触发成交
     *
     * @param array $recMsg
     */
    public function triggerDeal($recMsg)
    {
        go(function () use ($recMsg) {
            $market = $recMsg['Data']['market'];;
            $stockCode = $recMsg['Data']['stock_code'];;
            $key = 'waiting_deal_' . $market . $stockCode;

            $len = RedisUtil::redis()->lLen($key);
            if ($len) {
                for ($i = 0; $i < $len; $i++) {
                    $value = RedisUtil::redis()->lPop($key);
                    list ($orderID, $volume, $direction, $market, $stockCode) = explode(',', $value);
                    if($volume) {
                        $this->deal($orderID, $volume, $direction, $market, $stockCode);
                    }
                }
            }
        });
    }

    /**
     * 成交
     * -- 不可直接调用
     *
     * @param int    $orderID 委托单编号
     * @param int    $volume 委托数量
     * @param string $direction 买卖方向
     * @param string $market 证券市场
     * @param string $stockCode 股票代码
     *
     * @return bool
     */
    public function deal($orderID, $volume, $direction, $market, $stockCode)
    {
        // 检查一档价格
        $stall = TRADE_DIRECTION_BUY == $direction ? -1 : 0;
        $price = QuotationRedis::getStallsPrice($market, $stockCode, $stall, 'only');
        if (!$price) {
            // 一档价格不能成交，重新加入队列
            $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode, false);

            return false;
        }

        $reDeal = false;

        // $volume < 10000（单笔成交），$volume >= 10000（1到2笔成交），$volume >= 20000（2到4笔成交）
        $tradedCount = 1;
        $dealCount = 0;
        $volume >= 10000 && $tradedCount = mt_rand(1, 2);
        $volume >= 20000 && $tradedCount = mt_rand(2, 4);
        for ($i = 1; $i <= $tradedCount; $i++) {
            // 需要为后续成交预留足够数量（剩余次数 * 100）
            $randMax = ($volume - ($tradedCount - $i) * 100) / 100;
            if($randMax >=1) {
                $hand = mt_rand(1, $randMax);
            } else {
                return false;
            }

            $dealVolume = $hand * 100;

            // 如果是最后一次成交，则成交数量为全部未成交数量
            if ($i == $tradedCount) {
                $dealVolume = $volume;
            }

            // 剩余未成交数量
            $volume = $volume - $dealVolume;
            $dealCount += $dealVolume;

            // 执行成交
            $tradedRet = TradedMsg::execute($orderID, $dealVolume);

            if (is_array($tradedRet)) {
                list ($userID, $stockID, $market, $stockCode, $tradedVolume, $code, $msg) = $tradedRet;
                $this->sendToUser($userID, 'Traded', 1, $msg);
            } else {
                if ($reDeal == false) {
                    $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode,false);
                    $reDeal = true;
                }
            }
        }

        return true;
    }

    /**
     * 向指定用户的发送消息
     * -- 多端发送
     *
     * @param int    $userID 用户ID
     * @param string $key 消息KEY
     * @param int    $code 状态 1 成功消息， 0 错误消息
     * @param string $msg 提示信息
     * @param array  $data 消息数据
     */
    protected function sendToUser($userID, $key, $code, $msg = '', $data = [])
    {
        go(function () use ($userID, $key, $code, $msg, $data) {
            // $userID 必须大于0
            if ($userID <= 0) return false;

            // 发送的数据
            $data = ['Key' => $key, 'code' => $code, 'msg' => $msg, 'data' => $data,];

            // 向用户的所有客户端发送消息
            $clientIDList = WsServerRedis::getWsClientIDList($userID);
            if (is_array($clientIDList) && count($clientIDList)) {
                foreach ($clientIDList as $fd) {
                    try {
                        if ($this->server->connection_info($fd)) {
                            $this->server->push($fd, json_encode($data));
                        }
                    } catch (\Exception $e) {
                    } catch (\Throwable $e) {
                    }
                }
            }
        });
    }

    /**
     * 向指定的客户端发送消息
     * -- 单端发送
     *
     * @param int    $fd websocket 客户端ID
     * @param string $key
     * @param int    $code
     * @param string $msg
     * @param array  $data
     */
    protected function sendToClient($fd, $key, $code, $msg = '', $data = [])
    {
        $data = [
            'Key'  => $key,
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        if ($this->server->connection_info($fd)) {
            $this->server->push($fd, json_encode($data));
        }
    }
    /*远程获取数据函数*/
    protected function curl($url,$cookies=null){
		//$cookies = "";//cookie填这里
        //下面是允许请求跨域，跨域删除
        header('Content-Type: text/html;charset=utf-8');
        header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
        header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
        header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');
        $headers = array(
            'Authorization:'.'bearer 87d1a782-193b-423a-8097-fb8285f6bc05',
        );//授权认证
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        //curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_COOKIE, $cookies); // 带上COOKIE请求
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $result = curl_exec($curl); // 执行操作
        //$add = json_decode($result, true);
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $result;
	}
}
