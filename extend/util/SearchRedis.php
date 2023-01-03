<?php
namespace util;

use app\common\model\Stock;

/**
 * 搜索股票并缓存结果
 *
 * @package util
 */
class SearchRedis extends RedisUtil
{

    /**
     * 搜索股票
     * -- 查询并缓存结果
     * -- 股票代码
     * -- 股票首字母
     * -- 名称
     *
     * @param string $keyword 关键词
     *
     * @return array
     * - 返回格式：['证券市场代码|证券代码', ...]
     */
    public static function search($keyword)
    {
        if (preg_match('/^\d+$/', $keyword)) {
            // 股票代码搜索
            return self::searchByStockCode($keyword);
        } elseif (preg_match('/^[A-Za-z]+$/', $keyword)) {
            // 首字母搜索
            return self::SearchByInitial($keyword);
        } else {
            // 名称搜索
            return self::SearchByName($keyword);
        }
    }

    /**
     * 【股票代码】搜索
     * -- 查询并缓存结果
     *
     * @param string $stockCode 股票代码
     *
     * @return array
     * - 返回格式：['证券市场代码|证券代码', ...]
     */
    private static function searchByStockCode($stockCode)
    {
        if (strlen($stockCode) > 0 && strlen($stockCode) < 6) {
            $key = 'search_' . $stockCode;

            // 如果缓存数据不存在,查询并缓存
            if (!self::redis()->exists($key)) {

                $list = Stock::where('stock_code', 'LIKE', "%{$stockCode}%")
                    ->order('stock_code', 'ASC')
                    ->limit(50)
                    ->column("market||'|'||stock_code");
                $list = $list ?: [''];
                self::redis()->sAddArray($key, $list);
                // 设置缓存时间
                self::redis()->expire($key, SEARCH_CACHE_EXPIRE_TIME);
            }

            $list = self::redis()->sMembers($key);
        } elseif (strlen($stockCode) == 6) {
            $list = [];
            if (self::isStockExist($stockCode, MARKET_SH)) $list[] = MARKET_SH . '|' . $stockCode;
            if (self::isStockExist($stockCode, MARKET_SZ)) $list[] = MARKET_SZ . '|' . $stockCode;
            if (self::isStockExist($stockCode, MARKET_KC)) $list[] = MARKET_KC . '|' . $stockCode;
        } else {
            $list = [];
        }

        // 去掉空值
        $list = count($list) == 1 && empty($list[0]) ? [] : $list;

        return $list ?: [];
    }

    /**
     * 【首字母】搜索
     * -- 查询并缓存结果
     *
     * @param string $initial
     *
     * @return array
     * - 返回格式：['证券市场代码|证券代码', ...]
     */
    private static function SearchByInitial($initial)
    {
        $initial = strtoupper($initial);
        $key     = 'search_' . $initial;

        // 如果缓存数据不存在,查询并缓存
        if (!self::redis()->exists($key)) {

            $list = Stock::where('initial', 'LIKE', "%{$initial}%")
                ->order('stock_code', 'ASC')
                ->limit(50)
                ->column("market||'|'||stock_code");
            $list = $list ?: [''];
            self::redis()->sAddArray($key, $list);
            // 设置缓存时间
            self::redis()->expire($key, SEARCH_CACHE_EXPIRE_TIME);
        }

        $list = self::redis()->sMembers($key);
        // 去掉空值
        $list = count($list) == 1 && empty($list[0]) ? [] : $list;

        return $list ?: [];
    }

    /**
     * 【名称】搜索
     * -- 查询并缓存结果
     *
     * @param string $name
     *
     * @return array
     * - 返回格式：['证券市场代码|证券代码', ...]
     */
    private static function SearchByName($name)
    {
        $md5 = md5($name);
        $key = 'search_' . substr($md5, 0, 3) . substr($md5, 30, 3) . substr($md5, 14, 4);
        // 如果缓存数据不存在,查询并缓存
        if (!self::redis()->exists($key)) {
            $list = Stock::where('stock_name', 'LIKE', "%{$name}%")
                ->order('stock_code', 'ASC')
                ->limit(50)
                ->column("market||'|'||stock_code");
            $list = $list ?: [''];
            self::redis()->sAddArray($key, $list);
            // 设置缓存时间
            self::redis()->expire($key, SEARCH_CACHE_EXPIRE_TIME);
        }

        $list = self::redis()->sMembers($key);
        // 去掉空值
        $list = count($list) == 1 && empty($list[0]) ? [] : $list;

        return $list ?: [];
    }

}
