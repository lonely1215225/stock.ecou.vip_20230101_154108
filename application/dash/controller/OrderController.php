<?php

namespace app\dash\controller;

use app\stock\controller\ConditionController;
use app\stock\controller\ForcedSellLogController;
use app\stock\controller\OrderController AS OrderApi;
use app\stock\controller\OrderPositionController;
use app\stock\controller\OrderTradedController;
use app\stock\controller\OrgFilterController;
use app\common\model\OrderPosition;
use util\OrderRedis;


class OrderController extends BaseController
{

    // 持仓列表
    public function position()
    {
        // 获取持仓列表
        $orderPositionApi = new OrderPositionController();
        $positionList = $orderPositionApi->index()->getData();
        $this->assign('positionList', $positionList['code'] == 1 ? $positionList['data'] : []);
        // 持仓列表详情统计
        $positionStatistic = $orderPositionApi->positionStatistic()->getData();
        $this->assign('positionStatistic', $positionStatistic['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $agent_id = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $broker_id = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $primary_account = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stock_code', $stock_code);
        $this->assign('mobile', $mobile);
        $this->assign('agent_id', $agent_id);
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('id', $id);
        $this->assign('stock_code', $stock_code);
        $this->assign('primary_account', $primary_account);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 委托列表
    public function order()
    {
        // 获取委托列表
        $ordeApi = new OrderApi();
        $orderList = $ordeApi->index()->getData();
        $this->assign('orderList', $orderList['code'] == 1 ? $orderList['data'] : []);
        // 获取委托单状态常量
        $orderStateList = $ordeApi->orderStateList()->getData();
        $this->assign('orderStateList', $orderStateList['data']);
        // 获取委托列表统计详情
        $orderStatistic = $ordeApi->orderStatistic()->getData();
        $this->assign('orderStatistic', $orderStatistic['data']);
        // 获取撤单状态常量
        $cancelStateList = $ordeApi->cancelStateList()->getData();
        $this->assign('cancelStateList', $cancelStateList['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);
        // 获取交易方向列表
        $tradeDirectionList = $ordeApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $direction = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $agent_id = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $broker_id = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $primary_account = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $order_position_id = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('stock_code', $stock_code);
        $this->assign('direction', $direction);
        $this->assign('mobile', $mobile);
        $this->assign('agent_id', $agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('primary_account', $primary_account);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('order_position_id', $order_position_id);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 成交明细
    public function traded()
    {
        // 获取成交明细列表
        $tradedApi = new OrderTradedController();
        $tradedList = $tradedApi->index()->getData();
        $this->assign('tradedList', $tradedList['code'] == 1 ? $tradedList['data'] : []);
        // 获取成交明细详情统计
        $tradedStatistic = $tradedApi->orderTradedStatistic()->getData();
        $this->assign('tradedStatistic', $tradedStatistic['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);
        // 获取交易方向列表
        $tradeDirectionList = $tradedApi->tradeDirectionList()->getData();
        $this->assign('tradeDirectionList', $tradeDirectionList['data']);
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $direction = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $agent_id = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $broker_id = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $trading_date = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_time = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_time = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $order_position_id = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $primary_account = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('stock_code', $stock_code);
        $this->assign('direction', $direction);
        $this->assign('mobile', $mobile);
        $this->assign('agent_id', $agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('trading_date', $trading_date);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('order_position_id', $order_position_id);
        $this->assign('primary_account', $primary_account);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    // 平仓结算
    public function close_position()
    {
        // 获取平仓列表
        $orderPositionApi = new OrderPositionController();
        $closePositionList = $orderPositionApi->closePosition()->getData();
        $this->assign('closePositionList', $closePositionList['code'] == 1 ? $closePositionList['data'] : []);
        // 持仓列表详情统计
        $closePositionStatistic = $orderPositionApi->closePositionStatistic()->getData();
        $this->assign('closePositionStatistic', $closePositionStatistic['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $agent_id = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $broker_id = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $primary_account = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $start_date = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $end_date = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('stock_code', $stock_code);
        $this->assign('mobile', $mobile);
        $this->assign('agent_id', $agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('id', $id);
        $this->assign('primary_account', $primary_account);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    /**
     * 获取条件单列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function condition()
    {
        // 获取条件单列表
        $conditionApi = new ConditionController();
        $list = $conditionApi->index()->getData();
        $this->assign('list', $list['code'] == 1 ? $list['data'] : '');
        // 条件单状态列表
        $stateList = $conditionApi->condition_state_list()->getData();
        $this->assign('stateList', $stateList['data']);
        // 委托价类型
        $priceType = $conditionApi->price_type_list()->getData();
        $this->assign('priceType', $priceType['data']);
        // 委托方向列表
        $directionList = $conditionApi->trade_direction_list()->getData();
        $this->assign('directionList', $directionList['data']);
        // 触发价比较条件
        $compareList = $conditionApi->condition_compare_list()->getData();
        $this->assign('compareList', $compareList['data']);
        // 代理商列表
        $OrgFilterApi = new OrgFilterController();
        $agentList = $OrgFilterApi->agent()->getData();
        $this->assign('agentList', $agentList['data']);
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $direction = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $trading_date = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $order_position_id = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $state = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $agent_id = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $broker_id = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $no_agent_id = input('no_agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $submit_flag = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($submit_flag == 1) {
            $no_agent_id = EXCLUDE_AGENT;
        }
        $is_monthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('no_agent_id', $no_agent_id);
        $this->assign('stock_code', $stock_code);
        $this->assign('direction', $direction);
        $this->assign('mobile', $mobile);
        $this->assign('trading_date', $trading_date);
        $this->assign('order_position_id', $order_position_id);
        $this->assign('state', $state);
        $this->assign('agent_id', $agent_id);
        $this->assign('broker_id', $broker_id);
        $this->assign('is_monthly', $is_monthly);

        return $this->fetch();
    }

    /**
     * 强平记录
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function forced_sell_log()
    {
        // 强平记录列表
        $forcedApi = new ForcedSellLogController();
        $list = $forcedApi->index()->getData();
        $this->assign('list', $list['data']);
        // 强平类型
        $typeList = $forcedApi->forced_sell_type_list()->getData();
        $this->assign('typeList', $typeList['data']);
        // 强平顺序
        $sellOrder = $forcedApi->forced_sell_order_list()->getData();
        $this->assign('sellOrder', $sellOrder['data']);
        $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $trading_date = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $position_id = input('position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $this->assign('mobile', $mobile);
        $this->assign('trading_date', $trading_date);
        $this->assign('position_id', $position_id);

        return $this->fetch();
    }

}
