<?php
namespace app\stock\controller;

use app\common\model\OrgAccount;
use app\common\model\OrgAccountLog;
use app\common\model\OrgWithdraw;
use think\App;
use think\Db;

class OrgAccountController extends BaseController
{

    protected $orgAccountModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orgAccountModel = new OrgAccount();
    }

    /**
     * 获取登录用户账户表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function orgAccount()
    {
        $orgAccountInfo = $this->orgAccountModel->field('id,balance,total_commission,total_withdraw,update_time')
            ->where('admin_id', $this->adminId)
            ->find();
        $orgAccountInfo = $orgAccountInfo ?: [];

        return $orgAccountInfo ? $this->message(1, '', $orgAccountInfo) : $this->message(0, '');
    }

    /**
     * 提现申请处理
     *
     * @return null|\think\response\Json
     */
    public function withdraw()
    {
        if (!$this->request->isPost()) return null;
        // 获取提交参数
        $data['total_withdraw'] = input('post.total_withdraw', 0, ['filter_float', 'abs']);
        $data['id']             = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 验证提交数据
        $result = $this->validate($data, 'OrgAccount.withdraw');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        // 获取用户账户总余额
        $accountInfo = $this->orgAccountModel->field('balance')->where('admin_id', $this->adminId)->find();
        $balance     = $accountInfo['balance'];
        if ($data['total_withdraw'] > $balance) {
            return $this->message(0, '提现金额不能大于账户余额');
        }
        Db::startTrans();
        try {
            $upInfo = $this->orgAccountModel->isUpdate(true)->save(
                [
                    'id'             => $data['id'],
                    'balance'        => Db::raw('balance-' . $data['total_withdraw']),
                    'total_withdraw' => Db::raw('total_withdraw+' . $data['total_withdraw']),
                ]
            );
            // 追加代理商经济人账户变动明细流水
            $orgAccountLog = OrgAccountLog::create([
                'admin_id'       => $this->adminId,
                'change_money'   => -$data['total_withdraw'],
                'change_type'    => ORG_ACCOUNT_WITHDRAW,
                'change_time'    => date('Y-m-d H:i:s'),
                'before_balance' => $balance,
                'after_balance'  => $balance - $data['total_withdraw'],
            ]);
            // 在提现申请表里追加记录
            $orgWithdrawModel = new OrgWithdraw();
            $orgWithdraw      = $orgWithdrawModel->save(
                [
                    'admin_id'     => $this->adminId,
                    'money'        => $data['total_withdraw'],
                    'apply_time'   => time(),
                    'state'        => ORG_WITHDRAW_WAITING,
                    'apply_log_id' => $orgAccountLog->id,
                ]
            );
            if ($upInfo && $orgWithdraw && $orgAccountLog) {
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
     * 返回代理商经济人账户表
     *
     * @return \think\response\Json
     */
    public function getOrgAccountAll()
    {
        $list = $this->orgAccountModel->column('balance,total_commission,total_withdraw', 'admin_id');
        $list = $list ?: [];

        return $list ? $this->message(1, '', $list) : $this->message(0, '');
    }

}
