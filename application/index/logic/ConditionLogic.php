<?php
namespace app\index\logic;

use app\common\model\Condition;
use util\ConditionRedis;

class ConditionLogic
{

    /**
     * 设置条件单为已触发
     *
     * @param $id
     * @param $market
     * @param $stockCode
     */
    public static function setConditionEnd($id, $market, $stockCode)
    {
        // 已触发
        Condition::update([
            'state'        => CONDITION_STATE_END,
            'trigger_time' => date('Y-m-d H:i:s'),
        ], [
            ['id', '=', $id],
        ]);

        // 缓存中删除
        ConditionRedis::delConditionCache($id, $market, $stockCode);
    }

    /**
     * 更新条件单的触发结果
     *
     * @param        $id
     * @param        $orderID
     * @param string $remark
     */
    public static function setConditionExecResult($id, $orderID, $remark = '')
    {
        // 已触发
        Condition::update([
            'order_id' => $orderID,
            'remark'   => $remark,
        ], [
            ['id', '=', $id],
        ]);
    }

}
