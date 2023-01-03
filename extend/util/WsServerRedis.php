<?php
namespace util;

/**
 * 交易Websocket服务器相关redis操作类
 *
 * @package util
 */
class WsServerRedis extends RedisUtil
{

    /**
     * 缓存用户与WebSocket客户端的关系
     * -- $userID   => [$clientID, ...]
     * -- $clientID => $userID
     *
     * @param $userID
     * @param $clientID
     */
    public static function cacheWsClient($userID, $clientID)
    {
        $pipe = self::redis()->multi(\Redis::MULTI);

        // userID 对应的 clientID 集合
        $u2c_key = 'ws_client_u2c_' . $userID;
        $pipe->sAdd($u2c_key, $clientID);

        // clientID 对应的 userID
        $c2u_key = 'ws_client_c2u_' . $clientID;
        $pipe->set($c2u_key, $userID);

        // 午夜过期
        $pipe->expireAt($u2c_key, self::midnight());
        $pipe->expireAt($c2u_key, self::midnight());

        $pipe->exec();
    }

    /**
     * 获取用户对应的所有客户端ID列表
     *
     * @param int $userID 用户ID
     *
     * @return array
     */
    public static function getWsClientIDList($userID)
    {
        $key = 'ws_client_u2c_' . $userID;

        return self::redis()->sMembers($key);
    }

    /**
     * 获取客户端ID对应的用户ID
     *
     * @param int $clientID 客户端ID
     *
     * @return int
     */
    public static function getWsUserID($clientID)
    {
        $key    = 'ws_client_c2u_' . $clientID;
        $userID = self::redis()->get($key);

        return intval($userID) ?: 0;
    }

}
