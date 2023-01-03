<?php
namespace app\dash\controller;

use app\stock\controller\AdminIncomeController;
use app\stock\controller\OrgFilterController;

class IncomeController extends BaseController
{

    /**
     * 佣金明细列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 佣金明细列表
        $adminIncomeApi = new AdminIncomeController();
        $incomeList     = $adminIncomeApi->index()->getData();
        $this->assign('incomeList', $incomeList['code'] == 1 ? $incomeList['data'] : '');

        // 佣金收入类型
        $incomeType = $adminIncomeApi->orgIncomeTypeList()->getData();
        $this->assign('incomeType', $incomeType['data']);

        // 获取所有代理商列表
        $orgFilterApi = new OrgFilterController();
        $agentInfo    = $orgFilterApi->agent()->getData();
        $this->assign('agentInfo', $agentInfo['code'] == 1 ? $agentInfo['data'] : []);

        // 获取佣金明细汇总
        $adminStatistic = $adminIncomeApi->adminStatistic()->getData();
        $this->assign('adminStatistic', $adminStatistic['data']);
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
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
        $this->assign('income_type', $data['income_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

}
