<?php
namespace app\cli\sims\logic;

use app\common\model\Order;
use util\RedisUtil;

/**
 * 撤单业务逻辑类
 *
 * @package app\index\logic
 */
class OrderCancel
{

    /**
     * 用户提交撤单主入口
     *
     * @param array $message
     *
     * @return array|string
     * -- 当且仅当返回值为array时，表明成功
     * -- 返回值为string类型时，为错误信息
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function execute($message)
    {
        if($message['Data']['order_id'] && $message['Data']['user_id']){
            $orderID = $message['Data']['order_id'];
            $userID  = $message['Data']['user_id'];
        }else{
            // 参数
            $orderData = self::parseRecMsg($message);
            if (!is_array($orderData)) return $orderData;
            list ($orderID, $token) = $orderData;
    
            // 验证Token
            $userData = $token ? RedisUtil::getToken($token) : [];
            if (!$userData) return '非法操作c';
            $userID = $userData['user_id'];
        }
        return self::create($orderID, $userID);
    }

    /**
     * 撤单操作
     * -- 用户撤单，请走execute入口
     * -- 本方法可用于系统自动发起的撤单
     *
     * @param      $orderID
     * @param      $userID
     *
     * @param bool $isAuto 是否自动撤单，默认false
     *
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function create($orderID, $userID, $isAuto = false)
    {
        /**
         * @var \think\Model $order 委托单对象
         */
        $order = Order::where('id', $orderID)
            ->where('user_id', $userID)
            ->field('id,stock_code,market,primary_account,order_sn,state,cancel_state,cancel_type,is_system,is_monthly,is_cash_coupon')
            ->find();

        // 检测委托单是否存在
        if (!$order) return '没有对应委托单';

        // 检测是 ORDER_WAITING(常量) 状态的，不可撤
        if ($order['state'] == ORDER_WAITING) return '不可撤单';

        // 检测不是 CANCEL_NONE(常量) 状态的，不可撤
        if ($order['cancel_state'] != CANCEL_NONE) return '不可重复提交撤单';

        // 强平不能撤单
        if ($order['is_system']) return '系统强平不能撤单';

        // 委托单状态:已提交撤单
        $order['cancel_state'] = CANCEL_SUBMITTED;
        // 撤单类型:用户撤单
        $order['cancel_type'] = $isAuto ? CANCEL_TYPE_AUTO : CANCEL_TYPE_USER;
        // 保存状态
        $ret = $order->save();

        return $ret ? [$orderID] : '操作错误';
    }

    /**
     * 解析用户提交的数据
     *
     * @param array $message
     *
     * @return array|string
     * -- 当且仅当返回值是array类型时，表示用户数据可用，否者不可用
     * -- 当返回值是string类型时，为错误信息
     */
    private static function parseRecMsg($message)
    {
        if (!isset($message['Data'])) return '参数错误';

        // 取数据
        $orderID = $message['Data']['order_id'] ?? 0;
        $token   = $message['Token'] ?? '';

        // 过滤
        $orderID = filter_var($orderID, FILTER_SANITIZE_NUMBER_INT);

        // 验证
        if ($orderID <= 0) return '参数错误';
        if (empty($token)) return '参数错误';

        return [$orderID, $token];
    }

}
