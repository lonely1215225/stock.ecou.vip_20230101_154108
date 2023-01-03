<?php
namespace app\index\logic;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use app\common\model\User;

class Commission
{

    /**
     * 生成返佣记录
     * -- 成交、隔夜
     * -- 仅【管理费】对代理商、经纪人、上级推广用户返佣
     * -- 代理商、经纪人的佣金比例，都是对于管理费的比例
     * -- 代理商佣金 = 管理费 * 代理商佣金比例 - 管理费 * 经纪人佣金比例
     *
     * @param $userID
     * @param $money
     * @param $positionID
     * @param $tradedID
     * @param $market
     * @param $stockCode
     * @param $stockValue
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function tradedCommission($userID, $money, $positionID, $tradedID, $market, $stockCode, $stockValue)
    {
        // 查询 用户对应的 代理商 经纪人 上级用户
        $user       = User::where('id', $userID)->field('platform_id,agent_id,broker_id,pid')->find();
        $platformID = $user['platform_id'];
        $agentID    = $user['agent_id'];
        $brokerID   = $user['broker_id'];
        $upUserID   = $user['pid'];

        // 获取分成比例
        $rate = AdminUser::where('id', 'in', [$user['agent_id'], $user['broker_id']])->column('commission_rate,user_rate', 'id');
        // 代理商分成比例
        $agentRate  = isset($rate[$user['agent_id']])  ? bcdiv($rate[$user['agent_id']]['commission_rate'], 100, 4) : 0;
        // 经纪人分成比例
        $brokerRate = isset($rate[$user['broker_id']]) ? bcdiv($rate[$user['broker_id']]['commission_rate'], 100, 4) : 0;
        // 上级用户分成比例
        $userRate   = isset($rate[$user['agent_id']])  ? bcdiv($rate[$user['agent_id']]['user_rate'], 100, 4) : 0;

        // 计算经纪人分成
        $brokerMoney = bcmul($money, $brokerRate, 4);
        // 计算代理商分成（代理商分成需要减掉经纪人的分成）
        $agentMoney = bcmul($money, $agentRate, 4);
        $agentMoney = bcsub($agentMoney, $brokerMoney, 4);

        // 计算上级用户返佣
        $upUserMoney = 0;
        if ($upUserID > 0) {
            $upUserMoney = bcmul($money, $userRate, 4);
            $upUserMoney = round($upUserMoney, 2);
        }

        // 上级用户，不足一分时不返佣
        if ($upUserMoney < 0.01) {
            $upUserID    = 0;
            $upUserMoney = 0;
        }

        // 平台收入 = 总收入 - 代理商收入 - 经纪人收入 - 用户收入
        $platformMoney = bcsub(bcsub($money, $agentMoney, 4), bcadd($brokerMoney, $upUserMoney, 4), 4);

        // 写入管理员收入记录（佣金分成数据）
        $adminIncome = AdminIncome::create([
            'user_id'           => $userID,
            'order_position_id' => $positionID,
            'order_traded_id'   => $tradedID,
            'income_type'       => $tradedID == 0 ? ORG_INCOME_POSITION : ORG_INCOME_BUY,
            'market'            => $market,
            'stock_code'        => $stockCode,
            'stock_value'       => $stockValue,
            'money'             => $money,
            'platform_id'       => $platformID,
            'platform_money'    => $platformMoney,
            'agent_id'          => $agentID,
            'agent_money'       => $agentMoney,
            'broker_id'         => $brokerID,
            'broker_money'      => $brokerMoney,
            'up_user_id'        => $upUserID,
            'up_user_money'     => $upUserMoney,
            'is_return'         => false,
            'income_time'       => date('Y-m-d H:i:s'),
        ]);

        return $adminIncome ? true : false;
    }


    /**
     * 生成月管理费返佣记录
     * -- 成交、隔夜
     * -- 仅【管理费】对代理商、经纪人、上级推广用户返佣
     * -- 代理商、经纪人的佣金比例，都是对于管理费的比例
     * -- 代理商佣金 = 管理费 * 代理商佣金比例 - 管理费 * 经纪人佣金比例
     *
     * @param $userID
     * @param $money
     * @param $positionID
     * @param $tradedID
     * @param $market
     * @param $stockCode
     * @param $stockValue
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function tradedCommissionMonthly($userID, $money, $positionID, $tradedID, $market, $stockCode, $stockValue){
        // 查询 用户对应的 代理商 经纪人 上级用户
        $user       = User::where('id', $userID)->field('platform_id,agent_id,broker_id,pid')->find();
        $platformID = $user['platform_id'];
        $agentID    = $user['agent_id'];
        $brokerID   = $user['broker_id'];
        $upUserID   = $user['pid'];

        // 获取分成比例
        $rate = AdminUser::where('id', 'in', [$user['agent_id'], $user['broker_id']])->column('commission_rate,user_rate', 'id');
        // 代理商分成比例
        $agentRate = bcdiv($rate[$user['agent_id']]['commission_rate'], 100, 4);
        // 经纪人分成比例
        $brokerRate = bcdiv($rate[$user['broker_id']]['commission_rate'], 100, 4);
        // 上级用户分成比例
        $userRate = bcdiv($rate[$user['agent_id']]['user_rate'], 100, 4);

        // 计算经纪人分成
        $brokerMoney = bcmul($money, $brokerRate, 4);
        // 计算代理商分成（代理商分成需要减掉经纪人的分成）
        $agentMoney = bcmul($money, $agentRate, 4);
        $agentMoney = bcsub($agentMoney, $brokerMoney, 4);

        // 计算上级用户返佣
        $upUserMoney = 0;
        if ($upUserID > 0) {
            $upUserMoney = bcmul($money, $userRate, 4);
            $upUserMoney = round($upUserMoney, 2);
        }

        // 上级用户，不足一分时不返佣
        if ($upUserMoney < 0.01) {
            $upUserID    = 0;
            $upUserMoney = 0;
        }

        // 平台收入 = 总收入 - 代理商收入 - 经纪人收入 - 用户收入
        $platformMoney = bcsub(bcsub($money, $agentMoney, 4), bcadd($brokerMoney, $upUserMoney, 4), 4);

        // 写入管理员收入记录（佣金分成数据）
        $adminIncome = AdminIncome::create([
            'user_id'           => $userID,
            'order_position_id' => $positionID,
            'order_traded_id'   => $tradedID,
            'income_type'       => ORG_INCOME_MONTHLY_BUY,
            'market'            => $market,
            'stock_code'        => $stockCode,
            'stock_value'       => $stockValue,
            'money'             => $money,
            'platform_id'       => $platformID,
            'platform_money'    => $platformMoney,
            'agent_id'          => $agentID,
            'agent_money'       => $agentMoney,
            'broker_id'         => $brokerID,
            'broker_money'      => $brokerMoney,
            'up_user_id'        => $upUserID,
            'up_user_money'     => $upUserMoney,
            'is_return'         => false,
            'income_time'       => date('Y-m-d H:i:s'),
        ]);

        return $adminIncome ? true : false;
    }
}
