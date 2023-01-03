<?php
namespace app\stock\controller;
//include_once(\think\facade\Env::get("root_path") . 'extend/instantllpay/lib/llpay_security.function.php');

use app\common\model\AdminUser;
use app\common\model\UserAccount;
use app\common\model\UserWalletLog;
use app\common\model\UserWithdraw;
use app\payment\controller\PayController;
use think\App;
use think\Db;
use app\common\model\User;
use app\common\model\OrderPosition;
use util\ExportExcel;
use util\SysWsRedis;

class UserWithdrawController extends BaseController
{

    protected $userWithdrawModel;
    protected $userModel;
    protected $adminUserModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserModel    = new AdminUser();
        $this->userWithdrawModel = new UserWithdraw();
        $this->userAccountModel  = new UserAccount();
    }

    /**
     * 用户提现列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map[] = ['is_delete', '=', false];
        // 获取查询提交数据
        $data['state']       = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }

        // 根据传递的参数生产where条件
        if ($data['state']) {
            $map[] = ['uw.state', '=', $data['state']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['start_date']) {
            $map[] = ['uw.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['uw.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        $withdrawInfo = $this->userWithdrawModel->alias('uw')
            ->field('uw.id,uw.user_id,uw.money,uw.state,uw.apply_time,uw.operation_time,uw.success_time,uw.service_fee,uw.apply_money,u.mobile,u.broker_id,u.agent_id,u.real_name,ubc.real_name as bank_user_name,bank_name,bank_number')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->join(['__USER_BANK_CARD__' => 'ubc'], 'uw.user_id=ubc.user_id')
            ->where($map)
            ->order('uw.id DESC')
            ->paginate();

        // 代理商经济人信息
        $adminInfo = AdminUser::column('id,username', 'id');

        return $withdrawInfo ? $this->message(1, '', ['withdrawInfo' => $withdrawInfo, 'adminInfo' => $adminInfo]) : $this->message(0, '');
    }

    /**
     * 处理提现状态
     * 管理员已审核
     *
     * @return bool|\think\response\Json
     */
    public function withdraw_checked()
    {
        if (!$this->request->isPost()) return false;

        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 更改提现状态
        $upInfo = $this->userWithdrawModel->isUpdate(true)->save([
            'id'             => $id,
            'state'          => USER_WITHDRAW_CHECKED,
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
        Db::startTrans();
        try {
            $withdrawInfo = $this->userWithdrawModel->field('money,apply_money,user_id')->where('id', $id)->find();
            // 追加用户账户变动明细流水
            $userWalletLog = UserWalletLog::create([
                'user_id'      => $withdrawInfo['user_id'],
                'change_money' => $withdrawInfo['apply_money'],
                'change_type'  => USER_WALLET_WITHDRAW_FAILED,
                'change_time'  => date('Y-m-d H:i:s'),
            ]);
            // 更改提现状态
            $uRet = $this->userWithdrawModel->isUpdate(true)->save([
                'id'             => $id,
                'state'          => USER_WITHDRAW_ADMIN_REFUSED,
                'operation_time' => time(),
                'failed_log_id'  => $userWalletLog->id,
            ]);
            // 提现失败返回账户申请提现金额
            $userAccount = UserAccount::where('user_id', $withdrawInfo['user_id'])->field('wallet_balance,total_withdraw')->find();
            // 更改用户账户表信息
            $userAccount['wallet_balance'] = Db::raw("wallet_balance+{$withdrawInfo['money']}");
            $userAccount['total_withdraw'] = Db::raw("total_withdraw-{$withdrawInfo['money']}");
            $sRet                          = $userAccount->save();

            if ($uRet && $sRet && $userWalletLog) {
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
     * 获取用户总提现金额数据
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userTotalWithdraw()
    {
        $map[] = ['is_delete', '=', false];
        // 获取查询提交数据
        $data['state']       = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
        if ($data['state']) {
            $map[] = ['uw.state', '=', $data['state']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['start_date']) {
            $map[] = ['uw.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['uw.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        // 获取用户总申请提现金额
        $totalWithdrawMoney = $this->userWithdrawModel->alias('uw')
            ->field('SUM(uw.apply_money) as money')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalWithdrawMoney);
    }

    /**
     * 获取用户总成功提现金额数据
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userSuccessWithdraw()
    {
        $map[] = ['is_delete', '=', false];
        // 获取查询提交数据
        $data['state']         = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']        = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']      = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']    = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']      = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['exclude_agent'] = input('exclude_agent', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['state']) {
            $map[] = ['uw.state', '=', $data['state']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['start_date']) {
            $map[] = ['uw.apply_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['uw.apply_time', '<=', strtotime($data['end_date'])];
        }
        if ($data['exclude_agent']) {
            $map[] = ['u.agent_id', 'not in', $data['exclude_agent']];
        }
        // 获取用户总申请提现金额
        $successWithdrawMoney = $this->userWithdrawModel->alias('uw')
            ->field('SUM(uw.money) as money,SUM(service_fee) as servicefee')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->where('uw.state', USER_WITHDRAW_SUCCESS)
            ->find();

        return $this->message(1, '', $successWithdrawMoney);
    }

    /**
     * 用户提现管理员代付
     *
     * @return \think\response\Json
     */
    public function doPay()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        // 调用代付接口
        $daifuModel = new PayController();
        $res        = $daifuModel->userBackmoney($id)->getData();
        if ($res['code'] == 1) {
            // 更改提现状态
            $upInfo = $this->userWithdrawModel->isUpdate(true)->save([
                'id'             => $id,
                'state'          => USER_WITHDRAW_PAYING,
                'operation_time' => time(),
            ]);

            return $upInfo ? $this->message(1, '代付成功') : $this->message(0, '代付失败');
        }

        return $this->message($res['code'], $res['msg']);
    }

    /**
     * 用户提现申请管理员手动支付
     *
     * @return \think\response\Json
     */
    public function manualPay()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        // 更改提现状态
        $upInfo = $this->userWithdrawModel->isUpdate(true)->save([
            'id'             => $id,
            'state'          => USER_WITHDRAW_SUCCESS,
            'operation_time' => time(),
            'success_time'   => time(),
        ]);

        return $upInfo ? $this->message(1, '手动支付成功') : $this->message(0, '手动支付失败');
    }

    /**
     * 删除提现记录
     * -- 软删除
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function delete()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        $withdraw = UserWithdraw::where('id='.$id)->value('state');
        if ($withdraw == ORG_WITHDRAW_WAITING) return $this->message(0, '该提现信息未处理不能删除！');
        
        // 更新状态
        $upRet = UserWithdraw::update([
            'is_delete' => true,
        ], [
            ['id', '=', $id],
        ]);

        return $upRet ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 移除提醒标识
     *
     * @return \think\response\Json
     */
    public function removeFlag()
    {
        $remind = input('remind', '', [FILTER_SANITIZE_STRING, 'trim']);
        SysWsRedis::removeFlag($remind);

        return $this->message(1, '');

    }

    public function exportJournalLog()
    {
        $this->userModel = new User();
        $map             = [];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['broker_id', '=', $data['broker_id']];
        }
        // 获取用户信息列表
        $userList  = $this->userModel->where($map)
            ->field('id,mobile,is_deny_login,is_deny_cash,create_time,reg_ip,is_bound_bank_card,agent_id,broker_id,real_name,is_deny_buy,is_deny_sell')
            ->order('id DESC')
            ->select();
        $exportArr = array();
        if ($userList->toArray()) {
            // 提取id列
            $user_id_arr = array_column($userList->toArray(), 'id');

            // 提取代理商id
            $user_agent_arr = array_column($userList->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');

            // 提取代理商id
            $user_broker_arr = array_column($userList->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');

            // 获取用户账户信息
            $userAccountList = UserAccount::where('user_id', 'in', $user_id_arr)
                ->column('wallet_balance,strategy_balance,total_recharge,total_withdraw,frozen', 'user_id');

            // 获取用户已提现金额
            $totalWithdraw = UserWithdraw::where('state', USER_WITHDRAW_SUCCESS)
                ->group('user_id')
                ->column('SUM(money)', 'user_id');

            // 获取用户平仓盈亏结算
            $spalList = OrderPosition::where('is_finished', true)->group('user_id')->column('SUM(s_pal) as totalpal', 'user_id');

            foreach ($userList as $key => $item) {
                $exportArr[$key]['手机号']    = $userList[$key]['mobile'];
                $exportArr[$key]['姓名']     = $userList[$key]['real_name'];
                $exportArr[$key]['账户资金']   = isset($userAccountList[$item['id']]['wallet_balance']) ? $userAccountList[$item['id']]['wallet_balance'] : '';
                $exportArr[$key]['策略金余额']  = isset($userAccountList[$item['id']]['strategy_balance']) ? $userAccountList[$item['id']]['strategy_balance'] : '';
                $exportArr[$key]['冻结资金']   = isset($userAccountList[$item['id']]['frozen']) ? $userAccountList[$item['id']]['frozen'] : '';
                $exportArr[$key]['累计充值']   = isset($userAccountList[$item['id']]['total_recharge']) ? $userAccountList[$item['id']]['total_recharge'] : '';
                $exportArr[$key]['累计提现']   = isset($totalWithdraw[$item['id']]) ? $totalWithdraw[$item['id']] : '';
                $exportArr[$key]['平仓结算盈亏'] = isset($spalList[$item['id']]) ? $spalList[$item['id']] : '';
                $exportArr[$key]['代理商']    = isset($agentInfo[$item['agent_id']]) ? $agentInfo[$item['agent_id']] : '';
                $exportArr[$key]['经济人']    = isset($brokerInfo[$item['broker_id']]) ? $brokerInfo[$item['broker_id']] : '';

            }
        }
        if ($exportArr) {
            $style    = array(
                'A' => 20, 'B' => 10, 'C' => 10, 'D' => 12, 'E' => 10, 'F' => 10, 'G' => 10, 'H' => 15, 'I' => 10, 'J' => 10,
            );
            $fileName = '账户资金流水-' . date('Y-m-d') . '.xlsx';
            ExportExcel::excel($exportArr, $fileName, $style);
        }
    }

}
