<?php
namespace app\broker\controller;

use app\stock\controller\AdminIncomeController;

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
        $incomeList     = $adminIncomeApi->listByBroker()->getData();
        $this->assign('incomeList', $incomeList['code'] == 1 ? $incomeList['data'] : '');
        // 佣金收入类型
        $incomeType = $adminIncomeApi->orgIncomeTypeList()->getData();
        $this->assign('incomeType', $incomeType['data']);
        // 获取佣金明细汇总
        $brokerStatistic = $adminIncomeApi->brokerStatistic()->getData();
        $this->assign('brokerStatistic', $brokerStatistic['data']);
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('income_type', $data['income_type']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

}
