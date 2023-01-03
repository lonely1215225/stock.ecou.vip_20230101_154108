<?php
namespace app\stock\controller;

use app\common\model\AdminUser;
use app\common\model\UserAccount;
use app\common\model\UserRecharge;
use app\common\model\UserWalletLog;
use think\App;
use util\BasicData;
use think\Db;

class UserRechargeController extends BaseController
{

    protected $userRechargeModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->userRechargeModel = new UserRecharge();
    }

    /**
     * 获取用户充值记录
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function userRechargeList()
    {
        $map[] = ['is_delete', '=', false];
        // 获取查询提交数据
        $data['pay_state']   = input('pay_state', '', [FILTER_SANITIZE_STRING, 'trim']);
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
        if ($data['pay_state']) {
            $map[] = ['ur.pay_state', '=', $data['pay_state']];
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
            $map[] = ['ur.create_time', '>', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ur.create_time', '<', strtotime($data['end_date'])];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        $list = UserRecharge::alias('ur')
            ->field('ur.id,ur.user_id,ur.money,ur.real_money,ur.pay_state,ur.pay_time,ur.third_order_sn,ur.pay_company_id,ur.create_time,ur.offline_name,u.mobile,u.agent_id,u.broker_id,u.real_name')
            ->join(['__USER__' => 'u'], 'u.id=ur.user_id')
            ->where($map)
            ->order('ur.id desc')
            ->paginate(15, false, ['query' => $this->request->param()]);
        // 代理商经济人信息
        $adminInfo = AdminUser::column('id,username', 'id');
        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['agent_name']  = $adminInfo[$item['agent_id']] ?? '';
                $list[$key]['broker_name'] = $adminInfo[$item['broker_id']] ?? '';
            }
        }

        return $list ? $this->message(1, '', $list) : $this->message(0, '');
    }

    /**
     * 获取用户总充值金额数据
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userTotalRecharge()
    {
        $map[] = ['is_delete', '=', false];
        // 获取查询提交数据
        $data['pay_state']   = input('pay_state', '', [FILTER_SANITIZE_STRING, 'trim']);
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
        if ($data['pay_state']) {
            $map[] = ['ur.pay_state', '=', $data['pay_state']];
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
            $map[] = ['ur.create_time', '>', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['ur.create_time', '<', strtotime($data['end_date'])];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        // 获取用户总充值金额数据
        $userTotalRecharge = UserRecharge::alias('ur')
            ->field('sum(ur.real_money) as money')
            ->join(['__USER__' => 'u'], 'u.id=ur.user_id')
            ->where($map)
            ->where(function (\think\Db\Query $query) {
                $query->where('ur.pay_state', RECHARGE_PAY_SUCCESS)->whereor('ur.pay_state', RECHARGE_PAY_OFFLINE);
            })
            ->find();

        return $userTotalRecharge ? $this->message(1, '', $userTotalRecharge) : $this->message(0, '');
    }

    /**
     * 获取支付状态常量
     *
     * @return \think\response\Json
     */
    public function rechargePayStateList()
    {
        return $this->message(1, '', BasicData::RECHARGE_PAY_STATE_LIST);
    }

    /**
     * 获取单条充值记录
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 获取单个充值记录内容
        $userRecharge = UserRecharge::where('id', $id)->field('money,third_order_sn,pay_company_id,real_money,offline_to_account,offline_img,offline_name')->find();

        return $userRecharge ? $this->message(1, '', $userRecharge) : $this->message(0, '没有找到信息');
    }

    /**
     * 充值记录手动入账
     *
     * @return \think\response\Json
     */
    public function manualByAdmin()
    {
        $data['id']    = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['money'] = input('post.money', '', ['filter_float', 'abs']);
        // 提交数据校验
        $result = $this->validate($data, 'UserRecharge.manualByAdmin');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        $data['third_order_sn'] = input('third_order_sn', '', [FILTER_SANITIZE_STRING, 'trim']);
        $user_id                = input('user_id', '', [FILTER_SANITIZE_STRING, 'trim']);

        Db::startTrans();
        try {
            // 手动入账
            UserRecharge::update(['money' => $data['money'], 'third_order_sn' => $data['third_order_sn'], 'pay_state' => RECHARGE_PAY_MANUAL], [['id', '=', $data['id']]]);
            $uRes = UserRecharge::getNumRows();
            // 更改账户余额
            $userAccount                   = UserAccount::where('user_id', $user_id)->field('wallet_balance,total_recharge')->find();
            $userAccount['wallet_balance'] = Db::raw("wallet_balance+{$data['money']}");
            $userAccount['total_recharge'] = Db::raw("total_recharge+{$data['money']}");
            $uRet                          = $userAccount->save();
            // 增加用户钱包流水明细
            $walletLogRet = UserWalletLog::create([
                'user_id'      => $user_id,
                'change_money' => $data['money'],
                'change_type'  => USER_WALLET_RECHARGE,
                'change_time'  => date('Y-m-d H:i:s'),
                'recharge_id'  => $data['id'],
            ]);
            if ($uRes && $uRet && $walletLogRet) {
                // 提交事务
                Db::commit();
                $code = 1;
                $msg  = '操作成功';
            } else {
                // 提交事务
                Db::rollback();
                $code = 0;
                $msg  = '操作失败';
            }
        } catch (\Exception $e) {
            Db::rollback();
            $code = 0;
            $msg  = '操作失败2';
        }

        return $this->message($code, $msg);
    }

    /**
     * 删除充值记录
     * -- 软删除
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function delete()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 更新状态
        $upRet = UserRecharge::update([
            'is_delete' => true,
        ], [
            ['id', '=', $id],
        ]);

        return $upRet ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 线下转账
     *
     * @return \think\response\Json
     */
    public function offlineTransfer()
    {
        $realMoney = input('post.money', 0, 'filter_float');
        $user_id   = input('user_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $id        = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($realMoney <= 0) $this->message(0, '实际到账金额必须大于0');
        if ($user_id <= 0) $this->message(0, '用户信息错误');
        if ($id <= 0) $this->message(0, '操作错误');

        Db::startTrans();
        try {
            // 获取用户的账户信息
            $uAccount     = UserAccount::where('user_id', $user_id)->field('wallet_balance')->find();
            $walletBefore = $uAccount['wallet_balance'];

            // 增加账户资金
            $uAccount['wallet_balance'] = Db::raw("wallet_balance+{$realMoney}");
            $uAccount->save();
            $uRows = $uAccount->getNumRows();

            // 填写转账卡号、备注
            UserRecharge::update([
                'real_money' => $realMoney,
                'pay_state'  => RECHARGE_PAY_OFFLINE,
                'pay_time'   => time(),
            ], [
                ['id', '=', $id],
            ]);
            $urRows = UserRecharge::getNumRows();

            // 增加用户钱包流水明细
            $walletLog = UserWalletLog::create([
                'user_id'        => $user_id,
                'change_money'   => $realMoney,
                'change_type'    => USER_WALLET_RECHARGE,
                'change_time'    => date('Y-m-d H:i:s'),
                'recharge_id'    => $id,
                'before_balance' => $walletBefore,
                'after_balance'  => bcadd($walletBefore, $realMoney, 2),
                'remark'         => '线下银行转账',
            ]);

            if ($uRows && $urRows && $walletLog) {
                // 提交事务
                Db::commit();
                $code = 1;
                $msg  = '操作成功';
            } else {
                // 提交事务
                Db::rollback();
                $code = 0;
                $msg  = '操作失败';
            }
        } catch (\Exception $e) {
            Db::rollback();
            $code = 0;
            $msg  = '操作失败2';
        }

        return $this->message($code, $msg);
    }

}
