<?php

namespace app\cli\sims\logic;

use app\common\model\Order;
use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use think\Db;
use util\OrderRedis;

/**
 * 撤单回报处理类
 *
 * @package app\index\logic
 */
class OrderCancelMsg
{

    /**
     * 撤单回报处理主方法
     *
     * @param int $orderID 订单ID
     *
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function execute($orderID)
    {
        // 撤单成功处理
        $ret = self::success(intval($orderID));

        if (is_array($ret)) {
            // 更新用户的策略金缓存
            $userID = $ret[0];
            OrderRedis::cacheUserStrategy($userID);
        }

        return $ret;
    }

    /**
     * 撤单成功的处理
     *
     * @param int $orderID
     *
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function success($orderID)
    {
        // 获取委托单
        $order = Order::where('id', $orderID)
            ->field('id,user_id,market,stock_id,stock_code,direction,volume,volume_success,price,cancel_state,cancel_time,deposit100,order_position_id,is_monthly,is_cash_coupon')
            ->find();

        // 没有找到委托单
        if (!$order) return false;

        if ($order['direction'] == TRADE_DIRECTION_BUY) {
            // 买入委托单撤单成功
            $ret = self::buyOrderCancelSuccess($order);
        } else {
            // 卖出委托单撤单成功
            $ret = self::sellOrderCancelSuccess($order);
        }

        return $ret;
    }

    /**
     * 买入委托单，撤单成功
     * -- 更改订单状态为撤单成功
     * -- 解冻对应冻结资金（保证金）
     *
     * @param \think\Model $order 委托单
     *
     * @return array|false
     */
    private static function buyOrderCancelSuccess(&$order)
    {
        $orderID = $order['id'];
        $userID = $order['user_id'];
        $market = $order['market'];
        $stockID = $order['stock_id'];
        $stockCode = $order['stock_code'];
        $volume = $order['volume'];
        $volumeSuccess = $order['volume_success'];
        $deposit100 = $order['deposit100'];
        $isCashCoupon = $order['is_cash_coupon'];

        Db::startTrans();
        try {
            // 用户账户
            $uAccount = UserAccount::where('user_id', $order['user_id'])->field('id,frozen,cash_coupon_frozen')->find();

            // 发生前冻结资金余额
            $beforeFrozen = $isCashCoupon ? $uAccount['cash_coupon_frozen'] : $uAccount['frozen'];

            // 撤单数量 = 委托数量 - 成功数量
            $cancelVolume = $volume - $volumeSuccess;

            // 解冻资金
            $unfrozenMoney = Calc::calcUnfrozenDeposit($deposit100, $cancelVolume);

            // 委托单：已完结，撤单成功，撤单时间
            $order['is_finished'] = true;
            $order['cancel_state'] = CANCEL_SUCCESS;
            $order['cancel_time'] = time();
            // 保存委托单
            $order->save();
            $oRows = $order->getNumRows();

            // 账户变动：减少冻结资金（保证金），仅当剩余冻结大于等于解冻金额才减少冻结资金
            $uaRows = 1;
            $fRet = true;
            if ($beforeFrozen >= $unfrozenMoney) {
                if ($isCashCoupon) {
                    $uAccount['cash_coupon_frozen'] = Db::raw("cash_coupon_frozen-{$unfrozenMoney}");
                } else {
                    $uAccount['frozen'] = Db::raw("frozen-{$unfrozenMoney}");
                }

                // 保存账户
                $uAccount->save();
                $uaRows = $uAccount->getNumRows();

                // 写入冻结资金变动日志（减少冻结资金）
                $type = USER_FROZEN_CANCEL;
                $fRet = $isCashCoupon ? AccountLog::frozenCashCouponSub($userID, $orderID, $market, $stockID, $stockCode, $type, $unfrozenMoney, $beforeFrozen) : AccountLog::frozenSub($userID, $orderID, $market, $stockID, $stockCode, $type, $unfrozenMoney, $beforeFrozen);
            }

            if ($oRows && $uaRows && $fRet) {
                Db::commit();

                $code = 1;
                $msg = '撤单成功';

                $ret = [$userID, $code, $msg];
            } else {
                Db::rollback();

                $ret = false;
            }
        } catch (\Exception $e) {
            Db::rollback();

            dump($e->getFile());
            dump($e->getLine());
            dump($e->getMessage());
            dump($e->getTraceAsString());

            $ret = false;
        }

        return $ret;
    }

    /**
     * 卖出委托单，撤单成功
     * -- 更改订单状态为撤单成功
     * -- 返还对应持仓的可卖数量
     *
     * @param \think\Model $order 委托单
     *
     * @return array|false
     */
    private static function sellOrderCancelSuccess(&$order)
    {
        $volume = $order['volume'];
        $volumeSuccess = $order['volume_success'];

        // 撤单数量 = 委托数量 - 成功数量
        $cancelVolume = $volume - $volumeSuccess;

        Db::startTrans();
        try {
            // 返还持仓的可卖数量
            OrderPosition::update([
                'volume_for_sell' => Db::raw("volume_for_sell+$cancelVolume"),
            ], [
                ['id', '=', $order['order_position_id']],
            ]);
            $opRows = Order::getNumRows();

            // 委托单：已完结，撤单成功，撤单时间
            $order['is_finished'] = true;
            $order['cancel_state'] = CANCEL_SUCCESS;
            $order['cancel_time'] = time();
            $oRet = $order->save();

            if ($opRows && $oRet) {
                Db::commit();

                $userID = $order['user_id'];
                $code = 1;
                $msg = '撤单成功';

                $ret = [$userID, $code, $msg];
            } else {
                Db::rollback();

                $ret = false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            dump($e->getFile());
            dump($e->getLine());
            dump($e->getMessage());
            dump($e->getTraceAsString());

            $ret = false;
        }

        return $ret;
    }

}
