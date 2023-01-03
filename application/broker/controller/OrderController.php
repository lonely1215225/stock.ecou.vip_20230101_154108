<?php
namespace app\broker\controller;

use app\stock\controller\OrderController AS OrderApi;
use app\stock\controller\OrderPositionController;
use app\stock\controller\OrderTradedController;

class OrderController extends BaseController
{

    // 持仓列表
    public function position()
    {
        // 获取持仓列表
        $orderPositionApi = new OrderPositionController();
        $positionList     = $orderPositionApi->listByBroker()->getData();
        $this->assign('positionList', $positionList['code'] == 1 ? $positionList['data'] : []);
        // 持仓列表详情统计
        $positionStatistic = $orderPositionApi->brokerPositionStatistic()->getData();
        $this->assign('positionStatistic', $positionStatistic['data']);
        $data['stock_code']      = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']          = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id']              = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('id', $data['id']);
        $this->assign('primary_account', $data['primary_account']);

        return $this->fetch();
    }

    // 委托列表
    public function order()
    {
        // 获取委托列表
        $ordeApi   = new OrderApi();
        $orderList = $ordeApi->listByBroker()->getData();
        $this->assign('orderList', $orderList['code'] == 1 ? $orderList['data'] : []);
        // 获取委托单状态常量
        $orderStateList = $ordeApi->orderStateList()->getData();
        $this->assign('orderStateList', $orderStateList['data']);
        // 获取撤单状态常量
        $cancelStateList = $ordeApi->cancelStateList()->getData();
        $this->assign('cancelStateList', $cancelStateList['data']);
        // 获取委托列表统计详情
        $orderStatistic = $ordeApi->brokerOrderStatistic()->getData();
        $this->assign('orderStatistic', $orderStatistic['data']);
        // 获取交易方向列表
        $tradeDirectionList = $ordeApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $stock_code        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $direction         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $primary_account   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $order_position_id = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $this->assign('stock_code', $stock_code);
        $this->assign('direction', $direction);
        $this->assign('mobile', $mobile);
        $this->assign('primary_account', $primary_account);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('order_position_id', $order_position_id);

        return $this->fetch();
    }

    // 成交明细
    public function traded()
    {
        // 获取成交明细列表
        $tradedApi  = new OrderTradedController();
        $tradedList = $tradedApi->listByBroker()->getData();
        $this->assign('tradedList', $tradedList['code'] == 1 ? $tradedList['data'] : []);
        // 获取成交明细详情统计
        $tradedStatistic = $tradedApi->brokerTradedStatistic()->getData();
        $this->assign('tradedStatistic', $tradedStatistic['data']);
        // 获取交易方向列表
        $tradeDirectionList = $tradedApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('direction', $data['direction']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('trading_date', $data['trading_date']);
        $this->assign('start_time', $data['start_time']);
        $this->assign('end_time', $data['end_time']);
        $this->assign('order_position_id', $data['order_position_id']);
        $this->assign('primary_account', $data['primary_account']);

        return $this->fetch();
    }

    // 平仓结算
    public function close_position()
    {
        // 获取平仓列表
        $orderPositionApi  = new OrderPositionController();
        $closePositionList = $orderPositionApi->brokerClosePosition()->getData();
        $this->assign('closePositionList', $closePositionList['code'] == 1 ? $closePositionList['data'] : []);
        // 持仓列表详情统计
        $closePositionStatistic = $orderPositionApi->brokerClosePositionStatistic()->getData();
        $this->assign('closePositionStatistic', $closePositionStatistic['data']);
        $data['stock_code']      = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']          = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id']              = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']      = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']        = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('mobile', $data['mobile']);
        $this->assign('id', $data['id']);
        $this->assign('primary_account', $data['primary_account']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);

        return $this->fetch();
    }

}
