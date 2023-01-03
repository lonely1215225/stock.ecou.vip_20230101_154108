<?php
namespace util;

/**
 * CLI 脚本用到的一些状态
 *
 * @package util
 */
class ScriptRedis extends RedisUtil
{

    /**
     * 检测休市撤单是否未执行
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isCancelNotRun()
    {
        $key = 'SCRIPT_MARKET_CLOSED_CANCEL';

        return self::isNotExist($key);
    }

    /**
     * 检测休市设置可卖数量是否未执行
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isNotSetVolumeForSell()
    {
        $key = 'SCRIPT_SET_VOLUME_FOR_SELL';

        return self::isNotExist($key);
    }

    /**
     * 是否未结算
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isNotSettlement()
    {
        $key = 'SCRIPT_DAILY_SETTLEMENT';

        return self::isNotExist($key);
    }

    /**
     * 是否未收取管理费
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isNotTakeManagementFee()
    {
        $key = 'SCRIPT_DAILY_MANAGEMENT_FEE';

        return self::isNotExist($key);
    }

    /**
     * 是否未缓存用户策略金（不含冻结资金）
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isNotCacheUserStrategy()
    {
        $key = 'SCRIPT_CACHE_USER_STRATEGY';

        return self::isNotExist($key);
    }

    /**
     * 是否未缓存持仓信息（仅未完结持仓）
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isNotCachePosition()
    {
        $key = 'SCRIPT_CACHE_POSITION';

        return self::isNotExist($key);
    }

    /**
     * 检测账户收益是否未执行
     * -- 未执行返回 true
     *
     * @return bool
     */
    public static function isYuebaoNotRun()
    {
        $key = 'SCRIPT_YUEBAO_RUN';

        return self::isNotExist($key);
    }

    /**
     * 指定的KEY是否不存在
     * -- 不存在返回 true
     *
     * @param $key
     *
     * @return bool
     */
    private static function isNotExist($key)
    {
        if (self::redis()->exists($key)) {
            return false;
        } else {
            self::redis()->set($key, 'yes');
            self::redis()->expireAt($key, self::midnight());

            return true;
        }
    }

    /**
     * 对应持仓是否可执行追加保证金操作
     *
     * @param $positionID
     *
     * @return bool
     */
    public static function isAdditionalRunAble($positionID)
    {
        $key = 'additional_' . $positionID;
        if (self::redis()->exists($key)) {
            return false;
        } else {
            self::redis()->setex($key, 2, 'ing');

            return true;
        }
    }

}
