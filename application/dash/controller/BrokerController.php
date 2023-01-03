<?php
namespace app\dash\controller;

use app\stock\controller\OrgFilterController;
use app\stock\controller\OrgAccountController;
use app\stock\controller\OrgAccountLogController;
use app\stock\controller\OrgWithdrawController;
use app\stock\controller\BrokerController AS BrokerApi;
use think\App;

class BrokerController extends BaseController
{

    protected $adminUserApi;
    protected $orgFilterApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserApi = new BrokerApi();
        $this->orgFilterApi = new OrgFilterController();
    }

    /**
     * 获取所有经济人信息
     *
     * @return mixed
     */
    public function index()
    {
        // 获取经纪人列表
        $brokerInfo = $this->adminUserApi->getBrokerAll()->getData();
        $this->assign('brokerInfo', $brokerInfo['code'] == 1 ? $brokerInfo['data'] : []);

        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        $agentId = input('pid', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('agentId', $agentId);
        $orgAccountApi = new OrgAccountController();
        $orgAccount    = $orgAccountApi->getOrgAccountAll()->getData();
        $this->assign('orgAccount', $orgAccount['code'] == 1 ? $orgAccount['data'] : []);
        // 获取经济人列表统计详情
        $brokerDetail = $this->adminUserApi->brokerStatistic()->getData();
        $this->assign('brokerDetail', $brokerDetail['data']);
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('mobile', $data['mobile']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);

        return $this->fetch();
    }

    /**
     * 重置经济人密码
     *
     * @return mixed
     */
    public function reset_pwd()
    {
        // 获取代理商信息
        $agentInfo = $this->adminUserApi->getBrokerInfoById()->getData();
        if ($agentInfo['data']) {
            $this->assign('agentInfo', $agentInfo['data']);
        }

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
        $withdrawLog    = $withdrawLogApi->getBrokerWithdraw()->getData();
        $this->assign('withdrawLog', $withdrawLog['code'] == 1 ? $withdrawLog['data'] : []);
        // 获取提现申请处理状态
        $stateList = $this->adminUserApi->getWithdrawState()->getData();
        $this->assign('stateList', $stateList['code'] == 1 ? $stateList['data'] : []);
        // 获取经济人总提现金额
        $totalWithdraw = $withdrawLogApi->brokerTotalWithdraw()->getData();
        $this->assign('totalWithdraw', $totalWithdraw['data']);
        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['state']     = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('state', $data['state']);
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
        // 判断该提现申请是否已被管理员操作过
        $withdrawLogApi = new OrgWithdrawController();
        $isWithdraw     = $withdrawLogApi->isWithdrawByAdmin($data['id'])->getData();
        if ($isWithdraw['code'] == 1) {
            echo $isWithdraw['msg'];

            return;
        }

        return $this->fetch();
    }

    /**
     * 获取经济人资金明细
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function account_log()
    {
        $orgAccountLogApi = new OrgAccountLogController();
        $logInfo          = $orgAccountLogApi->getBrokerAccountLog()->getData();
        $this->assign('logInfo', $logInfo['code'] == 1 ? $logInfo['data'] : []);
        // 获取用户账户变动类型常量
        $changeType = $orgAccountLogApi->getChangeType()->getData();
        $this->assign('changeType', $changeType['data']);
        // 获取经济人总变动金额
        $totalChangeMoney = $orgAccountLogApi->brokerTotalChangeMoney()->getData();
        $this->assign('totalChangeMoney', $totalChangeMoney['data']);
        // 查询专用agentList
        $agentList = $this->orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentList['data']);
        // 获取查询提交数据
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date          = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date            = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('agent_id', $data['agent_id']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('change_type', $data['change_type']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);

        return $this->fetch();
    }

    /**
     * 编辑推广码
     *
     * @return mixed
     */
    public function edit_code()
    {
        return $this->fetch();
    }

}
