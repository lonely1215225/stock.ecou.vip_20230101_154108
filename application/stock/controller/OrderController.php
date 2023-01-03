<?php
namespace app\stock\controller;

use app\common\model\OrderPosition;
use app\common\model\Order;
use think\App;
use util\BasicData;
use util\RedisUtil;

class OrderController extends BaseController
{

    protected $orderModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orderModel = new Order();
    }

    /**
     * 获取委托信息列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id']       = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag']       = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['is_monthly']        = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['is_monthly']) {
            $map[] = ['o.is_monthly', '=', $data['is_monthly']];
        }
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }

        // 获取委托列表信息
        $orderList = $this->orderModel->alias('o')
            ->field('o.update_time,o.primary_account,o.order_position_id,o.create_time,o.id,o.primary_account,o.user_id,o.market,o.stock_id,o.stock_code,o.direction,o.price,o.price_type,o.volume,o.volume_success,o.state,o.is_system,o.order_sn,o.trading_date,o.cancel_state,o.cancel_type,o.is_monthly,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'u.id=o.user_id')->order('o.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = [];
        if ($orderList) {
            foreach ($orderList as &$item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
                $item['monthly_expire_date'] = $item['is_monthly'] ? ($item['order_position_id'] ? OrderPosition::where("id={$item['order_position_id']}")->value('monthly_expire_date') : '') : '';
            }
        }

        return $orderList ? $this->message(1, '', ['orderlist' => $orderList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取委托单状态常量
     *
     * @return \think\response\Json
     */
    public function orderStateList()
    {
        return $this->message(1, '', BasicData::ORDER_STATE_LIST);
    }

    /**
     * 撤单状态列表
     *
     * @return \think\response\Json
     */
    public function cancelStateList()
    {
        return $this->message(1, '', BasicData::CANCEL_STATE_LIST);
    }

    /**
     * 获取委托列表统计详情
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderStatistic()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id']          = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id']       = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag']       = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map[] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }
        // 获取委托列表详情统计
        $orderStatistic = $this->orderModel->alias('o')
            ->field('SUM(o.price) as totalPrice,SUM(o.volume) as totalVolume,SUM(o.volume_success) as totalSuccess')
            ->join(['__USER__' => 'u'], 'u.id=o.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $orderStatistic);
    }

    /**
     * 获取代理商委托信息列表
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByAgent()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['is_monthly']        = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);

        if ($data['is_monthly']) {
            $map[] = ['o.is_monthly', '=', $data['is_monthly']];
        }
        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }

        // 获取委托列表信息
        $orderList = $this->orderModel->alias('o')
            ->field('o.id,o.update_time,o.primary_account,o.order_position_id,o.create_time,o.primary_account,o.user_id,o.market,o.stock_id,o.stock_code,o.direction,o.price,o.price_type,o.volume,o.volume_success,o.state,o.is_system,o.order_sn,o.trading_date,o.cancel_state,o.is_monthly,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'o.user_id=u.id')->order('o.id DESC')
            ->where('u.agent_id', $this->adminId)
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        $stockInfo = [];
        if ($orderList) {
            foreach ($orderList as &$item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
                $item['monthly_expire_date'] = $item['is_monthly'] ? ($item['order_position_id'] ? OrderPosition::where("id={$item['order_position_id']}")->value('monthly_expire_date') : '') : '';
            }
        }

        return $orderList ? $this->message(1, '', ['orderlist' => $orderList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取代理商委托列表统计详情
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentOrderStatistic()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);

        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }
        // 获取委托列表详情统计
        $orderStatistic = $this->orderModel->alias('o')
            ->field('COUNT(*) as total,SUM(price) as totalPrice,SUM(volume) as totalVolume,SUM(volume_success) as totalSuccess')
            ->join(['__USER__' => 'u'], 'u.id=o.user_id')
            ->where('u.agent_id', $this->adminId)
            ->where($map)
            ->find();

        return $this->message(1, '', $orderStatistic);
    }

    /**
     * 获取经济人委托信息列表
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByBroker()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }
        // 获取委托列表信息
        $orderList = $this->orderModel->alias('o')
            ->field('o.id,o.update_time,o.primary_account,o.order_position_id,o.create_time,o.primary_account,o.user_id,o.market,o.stock_id,o.stock_code,o.direction,o.price,o.price_type,o.volume,o.volume_success,o.state,o.is_system,o.order_sn,o.trading_date,o.cancel_state,u.mobile,u.real_name')
            ->join(['__USER__' => 'u'], 'o.user_id=u.id')->order('o.id DESC')
            ->where('u.broker_id', $this->adminId)
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $orderList = $orderList ?: [];
        $stockInfo = [];
        if ($orderList) {
            foreach ($orderList as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $orderList ? $this->message(1, '', ['orderlist' => $orderList, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取经济人委托列表统计详情
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerOrderStatistic()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account']   = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']        = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']          = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['o.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['o.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['primary_account']) {
            $map[] = ['o.primary_account', '=', $data['primary_account']];
        }
        if ($data['order_position_id']) {
            $map[] = ['o.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['start_date']) {
            $map[] = ['o.create_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['o.create_time', '<=', strtotime($data['end_date'])];
        }
        // 获取委托列表详情统计
        $orderStatistic = $this->orderModel->alias('o')
            ->field('COUNT(*) as total,SUM(price) as totalPrice,SUM(volume) as totalVolume,SUM(volume_success) as totalSuccess')
            ->join(['__USER__' => 'u'], 'u.id=o.user_id')
            ->where('u.broker_id', $this->adminId)
            ->where($map)
            ->find();

        return $this->message(1, '', $orderStatistic);
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
