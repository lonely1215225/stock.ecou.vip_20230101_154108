<?php
namespace app\cli\auto;

use app\common\model\Condition;
use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\common\model\Order;
use app\index\logic\Calc;
use think\console\Command;
use think\console\Output;
use think\console\Input;
use util\ScriptRedis;
use util\TradingRedis;
use util\TradingUtil;
use think\Db;

/**
 * 休市脚本
 * -- 仅交易日执行
 * -- 15:05 设置持仓可卖数量
 * -- 15:10 撤单
 *
 * 撤单说明：
 * -- 当前交易日未完结状态的
 * -- 撤单类型 休市撤单（CANCEL_TYPE_CLOSE）
 * -- 撤单状态成功，撤单时间
 * -- 设置为完结状态
 * -- 买入委托单【需要】返还预扣保证金
 * -- 卖出委托单【不需要】返还持仓的可卖数量，由设置持仓可卖数量操作处理
 *
 * 设置持仓可卖数量说明：
 * -- 未完结的
 * -- 可卖数量 = 持仓数量
 * -- 今日数量 = 0
 *
 * @package app\cli\auto
 */
class MarketClosed extends Command
{

    protected function configure()
    {
        $this->setName('market_closed')->setDescription('休市后撤单，处理持仓可卖数量');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(10000, function () {
            // 交易日
            $tradingDate = TradingUtil::currentTradingDate();
            //if (TradingRedis::isTradingDate($tradingDate)) {
                // 当前时分
            $nowHi = intval(date('Hi'));

            // 达到条件：设置持仓可卖数量、设置条件单过期
            //if ($nowHi >= 1505) {
                $this->volumeForSell();
                $this->setConditionExpire();
            //}

            // 达到条件：撤单
            if ($nowHi >= 1510 && ScriptRedis::isCancelNotRun()) {
                $this->cancelOrder();
            }
            //}
        });
    }

    private function cancelOrder()
    {
        // 当前交易日
        $currentTradingDate = TradingUtil::currentTradingDate();

        // 今日，卖出，未完结的委托单，直接设置为已撤单
        Order::update([
            'cancel_state' => CANCEL_SUCCESS,
            'cancel_time'  => time(),
            'cancel_type'  => CANCEL_TYPE_CLOSE,
            'is_finished'  => true,
        ], [
            ['trading_date', '=', $currentTradingDate],
            ['direction', '=', TRADE_DIRECTION_SELL],
            ['is_finished', '=', false],
        ]);

        // 查询【今日,买入,未完结的委托单】
        $buyList = Order::where('trading_date', $currentTradingDate)
            ->where('direction', TRADE_DIRECTION_BUY)
            ->where('is_finished', false)
            ->column('user_id,market,stock_code,stock_id,price,volume,volume_success,deposit100', 'id');

        // 今日，买入，未完结委托单，执行撤单
        foreach ($buyList as $orderID => $item) {
            $userID     = $item['user_id'];
            $market     = $item['market'];
            $stockCode  = $item['stock_code'];
            $stockID    = $item['stock_id'];
            $deposit100 = $item['deposit100'];
            Db::startTrans();
            try {
                // 用户的账户
                $userAccount = UserAccount::where('user_id', $userID)->field('id,frozen')->find();
                // 发生前冻结资金余额
                $beforeFrozen = $userAccount['frozen'];

                // 剩余未成交数量
                $volumeRemain = $item['volume'] - $item['volume_success'];

                // 解冻金额
                $unfrozenMoney = Calc::calcUnfrozenDeposit($deposit100, $volumeRemain);

                // 账户：减少冻结资金（冻结保证金）
                $userAccount['frozen'] = Db::raw("frozen-{$unfrozenMoney}");
                // 保存账户
                $uaRet = $userAccount->save();

                // 写入冻结资金变动日志（减少冻结资金）
                $type = USER_FROZEN_CANCEL;
                $fRet = AccountLog::frozenSub($userID, $orderID, $market, $stockID, $stockCode, $type, $unfrozenMoney, $beforeFrozen);

                // 撤单
                $oRet = Order::update([
                    'cancel_state' => CANCEL_SUCCESS,
                    'cancel_time'  => time(),
                    'cancel_type'  => CANCEL_TYPE_CLOSE,
                    'is_finished'  => true,
                ], [['id', '=', $orderID]]);

                if ($oRet && $uaRet && $fRet) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }

    /**
     * 设置持仓可卖数量
     *
     * @return bool
     */
    private function volumeForSell()
    {
        $ret = OrderPosition::where('create_time' ,'<',strtotime(date('Y-m-d 15:05:00',strtotime('-1 day'))))->update([
            'volume_for_sell' => Db::raw("volume_position"),
            'volume_today'    => 0,
        ], [
            'is_finished' => false,
        ]);

        return $ret ? true : false;
    }

    /**
     * 设置今日所有【未触发】【未运行】状态的条件单为【过期】状态
     */
    private function setConditionExpire()
    {
        Condition::update([
            'state' => CONDITION_STATE_EXPIRE,
        ], [
            ['state', ['=', CONDITION_STATE_ING], ['=', CONDITION_STATE_NONE], 'or'],
            ['trading_date', '=', TradingUtil::currentTradingDate()],
        ]);
    }

}
