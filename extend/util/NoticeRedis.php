<?php

namespace util;

use app\common\model\Notice;

class NoticeRedis extends RedisUtil
{

    /**
     * 缓存指定公告
     *
     * @param int $noticeID 公告ID
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cacheNoticeData($noticeID)
    {
        // 获取单个公告内容
        $notice = Notice::where('id', $noticeID)->where('state', 1)->field('id,content')->find();

        // 数据不存在
        if (!$notice) return false;

        // 缓存数据
        $key = 'notice_content_' . $noticeID;
        $content = $notice['content'];
        $ret = self::redis()->set($key, $content);

        // 加入公告列表中
        self::redis()->sAdd('notice_list_set', $noticeID);

        // 设置午夜失效
        self::redis()->expireAt($key, self::midnight());
        self::redis()->expireAt('notice_list_set', self::midnight());

        return $ret ? true : false;
    }

    /**
     * 获取指定公告
     *
     * @param int $noticeID 公告ID
     *
     * @return bool|string
     */
    public static function getNoticeData($noticeID)
    {
        $key = 'notice_content_' . $noticeID;
        $data = self::redis()->get($key);

        return $data;
    }

    /**
     * 设置公告已读状态
     *
     * @param $userID
     * @param $noticeID
     */
    public static function setNoticeRead($userID, $noticeID)
    {
        $key = "notice_read_" . $noticeID;

        self::redis()->sAdd($key, $userID);
        self::redis()->expireAt($key, self::midnight());
    }

    /**
     * 获取用户的未读列表
     *
     * @param $userID
     *
     * @return array
     */
    public static function getUnreadList($userID)
    {
        // 获取所有公告列表
        $noticeList = self::redis()->sMembers('notice_list_set');

        // 未读列表
        $unreadList = [];
        foreach ($noticeList as $noticeID) {
            $key = "notice_read_" . $noticeID;
            if (!self::redis()->sIsMember($key, $userID)) {
                $unreadList[] = [
                    'notice_id' => $noticeID,
                    'content' => self::getNoticeData($noticeID),
                ];
            }
        }

        return $unreadList;
    }

    /**
     * 获取用户的未推送列表
     * -- 未读 AND 未推送
     *
     * @param int $userID
     *
     * @return array
     */
    public static function getNotPushList($userID)
    {
        $noticeListKey = 'notice_list_set';
        $pushKey = 'notice_user_push_' . $userID;
        $diffKey = 'notice_user_diff_' . $userID;

        // 获取公告列表与已推送列表的差集
        $ttl = mt_rand(60, 70);
        self::redis()->sDiffStore($diffKey, $noticeListKey, $pushKey);
        self::redis()->expire($diffKey, $ttl);
        $diffList = self::redis()->sMembers($diffKey);

        // 未读列表
        $notPushList = [];
        // 已读列表
        $readList = [];
        foreach ($diffList as $noticeID) {
            $key = "notice_read_" . $noticeID;
            if (!self::redis()->sIsMember($key, $userID)) {
                $notPushList[] = [
                    'notice_id' => $noticeID,
                    'content' => self::getNoticeData($noticeID),
                ];
            } else {
                $readList[] = $noticeID;
            }
        }

        // 缓存已推送列表（包含已读）
        $pushList = array_column($notPushList, 'notice_id');
        $pushList = array_merge($pushList, $readList);
        self::cacheUserPush($userID, $pushList);

        return $notPushList;
    }

    /**
     * 缓存用户已经推送过的公告列表
     *
     * @param $userID
     * @param $list
     */
    public static function cacheUserPush($userID, $list)
    {
        $key = 'notice_user_push_' . $userID;
        self::redis()->sAddArray($key, $list);
        // 设置过期时间
        $ttl = mt_rand(60, 70);
        self::redis()->expire($key, $ttl);
    }


    public function cacheRenewNotice()
    {

    }
}
