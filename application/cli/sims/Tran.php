<?php
namespace app\cli\sims;

use app\cli\sims\logic\OrderCancel;
use app\cli\sims\logic\OrderCancelMsg;
use app\cli\sims\logic\TradedMsg;
use app\cli\client\CoRedisPool;
use app\cli\client\WsClient;
use app\common\model\OrderPosition;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\Order;
use app\index\logic\AccountLog;
use app\index\logic\AccountLogic;
use app\index\logic\Calc;
use app\index\logic\ConditionLogic;
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

class Tran extends Command
{

    protected function configure()
    {
        $this->setName('Tran')->setDescription('交易');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(10000, function () {
            $this->checkTradeOrder();
            $this->checkUnTradeOrder();
        });
    }
    //检测实时委托订单
    public function checkTradeOrder() 
    {
        //交易时间检测
        $isTradeTime = $this->checkTradeTime();
        if($isTradeTime == false) return '不在交易时间';
        $orderSubmitted = ORDER_SUBMITTED;
        $orderPayTrade  = ORDER_PART_TRADED;
        $creteTime      = time() - 600;//十分钟之内
        $where = [
            ['state', 'in', "{$orderSubmitted},{$orderPayTrade}"],
            ['cancel_state', '=', CANCEL_NONE],
            ['is_system', '=', false],
            ['create_time', '>=', $creteTime]
        ];
        $list = Order::where($where)
            ->where('trading_date', TradingUtil::currentTradingDate())
            ->select()
            ->toArray();
        //echo "实时：";print_r($list);echo "\n";return;
        foreach ($list as $val) {
            //echo "{$val['stock_code']} {$val['price']} {$val['volume']} {$val['direction']}\n";
            $this->triggerDeal($val);
        }
    }
    //检测10分钟未成交订单
    public function checkUnTradeOrder() 
    {
        //交易时间检测
        $isTradeTime = $this->checkTradeTime();
        if($isTradeTime == false) return '不在交易时间';
        $orderSubmitted = ORDER_SUBMITTED;
        $orderPayTrade  = ORDER_PART_TRADED;
        $creteTime = time() - 600;//10分钟之前
        //echo "{$creteTime}\n";return;
        $where = [
            ['state', 'in', "{$orderSubmitted},{$orderPayTrade}"],
            ['cancel_state', '=', CANCEL_NONE],
            ['is_system', '=', false],
            ['create_time', '<', $creteTime]
        ];
        $list = Order::where($where)
            ->where('trading_date', TradingUtil::currentTradingDate())
            ->select()
            ->toArray();
        //echo "10分钟之前：";print_r($list);echo "\n";return;
        $recMsg = [];
        foreach ($list as $val) {
            //echo "{$val['stock_code']} {$val['direction']}\n";
            $recMsg['Data']['order_id'] = $val['id'];
            $recMsg['Data']['user_id']  = $val['user_id'];
            $this->cancel($recMsg);
        }
    }
    public function triggerDeal($recMsg)
    {
        go(function () use ($recMsg) {
            $orderID   = $recMsg['id'];
            $price     = $recMsg['price'];
            $volume    = $recMsg['volume'];
            $direction = $recMsg['direction'];
            $market    = $recMsg['market'];
            $stockCode = $recMsg['stock_code'];
            // 检查一档价格
            $eprice = QuotationRedis::getPrice($market, $stockCode, $price, $direction, 'auto');
            //echo "price:{$price}\n";echo "eprice:{$eprice}\n";return;
            if (!$eprice) {
                // 一档价格不能成交，重新加入队列
                //$this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode, false);
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
                $tradedRet = TradedMsg::execute($orderID, $eprice, $dealVolume);
            }
            return true;
        });
    }
    //取消委托
    public function cancel($recMsg)
    {
        go(function () use ($recMsg) {
            // 撤单系统处理
            $result = OrderCancel::execute($recMsg);
            //print_r($result);return;
            if (is_array($result)) {
                list ($orderID) = $result;
                $cancelRet = OrderCancelMsg::execute($orderID);
                
            } else {
                // 系统处理失败
                
            }
        });
    }
    /**
     * 检查是否在交易时间内
     * @return bool
     */
    public function checkTradeTime(){
        // 仅交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return false;
        $tradingTime = TradingUtil::isInTradingTime();
        if(!$tradingTime) return false;
        // 仅在 09:26 ~ 11:30 及 13:00 ~ 15:00 缓存行情
        //$nowHi = intval(date('Hi'));
        //if (!(($nowHi >= 926 && $nowHi <= 1130) || ($nowHi >= 1300 && $nowHi <= 1500))) return false;
        return true;
    }
}
