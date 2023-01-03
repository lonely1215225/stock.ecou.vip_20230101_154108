<?php

namespace util;

use app\common\model\System;

class SystemRedis extends RedisUtil
{
    /* 获取 基础设置*/
    public static function getConfig()
    {
        $key = 'SYSTEM_CONFIG';
        if (!self::redis()->exists($key)) {
            self::cacheConfig();
        }
        $fields = [
            'is_regist',
            'is_invita',
            'is_smsreg',
            'is_share',
            'sms_use',
            'sms_pwd',
            'sms_name',
            'h5_url',
            'kefu_link',
            'kefuphone',
            'banner_url',
        ];
        $data = self::redis()->hMGet($key, $fields);
        return $data ?: false;
    }
    /* 缓存 基础设置*/
    public static function cacheConfig()
    {
        $key = 'SYSTEM_CONFIG';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }
    /**
     * 缓存交易时间
     */
    public static function cacheTradingTime()
    {
        $key = 'SYSTEM_TRADING_TIME';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }

    /**
     * 获取缓存的交易时间
     *
     * @return array
     */
    public static function getTradingTime()
    {
        $key = 'SYSTEM_TRADING_TIME';
        if (!self::redis()->exists($key)) {
            self::cacheTradingTime();
        }

        $tradingTime = self::redis()->hMGet($key, ['am_market_open_time', 'am_market_close_time', 'pm_market_open_time', 'pm_market_close_time']);

        return $tradingTime ?: [];
    }

    /**
     * 缓存 涨跌停禁买线比例
     */
    public static function cacheBuyLimitRate()
    {
        $key = 'SYSTEM_BUY_LIMIT_RATE';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->set($key, $data);
        }
    }

    /**
     * 获取 涨跌停禁买线比例
     *
     * @return float
     */
    public static function getBuyLimitRate()
    {
        $key = 'SYSTEM_BUY_LIMIT_RATE';
        if (!self::redis()->exists($key)) {
            self::cacheBuyLimitRate();
        }

        $data = self::redis()->get($key);

        return floatval($data) ?: 0.1;
    }

    /**
     * 缓存 交易费用设置
     */
    public static function cacheTradingFee()
    {
        $key = 'SYSTEM_TRADING_FEE';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }

    /**
     * 获取 交易费用设置
     *
     * @return array|false
     * -- service_fee 手续费比例
     * -- service_fee_min  手续费最低收取
     * -- management_fee   管理费比例
     * -- monthly_m_fee    月管理费
     * -- stamp_tax        印花税比例
     * -- transfer_fee     过户费比例
     * -- management_fee_s 停牌管理费
     * -- deposit_rate     履约保证金比例
     * -- deposit_rate_s   停牌履约保证金比例
     */
    public static function getTradingFee()
    {
        $key = 'SYSTEM_TRADING_FEE';
        if (!self::redis()->exists($key)) {
            self::cacheTradingFee();
        }

        $fields = [
            'service_fee',
            'service_fee_min',
            'management_fee',
            'monthly_m_fee',
            'stamp_tax',
            'transfer_fee',
            'management_fee_s',
            'deposit_rate',
            'deposit_rate_s',
        ];

        $data = self::redis()->hMGet($key, $fields);

        return $data ?: false;
    }

    /**
     * 缓存 收益宝信息设置
     */
    public static function cacheYuebao()
    {
        $key = 'SYSTEM_YUEBAO';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }
    /* 获取 APP相关设置*/
    public static function getAppConfig()
    {
        $key = 'SYSTEM_APP';
        if (!self::redis()->exists($key)) {
            self::cacheAppConfig();
        }

        $fields = [
            'android_power',
            'android_version',
            'android_version_name',
            'android_description',
            'android_down',
            'apk_down_url',
            'ios_power',
            'ios_version',
            'ios_version_name',
            'ios_description',
            'ios_down',
            'ios_down_url'
        ];

        $data = self::redis()->hMGet($key, $fields);

        return $data ?: false;
    }
    /**
     * 缓存 APP相关设置
     */
    public static function cacheAppConfig()
    {
        $key = 'SYSTEM_APP';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }
    /**
     * 获取 交易费用设置
     *
     * @return array|false
     * -- is_open 是否开启收益宝
     * -- yuebao_fee 支付宝万份收益率
     */
    public static function getYuebao()
    {
        $key = 'SYSTEM_YUEBAO';
        if (!self::redis()->exists($key)) {
            self::cacheYuebao();
        }

        $fields = [
            'is_open',
            'yuebao_fee',
        ];

        $data = self::redis()->hMGet($key, $fields);

        return $data ?: false;
    }

    //获取二维码信息
    public static function getQrcode()
    {
        $key = 'SYSTEM_QRCODE';
        if (!self::redis()->exists($key)) {
            self::cacheQrcode();
        }

        $fields = [
            'wechat_customer_service',
            'wechat_official_account',
            'wechat_android',
            'wechat_ios',
        ];
        $data = self::redis()->hMGet($key, $fields);

        return $data ?:false;
    }

    /**
     * 缓存 二维码信息设置
     */
    public static function cacheQrcode()
    {
        $key = 'SYSTEM_QRCODE';
        $value = System::where('k', $key)->value('v');

        if (!is_null($value) && $value) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }

    /**
     * 缓存 代金券管理设置
     */
    public static function cacheCashCoupon(){
        $key = 'SYSTEM_CASH_COUPON';
        $value = System::where('k', $key)->value('v');
        if (!is_null($value)) {
            $data = unserialize($value);
            self::redis()->hMSet($key, $data);
        }
    }

    /**
     * 获取代金券的缓存设置
     * @return bool
     */
    public static function getCashCoupon()
    {
        $key = 'SYSTEM_CASH_COUPON';
        if (!self::redis()->exists($key)) {
            self::cacheCashCoupon();
        }

        $fields = [
            'is_open',
            'cash_coupon_money',
            'expiry_time',
            'expiry_unit',
            'in_loss',
            'close_position_time',
        ];
        $data = self::redis()->hMGet($key, $fields);

        return $data ?:false;
    }

}
