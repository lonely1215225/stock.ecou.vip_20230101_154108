<?php
namespace app\dash\controller;

use app\stock\controller\OrgFilterController;
use app\stock\controller\OrgAccountController;
use app\stock\controller\OrgAccountLogController;
use app\stock\controller\OrgWithdrawController;
use app\stock\controller\AgentController AS AgentApi;
use think\App;

class AgentController extends BaseController
{

    protected $adminUserApi;
    protected $orgFilterApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserApi = new AgentApi();
        $this->orgFilterApi = new OrgFilterController();
    }

    /**
     * 代理商信息列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取代理商列表
        $agentInfo = $this->adminUserApi->getAgentList()->getData();
        $this->assign('agentInfo', $agentInfo['code'] == 1 ? $agentInfo['data'] : []);
        $orgAccountApi = new OrgAccountController();
        $orgAccount    = $orgAccountApi->getOrgAccountAll()->getData();
        $this->assign('orgAccount', $orgAccount['code'] == 1 ? $orgAccount['data'] : []);
        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);

        //获取代理商统计详情
        $totalDetail = $this->adminUserApi->agentStatistic()->getData();
        $this->assign('totalDetail', $totalDetail['data']);
        $agent_id = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $mobile   = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('agent_id', $agent_id);
        $this->assign('mobile', $mobile);

        return $this->fetch();
    }

    /**
     * 添加编辑代理商信息
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_agent()
    {
        // 获取编辑代理商信息
        $agentInfo = $this->adminUserApi->getAgentInfoById()->getData();

        if ($agentInfo['data']) {
            $this->assign('agentInfo', $agentInfo['data']);
        }
        // 获取代理商最低分成比例
        $agentInfo = $this->adminUserApi->agentLowestCommissionRate()->getData();
        $this->assign('lowerCommissionRate', $agentInfo['data'] ? $agentInfo['data']['commission_rate'] : '');

        return $this->fetch();
    }

    /**
     * 重置代理商密码
     *
     * @return mixed
     */
    public function reset_pwd()
    {
        // 获取代理商信息
        $agentInfo = $this->adminUserApi->getAgentInfoById()->getData();
        if ($agentInfo['data']) {
            $this->assign('agentInfo', $agentInfo['data']);
        }

        return $this->fetch();
    }

    /**
     * 获取代理商下经济人信息
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function show_broker()
    {
        // 获取代理商下经济人信息
        $brokerInfo = $this->adminUserApi->getBrokerByAgent()->getData();
        if ($brokerInfo['data']) {
            $this->assign('brokerInfo', $brokerInfo['data']);
        }
        // 获取代理商id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('id', $id);

        return $this->fetch();
    }

    /**
     * 用户申请提现列表
     *
     * @return mixed
     */
    public function withdraw_log()
    {
        // 获取用户申请提现列表
        $withdrawLogApi = new OrgWithdrawController();
        $withdrawLog    = $withdrawLogApi->getWithdrawByAdmin()->getData();
        $this->assign('withdrawLog', $withdrawLog['code'] == 1 ? $withdrawLog['data'] : []);
        // 获取提现申请处理状态
        $stateList = $this->adminUserApi->getWithdrawState()->getData();
        $this->assign('stateList', $stateList['code'] == 1 ? $stateList['data'] : []);
        // 获取代理商总提现金额
        $totalWithdraw = $withdrawLogApi->agentTotalWithdraw()->getData();
        $this->assign('totalWithdraw', $totalWithdraw['data']);
        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        $agent_id   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $state      = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date   = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('agent_id', $agent_id);
        $this->assign('state', $state);
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
        // 检查该提现申请是否被管理员操作过
        $withdrawLogApi = new OrgWithdrawController();
        $isWithdraw     = $withdrawLogApi->checkIsWithdraw($data['id'])->getData();
        if ($isWithdraw['code'] == 1) {
            echo $isWithdraw['msg'];

            return;
        }

        return $this->fetch();
    }

    /**
     * 获取代理商资金明细
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function account_log()
    {
        $orgAccountLogApi = new OrgAccountLogController();
        $logInfo          = $orgAccountLogApi->getAgentAccountLog()->getData();
        $this->assign('logInfo', $logInfo['code'] == 1 ? $logInfo['data'] : []);
        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取用户账户变动类型常量
        $changeType = $orgAccountLogApi->getChangeType()->getData();
        $this->assign('changeType', $changeType['data']);

        //获取代理商总变动金额
        $totalChangeMoney = $orgAccountLogApi->agentTotalChangeMoney()->getData();
        $this->assign('totalChangeMoney', $totalChangeMoney['data']);

        $change_type = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $agent_id    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $start_date  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('change_type', $change_type);
        $this->assign('agent_id', $agent_id);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);

        return $this->fetch();
    }

}
