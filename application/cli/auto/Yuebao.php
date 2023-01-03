<?php

namespace app\cli\auto;

use app\common\model\UserAccount;
use app\common\model\Yuebao as YuebaoModel;
use app\index\logic\AccountLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use util\ScriptRedis;
use util\SystemRedis;

/**
 * 账户收益宝
 */
class Yuebao extends Command
{

    protected function configure()
    {
        $this->setName('yuebao')->setDescription('账户每日收益管理');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(5000, function () {
            $nowHi = intval(date('Hi'));

            // 计算收益
            if ($nowHi >= 1500 && $nowHi <= 1530 && ScriptRedis::isYuebaoNotRun()) {
                $yuebao = SystemRedis::getYuebao();

                if ($yuebao) {
                    if ($yuebao['is_open'] == 1) $this->createIncomeList();
                }
            }

            // 收益入账
            if ($nowHi > 800 && $nowHi < 900) {
                $this->toAccount();
            }
        });
    }

    /**
     * 主入口
     */
    protected function createIncomeList()
    {
        $yuebao = SystemRedis::getYuebao();
        // 每万元收益
        $baseIncome = $yuebao['yuebao_fee'];

        // 生成收益列表
        $susLogRet = UserAccount::fieldRaw("user_id,now(),wallet_balance,wallet_balance/10000*{$baseIncome},{$baseIncome},false,floor(extract(epoch from(now()))),floor(extract(epoch from(now())))")
            ->selectInsert('user_id,income_time,wallet_balance,income,base_income,is_received,create_time,update_time', YuebaoModel::getTable());

    }

    /**
     * 收益宝收益入账
     * 收益为0的话也进行入账操作
     * @return bool|string
     */
    protected function toAccount()
    {
        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $map[] = ['is_received', '=', false];
        $map[] = ['income_time', '>=', $yesterday];

        $yuebaoList = YuebaoModel::where($map)
            ->field('id,user_id,income_time,wallet_balance,income,base_income,is_received,create_time,update_time')
            ->select();

        if (!$yuebaoList) return false;

        $yuebaoList = $yuebaoList->toArray();
        foreach ($yuebaoList as $value) {
            Db::startTrans();
            try {
                $userId = $value['user_id'];
                $income = $value['income'];
                $walletBalance = $value['wallet_balance'];
                $baseIncome = $value['base_income'];

                YuebaoModel::where('user_id', $userId)->update([
                    'is_received' => true
                ]);
                $yuebaoRows = YuebaoModel::getNumRows();

                if ($income > 0) {
                    UserAccount::where('user_id', $userId)->update([
                        'wallet_balance' => Db::raw("wallet_balance+{$income}"),
                        'total_yuebao'   => Db::raw("total_yuebao+{$income}")
                    ]);

                    $uRows = UserAccount::getNumRows();

                    // 账户资金变动日志
                    $uaRet = AccountLog::walletYuebao($userId, $income, $walletBalance, $baseIncome);

                    if ($uRows && $uaRet && $yuebaoRows) {
                        Db::commit();
                    } else {
                        Db::rollback();
                    }
                } else {

                    if ($yuebaoRows) {
                        Db::commit();
                    } else {
                        Db::rollback();
                    }
                }

            } catch (\Exception $e) {
                $e->getLine();
                $e->getMessage();
                $e->getFile();
                Db::rollback();
            }
        }

    }

}
