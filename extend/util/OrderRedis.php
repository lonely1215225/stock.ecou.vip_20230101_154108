<?php
namespace util;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;

/**
 * 订单相关Redis操作
 * -- Order
 * -- OrderPosition
 * -- OrderTraded
 */
class OrderRedis extends RedisUtil
{

    /**
     * 缓存所有有持仓用户的策略金（不含冻结资金）
     */
    public static function cachePositionUserStrategy()
    {
        try {
            $list = UserAccount::where('user_id', 'in', function (\think\db\Query $query) {
                $query->name('order_position')->where('is_finished', 'false')->distinct(true)->field('user_id');
            })->column('strategy_balance-frozen AS strategy', 'user_id');

            if ($list) {
                foreach ($list as $userID => $strategy) {
                    $key = 'user_strategy_' . $userID;
                    self::redis()->set($key, $strategy);
                    self::redis()->expireAt($key, self::midnight());
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 缓存指定用户的策略金（不含冻结资金）
     * -- 发起委买 【有】
     * -- 委买回报 【有，成功，失败】
     * -- 撤单回报 【有，委买，委卖】
     * -- 成交回报 【有，买入，卖出】
     * -- 追加保证金 【有】
     * -- 停牌追加保证金 【有】
     * -- 钱包【转入】策略金 【有】
     * -- 策略金【转入】钱包 【有】
     *
     * @param $userID
     */
    public static function cacheUserStrategy($userID)
    {
        try {
            $ua = UserAccount::where('user_id', $userID)->field('strategy_balance-frozen AS strategy')->find();

            if ($ua) {
                $key = 'user_strategy_' . $userID;
                self::redis()->set($key, $ua['strategy']);
                self::redis()->expireAt($key, self::midnight());
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 缓存指定用户的代金券（不含冻结资金）
     * -- 发起委买 【有】
     * -- 委买回报 【有，成功，失败】
     * -- 撤单回报 【有，委买，委卖】
     * -- 成交回报 【有，买入，卖出】
     * -- 追加保证金 【有】
     * -- 停牌追加保证金 【有】
     * -- 钱包【转入】策略金 【有】
     * -- 策略金【转入】钱包 【有】
     *
     * @param $userID
     */
    public static function cacheUserCashCoupon($userID)
    {
        try {
            $ua = UserAccount::where('user_id', $userID)->field('cash_coupon-cash_coupon_frozen AS strategy')->find();

            if ($ua) {
                $key = 'user_cash_coupon_' . $userID;
                self::redis()->set($key, $ua['strategy']);
                self::redis()->expireAt($key, self::midnight());
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取指定用户的策略金
     *
     * @param $userID
     *
     * @return bool|string
     */
    public static function getUserStrategy($userID)
    {
        $key = 'user_strategy_' . $userID;
        if (!self::redis()->exists($key)) {
            self::cacheUserStrategy($userID);
        }

        return self::redis()->get($key);
    }

    /**
     * 缓存所有【未完结】【非停牌】持仓数据
     * -- 强平检测
     * -- 追加保证金检测
     * -- 停牌股票不参与检测
     */
    public static function cacheAllPosition()
    {
        try {
            $list = OrderPosition::where('is_finished', false)
                ->where('is_suspended', false)
                ->order('id', 'ASC')
                ->column('id,user_id,market,stock_code,stock_id,volume_position,volume_for_sell,stop_loss_price,position_price,sum_deposit', 'id');

            // 强平队列名名称
            $queue = 'forced_sell_list';

            // 删除之前的队列
            self::redis()->del($queue);

            if ($list) {
                // 缓存持仓数据
                foreach ($list as $positionID => $item) {
                    $key = 'position_' . $positionID;
                    self::redis()->hMSet($key, $item);
                    self::redis()->expireAt($key, self::midnight());

                    // 加入队列
                    self::redis()->rPush($queue, $positionID);

                    // 持仓按股票代码分组存set
                    $setKey = "{$item['market']}{$item['stock_code']}_position_set";
                    self::redis()->sAdd($setKey, $positionID);
                    self::redis()->expireAt($setKey, self::midnight());
                }

                // 设置队列过期时间
                self::redis()->expireAt($queue, self::midnight());
            }
        } catch (\Exception $e) {
            // dump($e->getFile());
            // dump($e->getLine());
            // dump($e->getMessage());
            // dump($e->getTraceAsString());
        }
    }

    /**
     * 缓存指定持仓的数据
     * -- 委托卖出时
     * -- 委托卖出失败
     * -- 成交时
     * -- 追加保证金时
     * -- 收取过夜费时（无需处理）
     *
     * @param $positionID
     */
    public static function cachePosition($positionID)
    {
        try {
            $position = OrderPosition::where('id', $positionID)
                ->field('id,user_id,market,stock_code,stock_id,volume_position,volume_for_sell,stop_loss_price,position_price,sum_deposit,is_monthly,monthly_expire_date,is_cash_coupon')
                ->find();

            // 强平队列名名称
            $queue = 'forced_sell_list';

            if ($position) {
                // 持仓数据的KEY
                $key = 'position_' . $positionID;

                // 加入队列（之前没有该持仓缓存的情况下）
                if (!self::redis()->exists($key)) {
                    self::redis()->rPush($queue, $positionID);
                    self::redis()->expireAt($queue, self::midnight());
                }

                // 持仓按股票代码分组存set
                $setKey = "{$position['market']}{$position['stock_code']}_position_set";
                self::redis()->sAdd($setKey, $positionID);
                self::redis()->expireAt($setKey, self::midnight());

                // 缓存持仓
                self::redis()->hMSet($key, $position->toArray());
                self::redis()->expireAt($key, self::midnight());
            }
        } catch (\Exception $e) {
            // dump($e->getFile());
            // dump($e->getLine());
            // dump($e->getMessage());
            // dump($e->getTraceAsString());
        }
    }

    /**
     * 获取持仓数据
     *
     * @param $positionID
     *
     * @return array
     */
    public static function getPosition($positionID)
    {
        $key = 'position_' . $positionID;
        if (!self::redis()->exists($key)) {
            self::cachePosition($positionID);
        }

        return self::redis()->hMGet($key, ['id', 'user_id', 'market', 'stock_code', 'stock_id', 'volume_position', 'volume_for_sell', 'stop_loss_price', 'position_price', 'sum_deposit', 'is_monthly', 'monthly_expire_date', 'is_cash_coupon']);
    }


    /**
     * 缓存指定按月收取管理费持仓的数据
     * -- 委托卖出时
     * -- 委托卖出失败
     * -- 成交时
     * -- 追加保证金时
     * -- 收取过夜费时（无需处理）
     *
     * @param $positionID
     */
    public static function cachePositionMonthly($positionID)
    {
        try {
            $position = OrderPosition::where('id', $positionID)
                ->field('id,user_id,market,stock_code,stock_id,volume_position,volume_for_sell,stop_loss_price,position_price,sum_deposit,is_monthly,monthly_expire_date')
                ->find();

            // 强平队列名名称
            $queue = 'renew_fee_list';

            if ($position) {
                // 持仓数据的KEY
                $key = 'notice_position_' . $positionID;

                // 加入队列（之前没有该持仓缓存的情况下）
                if (!self::redis()->exists($key)) {
                    self::redis()->rPush($queue, $positionID);
                    self::redis()->expireAt($queue, self::midnight());
                }

                // 持仓按股票代码分组存set
                $setKey = "notice_position_set";
                self::redis()->sAdd($setKey, $positionID);
                self::redis()->expireAt($setKey, self::midnight());

                // 缓存持仓
                self::redis()->hMSet($key, $position->toArray());
                self::redis()->expireAt($key, self::midnight());
            }
        } catch (\Exception $e) {
            // dump($e->getFile());
            // dump($e->getLine());
            // dump($e->getMessage());
            // dump($e->getTraceAsString());
        }
    }

    /**
     * 获取按月收取管理费即将到期的持仓的数据
     *
     * @param $positionID
     *
     * @return array
     */
    public static function getMonthlyPosition($positionID)
    {
        $key = 'notice_position_' . $positionID;
        if (!self::redis()->exists($key)) {
            self::cachePositionMonthly($positionID);
        }

        return self::redis()->hMGet($key, ['id', 'user_id', 'market', 'stock_code', 'stock_id', 'volume_position', 'volume_for_sell', 'stop_loss_price', 'position_price', 'sum_deposit', 'is_monthly', 'monthly_expire_date']);
    }
}
