<?php
namespace app\dash\controller;

use app\stock\controller\IndexStatisticsController;

class IndexController extends BaseController
{

    /**
     * 管理控制台首页
     *
     * @return mixed
     */
    public function index()
    {
        //获取今日注册人数
        $indexStatisticsModel = new IndexStatisticsController();
        $registeredToday      = $indexStatisticsModel->registeredToday()->getData();
        $this->assign('registeredToday', $registeredToday['data']);
        // 获取总注册人数
        $registeredTotal = $indexStatisticsModel->registeredTotal()->getData();
        $this->assign('registeredTotal', $registeredTotal['data']);
        // 今日累计充值金额
        $userRechargeToday = $indexStatisticsModel->userRechargeToday()->getData();
        $this->assign('userRechargeToday', $userRechargeToday['data']);
        // 今日提现成功总金额
        $userWithdrawToday = $indexStatisticsModel->userWithdrawToday()->getData();
        $this->assign('userWithdrawToday', $userWithdrawToday['data']);
        // 获取持仓列表统计详情
        $positionStatistics = $indexStatisticsModel->positionStatistics()->getData();
        $this->assign('positionStatistics', $positionStatistics['data']);
        // 成交明细统计
        $tradedStatistics = $indexStatisticsModel->TradedStatistics()->getData();
        $this->assign('tradedStatistics', $tradedStatistics['data']);
        // 总成交明细
        $tradedTotal = $indexStatisticsModel->TradedTotal()->getData();
        $this->assign('tradedTotal', $tradedTotal['data']);
        // 佣金明细统计
        $incomeStatistics = $indexStatisticsModel->incomeStatistics()->getData();
        $this->assign('incomeStatistics', $incomeStatistics['data']);
        // 总佣金明细
        $incomeTotal = $indexStatisticsModel->incomeTotal()->getData();
        $this->assign('incomeTotal', $incomeTotal['data']);
        // 获取所有用户账户资金、策略金余额
        $userAccount = $indexStatisticsModel->userAccount()->getData();
        $this->assign('userAccount', $userAccount['data']);
        // 获取累计充值金额
        $totalRecharge = $indexStatisticsModel->totalRecharge()->getData();
        $this->assign('totalRecharge', $totalRecharge['data']);
        // 获取累计提现金额
        $totalWithdraw = $indexStatisticsModel->totalWithdraw()->getData();
        $this->assign('totalWithdraw', $totalWithdraw['data']);

        return $this->fetch();
    }

}
