<?php

namespace app\stock\controller;

use app\common\model\AdminUser;
use app\common\model\OrderPosition;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\UserBankCard;
use app\common\model\UserCashCouponFrozenLog;
use app\common\model\UserStrategyLog;
use app\common\model\UserCashCouponLog;
use app\common\model\UserWalletLog;
use app\common\model\UserWithdraw;
use app\common\model\Yuebao;
use think\App;
use think\Db;
use think\facade\Request;
use util\BasicData;
use util\Excel;
use util\OrderRedis;
use util\RedisUtil;

class UserController extends BaseController
{

    protected $userModel;
    protected $userBankCardModel;
    protected $userAccountModel;
    protected $adminUserModel;
    protected $userStrategyLog;
    protected $userCashCouponLog;
    protected $userWalletLog;
    protected $userFrozenLog;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->userModel         = new User();
        $this->userAccountModel  = new UserAccount();
        $this->userBankCardModel = new UserBankCard();
        $this->adminUserModel = new AdminUser();
        $this->userStrategyLog = new UserStrategyLog();
        $this->userWalletLog = new UserWalletLog();
        $this->userFrozenLog = new UserCashCouponFrozenLog();
        $this->userCashCouponLog = new UserCashCouponLog();
    }

    /**
     * 所有用户列表(需管理员权限)
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['pid'] = input('pid', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);

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
        if($data['pid']) {
            $map[] = ['pid', '=', $data['pid']];
        }
        if ($data['submit_flag'] == 1) {
            $map[] = ['agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['no_agent_id']) {
            $map[] = ['agent_id', 'not in', $data['no_agent_id']];
        }

        // 获取用户信息列表
        $userList            = $this->userModel->where($map)
            ->field('id,mobile,is_deny_login,is_deny_cash,create_time,update_time,reg_ip,login_ip,is_bound_bank_card,agent_id,broker_id,real_name,is_deny_buy,is_deny_sell')
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['userList'] = $userList;

        if ($userList->getCollection()->toArray()) {
            // 提取id列
            $user_id_arr = array_column($userList->getCollection()->toArray(), 'id');

            // 提取代理商id
            $user_agent_arr = array_column($userList->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo            = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;

            // 提取代理商id
            $user_broker_arr = array_column($userList->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo            = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;

            // 获取用户账户信息
            $userAccountList            = $this->userAccountModel->where('user_id', 'in', $user_id_arr)
                ->column('wallet_balance,strategy_balance,total_recharge,total_withdraw,frozen,cash_coupon,cash_coupon_frozen,cash_coupon_time,cash_coupon_uptime', 'user_id');
            $dataAll['userAccountList'] = $userAccountList;

            // 获取用户已提现金额
            $totalWithdraw            = UserWithdraw::where('state', USER_WITHDRAW_SUCCESS)
                ->group('user_id')
                ->column('SUM(money)', 'user_id');
            $dataAll['totalWithdraw'] = $totalWithdraw;

            // 获取用户平仓盈亏结算
            $spalList            = OrderPosition::where('is_finished', true)->group('user_id')->column('SUM(s_pal) as totalpal', 'user_id');
            $dataAll['spalList'] = $spalList;
        }

        return $dataAll ? $this->message(1, '', $dataAll) : $this->message(0, '用户信息为空');
    }

    /**
     * 代理商下所有用户
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByAgent()
    {
        $map[] = ['agent_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['broker_id', '=', $data['broker_id']];
        }

        //获取用户信息列表
        $userList            = $this->userModel->where($map)
            ->field('id,mobile,is_deny_login,is_deny_cash,create_time,update_time,reg_ip,login_ip,is_bound_bank_card,agent_id,broker_id,real_name')
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['userList'] = $userList;

        // 提取id列
        $user_id_arr = array_column($userList->getCollection()->toArray(), 'id');
        if ($user_id_arr) {
            // 获取用户账户信息表
            $userAccountList            = $this->userAccountModel
                ->where('user_id', 'in', $user_id_arr)->column('wallet_balance,strategy_balance,total_recharge,total_withdraw,frozen,deposit', 'user_id');
            $dataAll['userAccountList'] = $userAccountList;

            // 提取代理商id
            $user_agent_arr = array_column($userList->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo            = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;

            // 提取代理商id
            $user_broker_arr = array_column($userList->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo            = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;

            // 获取用户已提现金额
            $totalWithdraw            = UserWithdraw::where('state', USER_WITHDRAW_SUCCESS)
                ->group('user_id')
                ->column('SUM(money)', 'user_id');
            $dataAll['totalWithdraw'] = $totalWithdraw;

            // 获取用户平仓盈亏结算
            $spalList            = OrderPosition::where('is_finished', true)->group('user_id')->column('SUM(s_pal) as totalpal', 'user_id');
            $dataAll['spalList'] = $spalList;
        }

        return $dataAll ? $this->message(1, '', $dataAll) : $this->message(0, '用户信息为空');
    }

    /**
     * 经纪人下所用户
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByBroker()
    {
        $map[] = ['broker_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        //获取用户信息列表
        $userList            = $this->userModel->where($map)
            ->field('id,mobile,is_deny_login,is_deny_cash,create_time,update_time,reg_ip,login_ip,is_bound_bank_card,agent_id,broker_id,real_name')
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['userList'] = $userList;

        // 提取id列
        $user_id_arr = array_column($userList->getCollection()->toArray(), 'id');

        // 获取用户账户信息表
        if ($user_id_arr) {
            $userAccountList            = $this->userAccountModel->where('user_id', 'in', $user_id_arr)
                ->column('wallet_balance,strategy_balance,total_recharge,total_withdraw,deposit,frozen', 'user_id');
            $dataAll['userAccountList'] = $userAccountList;
            // 获取用户平仓盈亏结算
            $spalList            = OrderPosition::where('is_finished', true)->group('user_id')->column('SUM(s_pal) as totalpal', 'user_id');
            $dataAll['spalList'] = $spalList;
            // 获取用户已提现金额
            $totalWithdraw            = UserWithdraw::where('state', USER_WITHDRAW_SUCCESS)
                ->group('user_id')
                ->column('SUM(money)', 'user_id');
            $dataAll['totalWithdraw'] = $totalWithdraw;
        }

        return $dataAll ? $this->message(1, '', $dataAll) : $this->message(0, '用户信息为空');
    }

    /**
     * 根据user_id返回一条用户信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        // 获取用户id
        $id      = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $dataAll = [];
        if ($id) {
            // 获取用户信息列表
            $userList            = $this->userModel->where('id', $id)
                ->field('id,mobile,is_deny_login,is_deny_cash,create_time,update_time,update_time,reg_ip,login_ip,is_bound_bank_card,remark,agent_id,broker_id')
                ->find();
            $dataAll['userList'] = $userList;

            // 获取用户账户信息
            $userAccountList            = $this->userAccountModel->where('user_id', $id)
                ->field('user_id,wallet_balance,strategy_balance,total_recharge,total_withdraw,total_pal,create_time,update_time,deposit')
                ->find();
            $dataAll['userAccountList'] = $userAccountList;

            // 获取用户绑定银行卡信息
            $userBankCardList            = $this->userBankCardModel->where('user_id', $id)->find();
            $dataAll['userBankCardList'] = $userBankCardList;

            // 获取代理人/经纪人信息
            $roleInfo             = $this->adminUserModel->field('org_name')->where('id', $userList['agent_id'])->whereOr('id', $userList['broker_id'])->column('org_name', 'role');
            $dataAll['adminUser'] = $roleInfo;

            return $dataAll ? $this->message(1, '', $dataAll) : $this->message(0, '用户信息为空');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取单个钱包流水信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWalletInfoById($order_position_id = '')
    {
        // 获取钱包流水信息id
        input('id') ? $where['id'] = input('id', 0, FILTER_SANITIZE_NUMBER_INT) : '';
        $order_position_id ? $where['order_position_id'] = intval($order_position_id) : '';

        if (!empty($where)) {
            $walletInfo = $this->userStrategyLog->where($where)
                ->field('id,stock_code,change_money,change_type,before_balance,after_balance,create_time')
                ->find();

            return $walletInfo ? $this->message(1, '', $walletInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取单个钱包流水信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWalletInfo($where)
    {
        if (!empty($where)) {
            $walletInfo = $this->userStrategyLog->where($where)
                ->column('id,stock_code,change_money,change_type,before_balance,after_balance,create_time', 'id');
            echo $this->userStrategyLog->getLastSql();

            return $walletInfo ? $this->message(1, '', $walletInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取提现申请处理状态
     *
     * @return \think\response\Json
     */
    public function getWithdrawState()
    {
        return $this->message(1, '', BasicData::USER_WITHDRAW_STATE_LIST);
    }

    /**
     * 获取用户钱包流水变动类型
     *
     * @return \think\response\Json
     */
    public function getUserWalletChangeType()
    {
        return $this->message(1, '', BasicData::USER_WALLET_CHANGE_TYPE_LIST);
    }

    /**
     * 获取用户策略金变动类型
     *
     * @return \think\response\Json
     */
    public function getUserStrategyChangeType()
    {
        return $this->message(1, '', BasicData::USER_STRATEGY_CHANGE_TYPE_LIST);
    }

    /**
     * 获取用户冻结资金变动类型
     *
     * @return \think\response\Json
     */
    public function getUserFrozenChangeType()
    {
        return $this->message(1, '', BasicData::USER_FROZEN_CHANGE_TYPE_LIST);
    }

    /**
     * 获取用户列表统计详情
     * 超级管理员后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userStatistic()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['pid'] = input('pid', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['pid']) {
            $map[] = ['u.pid', '=', $data['pid']];
        }
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }

        // 获取总用户数量
        $userTotal = $this->userModel->alias('u')->where($map)->count();
        // 获取用户总提现金额
        $successWithdraw = UserWithdraw::alias('uw')
            ->field('SUM(uw.money) as money')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->where('uw.state', USER_WITHDRAW_SUCCESS)
            ->find();

        // 获取所有用户总账户余额详情
        $totalAccount = $this->userAccountModel->alias('ua')
            ->field('SUM(wallet_balance) as totalWallet,SUM(strategy_balance) as totalStrategy,SUM(total_recharge) as totalRecharge,SUM(total_withdraw) as totalWithdraw,SUM(deposit) as totalDeposit,SUM(frozen) as frozen,SUM(cash_coupon) as cashCoupon')
            ->where($map)
            ->join(['__USER__' => 'u'], 'u.id=ua.user_id')
            ->find();

        // 获取用户总的平仓结算盈亏
        $totalSpal = OrderPosition::alias('op')
            ->field('SUM(op.s_pal) as totalSpal')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('is_finished', true)->where($map)->find();


        return $this->message(1, '', ['userTotal' => $userTotal, 'totalAccount' => $totalAccount, 'successWithdraw' => $successWithdraw, 'totalSpal' => $totalSpal]);
    }

    /**
     * 获取用户列表统计详情
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userAgentStatistic()
    {
        $map[] = ['agent_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['broker_id', '=', $data['broker_id']];
        }
        // 获取总用户数量
        $userTotal = $this->userModel->alias('u')->where($map)->count();
        // 获取用户总提现金额
        $successWithdraw = UserWithdraw::alias('uw')
            ->field('SUM(uw.money) as money')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->where('uw.state', USER_WITHDRAW_SUCCESS)
            ->find();

        // 获取所有用户总账户余额详情
        $totalAccount = $this->userAccountModel->alias('ua')
            ->field('SUM(wallet_balance) as totalWallet,SUM(strategy_balance) as totalStrategy,SUM(total_recharge) as totalRecharge,SUM(total_withdraw) as totalWithdraw,SUM(deposit) as totalDeposit,SUM(frozen) as frozen,SUM(deposit) AS deposit')
            ->where($map)
            ->join(['__USER__' => 'u'], 'u.id=ua.user_id')
            ->find();

        // 获取用户总的平仓结算盈亏
        $totalSpal = OrderPosition::alias('op')
            ->field('SUM(op.s_pal) as totalSpal')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('is_finished', true)->where($map)->find();

        return $this->message(1, '', ['userTotal' => $userTotal, 'totalAccount' => $totalAccount, 'successWithdraw' => $successWithdraw, 'totalSpal' => $totalSpal]);
    }

    /**
     * 获取用户列表统计详情
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userBrokerStatistic()
    {
        $map[] = ['broker_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        // 获取总用户数量
        $userTotal = $this->userModel->alias('u')->where($map)->count();
        // 获取用户总提现金额
        $successWithdraw = UserWithdraw::alias('uw')
            ->field('SUM(uw.money) as money')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->where('uw.state', USER_WITHDRAW_SUCCESS)
            ->find();

        // 获取所有用户总账户余额详情
        $totalAccount = $this->userAccountModel->alias('ua')
            ->field('SUM(wallet_balance) as totalWallet,SUM(strategy_balance) as totalStrategy,SUM(total_recharge) as totalRecharge,SUM(total_withdraw) as totalWithdraw,SUM(deposit) as totalDeposit,SUM(frozen) as frozen')
            ->where($map)
            ->join(['__USER__' => 'u'], 'u.id=ua.user_id')
            ->find();

        // 获取用户总的平仓结算盈亏
        $totalSpal = OrderPosition::alias('op')
            ->field('SUM(op.s_pal) as totalSpal')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('is_finished', true)->where($map)->find();

        return $this->message(1, '', ['userTotal' => $userTotal, 'totalAccount' => $totalAccount, 'successWithdraw' => $successWithdraw, 'totalSpal' => $totalSpal]);
    }

    /**
     * 编辑银行卡信息
     */
    public function edit_bank_card()
    {
        $data['user_id']        = input('post.user_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['real_name']      = input('post.real_name', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['id_card_number'] = input('post.id_card_number', '', 'filter_id_card_number');
        $data['mobile']         = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['bank_id']        = input('post.bank_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['bank_name']      = input('post.bank_name', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['province']       = input('post.province', '', FILTER_SANITIZE_NUMBER_INT);
        $data['city']           = input('post.city', '', FILTER_SANITIZE_NUMBER_INT);
        $data['branch']         = input('post.branch', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['bank_number']    = input('post.bank_number', '', FILTER_SANITIZE_NUMBER_INT);

        // 查询银行卡
        $bankCard = UserBankCard::where('user_id', $data['user_id'])
            ->field('real_name,id_card_number,mobile,bank_id,bank_name,province,city,branch,bank_number,state')
            ->find();

        if ($bankCard) {
            // 此操作为完善银行卡信息
            // 验证数据
            $result = $this->validate($data, 'UserBankCard');
        } else {
            // 数据验证
            $result = $this->validate($data, 'UserBankCard');
        }

        // 验证不合法
        if ($result !== true) return $this->message(0, $result);

        // 保存银行卡信息，并设置用户表为已绑定银行卡状态
        Db::startTrans();
        try {
            $userBankCardModel = new UserBankCard();

            // 保存银行卡
            if ($userBankCardModel->where('user_id', $data['user_id'])->count()) {
                // 完善信息
                $bankCard['real_name']      = $data['real_name'];
                $bankCard['mobile']         = $data['mobile'];
                $bankCard['bank_id']        = $data['bank_id'];
                $bankCard['bank_name']      = $data['bank_name'];
                $bankCard['province']       = $data['province'];
                $bankCard['city']           = $data['city'];
                $bankCard['branch']         = $data['branch'];
                $bankCard['bank_number']    = $data['bank_number'];
                $bankCard['id_card_number'] = $data['id_card_number'];
                $bankCard['state']          = BANK_CARD_BIND;
                // 保存银行卡信息
                $bRet = $bankCard->save();
            } else {
                // 新增
                $data['state'] = BANK_CARD_BIND;
                $bRet          = UserBankCard::create($data);
            }

            // 用户表：银行卡已绑定，姓名
            $uRet = User::update([
                'is_bound_bank_card' => true,
                'real_name'          => $data['real_name'],
            ], [['id', '=', $data['user_id']]]);

            if ($bRet && $uRet) {
                // 提交事务
                Db::commit();

                // 返回成功信息
                return $this->message(1, '绑定银行卡修改成功');
            } else {
                // 提交事务
                Db::rollback();

                // 返回成功信息
                return $this->message(0, '绑定银行卡修改失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            // 返回失败信息
            return $this->message(0, '绑定银行卡失败');
        }
    }

    /**
     * 删除用户的银行卡
     *
     * @return \think\response\Json
     */
    public function delete_bank_card()
    {
        $userID = Request::param('user_id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($userID <= 0) return $this->message(0, '操作失败');

        Db::startTrans();
        try {
            // 删除银行卡
            $dRet = UserBankCard::where('user_id', $userID)->delete();

            // 用户表：未绑定银行卡，清除姓名
            $uRet = User::update([
                'is_bound_bank_card' => false,
                'real_name'          => '',
            ], [
                ['id', '=', $userID],
            ]);

            if ($dRet && $uRet) {
                Db::commit();

                return $this->message(1, '操作成功');
            } else {
                Db::rollback();

                return $this->message(0, '操作失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            return $this->message(0, '操作失败');
        }
    }

    /**
     * 转移用户到新的经纪人
     *
     * @return \think\response\Json
     */
    public function change_pwd()
    {
        $password   = input('post.password', '', [FILTER_SANITIZE_STRING, 'trim']);
        $rePassword = input('post.rePassword', 0, [FILTER_SANITIZE_STRING, 'trim']);
        $userID     = input('post.userID', 0, FILTER_SANITIZE_NUMBER_INT);

        if (empty($password)) return $this->message(0, '请填写密码');
        if (empty($rePassword)) return $this->message(0, '请填写确认密码');
        if ($password != $rePassword) return $this->message(0, '确认密码不正确');

        $count = $this->userModel::where('id', $userID)->count();
        if ($count == 0) return $this->message(0, '未找到该用户');

        $password = password_hash($password, PASSWORD_DEFAULT);
        $ret      = User::update([
            'password' => $password,
        ], [
            ['id', '=', $userID],
        ]);

        return $ret ? $this->message(1, '密码修改成功') : $this->message(0, '密码修改失败');
    }

    /**
     * 转移用户到新的经纪人
     *
     * @return \think\response\Json
     */
    public function change_broker()
    {
        $agentID = input('post.agentID', 0, FILTER_SANITIZE_NUMBER_INT);
        $brokerID = input('post.brokerID', 0, FILTER_SANITIZE_NUMBER_INT);
        $userID = input('post.userID', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($userID <= 0 || $agentID <= 0 || $brokerID <= 0) return $this->message(0, '转移失败');

        $count = AdminUser::where('id', $brokerID)->where('pid', $agentID)->count();
        if ($count == 0) return $this->message(0, '转移失败');

        $ret = User::update([
            'agent_id'  => $agentID,
            'broker_id' => $brokerID,
        ], [
            ['id', '=', $userID],
        ]);

        return $ret ? $this->message(1, '转移成功') : $this->message(0, '转移失败');
    }

    /**
     * 获取所有用户钱包流水
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function walletLog()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['change_type']) {
            $map[] = ['uw.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['uw.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['uw.change_time', '<=', $data['end_date']];
        }
        // 获取钱包流水信息
        $walletLogInfo = $this->userWalletLog->alias('uw')
            ->field('uw.id,uw.user_id,uw.change_money,uw.change_type,uw.change_time,uw.before_balance,uw.after_balance,uw.remark,u.mobile,u.real_name,u.broker_id,u.agent_id')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['walletLogInfo'] = $walletLogInfo;
        if ($walletLogInfo->getCollection()->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($walletLogInfo->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;
            // 提取代理商id
            $user_broker_arr = array_column($walletLogInfo->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;
        }

        return $walletLogInfo ? $this->message(1, '', $dataAll) : $this->message(0, '信息为空');
    }

    /**
     * 获取用户策略金流水信息
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function strategyLog()
    {
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);

        $map = [];
        // 根据传递的参数生产where条件
        if ($data['submit_flag'] == 1) {
            $map[] = ['agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['no_agent_id']) {
            $map[] = ['agent_id', 'not in', $data['no_agent_id']];
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
        if ($data['change_type']) {
            $map[] = ['us.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['us.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['us.change_time', '<=', $data['end_date']];
        }
        if ($data['order_position_id']) {
            $map[] = ['us.order_position_id', '=', $data['order_position_id']];
        }
        // 获取策略金流水信息
        $strategyLogInfo = $this->userStrategyLog->alias('us')
            ->field('us.id,us.market,us.stock_id,us.stock_code,us.order_id,us.order_position_id,us.order_traded_id,us.change_time,us.change_type,us.change_money,us.before_balance,us.after_balance,us.total_fee,service_fee,us.service_fee,us.stamp_tax,us.transfer_fee,us.create_time,us.remark,u.mobile,u.real_name,u.agent_id,u.broker_id')
            ->join(['__USER__' => 'u'], 'u.id=us.user_id')
            ->where($map)
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $agentInfo = $brokerInfo = [];
        if ($strategyLogInfo->getCollection()->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($strategyLogInfo->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;
            // 提取代理商id
            $user_broker_arr = array_column($strategyLogInfo->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;
        }
        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($strategyLogInfo) {
            foreach ($strategyLogInfo->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $strategyLogInfo ? $this->message(1, '', ['strategyLogInfo' => $strategyLogInfo, 'agentInfo' => $agentInfo, 'brokerInfo' => $brokerInfo, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 获取用户代金券资金流水信息
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function cashCouponLog()
    {
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);

        $map = [];
        // 根据传递的参数生产where条件
        if ($data['submit_flag'] == 1) {
            $map[] = ['agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['no_agent_id']) {
            $map[] = ['agent_id', 'not in', $data['no_agent_id']];
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
        if ($data['change_type']) {
            $map[] = ['us.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['us.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['us.change_time', '<=', $data['end_date']];
        }
        if ($data['order_position_id']) {
            $map[] = ['us.order_position_id', '=', $data['order_position_id']];
        }
        // 获取策略金流水信息
        $strategyLogInfo = $this->userCashCouponLog->alias('us')
            ->field('us.id,us.market,us.stock_id,us.stock_code,us.order_id,us.order_position_id,us.order_traded_id,us.change_time,us.change_type,us.change_money,us.before_balance,us.after_balance,us.total_fee,service_fee,us.service_fee,us.stamp_tax,us.transfer_fee,us.create_time,us.remark,u.mobile,u.real_name,u.agent_id,u.broker_id')
            ->join(['__USER__' => 'u'], 'u.id=us.user_id')
            ->where($map)
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $agentInfo = $brokerInfo = [];
        if ($strategyLogInfo->getCollection()->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($strategyLogInfo->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;
            // 提取代理商id
            $user_broker_arr = array_column($strategyLogInfo->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;
        }
        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($strategyLogInfo) {
            foreach ($strategyLogInfo->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $strategyLogInfo ? $this->message(1, '', ['strategyLogInfo' => $strategyLogInfo, 'agentInfo' => $agentInfo, 'brokerInfo' => $brokerInfo, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 获取用户策略金流水信息
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function frozenLog()
    {
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $map = [];
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['change_type']) {
            $map[] = ['us.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['us.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['us.change_time', '<=', $data['end_date']];
        }
        if ($data['order_position_id']) {
            $map[] = ['us.order_position_id', '=', $data['order_position_id']];
        }

        // 获取策略金流水信息
        $userFrozenLog = $this->userFrozenLog->alias('us')
            ->field('us.id,us.user_id,us.order_id,us.order_position_id,us.market,us.stock_id,us.stock_code,us.order_position_id,us.change_type,us.change_time,us,us.change_money,us.before_money,us.after_money,u.mobile,u.real_name,u.agent_id,u.broker_id')
            ->join(['__USER__' => 'u'], 'u.id=us.user_id')
            ->where($map)
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);

        $agentInfo = $brokerInfo = [];
        if ($userFrozenLog->getCollection()->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($userFrozenLog->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;
            // 提取代理商id
            $user_broker_arr = array_column($userFrozenLog->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;
        }
        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($userFrozenLog) {
            foreach ($userFrozenLog->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $userFrozenLog ? $this->message(1, '', ['userFrozenLog' => $userFrozenLog, 'agentInfo' => $agentInfo, 'brokerInfo' => $brokerInfo, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 编辑是否禁止买入
     * WW
     *
     * @return \think\response\Json
     */
    public function is_deny_buy()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 更新状态
        $changeInfo = $this->userModel->isUpdate(true)->save([
                'id'          => $id,
                'is_deny_buy' => Db::raw('NOT is_deny_buy'),
            ]
        );

        if ($changeInfo) {
            OrderRedis::cacheUserData($id);
        }
        return $changeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 编辑是否禁止买入
     * WW
     *
     * @return \think\response\Json
     */
    public function is_deny_login()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 更新状态
        $changeInfo = $this->userModel->isUpdate(true)->save([
                'id'            => $id,
                'is_deny_login' => Db::raw('NOT is_deny_login'),
            ]
        );

        if ($changeInfo) {
            OrderRedis::cacheUserData($id);
        }
        return $changeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 导出用户列表
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportUser()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
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
        $userList = User::where($map)
            ->field('id,mobile,is_deny_login,is_deny_cash,create_time,update_time,reg_ip,login_ip,is_bound_bank_card,agent_id,broker_id,real_name,is_deny_buy,is_deny_sell')
            ->order('id DESC')
            ->select();
        $exportArr = [];
        if ($userList->toArray()) {
            // 提取id列
            $user_id_arr = array_column($userList->toArray(), 'id');

            // 提取代理商id
            $user_agent_arr = array_column($userList->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = AdminUser::where('id', 'in', $user_agent_arr)->column('org_name', 'id');

            // 提取代理商id
            $user_broker_arr = array_column($userList->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = AdminUser::where('id', 'in', $user_broker_arr)->column('org_name', 'id');

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
                $exportArr[$key][] = $item['mobile'];
                $exportArr[$key][] = $item['real_name'];
                $exportArr[$key][] = isset($userAccountList[$item['id']]['wallet_balance']) ? $userAccountList[$item['id']]['wallet_balance'] : '';
                $exportArr[$key][] = isset($userAccountList[$item['id']]['strategy_balance']) ? $userAccountList[$item['id']]['strategy_balance'] : '';
                $exportArr[$key][] = isset($userAccountList[$item['id']]['frozen']) ? $userAccountList[$item['id']]['frozen'] : '';
                $exportArr[$key][] = isset($userAccountList[$item['id']]['total_recharge']) ? $userAccountList[$item['id']]['total_recharge'] : '';
                $exportArr[$key][] = isset($totalWithdraw[$item['id']]) ? $totalWithdraw[$item['id']] : '';
                $exportArr[$key][] = isset($spalList[$item['id']]) ? $spalList[$item['id']] : '';
                $exportArr[$key][] = isset($agentInfo[$item['agent_id']]) ? $agentInfo[$item['agent_id']] : '';
                $exportArr[$key][] = isset($brokerInfo[$item['broker_id']]) ? $brokerInfo[$item['broker_id']] : '';

            }
        }
        if ($exportArr) {
            $headData['text'] = [
                '手机号', '姓名', '账户资金', '策略金余额', '冻结资金', '累计充值', '累计提现', '平仓结算盈亏', '代理商', '经济人',
            ];
            $headData['width'] = [
                'A' => 20, 'B' => 10, 'C' => 10, 'D' => 12, 'E' => 10, 'F' => 10, 'G' => 10, 'H' => 15, 'I' => 10, 'J' => 10,
            ];
            $fileName = date('Y-m-d') . '-user.xlsx';
            $excel = new Excel($fileName);
            $excel->setActiveSheet(0, '用户列表');
            $excel->setData($exportArr, $headData);
            $excel->export();
        }
    }

    /**
     * 导出账户资金流水
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportJournalLog()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id'] = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['change_type']) {
            $map[] = ['uw.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['uw.change_time', '>=', date('Y-m-d H:i:s', $data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['uw.change_time', '<=', date('Y-m-d H:i:s', $data['end_date'])];
        }
        // 获取钱包流水信息
        $walletLogInfo = UserWalletLog::alias('uw')
            ->field('uw.id,uw.user_id,uw.change_money,uw.change_type,uw.change_time,uw.before_balance,uw.after_balance,u.mobile,u.real_name,u.broker_id,u.agent_id')
            ->join(['__USER__' => 'u'], 'u.id=uw.user_id')
            ->where($map)
            ->order('id DESC')->select();
        $exportArr = [];
        if ($walletLogInfo->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($walletLogInfo->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = AdminUser::where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            // 提取代理商id
            $user_broker_arr = array_column($walletLogInfo->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = AdminUser::where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            // 钱包变动类型
            $changeType = $this->getUserWalletChangeType()->getData();
            foreach ($walletLogInfo as $key => $item) {
                $exportArr[$key][] = $item['id'];
                $exportArr[$key][] = $item['real_name'];
                $exportArr[$key][] = $item['mobile'];
                $exportArr[$key][] = isset($item['change_type']) ? $changeType['data'][$item['change_type']] : '';
                $exportArr[$key][] = $item['change_time'];
                $exportArr[$key][] = $item['change_money'];
                $exportArr[$key][] = $item['before_balance'];
                $exportArr[$key][] = $item['after_balance'];
                $exportArr[$key][] = isset($agentInfo[$item['agent_id']]) ? $agentInfo[$item['agent_id']] : '';
                $exportArr[$key][] = isset($brokerInfo[$item['broker_id']]) ? $brokerInfo[$item['broker_id']] : '';
            }
            if ($exportArr) {
                $headData['text'] = [
                    '流水号', '姓名', '手机号', '变动类型', '变动时间', '发生金额', '发生前金额', '发生后金额', '代理商', '经济人',
                ];
                $headData['width'] = [
                    'A' => 10, 'B' => 10, 'C' => 15, 'D' => 20, 'E' => 20, 'F' => 10, 'G' => 15, 'H' => 15, 'I' => 10, 'J' => 10,
                ];
                $fileName = date('Y-m-d') . '-wallet_log.xlsx';
                $excel = new Excel($fileName);
                $excel->setActiveSheet(0, '账户资金流水');
                $excel->setData($exportArr, $headData);
                $excel->export();
            }
        }
    }

    /**
     * 获取所有用户钱包流水
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function yuebaoLog()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
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
            $map[] = ['y.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['y.income_time', '<=', $data['end_date']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }

        // 获取钱包流水信息
        $yuebaoLogInfo = Yuebao::alias('y')
            ->field('y.id,y.user_id,y.income_time,y.wallet_balance,y.income,y.base_income,y.is_received,u.mobile,u.real_name,u.broker_id,u.agent_id,ua.total_yuebao')
            ->join(['__USER__' => 'u'], 'u.id=y.user_id')
            ->join(['__USER_ACCOUNT__' => 'ua'], 'y.user_id=ua.user_id')
            ->where($map)
            ->order('y.id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['yuebaoLogInfo'] = $yuebaoLogInfo;
        if ($yuebaoLogInfo->getCollection()->toArray()) {
            // 提取代理商id
            $user_agent_arr = array_column($yuebaoLogInfo->getCollection()->toArray(), 'agent_id');
            // 获取代理商信息
            $agentInfo = $this->adminUserModel->where('id', 'in', $user_agent_arr)->column('org_name', 'id');
            $dataAll['agentInfo'] = $agentInfo;
            // 提取代理商id
            $user_broker_arr = array_column($yuebaoLogInfo->getCollection()->toArray(), 'broker_id');
            // 获取经济人信息
            $brokerInfo = $this->adminUserModel->where('id', 'in', $user_broker_arr)->column('org_name', 'id');
            $dataAll['brokerInfo'] = $brokerInfo;
        }

        return $yuebaoLogInfo ? $this->message(1, '', $dataAll) : $this->message(0, '信息为空');
    }

    /**
     * 余额表详情统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function yuebaoStatistic()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile'] = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id'] = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date'] = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
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
            $map[] = ['y.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['y.income_time', '<=', $data['end_date']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }

        // 获取钱包流水信息
        $yuebaoAccountInfo = Yuebao::alias('y')
            ->field('SUM(y.income) as totalaccount')
            ->join(['__USER__' => 'u'], 'u.id=y.user_id')
            ->join(['__USER_ACCOUNT__' => 'ua'], 'y.user_id=ua.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $yuebaoAccountInfo);
    }

}