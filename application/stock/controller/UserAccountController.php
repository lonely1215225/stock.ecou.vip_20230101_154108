<?php
namespace app\stock\controller;

use app\common\model\UserAccount;
use app\common\model\UserWalletLog;
use app\common\model\UserStrategyLog;
use think\Db;

class UserAccountController extends BaseController
{

    /**
     * 管理员调整钱包余额
     *
     * @return null|\think\response\Json
     */
    public function changeWallet()
    {
        if (!$this->request->isPost()) return null;
        // 获取提交参数
        $data['ctype']        = input('post.ctype', '', FILTER_SANITIZE_NUMBER_INT);
        $data['change_money'] = input('post.change_money', 0, ['filter_float', 'abs']);
        $data['change_money'] = $data['ctype'] == 2 ? -$data['change_money'] : $data['change_money'];
        $data['remark']       = input('post.remark', '');
        $data['user_id']      = input('post.user_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 提交数据校验
        $result = $this->validate($data, 'UserAccount.saveStrategyBalance');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        Db::startTrans();
        try {
            // 获取用户账户信息
            $userAccount = UserAccount::where('user_id', $data['user_id'])->field('wallet_balance')->find();
            // 变动前金额
            $beforeBalance = $userAccount['wallet_balance'];
            // 保存用户账目表余额信息
            $userAccount['wallet_balance'] = Db::raw("wallet_balance+{$data['change_money']}");
            $uRet                          = $userAccount->save();

            // 增加用户钱包明细流水
            $sRet = UserWalletLog::create([
                'user_id'        => $data['user_id'],
                'change_time'    => date('Y-m-d H:i:s'),
                'change_money'   => $data['change_money'],
                'change_type'    => $data['ctype'] == 2 ? USER_WALLET_SYSTEM_OUT : USER_WALLET_SYSTEM_IN,
                'remark'         => $data['remark'],
                'before_balance' => $beforeBalance,
                'after_balance'  => bcadd($beforeBalance, $data['change_money'], 2),
            ]);
            if ($uRet && $sRet) {
                // 提交事务
                Db::commit();

                // 返回成功信息
                return $this->message(1, '操作成功');
            } else {
                // 提交事务
                Db::rollback();

                // 返回成功信息
                return $this->message(0, '操作失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            // 返回失败信息
            return $this->message(0, '操作失败2');
        }
    }

    /**
     * 管理员调整策略金余额
     *
     * @return null|\think\response\Json
     */
    public function changeStrategy()
    {
        if (!$this->request->isPost()) return null;
        // 获取提交参数
        $data['stype']        = input('post.ctype', '', FILTER_SANITIZE_NUMBER_INT);
        $data['change_money'] = input('post.change_money', 0, ['filter_float', 'abs']);
        $data['change_money'] = $data['stype'] == 4 ? -$data['change_money'] : $data['change_money'];
        $data['remark']       = input('post.remark', '');
        $data['user_id']      = input('post.user_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 提交数据校验
        $result = $this->validate($data, 'UserAccount.saveStrategyBalance');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        Db::startTrans();
        try {
            // 获取用户账户信息
            $userAccount = UserAccount::where('user_id', $data['user_id'])->field('strategy_balance')->find();
            // 变动前金额
            $beforeStrategyBalance = $userAccount['strategy_balance'];
            // 保存用户账目表余额信息
            $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$data['change_money']}");

            $uRet = $userAccount->save();

            // 增加用户钱包明细流水
            $sRet = UserStrategyLog::create([
                'user_id'        => $data['user_id'],
                'change_time'    => date('Y-m-d H:i:s'),
                'change_money'   => $data['change_money'],
                'change_type'    => $data['stype'] == 3 ? USER_STRATEGY_REFUND : USER_STRATEGY_SUBTRACT,
                'remark'         => $data['remark'],
                'before_balance' => $beforeStrategyBalance,
                'after_balance'  => bcadd($beforeStrategyBalance, $data['change_money'], 2),
            ]);

            if ($uRet && $sRet) {
                // 提交事务
                Db::commit();

                // 返回成功信息
                return $this->message(1, '操作成功');
            } else {
                // 提交事务
                Db::rollback();

                // 返回成功信息
                return $this->message(0, '操作失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            // 返回失败信息
            return $this->message(0, '操作失败2');
        }
    }

}
