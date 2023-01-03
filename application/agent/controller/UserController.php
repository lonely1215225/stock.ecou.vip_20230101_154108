<?php
namespace app\agent\controller;

use app\stock\controller\CityController;
use app\stock\controller\OrgFilterController;
use app\stock\controller\UserController AS UserApi;
use think\App;

class UserController extends BaseController
{

    protected $userInfoApi;
    protected $orgFilter;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->userInfoApi = new UserApi();
        $this->orgFilter   = new OrgFilterController();
    }

    // 用户列表
    public function index()
    {
        // 获取用户信息
        $userList = $this->userInfoApi->listByAgent()->getData();
        $this->assign('userList', $userList['code'] == 1 ? $userList['data'] : []);

        // 获取所有经济人信息
        $brokerInfo = $this->orgFilter->broker()->getData();
        $this->assign('brokerInfo', $brokerInfo['code'] == 1 ? $brokerInfo['data'] : []);

        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('mobile', $data['mobile']);
        $this->assign('broker_id', $data['broker_id']);

        // 获取用户列表统计详情
        $userTotal = $this->userInfoApi->userAgentStatistic()->getData();
        $this->assign('userTotal', $userTotal['data']);

        return $this->fetch();
    }

    /**
     * 用户详情
     *
     * @return mixed
     */
    public function user_detail()
    {
        // 获取用户信息
        $userList = $this->userInfoApi->read()->getData();
        $this->assign('userList', $userList['code'] == 1 ? $userList['data'] : []);
        // 获取省份信息
        $cityApi  = new CityController();
        $cityInfo = $cityApi->getCityInfo()->getData();
        $this->assign('cityInfo', $cityInfo['code'] == 1 ? $cityInfo['data'] : '');

        return $this->fetch();
    }

    /**
     * 添加编辑钱包流水
     *
     * @return mixed
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
        // 获取所有代理商信息
        $agentInfo = $this->orgFilter->agent()->getData();
        $this->assign('agentInfo', $agentInfo['code'] == 1 ? $agentInfo['data'] : []);
        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agentId', $data['agent_id']);
        $this->assign('brokerId', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

    public function strategy_log()
    {
        $strategyLogAll = $this->userInfoApi->strategyLog()->getData();
        $this->assign('strategyLogAll', $strategyLogAll['data']);
        // 获取用户钱包流水变动类型
        $strategyChangeType = $this->userInfoApi->getUserStrategyChangeType()->getData();
        $this->assign('strategyChangeType', $strategyChangeType['data']);
        // 获取所有代理商信息
        $agentInfo = $this->orgFilter->agent()->getData();
        $this->assign('agentInfo', $agentInfo['code'] == 1 ? $agentInfo['data'] : []);
        // 获取查询提交数据
        $data['mobile']            = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type']       = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agentId', $data['agent_id']);
        $this->assign('brokerId', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

}
