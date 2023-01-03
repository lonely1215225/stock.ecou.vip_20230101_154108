<?php
namespace app\dash\controller;

use app\stock\controller\CityController;
use app\stock\controller\OrgFilterController;
use app\stock\controller\PayCompanyController;
use app\stock\controller\UserController AS UserApi;
use app\stock\controller\UserRechargeController;
use app\stock\controller\UserWithdrawController;
use app\common\model\UserAccount;
use app\common\model\User;
use app\common\model\Banks;
use app\index\logic\UserLogic;
use think\Db;
use think\App;

class UserController extends BaseController
{

    protected $userInfoApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->userInfoApi = new UserApi();
    }

    /**
     * 用户列表
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取用户信息
        $userList = $this->userInfoApi->index()->getData();
        $this->assign('userList', $userList['code'] == 1 ? $userList['data'] : []);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);

        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('no_agent_id', $no_agent_id);

        // 获取用户列表统计详情
        $userTotal = $this->userInfoApi->userStatistic()->getData();
        $this->assign('userTotal', $userTotal['data']);

        return $this->fetch();
    }

    /**
     * 推广用户列表
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function generalize_user_list()
    {

        // 获取用户信息
        $userList = $this->userInfoApi->index()->getData();
        $this->assign('userList', $userList['code'] == 1 ? $userList['data'] : []);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);

        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['pid']       = input('pid', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('pid', $data['pid']);

        // 获取用户列表统计详情
        $userTotal = $this->userInfoApi->userStatistic()->getData();
        $this->assign('userTotal', $userTotal['data']);

        $this->assign('username', User::where('id', $data['pid'])->value('username'));

        return $this->fetch();
    }

    /**
     * 添加用户
     */
    public function add_user()
    {

        // 获取省份信息
        $cityApi  = new CityController();
        $cityInfo = $cityApi->getCityInfo()->getData();
        $this->assign('cityInfo', $cityInfo['code'] == 1 ? $cityInfo['data'] : '');

        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);

        return $this->fetch();
    }

    /**
     * 保存用户信息
     */
    public function saveUser()
    {
        // 用户参数
        $data['mobile']    = $mobile = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['password']  = input('post.password', '');
        $data['confirm']   = input('post.confirm', '');
        $data['code']      = input('post.code', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['agent_id']  = input('post.agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('post.broker_id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 验证数据
        $result = $this->validate($data, 'User.Register');
        if ($result !== true) return $this->message(0, $result);

        // 获取代理商对应的平台ID，当前仅有一个平台，ID为1
        $platformID = 1;

        // 用户归属
        $data['platform_id'] = $platformID;

        // 入库操作
        Db::startTrans();
        try {
            // 其他数据
            unset($data['confirm'], $data['code']);
            $data['username'] = $data['mobile'];
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['reg_ip']   = $this->request->ip();

            // 写入用户表
            $user = User::create($data);
            // 用户ID
            $userID = $user['id'];
            // 写入用户账户表
            $userAccount = UserAccount::create(['user_id' => $user['id']]);

            if ($user && $userAccount) {
                Db::commit();
                $ret = true;

                // 生成推广码，及二维码图片
                //UserLogic::setInviteCode($userID, $this->request);
            } else {
                Db::rollback();
                $ret = false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $ret = false;
        }

        return $ret ? $this->message(1, '注册成功') : $this->message(0, '注册失败');
    }

    /**
     * 用户详情
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_detail()
    {
        // 获取用户信息
        $userList = $this->userInfoApi->read()->getData();
        $this->assign('userList', $userList['code'] == 1 ? $userList['data'] : []);

        // 获取用户id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('id', $id);

        // 获取省份信息
        $cityApi  = new CityController();
        $cityInfo = $cityApi->getCityInfo()->getData();
        $this->assign('cityInfo', $cityInfo['code'] == 1 ? $cityInfo['data'] : '');

        //获取身份
        $city = $cityApi->tree()->getData();
        $this->assign('city',$city['data']);

        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);

        //获取银行
        $banksModel = new Banks();
        $banksList       = $banksModel->field('id,bank_name')->select()->toArray();
        $this->assign('banksList',$banksList);

        return $this->fetch();
    }

    /**
     * 添加编辑钱包流水
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_walletlog()
    {
        // 获取用户id
        $userId = input('user_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('userId', $userId);

        // 获取待编辑钱包流水信息
        $editWalletInfo = $this->userInfoApi->getWalletInfoById()->getData();
        if ($editWalletInfo['data']) {
            $this->assign('editWalletInfo', $editWalletInfo['data']);
        }

        return $this->fetch();
    }

    /**
     * 用户申请提现列表
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw_log()
    {
        // 获取用户申请提现列表
        $withdrawLogApi = new UserWithdrawController();
        $withdrawLog    = $withdrawLogApi->index()->getData();
        $this->assign('withdrawLog', $withdrawLog['code'] == 1 ? $withdrawLog['data'] : []);
        // 获取提现申请处理状态
        $stateList = $this->userInfoApi->getWithdrawState()->getData();
        $this->assign('stateList', $stateList['code'] == 1 ? $stateList['data'] : []);
        // 获取用户总申请提现金额数据
        $totalWithdrawMoney = $withdrawLogApi->userTotalWithdraw()->getData();
        $this->assign('totalWithdrawMoney', $totalWithdrawMoney['data']);
        // 获取用户总成功提现金额数据
        $successWithdrawMoney = $withdrawLogApi->userSuccessWithdraw()->getData();
        $this->assign('successWithdrawMoney', $successWithdrawMoney['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        $state       = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $agent_id    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $broker_id   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $start_date  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('state', $state);
        $this->assign('mobile', $mobile);
        $this->assign('agent_id', $agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);

        return $this->fetch();
    }

    /**
     * 提现申请处理
     *
     * @return mixed
     */
    public function do_withdraw()
    {
        $data['money']    = input('money');
        $data['id']       = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['username'] = input('username', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('money', $data['money']);
        $this->assign('id', $data['id']);
        $this->assign('username', $data['username']);
        // 获取提现申请处理状态
        $stateList = $this->userInfoApi->getWithdrawState()->getData();
        $this->assign('stateList', $stateList['code'] == 1 ? $stateList['data'] : []);

        return $this->fetch();
    }

    /**
     * 用户充值记录
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recharge_log()
    {
        $useRechargeApi = new UserRechargeController();
        $useRecharge    = $useRechargeApi->UserRechargeList()->getData();

        $this->assign('useRecharge', $useRecharge ['code'] == 1 ? $useRecharge ['data'] : []);

        //获取用户总充值金额数据
        $totalMoney = $useRechargeApi->userTotalRecharge()->getData();
        $this->assign('totalMoney', $totalMoney ['code'] == 1 ? $totalMoney ['data'] : []);

        // 获取支付状态常量
        $rechargePayStateList = $useRechargeApi->rechargePayStateList()->getData();
        $this->assign('rechargePayStateList', $rechargePayStateList['data']);

        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);

        // 获取支付公司列表
        $payCompanyApi  = new PayCompanyController();
        $payCompanyList = $payCompanyApi->index()->getData();

        $this->assign('payCompanyList', $payCompanyList['data']);
        $pay_state   = input('pay_state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $pay_company = input('pay_company', 0, FILTER_SANITIZE_NUMBER_INT);
        $mobile      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $agent_id    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $broker_id   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $start_date  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('pay_state', $pay_state);
        $this->assign('agent_id', $agent_id);
        $this->assign('mobile', $mobile);
        $this->assign('broker_id', $broker_id);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('pay_company', $pay_company);

        return $this->fetch();
    }

    /**
     * 用户充值记录手动入账
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function manual()
    {
        // 获取单条充值记录
        $useRechargeApi = new UserRechargeController();
        $oneRecharge    = $useRechargeApi->read()->getData();
        $this->assign('oneRecharge', $oneRecharge['data']);

        return $this->fetch();
    }

    /**
     * 获取所有用户钱包流水
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function wallet_log()
    {
        // 获取所有用户钱包流水
        $walletLogAll = $this->userInfoApi->walletLog()->getData();
        $this->assign('walletLogAll', $walletLogAll['data']);
        // 获取用户钱包流水变动类型
        $walletChangeType = $this->userInfoApi->getUserWalletChangeType()->getData();
        $this->assign('walletChangeType', $walletChangeType['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id         = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag         = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

    /**
     * 用户策略金流水
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function strategy_log()
    {
        $strategyLogAll = $this->userInfoApi->strategyLog()->getData();
        $this->assign('strategyLogAll', $strategyLogAll['data']);
        // 获取用户钱包流水变动类型
        $strategyChangeType = $this->userInfoApi->getUserStrategyChangeType()->getData();
        $this->assign('strategyChangeType', $strategyChangeType['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['mobile']            = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type']       = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id               = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag               = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);
        $this->assign('order_position_id', $data['order_position_id']);

        return $this->fetch();
    }

    /**
     * 用户策略金流水
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function frozen_log(){
        $frozenLogAll = $this->userInfoApi->frozenLog()->getData();
        $this->assign('userFrozenLog', $frozenLogAll['data']);
        // 获取用户钱包流水变动类型
        $frozenChangeType = $this->userInfoApi->getUserFrozenChangeType()->getData();
        $this->assign('frozenChangeType', $frozenChangeType['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['mobile']            = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type']       = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id               = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag               = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);
        $this->assign('order_position_id', $data['order_position_id']);

        return $this->fetch();
    }

    /**
     * 线下转账
     *
     * @return mixed
     */
    public function offline_transfer()
    {
        // 获取单条充值记录
        $useRechargeApi = new UserRechargeController();
        $oneRecharge    = $useRechargeApi->read()->getData();
        $this->assign('oneRecharge', $oneRecharge['data']);
        // 获取支付公司列表
        $payCompanyApi  = new PayCompanyController();
        $payCompanyList = $payCompanyApi->index()->getData();
        $this->assign('payCompanyList', $payCompanyList['data']);

        return $this->fetch();
    }

    /**
     * 线下转账详情
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offline_transfer_detail()
    {
        // 获取单条充值记录
        $useRechargeApi = new UserRechargeController();
        $oneRecharge    = $useRechargeApi->read()->getData();
        $this->assign('oneRecharge', $oneRecharge['data']);
        // 获取支付公司列表
        $payCompanyApi  = new PayCompanyController();
        $payCompanyList = $payCompanyApi->index()->getData();
        $this->assign('payCompanyList', $payCompanyList['data']);

        return $this->fetch();
    }


    /**
     * 获取所有用户收益宝明细
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function yuebao_log()
    {
        // 获取所有用户钱包流水
        $yuebaoLogAll = $this->userInfoApi->yuebaoLog()->getData();
        $this->assign('yuebaoLogAll', $yuebaoLogAll['data']);

        //余额宝详情统计
        $yuebaoAccount = $this->userInfoApi->yuebaoStatistic()->getData();
        $this->assign('yuebaoAccount', $yuebaoAccount['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id         = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag         = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

    /**
     * 用户代金券列表
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cash_coupon_log()
    {
        $strategyLogAll = $this->userInfoApi->cashCouponLog()->getData();
        $this->assign('strategyLogAll', $strategyLogAll['data']);
        // 获取用户钱包流水变动类型
        $strategyChangeType = $this->userInfoApi->getUserStrategyChangeType()->getData();
        $this->assign('strategyChangeType', $strategyChangeType['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList    = $OrgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['mobile']            = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type']       = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id               = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag               = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);
        $this->assign('order_position_id', $data['order_position_id']);

        return $this->fetch();
    }

}
