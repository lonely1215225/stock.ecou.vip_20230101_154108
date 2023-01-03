<?php

namespace app\index\logic;

use util\SystemRedis;

class Calc
{

    /**
     * 计算持仓均价
     * 公式：
     * -- 持仓均价 = （总买入市值 - 总卖出市值） / 持仓股数
     * 适用：
     * -- 买入成交
     * -- 卖出成交
     * -- 除权除息
     *
     * @param $sumBuyValueCost
     * @param $sumSellValueIn
     * @param $volumePosition
     *
     * @return float
     */
    public static function calcPositionPrice($sumBuyValueCost, $sumSellValueIn, $volumePosition)
    {
        // 持仓完结时，置零
        if ($volumePosition <= 0) return 0;

        return bcdiv(bcsub($sumBuyValueCost, $sumSellValueIn, 4), $volumePosition, 4);
    }

    /**
     * 计算用户的动态资产
     * 仓位控制也用这个值
     * -- 动态资产 = 策略金余额（含冻结） + 累计保证金 + 持仓总收益
     *
     * @param float $strategy 策略金余额
     * @param float $deposit 累计保证金
     * @param float $sumPositionIncome 持仓总收益
     *
     * @return float
     */
    public static function calcDynamic($strategy, $deposit, $sumPositionIncome)
    {
        return bcadd(bcadd($strategy, $deposit, 2), $sumPositionIncome, 2);
    }

    /**
     * 计算持仓的浮动盈亏
     * -- 持仓的浮动盈亏 = (当前价 - 持仓均价) * 股数
     *
     * @param float $nowPrice 当前价
     * @param float $positionPrice 持仓均价
     * @param int   $volume 持仓数量
     *
     * @return float
     * -- 四舍五入保留两位小数
     */
    public static function calcFloatPAL($nowPrice, $positionPrice, $volume)
    {
        $diffPrice = bcsub($nowPrice, $positionPrice, 4);
        $pal       = bcmul($diffPrice, $volume, 4);

        return round($pal, 2);
    }

    /**
     * 计算持仓的累计盈亏
     * -- 累计盈亏 = 浮动盈亏 + 累计卖出盈亏
     *
     * @param $nowPrice
     * @param $avgCostPrice
     * @param $volume
     * @param $sumSellPAL
     *
     * @return string
     */
    public static function calcTotalPAL($nowPrice, $avgCostPrice, $volume, $sumSellPAL)
    {
        $floatPAL = self::calcFloatPAL($nowPrice, $avgCostPrice, $volume);
        $totalPAL = bcadd($floatPAL, $sumSellPAL, 2);

        return $totalPAL;
    }

    /**
     * 计算股票市值
     * -- 市值 = 价格 * 股数
     * 用例：
     * -- 计算持仓的当前市值
     * -- 计算持仓的成本市值
     * -- 委托单的委托买入市值
     * -- 撤单的市值
     * -- 其它市值类型计算
     *
     * @param float $price 当前价
     * @param int   $volume 持仓股数
     *
     * @return float
     * -- 返回值保留两位小数
     */
    public static function calcStockValue($price, $volume)
    {
        return round(bcmul($price, $volume, 4), 2);
    }


    /**
     * 计算最大可买市值
     * 说明：
     * -- 购买时，【可用策略金】能够支付【保证金】及【管理费】
     * -- 附：可用策略金 = 策略金余额 - 冻结资金
     * 公式推导：
     * -- 可用策略金 = 最大可卖市值 * 管理费比例（或月管理费比例） + 最大可卖市值 * 保证金比例
     * -> 最大可卖市值 = 可用策略金 / （管理费比例 + 保证金比例）
     *
     * @param bool  $isMonthly 是否按月收取管理费，获取管理费比例
     * @param float $strategyBalance 策略金余额
     * @param float $frozen 冻结资金
     *
     * @return float
     */
    public static function calcBuyCapital($strategyBalance, $frozen, $isMonthly = false)
    {
        // 获取系统交易费用设置
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFee = $isMonthly ? $tradingFee['monthly_m_fee'] : $tradingFee['management_fee'];
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        // 可用策略金
        $strategy = $strategyBalance - $frozen;

        // 最大可买市值
        $buyCapital = bcdiv($strategy, $depositRate + $managementFee,2);

        return $buyCapital;
    }

    /**
     * 计算最高可买股数
     * 说明：
     * -- 购买时，【可用策略金】能够支付【保证金】及【管理费】
     * -- 附：可用策略金 = 策略金余额 - 冻结资金
     * -- 附：保证金 = 股数 * 价格 * 保证金比例
     * -- 附：管理费 = 股数 * 价格 * 管理费比例
     * 公式推导：
     * -- 可用策略金 = 保证金 + 管理费
     * -> 保证金 = 可用策略金 - 管理费
     * -> （股数 * 价格 * 保证金比例） = 可用策略金 - （股数 * 价格 * 管理费比例）
     * -> 可用策略金 = （股数 * 价格 * 保证金比例） + （股数 * 价格 * 管理费比例）
     * -> 可用策略金 = 股数 * 价格 * （保证金比例 + 管理费比例）
     * -> 股数 = 可用策略金 / （价格 * （保证金比例 + 管理费比例））
     * -- 最大可买股数 = floor(股数 / 100) * 100
     *
     * @param bool  $isMonthly 按月收取管理费
     * @param float $strategyBalance 策略金余额
     * @param float $frozen 冻结资金
     * @param float $price 用户委托价
     *
     * @return int
     */
    public static function calcMaxBuyVolume($strategyBalance, $frozen, $price, $isMonthly = false)
    {
        if ($price <= 0) return 0;

        // 可用策略金
        $strategy = bcsub($strategyBalance, $frozen, 2);

        // 获取系统交易费用设置
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFee = $isMonthly ? $tradingFee['monthly_m_fee'] : $tradingFee['management_fee'];
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        if ($depositRate == 0 && $managementFee == 0) {
            $volume = $strategy / $price;
        } else {
            $volume = $strategy / ($price * ($depositRate + $managementFee));
        }
        $maxBuyVolume = floor($volume / 100) * 100;

        return $maxBuyVolume;
    }

    /**
     * 计算代金券最高可买股数
     * 说明：
     * -- 购买时，【可用代金券】能够支付【保证金】及【管理费】
     * -- 股数 = 可用策略金 / 价格
     * -- 最大可买股数 = floor(股数 / 100) * 100
     *
     * @param float $strategyBalance 代金券余额
     * @param float $frozen 代金券冻结资金
     * @param float $price 用户委托价
     *
     * @return int
     */
    public static function calcCashCouponMaxBuyVolume($strategyBalance, $frozen, $price)
    {
        if ($price <= 0) return 0;

        // 可用策略金
        $strategy = bcsub($strategyBalance, $frozen, 2);

        $volume = $strategy / $price;

        $maxBuyVolume = floor($volume / 100) * 100;

        return $maxBuyVolume;
    }

    /**
     * 计算保证金
     * -- 针对没有停牌的股票
     *
     * @param $stockValue
     *
     * @return float
     */
    public static function calcDeposit($stockValue)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];
        // 保证金金额
        $deposit = round(bcmul($stockValue, $depositRate, 4), 2);

        return $deposit;
    }

    /**
     * 计算100股的保证金
     * -- 以此为基数来计算买入委托是总的冻结资金
     *
     * @param float $price 委托价
     *
     * @return float
     */
    public static function calcDeposit100($price)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        // 100股对应的市值
        $value100 = bcmul($price, 100, 4);

        // 每100股的保证金金额
        $deposit100 = round(bcmul($value100, $depositRate, 4), 2);

        return $deposit100;
    }

    /**
     * 计算100股的保证金
     * -- 以此为基数来计算买入委托是总的冻结资金
     *
     * @param float $price 委托价
     *
     * @return float
     */
    public static function calcCashCouponDeposit100($price)
    {
        // 100股对应的市值
        $value100 = bcmul($price, 100, 4);

        // 每100股的保证金金额
        $deposit100 = round(bcmul($value100, 1, 4), 2);

        return $deposit100;
    }

    /**
     * 计算停牌股票追加保证金
     * -- 针对停牌的股票
     *
     * @param $stockValue
     *
     * @return float
     */
    public static function calcSuspensionDeposit($stockValue)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 停牌保证金比例
        $depositRateS = $tradingFee['deposit_rate_s'];
        // 保证金金额
        $deposit = round(bcmul($stockValue, $depositRateS, 4), 2);

        return $deposit;
    }

    /**
     * 计算解冻金额
     * -- 以100股的保证金为计数，计算给定股数（100的倍数）的冻结保证金
     *
     * @param float $deposit100 每100股的保证金
     * @param int   $volume 股数 100 的整数倍
     *
     * @return string
     */
    public static function calcUnfrozenDeposit($deposit100, $volume)
    {
        $multiplier = $volume / 100;
        $unfrozen   = bcmul($deposit100, $multiplier, 2);

        return $unfrozen;
    }

    /**
     * 计算手续费
     * -- 手续费 = 成交市值 * 手续费比例
     * -- 手续费有最低收取额
     *
     * @param $stockValue
     *
     * @return float
     */
    public static function calcServiceFee($stockValue)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 手续费比例
        $serviceFeeRate = $tradingFee['service_fee'];
        // 最低手续费
        $minServiceFee = $tradingFee['service_fee_min'];
        // 手续费
        $fee = round(bcmul($stockValue, $serviceFeeRate, 4), 2);

        return $fee > $minServiceFee ? $fee : $minServiceFee;
    }

    /**
     * 计算印花税
     * -- 仅卖出时收取
     *
     * @param float $stockValue 股票市值
     *
     * @return float
     */
    public static function calcStampTax($stockValue)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 印花税收取比例
        $stampTaxRate = $tradingFee['stamp_tax'];

        return round(bcmul($stockValue, $stampTaxRate, 4), 2);
    }

    /**
     * 计算过户费
     * -- 仅卖出时收取
     *
     * @param float $stockValue 股票市值
     *
     * @return float
     */
    public static function calcTransferFee($stockValue)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 过户费收取比例
        $transferFeeRate = $tradingFee['transfer_fee'];

        return round(bcmul($stockValue, $transferFeeRate, 4), 2);
    }

    /**
     * 计算管理费
     * -- 针对非停牌股票的管理费计算
     *
     * @param float $stockValue 股票市值
     * @param bool  $isMonthly 是否月费
     * @param bool  $isSuspended 是否停牌
     *
     * @return float
     */
    public static function calcManagementFee($stockValue, $isMonthly = false, $isSuspended = false)
    {
        // 交易费用配置
        $tradingFee = SystemRedis::getTradingFee();
        // 正常管理费收取比例
        $managementFeeRate = $tradingFee['management_fee'];
        // 如果停牌
        $isSuspended && $managementFeeRate = $tradingFee['management_fee_s'];
        // 如果月费（月费不算停牌）
        $isMonthly && $managementFeeRate = $tradingFee['monthly_m_fee'];

        return round(bcmul($stockValue, $managementFeeRate, 4), 2);
    }

    /**
     * 计算月管理费
     * -- 针对非停牌股票的管理费计算
     *
     * @param float $stockValue 股票市值
     *
     * @return float
     */
    public static function calcMonthlyManagementFee($stockValue)
    {
        $isMonthly   = true;
        $isSuspended = false;

        return self::calcManagementFee($stockValue, $isMonthly, $isSuspended);
    }

    /**
     * 计算【停牌股票】管理费
     *
     * @param float $stockValue 股票市值
     *
     * @return float
     */
    public static function calcSuspendedManagementFee($stockValue)
    {
        $isMonthly   = false;
        $isSuspended = true;

        return self::calcManagementFee($stockValue, $isMonthly, $isSuspended);
    }

    /**
     * 计算结算盈亏
     * -- 结算盈亏 = 当前市值 + 总卖出市值 - 总买入市值 + 累计保证金 - 当前预留保证金
     * -- 当前市值 = 卖出后剩余股数 * 卖出成交价
     * -- 总卖出市值 = 包含本次卖出收入
     * -- 总买入市值 = 持仓表中记录的总买入市值
     * -- 累计保证金 = 持仓表中记录的保证金
     * -- 当前预留保证金 = 当前市值 * 保证金比例
     * 适用范围：
     * -- 部分卖出
     * -- 全部卖出
     * -- 休市结算
     *
     * @param float $price 结算价（卖出时是：卖出价，休市结算是：昨结价）
     * @param int   $volumePosition
     * @param float $sumSellValueIn
     * @param float $sumBuyValueCost
     * @param float $deposit
     *
     * @return float
     */
    public static function settlePAL($price, $volumePosition, $sumSellValueIn, $sumBuyValueCost, $deposit)
    {
        // 取交易费费用
        $tradingFee = SystemRedis::getTradingFee();
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        // 计算当前市值
        $nowValue = bcmul($price, $volumePosition, 4);
        // 当前市值预留保证金

        $nowDeposit = bcmul($nowValue, $depositRate, 4);
        // 当前市值 + 总卖出市值
        $settlePAL = bcadd($nowValue, $sumSellValueIn, 4);
        // 上值 - 总买入市值
        $settlePAL = bcsub($settlePAL, $sumBuyValueCost, 4);
        // 上值 + 累计保证金
        $settlePAL = bcadd($settlePAL, $deposit, 4);
        // 上值 - 当前预留保证金
        $settlePAL = bcsub($settlePAL, $nowDeposit, 4);
        // 保留两位小数
        $settlePAL = round($settlePAL, 2);

        return $settlePAL;
    }

    /**
     * 计算补仓价格
     * -- 补仓价 = 止损价 = （总买入市值 - 卖出市值 - 保证金）/ (0.95 * 当前股数)
     *
     * @param float $sumBuyValueCost 总买入市值（含费用的）
     * @param float $sumSellValueIn 总卖出市值（含费用的）
     * @param float $deposit 持仓占用保证金
     * @param int   $volumePosition 当前持仓股数（为0时，不计算）
     *
     * @return float
     */
    public static function calcStopLossPrice($sumBuyValueCost, $sumSellValueIn, $deposit, $volumePosition)
    {
        if ($volumePosition == 0) {
            return 0;
        } else {
            $l             = bcsub($sumBuyValueCost, $sumSellValueIn, 4);
            $l             = bcsub($l, $deposit, 4);
            $r             = bcmul($volumePosition, 0.95, 2);
            $stopLossPrice = bcdiv($l, $r, 4);

            return round($stopLossPrice, 2);
        }
    }

    /**
     * 计算持仓收益
     * -- 本方法仅计算参考值（其实就是持仓盈亏）
     * -- 参考值：持仓收益 = （当前价 - 持仓均价） * 持仓数量
     * 附：
     * -- 精确值：持仓收益 = 当前市值 + 总卖出市值 - 总买入市值
     *
     * @param float $nowPrice
     * @param float $positionPrice
     * @param int   $volumePosition
     *
     * @return float
     */
    public static function calcPositionIncome($nowPrice, $positionPrice, $volumePosition)
    {
        return round(bcmul(bcsub($nowPrice, $positionPrice, 4), $volumePosition, 4));
    }

    /**
     * 买入均价
     * -- 买入均价 = 总买入市值 / 总买入数量
     *
     * @param $sumBuyValueCost
     * @param $sumBuyVolume
     *
     * @return float
     */
    public static function calcBCostPrice($sumBuyValueCost, $sumBuyVolume)
    {
        if ($sumBuyVolume <= 0) return 0;

        return bcdiv($sumBuyValueCost, $sumBuyVolume, 4);
    }

}
