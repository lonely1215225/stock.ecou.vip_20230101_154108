<?php
namespace util;

class TradingUtil
{

    /**
     * 返回当前交易日
     *
     * @return false|string
     */
    public static function currentTradingDate()
    {
        return date('Y-m-d');
    }

    /**
     * 判断当前时间是否在交易时间内
     *
     * @return bool
     */
    public static function isInTradingTime()
    {
        $tradingTime = SystemRedis::getTradingTime();
        $nowHI       = intval(date('Hi'));

        if (count($tradingTime) == 0) return false;

        $amStartTime = str_replace(':', '', $tradingTime['am_market_open_time']);
        $amEndTime   = str_replace(':', '', $tradingTime['am_market_close_time']);
        $pmStartTime = str_replace(':', '', $tradingTime['pm_market_open_time']);
        $pmEndTime   = str_replace(':', '', $tradingTime['pm_market_close_time']);

        return (($nowHI >= $amStartTime && $nowHI < $amEndTime) || ($nowHI >= $pmStartTime && $nowHI < $pmEndTime));
    }

    /**
     * 当前价是否超过涨跌幅禁买线
     * -- 大盘价
     *
     * @param float $nowPrice 当前价
     * @param float $preClosePrice 昨日收盘价
     *
     * @return bool
     */
    public static function isOverBuyLimitLine($nowPrice, $preClosePrice, $rate = 0)
    {
        // 获取涨跌幅禁买线比例
        if (empty($rate)) {
            $rate = SystemRedis::getBuyLimitRate();
            $rate = $rate ? $rate : 0.1;
        }

        if($preClosePrice) {
            // 当前价相对昨收价的涨跌幅
            $nowRate = round(abs($preClosePrice - $nowPrice) / $preClosePrice, 4);

            return bccomp($nowRate, $rate, 4) > 0;
        } else {
            return false;
        }

    }

    /**
     * 检测【委托价】是否超过【涨停】或【跌停】
     *
     * @param float  $price 委托价
     * @param string $market 证券市场代码
     * @param string $stockCode 股票代码
     *
     * @return true|string
     */
    public static function checkLimitUpDown($price, $market, $stockCode)
    {
        $quotation      = RedisUtil::getQuotationData($stockCode, $market);
        $preClosePrice  = $quotation['Close'];
        $limitUpPrice   = round(bcmul($preClosePrice, 1.1, 4), 2);
        $limitDownPrice = round(bcmul($preClosePrice, 0.9, 4), 2);

        if ($price >= $limitUpPrice) return '委托价不能超过涨停价';
        if ($price <= $limitDownPrice) return '委托价不能低于跌停价';

        return true;
    }

}
