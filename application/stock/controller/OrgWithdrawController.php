<?php
namespace app\stock\controller;

use app\common\model\OrgAccount;
use app\common\model\OrgAccountLog;
use app\common\model\OrgWithdraw;
use app\payment\controller\PayController;
use think\App;
use think\Db;

class OrgWithdrawController extends BaseController
{

    protected $orgWithdrawModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orgWithdrawModel = new OrgWithdraw();
    }


    /**
     * 获取所有经济人提现申请列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getBrokerWithdraw()
    {
        $map[] = ['au.role', '=', 'broker'];
        // 获取查询提交数据
        $data['pid']        = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['pid']) {
            $map[] = ['au.pid', '=', $data['pid']];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['state']) {
            $map[] = ['ow.state', '=', $data['state']];
        }
        if ($data['start_date']) {
            $map[] = ['ow.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ow.apply_time', '<=', strtotime($data['end_date'])];
        }
        // 获取用户提现申请列表
        $WithdrawInfo = $this->orgWithdrawModel->alias('ow')
            ->field('ow.id,ow.admin_id,ow.money,ow.state,ow.apply_time,ow.operation_time,au.username,ow.success_time,au.role')
            ->join(['__ADMIN_USER__' => 'au'], 'ow.admin_id=au.id')->order('ow.apply_time DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $WithdrawInfo ? $this->message(1, '', $WithdrawInfo) : $this->message(0, '');
    }

    /**
     * 获取代理商、经济人提现申请列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getWithdrawByAdmin()
    {
        $map[] = ['au.pid', '=', $this->adminId];
        // 获取查询提交数据
        $data['id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['start_date']) {
            $map[] = ['ow.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ow.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['state']) {
            $map[] = ['ow.state', '=', $data['state']];
        }
        $WithdrawInfo = $this->orgWithdrawModel->alias('ow')
            ->field('ow.id,ow.admin_id,ow.money,ow.state,ow.apply_time,ow.operation_time,ow.success_time,au.username')
            ->join(['__ADMIN_USER__' => 'au'], 'ow.admin_id=au.id')
            ->where($map)
            ->order('ow.apply_time DESC')
            ->paginate(15, false, ['query' => request()->param()]);

        return $WithdrawInfo ? $this->message(1, '', $WithdrawInfo) : $this->message(0, '');
    }

    /**
     * 处理提现状态
     * 管理员已审核
     *
     * @return bool|\think\response\Json
     */
    public function withdraw_admin_checked()
    {
        if (!$this->request->isPost()) return false;

        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 更改提现状态
        $upInfo = $this->orgWithdrawModel->isUpdate(true)->save([
            'id'             => $id,
            'state'          => 'admin_checked',
            'operation_time' => time(),
        ]);

        return $upInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 处理提现状态
     * 代理商已审核
     *
     * @return bool|\think\response\Json
     */
    public function withdraw_agent_checked()
    {
        if (!$this->request->isPost()) return false;

        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        // 更改提现状态
        $upInfo = $this->orgWithdrawModel->isUpdate(true)->save([
            'id'             => $id,
            'state'          => ORG_WITHDRAW_AGENT_CHECKED,
            'operation_time' => time(),
        ]);

        return $upInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 处理提现状态
     * 拒绝提现
     *
     * @return bool|\think\response\Json
     */
    public function withdraw_failed()
    {
        if (!$this->request->isPost()) return false;

        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        $withdrawState = '';
        if ($this->role == 'super') {
            $withdrawState = ORG_WITHDRAW_ADMIN_REFUSED;
        } elseif ($this->role == 'agent') {
            $withdrawState = ORG_WITHDRAW_AGENT_REFUSED;
        }
        Db::startTrans();
        try {
            $withdrawInfo = $this->orgWithdrawModel->field('money,admin_id')->where('id', $id)->find()->toArray();
            // 追加代理商经济人账户变动明细流水
            $accountInfo   = OrgAccount::where('admin_id', $withdrawInfo['admin_id'])->field('balance')->find();
            $balance       = $accountInfo['balance'];
            $orgAccountLog = OrgAccountLog::create([
                'admin_id'       => $withdrawInfo['admin_id'],
                'change_money'   => $withdrawInfo['money'],
                'change_type'    => ORG_ACCOUNT_WITHDRAW_FAILED,
                'change_time'    => date('Y-m-d H:i:s'),
                'before_balance' => $balance,
                'after_balance'  => $balance + $withdrawInfo['money'],
            ]);
            // 更改提现状态
            $uRet = $this->orgWithdrawModel->isUpdate(true)->save([
                'id'             => $id,
                'state'          => $withdrawState,
                'operation_time' => time(),
                'failed_log_id'  => $orgAccountLog->id,
            ]);
            // 提现失败返回账户申请提现金额
            $orgAccount = OrgAccount::where('admin_id', $withdrawInfo['admin_id'])->field('balance,total_withdraw')->find();
            // 更改代理商经济人账户表信息
            $orgAccount['balance']        = Db::raw("balance+{$withdrawInfo['money']}");
            $orgAccount['total_withdraw'] = Db::raw("total_withdraw-{$withdrawInfo['money']}");
            $sRet                         = $orgAccount->save();

            if ($uRet && $sRet && $orgAccountLog) {
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
     * 获取代理商总申请提现金额
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentTotalWithdraw()
    {
        $map[] = ['au.pid', '=', $this->adminId];
        // 获取查询提交数据
        $data['id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['start_date']) {
            $map[] = ['ow.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ow.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['state']) {
            $map[] = ['ow.state', '=', $data['state']];
        }
        // 获取代理商总提现金额
        $totalWithdraw = $this->orgWithdrawModel->alias('ow')
            ->field('SUM(ow.money) as money')
            ->join(['__ADMIN_USER__' => 'au'], 'ow.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalWithdraw);
    }

    /**
     * 获取经纪人总提现金额
     * 总后台
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerTotalWithdraw()
    {
        $map[] = ['au.role', '=', 'broker'];
        // 获取查询提交数据
        $data['pid']        = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['pid']) {
            $map[] = ['au.pid', '=', $data['pid']];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['state']) {
            $map[] = ['ow.state', '=', $data['state']];
        }
        if ($data['start_date']) {
            $map[] = ['ow.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ow.apply_time', '<=', strtotime($data['end_date'])];
        }
        // 获取经济人总提现金额
        $totalWithdraw = $this->orgWithdrawModel->alias('ow')
            ->field('SUM(ow.money) as money')
            ->join(['__ADMIN_USER__' => 'au'], 'ow.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalWithdraw);
    }

    /**
     * 获取经纪人总提现金额
     * 代理商后台
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AgentBrokerTotalWithdraw()
    {
        $map[] = ['au.pid', '=', $this->adminId];
        // 获取查询提交数据
        $data['id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['start_date']) {
            $map[] = ['ow.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ow.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['state']) {
            $map[] = ['ow.state', '=', $data['state']];
        }
        // 获取经济人总提现金额
        $totalWithdraw = $this->orgWithdrawModel->alias('ow')
            ->field('SUM(ow.money) as money')
            ->join(['__ADMIN_USER__' => 'au'], 'ow.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalWithdraw);
    }

    /**
     * 判断该申请记录是否已经操作过
     * 总后台代理商提现申请
     * 代理商后台经济人提现申请
     *
     * @param $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkIsWithdraw($id)
    {
        $WithdrawInfo = $this->orgWithdrawModel->where('id', $id)->field('operation_time')->find()->toArray();

        return $WithdrawInfo['operation_time'] ? $this->message(1, '该申请已经被处理，不能重复操作', $WithdrawInfo) : $this->message(0, '');
    }

    /**
     * 判断该申请记录是否已经操作过
     * 总后台经济人提现申请
     *
     * @param $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isWithdrawByAdmin($id)
    {
        $WithdrawInfo = $this->orgWithdrawModel->where('id', $id)->where('state', 'agent_checked')->field('state')->count();

        return $WithdrawInfo ? $this->message(0, '') : $this->message(1, '该申请已经被处理，不能重复操作', $WithdrawInfo);
    }

    /**
     * 代理商、经济人提现管理员手动支付
     *
     * @return bool|\think\response\Json
     */
    public function manualPay()
    {
        if (!$this->request->isPost()) return false;

        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        // 更改提现状态
        $upInfo = $this->orgWithdrawModel->isUpdate(true)->save([
            'id'             => $id,
            'state'          => ORG_WITHDRAW_FINISHED,
            'operation_time' => time(),
            'success_time'   => time(),
        ]);

        return $upInfo ? $this->message(1, '手动支付成功') : $this->message(0, '手动支付失败');
    }

    /**
     * 总后台代理商商提现申请代付
     *
     * @return \think\response\Json
     */
    public function doPay()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        // 调用代付接口
        $daifuModel = new PayController();
        $res        = $daifuModel->agentBackmoney($id)->getData();
        if ($res['code'] == 1) {
            // 更改提现状态
            $upInfo = $this->orgWithdrawModel->isUpdate(true)->save([
                'id'             => $id,
                'state'          => ORG_WITHDRAW_PAYING,
                'operation_time' => time(),
            ]);

            return $upInfo ? $this->message(1, '代付成功') : $this->message(0, '代付失败');
        }

        return $this->message($res['code'], $res['msg']);
    }

}
