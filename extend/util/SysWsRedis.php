<?php
namespace util;

class SysWsRedis extends RedisUtil
{

    /**
     * 缓存提现提示标识
     *
     * @return bool
     */
    public static function cacheWithdrawPrompt()
    {
        $ret   = self::redis()->sAdd('remind_flag',  'withdrawRemind');

        return $ret ? true : false;
    }

    /**
     * 缓存管理员token、客户端id
     *
     * @param string $token
     * @return bool
     */
    public static function cacheTokenClient($token, $clientID)
    {
        $value=serialize([$token, $clientID]);
        self::redis()->sAdd('admin_token_list', $value);

        // clientID 对应的 token
        $c2u_key = 'token_client_c2u_' . $clientID;
        self::redis()->set($c2u_key, $token);
    }

    /**
     * 获取WebSocket客户端对应的token
     *
     * @param $clientID
     * @return string
     */
    public static function getWsToken($clientID)
    {
        $key   = 'token_client_c2u_' . $clientID;
        $token = self::redis()->get($key);

        return $token ?: '';
    }

    /**
     * 获取token列表
     *
     * @return array
     */
    public static function getTokenList()
    {
        $key = 'admin_token_list';
        if (self::redis()->exists($key)) {

            return self::redis()->sMembers($key);
        } else {
            return [];
        }
    }

    /**
     * 缓存充值提示标识
     *
     * @return bool
     */
    public static function cacheRechargePrompt()
    {
        $ret   = self::redis()->sAdd('remind_flag',  'rechargeRemind');

        return $ret ? true : false;
    }

    /**
     * 获取缓存标识
     *
     * @return array|bool|string
     */
    public static function getPromptFlag()
    {
        $key = 'remind_flag';
        if (self::redis()->exists($key)) {

            return self::redis()->sMembers($key);
        } else {
            return [];
        }
    }

    /**
     * 移除集合元素
     *
     * @param $member
     * @return bool
     */
    public static function removeFlag($member)
    {
        $ret = self::redis()->sRem('remind_flag', $member);

        return $ret ? true : false;
    }

    /**
     * 移除token
     *
     * @param $member
     * @return bool
     */
    public static function removeToken($token, $fd)
    {
        $value=serialize([$token, $fd]);
        $ret = self::redis()->sRem('admin_token_list', $value);

        return $ret ? true : false;
    }

}
