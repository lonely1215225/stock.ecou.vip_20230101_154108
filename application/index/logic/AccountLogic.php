<?php
namespace app\index\logic;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;

class AccountLogic
{

    /**
     * 计算并更新用户的总持仓占用
     * 调用场景
     * -- 成交（买入、卖出）
     * -- 实时追加保证金
     * -- 每日结算
     * -- 停牌追加保证金
     *
     * @param int $userID 用户ID
     *
     * @return bool
     */
    public static function updateTotalDeposit($userID)
    {
        // 为了防止该操作出错时，影响到主业务逻辑，故此处用try catch
        try {
            $sumDeposit = OrderPosition::where('user_id', $userID)->where('is_finished', false)->sum('sum_deposit');
            $sumDeposit = $sumDeposit ?: 0;
            UserAccount::update(['deposit' => $sumDeposit], [['user_id', '=', $userID]]);
            $uaRows = UserAccount::getNumRows();

            $ret = $uaRows > 0;
        } catch (\Exception $e) {
            $ret = false;
        }

        return $ret;
    }

}
