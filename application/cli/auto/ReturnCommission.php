<?php
namespace app\cli\auto;

use app\common\model\AdminIncome;
use app\common\model\OrgAccount;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use think\console\Command;
use think\console\Output;
use think\console\Input;
use think\Db;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 执行返佣操作
 * -- 交易日 08:00 ~ 11:40 和 13:00 ~ 15:10
 *
 * @package app\cli\auto
 */
class ReturnCommission extends Command
{

    protected function configure()
    {
        $this->setName('return_commission')->setDescription('定时执行返佣金操作');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(10000, function () {
            $tradingDate = TradingUtil::currentTradingDate();
            $nowHours    = intval(date('Hi'));
            if (TradingRedis::isTradingDate($tradingDate)) {
                if (($nowHours >= 800 && $nowHours <= 1140) || ($nowHours >= 1300 && $nowHours <= 1510)) {
                    $this->main();
                }
            }
        });
    }

    /**
     * 返佣主入口
     */
    private function main()
    {
        try {
            // 每次取20条返佣记录
            $list = AdminIncome::where('is_return', false)
                ->where('income_type', ['=', ORG_INCOME_BUY], ['=', ORG_INCOME_POSITION], 'or')
                ->order('id', 'ASC')
                ->limit(20)
                ->column('id,agent_id,agent_money,broker_id,broker_money,up_user_id,up_user_money', 'id');

            if (count($list)) {
                // 循环返佣
                foreach ($list as $adminIncomeData) {
                    $this->returnCommission($adminIncomeData);
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 返佣
     * -- 返佣不用写入账户变动日志
     *
     * @param array $adminIncome
     *
     * @return bool
     */
    public function returnCommission($adminIncome)
    {
        Db::startTrans();
        try {
            // 设置为已返佣
            AdminIncome::update([
                'is_return' => true,
            ], [
                ['id', '=', $adminIncome['id']],
                ['is_return', '=', false],
            ]);
            $aiRows = AdminIncome::getNumRows();

            // 返佣数据
            $agentID     = $adminIncome['agent_id'];
            $brokerID    = $adminIncome['broker_id'];
            $upUserID    = $adminIncome['up_user_id'];
            $agentMoney  = $adminIncome['agent_money'];
            $brokerMoney = $adminIncome['broker_money'];
            $upUserMoney = $adminIncome['up_user_money'];

            // 执行返佣(代理商)
            $aRows = 1;
            if ($agentMoney > 0) {
                OrgAccount::update([
                    'balance'          => Db::raw("balance+{$agentMoney}"),
                    'total_commission' => Db::raw("total_commission+{$agentMoney}"),
                ], [
                    ['admin_id', '=', $agentID],
                ]);
                $aRows = OrgAccount::getNumRows();
            }

            // 执行返佣(经纪人)
            $bRows = 1;
            if ($brokerMoney > 0) {
                OrgAccount::update([
                    'balance'          => Db::raw("balance+{$brokerMoney}"),
                    'total_commission' => Db::raw("total_commission+{$brokerMoney}"),
                ], [
                    ['admin_id', '=', $brokerID],
                ]);
                $bRows = OrgAccount::getNumRows();
            }

            // 执行返佣(上级用户)
            $uRows = 1;
            $uaRet = true;
            if ($upUserMoney > 0) {
                // 账户资金返佣入账
                UserAccount::update([
                    'wallet_balance'   => Db::raw("wallet_balance+{$upUserMoney}"),
                    'total_commission' => Db::raw("total_commission+{$upUserMoney}"),
                ], [
                    ['user_id', '=', $upUserID],
                ]);
                $uRows = UserAccount::getNumRows();

                // 查询账户资金变动前余额
                $uAccount = UserAccount::where('user_id', $upUserID)->field('wallet_balance')->find();

                // 账户资金变动日志
                $uaRet = AccountLog::walletCommission($upUserID, $upUserMoney, $uAccount['wallet_balance']);
            }

            if ($aiRows && $aRows && $bRows && $uRows && $uaRet) {
                Db::commit();
                $ret = true;
            } else {
                Db::rollback();
                $ret = false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $ret = false;
        }

        return $ret;
    }

}
