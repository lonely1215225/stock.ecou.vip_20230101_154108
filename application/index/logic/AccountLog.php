<?php

namespace app\index\logic;

use app\common\model\UserStrategyLog;
use app\common\model\UserCashCouponFrozenLog;
use app\common\model\UserWalletLog;
use app\common\model\UserCashCouponLog;
use util\SystemRedis;

/**
 * 用户金钱变动日志辅助类
 * -- 策略金变动记录
 * -- 钱包变动记录
 * -- 预扣保证金变动记录
 *
 * @package app\index\logic
 */
class AccountLog
{

    /**
     * 钱包余额日志 - 申请提现
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param float $money 提现金额，内部取绝对值的负数
     * @param float $before 钱包变动前余额
     *
     * @return int
     */
    public static function walletWithdraw($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => -$money,
            'change_type' => USER_WALLET_WITHDRAW,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => '提现' . $money,
        ]);

        return $ret ? $ret['id'] : 0;
    }

    /**
     * 钱包余额日志 - 申请提现失败（被拒绝，支付失败）
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param float $money 提现金额，内部取绝对值
     * @param float $before 钱包变动前余额
     *
     * @return int
     */
    public static function walletWithdrawFailed($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => $money,
            'change_type' => USER_WALLET_WITHDRAW_FAILED,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcadd($before, $money, 2),
            'remark' => '提现失败，返回' . $money,
        ]);

        return $ret ? $ret['id'] : 0;
    }

    /**
     * 钱包余额日志 - 策略金【转出到】账户余额（钱包）
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝对值
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function walletFromStrategy($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => $money,
            'change_type' => USER_WALLET_FROM_STRATEGY,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcadd($before, $money, 2),
            'remark' => '从策略金转入' . $money,
        ]);

        return $ret ? true : false;
    }

    /**
     * 钱包余额日志 - 账户余额（钱包）【转出到】策略金
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝的负数
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function walletToStrategy($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => -$money,
            'change_type' => USER_WALLET_TO_STRATEGY,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => '转出到策略金' . $money,
        ]);

        return $ret ? true : false;
    }

    /**
     * 钱包余额日志 - 返佣
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function walletCommission($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => $money,
            'change_type' => USER_WALLET_COMMISSION,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => '',
        ]);

        return $ret ? true : false;
    }

    /**
     * 钱包余额日志 - 收益宝收益
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function walletYuebao($userID, $income, $before, $baseIncome)
    {
        $income = abs($income);
        $ret = UserWalletLog::create([
            'user_id' => $userID,
            'change_money' => $income,
            'change_type' => USER_WALLET_YUEBAO,
            'change_time' => date('Y-m-d H:i:s'),
            'before_balance' => $before,
            'after_balance' => bcsub($before, $income, 2),
            'remark' => '当前收益为：' . $income . ' = ' . $before . '(当前账户资金) / 10000 * ' . $baseIncome . ' (万份收益率)',
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 账户余额（钱包）【转入】策略金
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝对值
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function strategyFromWallet($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_FROM_WALLET,
            'change_money' => $money,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $money, 2),
            'remark' => '从账户资金转入' . $money,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 策略金【转出到】账户余额（钱包）
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param float $money 变动金额，内部取绝对值的负数
     * @param float $before 变动之前的金额
     *
     * @return bool
     */
    public static function strategyToWallet($userID, $money, $before)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_TO_WALLET,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => '转出到账户资金' . $money,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 扣除管理费
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $tradedID 成交单ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function strategySubManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $money, $before)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFeeRate = $tradingFee['management_fee'];

        $money = abs($money);
        $remark = "管理费。买入{$market}{$stockCode}（当前价{$price}*股数{$volume}*$managementFeeRate={$money}）";
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_MANAGEMENT_FEE,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 扣除每日管理费
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param bool $isSuspended 是否停牌
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function strategySubDailyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $price, $volume, $money, $isSuspended, $before)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $rate = $isSuspended ? $tradingFee['management_fee_s'] : $tradingFee['management_fee'];

        $money = abs($money);
        $remark = "管理费。持仓（{$positionID}）管理费（{$market}{$stockCode}）（当前价{$price}*股数{$volume}*$rate={$money}）";
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_MANAGEMENT_FEE,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志  - 扣出月管理费
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function strategySubMonthlyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $money, $before)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFeeRate = $tradingFee['monthly_m_fee'];

        $money = abs($money);
        $remark = "月管理费。买入{$market}{$stockCode}（当前价{$price}*股数{$volume}*$managementFeeRate={$money}）";
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_MONTHLY_M_FEE,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 买入成交扣除保证金
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $tradedID 成交单ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param float $before 发生前金额
     * @param float $stopLossPrice 止损价
     *
     * @return bool
     */
    public static function strategySubDeposit($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $money, $before, $stopLossPrice)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        $money = abs($money);
        $remark = "买入股票：买入({$market}{$stockCode})(支出保证金:{$price}*{$volume}*{$depositRate}={$money}) （止损参数：{$stopLossPrice}）";
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_BUY,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 卖出结算
     * -- 增加 或 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓编号
     * @param int $tradedID 成交单编号
     * @param float $price 成交价
     * @param int $volume 成交股数
     * @param float $settleMoney 结算金额
     * @param float $before 策略金变动前
     * @param float $stopLossPrice 止损价
     * @param bool $isCashCoupon 代金券
     *
     * @return bool
     */
    public static function strategySellSettlement($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $settleMoney, $before, $stopLossPrice, $isCashCoupon = false)
    {
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => $isCashCoupon ? USER_CASH_COUPON_SELL : USER_STRATEGY_SELL,
            'change_money' => $settleMoney,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $settleMoney, 2),
            'remark' => "卖出股票：卖出（{$market}{$stockCode} {$price} * {$volume}）(结算金额：{$settleMoney}) （止损参数：{$stopLossPrice}）",
        ]);

        return $ret ? true : false;
    }

    /**
     * 代金券日志 - 买入成交扣除保证金
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $tradedID 成交单ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param float $before 发生前金额
     * @param float $stopLossPrice 止损价
     *
     * @return bool
     */
    public static function cashCouponSubDeposit($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $money, $before, $stopLossPrice)
    {
        $money = abs($money);
        $remark = "买入股票：买入({$market}{$stockCode})(支出保证金:{$price}*{$volume}={$money}) （止损参数：{$stopLossPrice}）";
        $ret = UserCashCouponLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_BUY,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 代金券日志 - 卖出结算
     * -- 增加 或 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓编号
     * @param int $tradedID 成交单编号
     * @param float $price 成交价
     * @param int $volume 成交股数
     * @param float $settleMoney 结算金额
     * @param float $before 策略金变动前
     * @param float $stopLossPrice 止损价
     *
     * @return bool
     */
    public static function cashCouponSellSettlement($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $settleMoney, $before, $stopLossPrice)
    {
        $ret = UserCashCouponLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_SELL,
            'change_money' => $settleMoney,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $settleMoney, 2),
            'remark' => "卖出股票：卖出（{$market}{$stockCode} {$price} * {$volume}）(结算金额：{$settleMoney}) （止损参数：{$stopLossPrice}）",
        ]);

        return $ret ? true : false;
    }

    /**
     * 代金券日志 - 扣除管理费
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $tradedID 成交单ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function cashCouponSubManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $volume, $money, $before)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFeeRate = $tradingFee['management_fee'];

        $money = abs($money);
        $remark = "管理费。买入{$market}{$stockCode}（当前价{$price}*股数{$volume}*$managementFeeRate={$money}）";
        $ret = UserCashCouponLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_MANAGEMENT_FEE,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 扣除每日管理费
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $price 成交价
     * @param int $volume 成交数量
     * @param float $money 金额，内部取绝对值的负数
     * @param bool $isSuspended 是否停牌
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function cashCouponSubDailyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $price, $volume, $money, $isSuspended, $before)
    {
        // 获取交易费用
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $rate = $isSuspended ? $tradingFee['management_fee_s'] : $tradingFee['management_fee'];

        $money = abs($money);
        $remark = "管理费。持仓（{$positionID}）管理费（{$market}{$stockCode}）（当前价{$price}*股数{$volume}*$rate={$money}）";
        $ret = UserCashCouponLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_MANAGEMENT_FEE,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }


    /**
     * 策略金日志 - 每日结算入账
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param string $stockID 股票ID
     * @param int $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $price 结算价格
     * @param int $volume 持仓股数
     * @param float $sumSellValueIn 总卖出收入
     * @param float $sumBuyValueCost 总买入市值
     * @param float $sumDeposit 保证金
     * @param float $settleMoney 结算金额
     * @param float $before 变动之前
     * @param float $stopLossPrice 新的上损参数
     *
     * @return bool
     */
    public static function strategyDailySettlement($userID, $market, $stockID, $stockCode, $positionID, $price, $volume, $sumSellValueIn, $sumBuyValueCost, $sumDeposit, $settleMoney, $before, $stopLossPrice)
    {
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_SETTLEMENT,
            'change_money' => $settleMoney,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $settleMoney, 2),
            'remark' => "结算盈亏：（结算价{$price} 股数{$volume} 已卖额{$sumSellValueIn} 买入额{$sumBuyValueCost} 上次结算金额{$sumDeposit}） 止损参数：{$stopLossPrice}",
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 追加保证金
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param string $stockID 股票ID
     * @param int $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $stopLossPrice 止损价
     * @param int $volume 持仓股数
     * @param float $money 追加保证金金额，内部取绝对值的负数
     * @param float $before 变动之前
     *
     * @return bool
     */
    public static function strategyAdditionalDeposit($userID, $market, $stockID, $stockCode, $positionID, $stopLossPrice, $volume, $money, $before)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_ADD_DEPOSIT,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => "追加保证金：{$positionID}自动追加履约金:{$money}（{$market}{$stockCode}股数{$volume}）止损参数:{$stopLossPrice}",
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 停牌追加保证金
     * -- 减少
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param string $stockID 股票ID
     * @param int $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $volume 持仓股数
     * @param float $money 停牌追加保证金金额，内部取绝对值的负数
     * @param float $before 变动之前
     *
     * @return bool
     */
    public static function strategySuspensionDeposit($userID, $market, $stockID, $stockCode, $positionID, $volume, $money, $before)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_SUSPENDED_DEPOSIT,
            'change_money' => -$money,
            'before_balance' => $before,
            'after_balance' => bcsub($before, $money, 2),
            'remark' => "停牌追加保证金：（{$market}{$stockCode}停牌)(持仓{$positionID}股数{$volume}）自动追加履约金:{$money}",
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 股利金入账
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param string $stockID 股票ID
     * @param int $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param int $volume 持仓股数
     * @param float $money 股利金，内部取绝对值
     * @param float $before 变动之前
     *
     * @return bool
     */
    public static function strategyBackDividend($userID, $market, $stockID, $stockCode, $positionID, $volume, $money, $before)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_EX_DIVIDEND,
            'change_money' => $money,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $money, 2),
            'remark' => "股利金：{$positionID}（{$money}({$market}{$stockCode}股数{$volume}）派息:{$money}",
        ]);

        return $ret ? true : false;
    }

    /**
     * 策略金日志 - 退款
     * -- 增加
     * -- 退重复收取的管理费
     * -- 其他退款
     *
     * @param int $userID 用户ID
     * @param string $market 证券市场代码
     * @param string $stockID 股票ID
     * @param int $stockCode 股票代码
     * @param int $positionID 持仓ID
     * @param float $money 股利金，内部取绝对值
     * @param float $before 变动之前
     * @param string $remark 备注
     *
     * @return bool
     */
    public static function strategyRefund($userID, $market, $stockID, $stockCode, $positionID, $money, $before, $remark)
    {
        $money = abs($money);
        $ret = UserStrategyLog::create([
            'user_id' => $userID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'order_position_id' => $positionID,
            'change_time' => date('Y-m-d H:i:s'),
            'change_type' => USER_STRATEGY_REFUND,
            'change_money' => $money,
            'before_balance' => $before,
            'after_balance' => bcadd($before, $money, 2),
            'remark' => $remark,
        ]);

        return $ret ? true : false;
    }

    /**
     * 冻结资金变动日志 - 委托买入增加冻结资金
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param int $orderID 委托单ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param float $money 变动金额，内部取绝对值
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function frozenAdd($userID, $orderID, $market, $stockID, $stockCode, $money, $before)
    {
        $money = abs($money);
        $ret = UserCashCouponFrozenLog::create([
            'user_id' => $userID,
            'order_id' => $orderID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'change_type' => USER_FROZEN_BUY,
            'change_time' => date('Y-m-d H:i:s'),
            'change_money' => $money,
            'before_money' => $before,
            'after_money' => bcadd($before, $money, 2),
        ]);

        return $ret ? true : false;
    }

    /**
     * 冻结资金变动日志 - 减少冻结资金
     * -- 减少
     * -- 委托失败、成交、撤单
     *
     * @param int $userID 用户ID
     * @param int $orderID 委托单ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param string $changeType 变动类型 [USER_FROZEN_TRADED, USER_FROZEN_CANCEL, USER_FROZEN_FAILED]
     * @param float $money 变动金额，内部取绝对值的负数
     * @param float $before 发生前金额
     * @param int $positionID 对应持仓ID
     * @param int $tradedID 对应成交单ID
     *
     * @return bool
     */
    public static function frozenSub($userID, $orderID, $market, $stockID, $stockCode, $changeType, $money, $before, $positionID = 0, $tradedID = 0)
    {
        $money = abs($money);
        $ret = UserCashCouponFrozenLog::create([
            'user_id' => $userID,
            'order_id' => $orderID,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'change_type' => $changeType,
            'change_time' => date('Y-m-d H:i:s'),
            'change_money' => -$money,
            'before_money' => $before,
            'after_money' => bcsub($before, $money, 2),
        ]);

        return $ret ? true : false;
    }


    /**
     * 代金券委托冻结金额日志
     * -- 增加
     *
     * @param int $userID 用户ID
     * @param int $orderID 委托单ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param float $money 变动金额，内部取绝对值
     * @param float $before 发生前金额
     *
     * @return bool
     */
    public static function cashCouponFrozenAdd($userID, $orderID, $market, $stockID, $stockCode, $money, $before)
    {
        $money = abs($money);
        $ret = UserCashCouponFrozenLog::create([
            'user_id' => $userID,
            'order_id' => $orderID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'change_type' => USER_FROZEN_BUY,
            'change_time' => date('Y-m-d H:i:s'),
            'change_money' => $money,
            'before_money' => $before,
            'after_money' => bcadd($before, $money, 2),
        ]);

        return $ret ? true : false;
    }

    /**
     * 代金券委托冻结金额日志 - 减少冻结资金
     * -- 减少
     * -- 委托失败、成交、撤单
     *
     * @param int $userID 用户ID
     * @param int $orderID 委托单ID
     * @param string $market 证券市场代码
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param string $changeType 变动类型 [USER_FROZEN_TRADED, USER_FROZEN_CANCEL, USER_FROZEN_FAILED]
     * @param float $money 变动金额，内部取绝对值的负数
     * @param float $before 发生前金额
     * @param int $positionID 对应持仓ID
     * @param int $tradedID 对应成交单ID
     *
     * @return bool
     */
    public static function frozenCashCouponSub($userID, $orderID, $market, $stockID, $stockCode, $changeType, $money, $before, $positionID = 0, $tradedID = 0)
    {
        $money = abs($money);
        $ret = UserCashCouponFrozenLog::create([
            'user_id' => $userID,
            'order_id' => $orderID,
            'order_position_id' => $positionID,
            'order_traded_id' => $tradedID,
            'market' => $market,
            'stock_id' => $stockID,
            'stock_code' => $stockCode,
            'change_type' => $changeType,
            'change_time' => date('Y-m-d H:i:s'),
            'change_money' => -$money,
            'before_money' => $before,
            'after_money' => bcsub($before, $money, 2),
        ]);

        return $ret ? true : false;
    }


}
