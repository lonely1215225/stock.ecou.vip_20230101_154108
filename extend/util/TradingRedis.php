<?php
namespace util;

use app\common\model\TradingCalendar;

class TradingRedis extends RedisUtil
{

    /**
     * 缓存非交易日设置
     *
     * @throws \Exception
     */
    public static function cacheNonTradingDate()
    {
        // 获取本月及后一个月的非交易日
        $dateTime           = new \DateTime(date('Y-m-01'));
        $startDate          = $dateTime->format('Y-m-d');
        $endDate            = $dateTime->add(\DateInterval::createFromDateString('2 month'))->format('Y-m-d');
        $nonTradingDateList = TradingCalendar::where('is_disabled', true)
            ->where('trading_date', '>=', $startDate)
            ->where('trading_date', '<', $endDate)
            ->column('trading_date');
        $nonTradingDateList = $nonTradingDateList ?: ['0000-00-00'];

        // 缓存数据并设置午夜过期
        $key = 'non_trading_date';
        self::redis()->sAddArray($key, $nonTradingDateList);
        self::redis()->expireAt($key, self::midnight());
    }

    /**
     * 判断一个日期是否是交易日
     *
     * @param string $date 格式:yyyy-mm-dd
     *
     * @return bool
     */
    public static function isTradingDate($date)
    {
        try {
            $key = 'non_trading_date';
            if (!self::redis()->exists($key)) {
                self::cacheNonTradingDate();
            }
            $ret = !self::redis()->sIsMember($key, $date);
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        return $ret ?? false;
    }

}
