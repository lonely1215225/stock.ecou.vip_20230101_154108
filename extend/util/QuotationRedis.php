<?php
namespace util;

use app\common\model\Condition;
use app\common\model\Favorite;
use app\common\model\OrderPosition;
use app\common\model\Stock;

/**
 * 行情订阅相关Redis操作类
 *
 * @package util
 */
class QuotationRedis extends RedisUtil
{

    public static function getPrice($market, $stockCode, $price, $direction, $mode = 'auto')
    {
        try {
            // 最新行情
            $eprice = 0;
            $qdata  = RedisUtil::getQuotationData($stockCode, $market);
            //if(!$price) $price = $qdata['Price'];
            switch ($direction) {
               case 'buy':
                    if($price <= 0){
                        $eprice = $qdata['Price'] >= $qdata['Sp1'] && $qdata['Sp1'] > 0 ? $qdata['Price'] : $qdata['Sp1'];
                    }else{
                        $eprice = $price >= $qdata['Price'] && $qdata['Price'] > 0 && $price <= $qdata['Sp5'] ? $price : 0;
                    }
                    //echo "price2:{$price}\n";echo "eprice2:{$eprice}\n";echo "Sp1:{$qdata['Sp1']}\n";return;
                    break;
                case 'sell':
                    if($price <= 0){
                        $eprice = $qdata['Price'] <= $qdata['Bp1'] && $qdata['Bp1'] > 0 ? $qdata['Price'] : $qdata['Bp1'];
                    }else{
                        $eprice = $price <= $qdata['Price'] && $qdata['Price'] > 0 && $price <= $qdata['Bp1'] ? $price : 0;
                    }
                    break;
            }
        } catch (\Exception $e) {
            $eprice = 0;
        }
        
        return $eprice;
    }
    /**
     * 获取五档盘口价格
     * -- step模式说明：根据给定盘口，依次向大盘价递进取值
     * -- 例：$stall = 3，则会按照买三价到买一价，再到大盘价，依次取值
     *
     * @param string $market 证券市场代码
     * @param string $stockCode 股票代码
     * @param int    $stall 盘口，取值范围 [-5, 5]，分别代表五档盘口的【卖五】到【买五】，0表示大盘价
     * @param string $mode 模式['only' => '仅取指定盘口价', 'step' => '向市价递进', 'step_not_market' => '向市价递进，不取市价']
     *
     * @return float
     */
    public static function getStallsPrice($market, $stockCode, $stall = 0, $mode = 'step')
    {
        try {
            // 最新行情
            $quotation = RedisUtil::getQuotationData($stockCode, $market);

            // 仅取大盘价
            if ($stall == 0) return $quotation['Price'];

            $field = $stall > 0 ? 'Bp' : 'Sp';
            $stall = abs($stall);
            $stall = $stall > 5 ? 5 : $stall;
            $price = 0;
            switch ($mode) {
                default:
                case 'step':
                case 'step_not_market':
                    // 向1档递进取价格
                    for (; $stall >= 1; $stall--) {
                        $key   = $field . $stall;
                        $price = $quotation[$key];
                        if ($price > 0) break;
                    }
                    if ($mode != 'step_not_market') {
                        // 取市价
                        $price = $price > 0 ? $price : $quotation['Price'];
                    }
                    break;
                case 'only':
                    // 仅取指定盘口价
                    $key   = $field . $stall;
                    $price = $quotation[$key];
                    break;
            }
        } catch (\Exception $e) {
            $price = 0;
        }

        return $price;
    }
    /**
     * 获取活跃股票列表
     *
     * @param string $market 证券市场代码
     *
     * @return array
     */
    public static function getActiveStockList()
    {
        $key = 'subscribe_position';
        
        // 如果所有股票缓存列表不存在，则先更新缓存
        if (!self::redis()->exists($key)) {
            QuotationRedis::initPositionSubscribe();
        }
        //$list = self::redis()->lRange($key, 0, -1);
        $list = self::redis()->sMembers($key);
        $list = array_chunk($list,800,true);
        return $list ?: [];
    }
    /**
     * 初始化持仓订阅列表
     * -- 用于每日开市前初始化订阅列表
     */
    public static function initPositionSubscribe()
    {
        try {
            // 删除列表
            $key     = 'subscribe_position';
            $lastKey = 'subscribe_position_last';
            self::redis()->del($key);
            self::redis()->del($lastKey);

            // 获取持仓中的股票列表
            $list = OrderPosition::where('is_finished', false)->column('market,stock_code', 'stock_id');
            //print_r($list);return;
            // 获取获取条件单中的股票列表
            $list2 = Condition::where('state', CONDITION_STATE_ING)->column('market,stock_code', 'stock_id');

            $list = array_merge($list, $list2);

            $data = [];
            foreach ($list as $item) {
                $data[] = $item['market'] . '_' . $item['stock_code'];// . '_' . BasicData::marketToSecurityType($item['market']);
            }
            //var_dump($data);
            self::redis()->sAddArray($key, $data);
            self::redis()->expireAt($key, self::midnight());
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }

    /**
     * 加入持仓订阅列表
     * -- 用于成交回报处理完成后
     *
     * @param $market
     * @param $stockCode
     */
    public static function addPositionSubscribe($market, $stockCode)
    {
        try {
            $key   = 'subscribe_position';
            $value = $stockCode . '_' . BasicData::marketToSecurityType($market);
            self::redis()->sAdd($key, $value);
            self::redis()->expireAt($key, self::midnight());
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }

    /**
     * 初始化活跃订阅列表
     *
     * @param bool $isFirst 是否初次调用，默认true
     */
    public static function initActiveSubscribe($isFirst = true)
    {
        try {
            // 活跃订阅列表中删除持仓订阅列表中已存在的
            $activeKey   = 'subscribe_active';
            $positionKey = 'subscribe_position';
            self::redis()->sDiffStore($activeKey, $activeKey, $positionKey);

            // 删除上次订阅列表
            $lastKey = 'subscribe_active_last';
            self::redis()->del($lastKey);

            if ($isFirst) {
                // 自选
                self::cacheFavoriteSubscribe();
                // 优选
                self::cacheSelectiveSubscribe();
            }

            self::redis()->expireAt($activeKey, self::midnight());
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }

    /**
     * 自选列表当作活跃股票处理
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function cacheFavoriteSubscribe()
    {
        $key  = 'subscribe_active';
        $list = Favorite::field('market,stock_code')->distinct(true)->select();

        foreach ($list as $item) {
            $market    = $item['market'];
            $stockCode = $item['stock_code'];
            $value     = $stockCode . '_' . BasicData::marketToSecurityType($market);
            // 当持仓股票订阅列表中不存在时，才加入活跃列表
            if (!self::redis()->sIsMember('subscribe_position', $value)) {
                self::redis()->sAdd($key, $value);
            }
        }
    }

    /**
     * 优选列表当作活跃股票处理
     */
    private static function cacheSelectiveSubscribe()
    {
        $key  = 'subscribe_active';
        $list = Stock::where('is_selective', true)->column('market,stock_code', 'id');

        foreach ($list as $item) {
            $market    = $item['market'];
            $stockCode = $item['stock_code'];
            $value     = $stockCode . '_' . BasicData::marketToSecurityType($market);
            // 当持仓股票订阅列表中不存在时，才加入活跃列表
            if (!self::redis()->sIsMember('subscribe_position', $value)) {
                self::redis()->sAdd($key, $value);
            }
        }
    }

    /**
     * 获取持仓订阅列表
     *
     * @return array|bool
     */
    public static function getPositionSubscribeList()
    {
        try {
            $key     = 'subscribe_position';
            $lastKey = 'subscribe_position_last';
            $diffKey = 'subscribe_position_diff';

            // 与上次列表求差集
            self::redis()->sDiffStore($diffKey, $key, $lastKey);

            // 如果差集数量大于0，说明有新加入的，需要重新订阅行情
            if (self::redis()->sCard($diffKey)) {
                // 取最新的订阅列表
                $list = self::redis()->sMembers($key);

                // 合并新增的到上次列表中
                self::redis()->sUnionStore($lastKey, $lastKey, $key);
            }

            // 删除差集列表
            self::redis()->del($diffKey);

            // 设置key的过期时间
            self::redis()->expireAt($lastKey, self::midnight());
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        return $list ?? false;
    }

    /**
     * 获取活跃订阅列表
     *
     * @return array|bool
     */
    public static function getActiveSubscribeList()
    {
        try {
            $key     = 'subscribe_active';
            $lastKey = 'subscribe_active_last';
            $diffKey = 'subscribe_active_diff';

            // 与上次列表求差集
            self::redis()->sDiffStore($diffKey, $key, $lastKey);

            // 如果差集数量大于0，说明有新加入的，需要重新订阅行情
            if (self::redis()->sCard($diffKey)) {
                // 取最新的订阅列表
                $list = self::redis()->sMembers($key);

                // 合并新增的到上次列表中
                self::redis()->sUnionStore($lastKey, $lastKey, $key);
            }

            // 删除差集列表
            self::redis()->del($diffKey);

            // 设置key的过期时间
            self::redis()->expireAt($lastKey, self::midnight());
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        return $list ?? false;
    }

    /**
     * 加入活跃订阅列表
     *
     * @param $market
     * @param $stockCode
     */
    public static function addActiveSubscribe($market, $stockCode)
    {
        try {
            $value = $stockCode . '_' . BasicData::marketToSecurityType($market);
            // 当持仓股票订阅列表中不存在时，才加入活跃列表
            if (!self::redis()->sIsMember('subscribe_position', $value)) {
                $key = 'subscribe_active';
                self::redis()->sAdd($key, $value);
                self::redis()->expireAt($key, self::midnight());
            }
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }

}
