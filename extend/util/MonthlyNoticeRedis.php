<?php

namespace util;

use app\common\model\OrderPosition;

class MonthlyNoticeRedis extends RedisUtil
{
    /**
     * 获取即将过期的按月收取管理费的持仓信息
     */
    public static function catchPositionAllMonthly($userID)
    {
        try {
            $monthly_expire_date = date('Y-m-d');
            $list = OrderPosition::alias('a')
                ->join(['__STOCK__' => 'b'], 'a.stock_id=b.id')
                ->where('user_id', $userID)
                ->where('a.is_finished', false)
                ->where('a.monthly_expire_date', '<= time', $monthly_expire_date)
                ->order('a.id', 'ASC')
                ->order('a.monthly_expire_date', 'desc')
                ->field('a.id,a.user_id,a.market,a.stock_code,a.stock_id,a.volume_position,a.volume_for_sell,a.stop_loss_price,a.position_price,a.sum_deposit,b.stock_name')
                ->select();

            //续费消息队列名名称
            $queue = 'monthly_notice_list_set';

            // 删除之前的队列
            self::redis()->del($queue);

            if ($list->toArray()) {
                $expire = time() + 60000;
                // 缓存持仓数据
                foreach ($list as $positionID => $item) {
                    $key = 'monthly_notice_content_' . $item['id'];
                    $content = '用户您好：您的持仓编号为【' . $item['id'] . '】名称代码为【' . $item['stock_code'] . '-' . $item['stock_name'] . '】的持仓订单，月管理费即将到期请您及时续费，避免强制平仓';
                    self::redis()->hMSet($key, $content);
                    self::redis()->expireAt($key, $expire);

                    // 加入队列
                    self::redis()->rPush($queue, $positionID);

                    // 持仓按股票代码分组存set
                    $setKey = "monthly_notice_list_set";
                    self::redis()->sAdd($setKey, $item['id']);
                    self::redis()->expireAt($setKey, $expire);
                }

                // 设置队列过期时间
                self::redis()->expireAt($queue, $expire);
            }

        } catch (\Exception $e) {
            // dump($e->getFile());
            // dump($e->getLine());
            // dump($e->getMessage());
            // dump($e->getTraceAsString());
        }
    }

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
        // 获取单个月管理费公告信息
        $notice = OrderPosition::alias('a')
            ->join(['__STOCK__' => 'b'], 'a.stock_id=b.id')
            ->where('id', $noticeID)
            ->field('a.id', 'a.user_id', 'a.market', 'a.stock_code', 'a.stock_id', 'a.volume_position', 'a.volume_for_sell', 'a.stop_loss_price', 'a.position_price', 'a.sum_deposit', 'a.is_monthly', 'a.monthly_expire_date', 'b.stock_name')
            ->find();

        // 数据不存在
        if (!$notice) return false;

        // 缓存数据
        $key = 'monthly_notice_content_' . $noticeID;
        $content = '用户您好：您的持仓编号为【' . $noticeID . '】名称代码为【' . $notice['stock_code'] . '-' . $notice['stock_name'] . '】的持仓订单，月管理费即将到期请您及时续费，避免强制平仓';
        $ret = self::redis()->set($key, $content);

        // 加入公告列表中
        self::redis()->sAdd('monthly_notice_list_set', $noticeID);

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
        $key = 'monthly_notice_content_' . $noticeID;
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
        $key = "monthly_notice_read_" . $noticeID;

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
        $noticeList = self::redis()->sMembers('monthly_notice_list_set');

        // 未读列表
        $unreadList = [];
        foreach ($noticeList as $noticeID) {
            $key = "monthly_notice_read_" . $noticeID;
            if (!self::redis()->sIsMember($key, $userID)) {
                $unreadList[] = [
                    'position_id' => $noticeID,
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
        $noticeListKey = 'monthly_notice_list_set';
        $pushKey = 'monthly_notice_user_push_' . $userID;
        $diffKey = 'monthly_notice_user_diff_' . $userID;

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
            $key = "monthly_notice_read_" . $noticeID;
            if (!self::redis()->sIsMember($key, $userID)) {
                $notPushList[] = [
                    'position_id' => $noticeID,
                    'content' => self::getNoticeData($noticeID),
                ];
            } else {
                $readList[] = $noticeID;
            }
        }

        // 缓存已推送列表（包含已读）
        $pushList = array_column($notPushList, 'position_id');
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
        $key = 'monthly_notice_user_push_' . $userID;
        self::redis()->sAddArray($key, $list);
        // 设置过期时间
        $ttl = 600;
        self::redis()->expire($key, $ttl);
    }


    public function cacheRenewNotice()
    {

    }
}
