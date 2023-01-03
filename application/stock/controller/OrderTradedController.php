<?php
namespace app\stock\controller;

use app\common\model\OrderTraded;
use app\common\model\OrderPosition;
use think\App;
use util\RedisUtil;
use util\BasicData;

class OrderTradedController extends BaseController
{

    protected $orderTradedModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orderTradedModel = new OrderTraded();
    }

    /**
     * 获取成交明细列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map          = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id']       = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag']       = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['is_monthly']        = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['is_monthly']) {
            $map[] = ['t.is_monthly', '=', $data['is_monthly']];
        }
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.create_time', '>=', strtotime($data['start_time'])];
        }
        if ($data['end_time']) {
            $map[] = ['t.create_time', '<=', strtotime($data['end_time'])];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        // 获取成交明细列表
        $orderTradedList = $this->orderTradedModel
            ->alias('t')
            ->field('t.id,t.order_position_id,t.primary_account,t.order_id,t.create_time,t.update_time,t.user_id,t.primary_account,t.direction,t.market,t.stock_id,t.stock_code,t.trading_date,t.trading_time,t.price,t.cost_price,t.volume,t.traded_value,t.buy_value_cost,t.sell_value_in,t.deposit,t.total_fee,t.service_fee,t.stamp_tax,t.transfer_fee,t.traded_sn,t.management_fee,t.is_monthly,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->order('t.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo       = [];
        if ($orderTradedList) {
            foreach ($orderTradedList as &$item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
                $item['monthly_expire_date'] = $item['is_monthly'] ? ($item['order_position_id'] ? OrderPosition::where("id={$item['order_position_id']}")->value('monthly_expire_date') : '') : '';
            }
        }

        return $orderTradedList ? $this->message(1, '', ['orderTradedList' => $orderTradedList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 成交明细详情统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderTradedStatistic()
    {
        $map          = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id']       = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag']       = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.create_time', '>=', strtotime($data['start_time'])];
        }
        if ($data['end_time']) {
            $map[] = ['t.create_time', '<=', strtotime($data['end_time'])];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        // 成交明细详情统计
        $tradedStatistic = $this->orderTradedModel->alias('t')
            ->field('SUM(t.volume) as volume,SUM(t.total_fee) as total_fee,SUM(t.traded_value) as traded_value,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in,SUM(t.deposit) as deposit,SUM(t.management_fee) as management_fee,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $tradedStatistic);
    }

    /**
     * 获取代理商成交明细列表
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByAgent()
    {
        $map[] = ['u.agent_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_monthly']        = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['is_monthly']) {
            $map[] = ['t.is_monthly', '=', $data['is_monthly']];
        }
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.trading_time', '>=', $data['start_time']];
        }
        if ($data['end_time']) {
            $map[] = ['t.trading_time', '<=', $data['end_time']];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        // 获取成交明细列表
        $orderTradedList = $this->orderTradedModel
            ->alias('t')
            ->field('t.id,t.order_position_id,t.order_id,t.create_time,t.update_time,t.user_id,t.primary_account,t.direction,t.market,t.stock_id,t.stock_code,t.trading_date,t.trading_time,t.price,t.cost_price,t.volume,t.traded_value,t.buy_value_cost,t.sell_value_in,t.deposit,t.total_fee,t.service_fee,t.stamp_tax,t.transfer_fee,t.traded_sn,t.management_fee,t.is_monthly,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->order('t.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo       = [];
        if ($orderTradedList) {
            foreach ($orderTradedList as &$item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
                $item['monthly_expire_date'] = $item['is_monthly'] ? ($item['order_position_id'] ? OrderPosition::where("id={$item['order_position_id']}")->value('monthly_expire_date') : '') : '';
            }
        }

        return $orderTradedList ? $this->message(1, '', ['orderTradedList' => $orderTradedList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 代理商成交明细详情统计
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentTradedStatistic()
    {
        $map[] = ['u.agent_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.trading_time', '>=', $data['start_time']];
        }
        if ($data['end_time']) {
            $map[] = ['t.trading_time', '<=', $data['end_time']];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        // 成交明细详情统计
        $tradedStatistic = $this->orderTradedModel
            ->alias('t')
            ->field('SUM(t.volume) as volume,SUM(t.total_fee) as total_fee,SUM(t.traded_value) as traded_value,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in,SUM(t.deposit) as deposit,SUM(t.management_fee) as management_fee,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $tradedStatistic);
    }

    /**
     * 获取经济人成交明细列表
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByBroker()
    {
        $map[] = ['u.broker_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.trading_time', '>=', $data['start_time']];
        }
        if ($data['end_time']) {
            $map[] = ['t.trading_time', '<=', $data['end_time']];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        // 获取成交明细列表
        $orderTradedList = $this->orderTradedModel
            ->alias('t')
            ->field('t.id,t.order_position_id,t.primary_account,t.order_id,t.create_time,t.update_time,t.user_id,t.primary_account,t.direction,t.market,t.stock_id,t.stock_code,t.trading_date,t.trading_time,t.price,t.cost_price,t.volume,t.traded_value,t.buy_value_cost,t.sell_value_in,t.deposit,t.total_fee,t.service_fee,t.stamp_tax,t.transfer_fee,t.traded_sn,t.management_fee,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->order('t.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo       = [];
        if ($orderTradedList) {
            foreach ($orderTradedList as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $orderTradedList ? $this->message(1, '', ['orderTradedList' => $orderTradedList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 经济人成交明细详情统计
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerTradedStatistic()
    {
        $map[] = ['u.broker_id', '=', $this->adminId];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_time']        = input('start_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_time']          = input('end_time', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['t.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['t.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['trading_date']) {
            $map[] = ['t.trading_date', '=', $data['trading_date']];
        }
        if ($data['start_time']) {
            $map[] = ['t.trading_time', '>=', $data['start_time']];
        }
        if ($data['end_time']) {
            $map[] = ['t.trading_time', '<=', $data['end_time']];
        }
        if ($data['order_position_id']) {
            $map[] = ['t.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['t.primary_account', '=', $data['primary_account']];
        }
        // 成交明细详情统计
        $tradedStatistic = $this->orderTradedModel
            ->alias('t')
            ->field('SUM(t.volume) as volume,SUM(t.total_fee) as total_fee,SUM(t.traded_value) as traded_value,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in,SUM(t.deposit) as deposit,SUM(t.management_fee) as management_fee,SUM(t.buy_value_cost) as buy_value_cost,SUM(t.sell_value_in) as sell_value_in')
            ->join(['__USER__' => 'u'], 'u.id=t.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $tradedStatistic);
    }

    /**
     * 交易方向列表
     *
     * @return \think\response\Json
     */
    public function tradeDirectionList()
    {
        return $this->message(1, '', BasicData::TRADE_DIRECTION_LIST);
    }

}
