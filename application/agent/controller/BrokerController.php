<?php
namespace app\agent\controller;

use app\stock\controller\OrgFilterController;
use app\stock\controller\UserController AS UserApi;
use app\stock\controller\OrgAccountController;
use app\stock\controller\OrgWithdrawController;
use app\stock\controller\BrokerController AS BrokerApi;
use think\App;

class BrokerController extends BaseController
{

    protected $adminUserApi;
    protected $orgFilter;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserApi = new BrokerApi();
        $this->orgFilter    = new OrgFilterController();
    }

    /**
     * 经纪人信息列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取经济人列表
        $brokerInfo = $this->adminUserApi->getAgentBroker()->getData();
        $this->assign('brokerInfo', $brokerInfo['code'] == 1 ? $brokerInfo['data'] : []);
        $orgAccountApi = new OrgAccountController();
        $orgAccount    = $orgAccountApi->getOrgAccountAll()->getData();
        $this->assign('orgAccount', $orgAccount['code'] == 1 ? $orgAccount['data'] : []);
        // 获取经济人列表统计详情
        $brokerDetail = $this->adminUserApi->AgentBrokerStatistic()->getData();
        $this->assign('brokerDetail', $brokerDetail['data']);
        // 经济人列表
        $brokerList = $this->orgFilter->broker()->getData();
        $this->assign('brokerList', $brokerList['data']);
        $data['id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $this->assign('broker_id', $data['id']);
        $this->assign('mobile', $data['mobile']);

        return $this->fetch();
    }

    /**
     * 添加编辑经济人信息
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_broker()
    {
        // 获取编辑经纪人信息
        $brokerInfo = $this->adminUserApi->getBrokerInfoById()->getData();
        if ($brokerInfo['data']) {
            $this->assign('brokerInfo', $brokerInfo['data']);
        }
        // 获取自己的分成比例
        $selfInfo = $this->adminUserApi->selfCommissionRate()->getData();
        $this->assign('selfInfo', $selfInfo['data']);

        return $this->fetch();
    }

    /**
     * 重置经纪人密码
     *
     * @return mixed
     */
    public function reset_pwd()
    {
        // 获取经济人信息
        $brokerInfo = $this->adminUserApi->getBrokerInfoById()->getData();
        if ($brokerInfo['data']) {
            $this->assign('brokerInfo', $brokerInfo['data']);
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
        $withdrawLog    = $withdrawLogApi->getWithdrawByAdmin()->getData();
        $this->assign('withdrawLog', $withdrawLog['code'] == 1 ? $withdrawLog['data'] : []);
        // 获取提现申请处理状态
        $stateList = $this->adminUserApi->getWithdrawState()->getData();
        $this->assign('stateList', $stateList['code'] == 1 ? $stateList['data'] : []);
        // 获取经济人总提现金额
        $totalWithdraw = $withdrawLogApi->AgentBrokerTotalWithdraw()->getData();
        $this->assign('totalWithdraw', $totalWithdraw['data']);
        // 经济人列表
        $brokerList = $this->orgFilter->broker()->getData();
        $this->assign('brokerInfo', $brokerList['data']);
        $data['id']    = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['state'] = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date    = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date      = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('broker_id', $data['id']);
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
        // 判断该提现申请是否已经被处理
        $withdrawLogApi = new OrgWithdrawController();
        $isWithdraw     = $withdrawLogApi->checkIsWithdraw($data['id'])->getData();
        if ($isWithdraw['code'] == 1) {
            echo $isWithdraw['msg'];

            return;
        }

        return $this->fetch();
    }

}
