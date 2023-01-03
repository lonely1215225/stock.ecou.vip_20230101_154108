<?php
namespace app\agent\controller;

use app\stock\controller\OrderController AS OrderApi;
use app\stock\controller\OrderPositionController;
use app\stock\controller\OrderTradedController;
use app\stock\controller\OrgFilterController;
use app\stock\controller\UserController AS UserApi;

class OrderController extends BaseController
{

    // 持仓列表
    public function position()
    {
        // 获取持仓列表
        $orderPositionApi = new OrderPositionController();
        $positionList     = $orderPositionApi->listByAgent()->getData();
        $this->assign('positionList', $positionList['code'] == 1 ? $positionList['data'] : []);
        // 持仓列表详情统计
        $positionStatistic = $orderPositionApi->agentPositionStatistic()->getData();
        $this->assign('positionStatistic', $positionStatistic['data']);
        // 经济人列表
        $orgFilterApi = new OrgFilterController();
        $brokerList   = $orgFilterApi->broker()->getData();
        $this->assign('brokerList', $brokerList['data']);
        $data['stock_code']      = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']          = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']       = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id']              = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('id', $data['id']);
        $this->assign('primary_account', $data['primary_account']);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 委托列表
    public function order()
    {
        // 获取委托列表
        $ordeApi   = new OrderApi();
        $orderList = $ordeApi->listByAgent()->getData();
        $this->assign('orderList', $orderList['code'] == 1 ? $orderList['data'] : []);
        // 获取委托单状态常量
        $orderStateList = $ordeApi->orderStateList()->getData();
        $this->assign('orderStateList', $orderStateList['data']);
        // 获取委托列表统计详情
        $orderStatistic = $ordeApi->agentOrderStatistic()->getData();
        $this->assign('orderStatistic', $orderStatistic['data']);
        // 获取撤单状态常量
        $cancelStateList = $ordeApi->cancelStateList()->getData();
        $this->assign('cancelStateList', $cancelStateList['data']);
        // 经济人列表
        $orgFilterApi = new OrgFilterController();
        $brokerList   = $orgFilterApi->broker()->getData();
        $this->assign('brokerList', $brokerList['data']);
        // 获取交易方向列表
        $tradeDirectionList = $ordeApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('direction', $data['direction']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('primary_account', $data['primary_account']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);
        $this->assign('order_position_id', $data['order_position_id']);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 成交明细
    public function traded()
    {
        // 获取成交明细列表
        $tradedApi  = new OrderTradedController();
        $tradedList = $tradedApi->listByAgent()->getData();
        $this->assign('tradedList', $tradedList['code'] == 1 ? $tradedList['data'] : []);
        // 获取成交明细详情统计
        $tradedStatistic = $tradedApi->agentTradedStatistic()->getData();
        $this->assign('tradedStatistic', $tradedStatistic['data']);
        // 经济人列表
        $orgFilterApi = new OrgFilterController();
        $brokerList   = $orgFilterApi->broker()->getData();
        $this->assign('brokerList', $brokerList['data']);
        // 获取交易方向列表
        $tradeDirectionList = $tradedApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $is_monthly                = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('direction', $data['direction']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('trading_date', $data['trading_date']);
        $this->assign('start_time', $data['start_time']);
        $this->assign('end_time', $data['end_time']);
        $this->assign('order_position_id', $data['order_position_id']);
        $this->assign('primary_account', $data['primary_account']);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 平仓结算
    public function close_position()
    {
        // 获取平仓列表
        $orderPositionApi  = new OrderPositionController();
        $closePositionList = $orderPositionApi->agentClosePosition()->getData();
        $this->assign('closePositionList', $closePositionList['code'] == 1 ? $closePositionList['data'] : []);
        // 持仓列表详情统计
        $closePositionStatistic = $orderPositionApi->agentClosePositionStatistic()->getData();
        $this->assign('closePositionStatistic', $closePositionStatistic['data']);
        // 经济人列表
        $orgFilterApi = new OrgFilterController();
        $brokerList   = $orgFilterApi->broker()->getData();
        $this->assign('brokerList', $brokerList['data']);
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']     = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']  = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id']         = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('broker_id', $data['broker_id']);
        $this->assign('id', $data['id']);

        return $this->fetch();
    }

}
