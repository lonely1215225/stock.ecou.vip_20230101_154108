<?php
namespace util;

use app\common\model\Condition;

class ConditionRedis extends RedisUtil
{

    /**
     * 缓存当前交易日的所有未触发条件单
     */
    public static function cacheAllCondition()
    {
        $tradingDate = TradingUtil::currentTradingDate();
        $list        = Condition::where('trading_date', $tradingDate)
            ->where('state', CONDITION_STATE_ING)
            ->column('id, stock_code, volume, state, order_position_id, user_id, market, direction, trigger_price, price_type, price, stock_id, trigger_compare', 'id');

        foreach ($list as $id => $item) {
            $market    = $item['market'];
            $stockCode = $item['stock_code'];

            $itemKey = 'condition_' . $id;
            $setKey  = 'condition_set_' . $market . $stockCode;
            $pipe    = self::redis()->multi(\Redis::MULTI);
            $pipe->hMSet($itemKey, $item);
            $pipe->sAdd($setKey, $id);
            $pipe->expireAt($itemKey, self::midnight());
            $pipe->expireAt($setKey, self::midnight());
            $pipe->exec();
        }
    }

    /**
     * 缓存指定条件单的数据
     *
     * @param $id
     */
    public static function cacheCondition($id)
    {
        $tradingDate = TradingUtil::currentTradingDate();
        $list        = Condition::where('id', $id)
            ->where('trading_date', $tradingDate)
            ->where('state', CONDITION_STATE_ING)
            ->column('id, stock_code, volume, state, order_position_id, user_id, market, direction, trigger_price, price_type, price, stock_id, trigger_compare', 'id');

        foreach ($list as $id => $item) {
            $market    = $item['market'];
            $stockCode = $item['stock_code'];

            $itemKey = 'condition_' . $id;
            $setKey  = 'condition_set_' . $market . $stockCode;
            $pipe    = self::redis()->multi(\Redis::MULTI);
            $pipe->hMSet($itemKey, $item);
            $pipe->sAdd($setKey, $id);
            $pipe->expireAt($itemKey, self::midnight());
            $pipe->expireAt($setKey, self::midnight());
            $pipe->exec();
        }
    }

    /**
     * 获取指定条件单的数据
     *
     * @param $id
     *
     * @return array
     */
    public static function getCondition($id)
    {
        $key = 'condition_' . $id;
        if (!self::redis()->exists($key)) {
            self::cacheCondition($id);
        }

        $condition = self::redis()->hMGet($key, [
            'id',
            'stock_code',
            'volume',
            'state',
            'order_position_id',
            'user_id',
            'market',
            'direction',
            'trigger_price',
            'price_type',
            'price',
            'stock_id',
            'trigger_compare',
        ]);

        return $condition ?: [];
    }

    /**
     * 删除指定条件单的缓存
     *
     * @param $id
     * @param $market
     * @param $stockCode
     */
    public static function delConditionCache($id, $market, $stockCode)
    {
        $itemKey = 'condition_' . $id;
        $setKey  = 'condition_set_' . $market . $stockCode;
        self::redis()->del($itemKey);
        self::redis()->sRem($setKey, $id);
    }

}
