<?php
namespace app\index\logic;

use app\common\model\Order;
use util\TradingUtil;

class OrderLogic
{

    /**
     * 设置委托单的 is_finished 状态
     *
     * @return bool
     */
    public static function execFinished()
    {
        // 为了防止该操作出错时，影响到主业务逻辑，故此处用try catch
        try {
            $ret = Order::where([
                ['is_finished', '=', false],
                ['trading_date', '=', TradingUtil::currentTradingDate()],
            ])->where(function (\think\db\Query $query) {
                $query->where('state', ORDER_ALL_TRADED)->whereOr('cancel_state', CANCEL_SUCCESS);
            })->update([
                'is_finished' => true,
                'update_time' => time(),
            ]);

            return $ret ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

}
