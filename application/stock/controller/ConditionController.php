<?php
namespace app\stock\controller;

use app\common\model\Condition;
use util\BasicData;
use util\RedisUtil;

class ConditionController extends BaseController
{

    /**
     * 获取条件单列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取查询提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile']            = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date']      = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['order_position_id'] = input('order_position_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['state']             = input('state', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']          = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']         = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['exclude_agent']     = input('exclude_agent', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_monthly']        = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag']       = input('submit_flag',1,FILTER_SANITIZE_NUMBER_INT);
        $map = [];
        if($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['is_monthly']) {
            $map[] = ['o.is_monthly', '=', $data['is_monthly']];
        }
        if ($data['stock_code']) {
            $map[] = ['c.stock_code', '=', $data['stock_code']];
        }
        if ($data['direction']) {
            $map[] = ['c.direction', '=', $data['direction']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }

        if ($data['trading_date']) {
            $map[] = ['c.trading_date', '=', $data['trading_date']];
        }
        if ($data['order_position_id']) {
            $map[] = ['c.order_position_id', '=', $data['order_position_id']];
        }
        if ($data['state']) {
            $map[] = ['c.state', '=', $data['state']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['exclude_agent']) {
            $map[0] = ['u.agent_id', 'not in', $data['exclude_agent']];
        }
        if ($data['agent_id']) {
            $map[0] = ['u.agent_id', '=', $data['agent_id']];
        }
        $list = Condition::alias('c')
            ->field('c.id,c.user_id,c.state,c.trigger_compare,c.order_position_id,c.trigger_price,c.create_time,c.price,c.price_type,c.volume,c.order_id,c.direction,c.trigger_time,c.market,c.stock_code,c.trading_date,c.remark,u.real_name,u.mobile')
            ->where($map)
            ->join(['__USER__' => 'u'], 'u.id=c.user_id')
            ->order('c.create_time DESC')
            ->paginate(15, false, ['query' => $this->request->param()]);
        // 从缓存中获取股票详情
        $stockInfo = [];
        if ($list) {
            foreach ($list as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $list ? $this->message(1, '', ['list' => $list, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 条件单状态列表
     *
     * @return \think\response\Json
     */
    public function condition_state_list()
    {
        return $this->message(1, '', BasicData::CONDITION_STATE_LIST);
    }

    /**
     * 委托价类型
     *
     * @return \think\response\Json
     */
    public function price_type_list()
    {
        return $this->message(1, '', BasicData::PRICE_TYPE_LIST);
    }

    /**
     * 委托方向列表
     *
     * @return \think\response\Json
     */
    public function trade_direction_list()
    {
        return $this->message(1, '', BasicData::TRADE_DIRECTION_LIST);
    }

    /**
     * 触发价比较条件列表
     *
     * @return \think\response\Json
     */
    public function condition_compare_list()
    {
        return $this->message(1, '', BasicData::CONDITION_COMPARE_LIST);
    }

}
