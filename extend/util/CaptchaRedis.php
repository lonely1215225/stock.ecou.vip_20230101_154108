<?php
namespace util;

class CaptchaRedis extends RedisUtil
{

    /**
     * 缓存短信验证码
     *
     * -- 使用Redis集合类型
     * -- 将每个用户发送的注册验证码都存入缓存集合
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public static function cacheCaptcha($mobile, $captcha)
    {
        $key = "sms_captcha";
        self::redis()->sAdd($key, $mobile . '|' . $captcha);
        self::redis()->expireAt($key, self::midnight());
    }

    /**
     * 短信验证码是否存在
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     *
     * @return bool
     */
    public static function isCaptchaExist($mobile, $captcha)
    {
        $key = 'sms_captcha';

        return self::redis()->sIsMember($key, $mobile . '|' . $captcha);
    }

    /**
     * 删除短信验证码
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public static function deleteCaptcha($mobile, $captcha)
    {
        $key = 'sms_captcha';
        self::redis()->sRem($key, $mobile . '|' . $captcha);
    }

}
