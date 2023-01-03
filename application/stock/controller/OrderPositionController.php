<?php

namespace app\stock\controller;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\stock\logic\ForcedSellLogic;
use util\BasicData;
use util\RedisUtil;
use util\OrderRedis;
use util\TradingUtil;
use util\TradingRedis;

class OrderPositionController extends BaseController
{
    /**
     * 获取持仓统计列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['is_monthly'] = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['no_agent_id']) {
            $map['agent_id'] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map['agent_id'] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        if ($data['is_monthly']) {
            $map[] = ['op.is_monthly', '=', $data['is_monthly']];
        }
        // 获取持仓列表
        $orderPositionList = OrderPosition
            ::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee',
                'op.stock_id', 'op.s_pal', 'op.stop_loss_price', 'op.sum_sell_pal', 'u.mobile', 'u.real_name', 'op.primary_account', 'op.is_monthly',
                'op.monthly_expire_date,op.is_cash_coupon'
            ))
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->order('op.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = $Data = [];
        // 系统证券类型 转 上游证券类型
        $marketList = BasicData::MARKET_LIST;
        $securityType = $strategyBalance = [];
        foreach ($marketList as $k => $v) {
            $securityType[$k] = BasicData::marketToSecurityType($k);
        }
        // 从缓存中获取股票详情
        if ($orderPositionList) {
            $strategyBalanceArr = [];
            foreach ($orderPositionList as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
                $strategyBalanceArr[] = $v['user_id'];
                $Data[$k] = $v['stock_code'] . '_' . $securityType[$v['market']];
            }
            $strategyBalanceStr = implode(",", $strategyBalanceArr);

            $sub_strategy = 0;
            $strategyBalance = UserAccount::where('user_id', 'in', $strategyBalanceStr)->column('strategy_balance,frozen', 'user_id');
            foreach ($orderPositionList as $key => $item) {
                $orderPositionList[$key]['strategy'] = bcsub($strategyBalance[$item['user_id']]['strategy_balance'], $strategyBalance[$item['user_id']]['frozen'], 2);
            }
        }

        return $orderPositionList ? $this->message(1, '', ['orderPositionList' => $orderPositionList, 'stockInfo' => $stockInfo, 'securityType' => $securityType, 'webSocket' => json_encode(['key' => 'Subscribe', 'Data' => $Data]), 'strategyBalance' => $strategyBalance, 'num' => count($Data)]) : $this->message(0, '信息为空');
    }

    /**
     * 获取平仓列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function closePosition()
    {
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['exclude_agent'] = input('exclude_agent', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_monthly'] = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        if ($data['is_monthly']) {
            $map[] = ['op.is_monthly', '=', $data['is_monthly']];
        }
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        if ($data['exclude_agent']) {
            $map[0] = ['u.agent_id', 'not in', $data['exclude_agent']];
        }
        if ($data['agent_id']) {
            $map[0] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['start_date']) {
            $map[] = ['op.s_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['op.s_time', '<=', strtotime($data['end_date'])];
        }

        // 获取平仓列表
        $closePositionList = OrderPosition::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee', 'u.mobile',
                'op.stock_id', 'op.s_cost_price', 'op.stop_loss_price', 'op.s_pal', 'op.s_time', 'op.primary_account', 'op.b_cost_price', 'op.is_monthly', 'monthly_expire_date', 'u.real_name',
            ))
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->order('op.s_time desc,op.create_time DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($closePositionList) {
            foreach ($closePositionList as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $closePositionList ? $this->message(1, '', ['orderPositionList' => $closePositionList, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 持仓列表详情统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function positionStatistic()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($data['submit_flag'] == 1) {
            $map['agent_id'] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['no_agent_id']) {
            $map['agent_id'] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        if ($data['agent_id']) {
            $map['agent_id'] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        $positionStatistic = OrderPosition::alias('op')
            ->field('SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_value_in) as sum_sell_value_in,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_buy_value_cost) as sum_buy_value_cost,SUM(op.sum_sell_value) as sum_sell_value,SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.sum_buy_value) as sum_buy_value,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.volume_position) as volume_position,SUM(op.volume_for_sell) as volume_for_sell,SUM(op.volume_today) as volume_today,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_back_profit) as sum_back_profit,SUM(op.xrxd_volume) as sum_xrxd_volume,SUM(op.xrxd_dividend) as sum_xrxd_dividend,SUM(op.s_pal) as totalpal,SUM(op.sum_management_fee) as sum_management_fee,SUM(ua.strategy_balance-ua.frozen) as sum_strategy')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->join(['__USER_ACCOUNT__' => 'ua'], 'ua.user_id=op.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 平仓列表详情统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function closePositionStatistic()
    {
        $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['agent_id'] = input('agent_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['exclude_agent'] = input('exclude_agent', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date'] = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        if ($data['exclude_agent']) {
            $map[0] = ['u.agent_id', 'not in', $data['exclude_agent']];
        }
        if ($data['agent_id']) {
            $map[0] = ['u.agent_id', '=', $data['agent_id']];
        }
        if ($data['start_date']) {
            $map[] = ['op.s_time', '>=', strtotime($data['start_date'])];
        }
        if ($data['end_date']) {
            $map[] = ['op.s_time', '<=', strtotime($data['end_date'])];
        }

        $positionStatistic = OrderPosition::alias('op')
            ->field('COUNT(*) as total,SUM(sum_buy_volume) as sum_buy_volume,SUM(sum_sell_volume) as sum_sell_volume,SUM(sum_buy_value_cost) as sum_buy_value_cost,SUM(sum_sell_value_in) as sum_sell_value_in,SUM(s_pal) as s_pal')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where($map)
            ->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 获取代理商持仓统计列表
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByAgent()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_monthly'] = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        if ($data['is_monthly']) {
            $map[] = ['op.is_monthly', '=', $data['is_monthly']];
        }
        // 获取持仓列表
        $orderPositionList = OrderPosition
            ::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee',
                'op.stock_id', 'op.s_pal', 'op.stop_loss_price', 'op.sum_sell_pal', 'op.is_monthly', 'op.monthly_expire_date', 'u.mobile', 'op.primary_account', 'u.real_name',
            ))
            ->order('op.id DESC')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('u.agent_id', $this->adminId)
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = $Data = [];
        // 系统证券类型 转 上游证券类型
        $marketList = BasicData::MARKET_LIST;
        $securityType = $strategyBalance = [];
        foreach ($marketList as $k => $v) {
            $securityType[$k] = BasicData::marketToSecurityType($k);
        }
        // 从缓存中获取股票详情
        if ($orderPositionList) {
            $strategyBalanceArr = [];
            foreach ($orderPositionList as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
                $strategyBalanceArr[] = $v['user_id'];
                $Data[$k] = $v['stock_code'] . '_' . $securityType[$v['market']];
            }
            $strategyBalanceStr = implode(",", $strategyBalanceArr);
            $strategyBalance = UserAccount::where('user_id', 'in', $strategyBalanceStr)->column('strategy_balance,frozen', 'user_id');
            foreach ($orderPositionList as $key => $item) {
                $orderPositionList[$key]['strategy'] = bcsub($strategyBalance[$item['user_id']]['strategy_balance'], $strategyBalance[$item['user_id']]['frozen'], 2);
            }
        }

        return $orderPositionList ? $this->message(1, '', ['orderPositionList' => $orderPositionList, 'stockInfo' => $stockInfo, 'securityType' => $securityType, 'webSocket' => json_encode(['key' => 'Subscribe', 'Data' => $Data]), 'strategyBalance' => $strategyBalance, 'num' => count($Data)]) : $this->message(0, '信息为空');
    }

    /**
     * 持仓列表详情统计
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentPositionStatistic()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        $positionStatistic = OrderPosition::alias('op')
            ->field('SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_value_in) as sum_sell_value_in,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_buy_value_cost) as sum_buy_value_cost,SUM(op.sum_sell_value) as sum_sell_value,SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.sum_buy_value) as sum_buy_value,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.volume_position) as volume_position,SUM(op.volume_for_sell) as volume_for_sell,SUM(op.volume_today) as volume_today,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_back_profit) as sum_back_profit,SUM(op.xrxd_volume) as sum_xrxd_volume,SUM(op.xrxd_dividend) as sum_xrxd_dividend,SUM(op.s_pal) as totalpal,SUM(op.sum_management_fee) as sum_management_fee,SUM(ua.strategy_balance-ua.frozen) as sum_strategy')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->join(['__USER_ACCOUNT__' => 'ua'], 'ua.user_id=op.user_id')
            ->where('u.agent_id', $this->adminId)
            ->where($map)
            ->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 获取代理商平仓统计列表
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function agentClosePosition()
    {
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        // 获取平仓列表
        $orderPositionList = OrderPosition
            ::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee', 'u.mobile',
                'op.stock_id', 'op.s_cost_price', 'op.stop_loss_price', 'op.s_pal', 'op.s_time', 'op.primary_account', 'u.real_name',
            ))
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('u.agent_id', $this->adminId)
            ->order('op.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($orderPositionList) {
            foreach ($orderPositionList as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $orderPositionList ? $this->message(1, '', ['orderPositionList' => $orderPositionList, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 平仓列表详情统计
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentClosePositionStatistic()
    {
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['u.broker_id', '=', $data['broker_id']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        $positionStatistic = OrderPosition
            ::alias('op')
            ->field('COUNT(*) as total,SUM(sum_buy_volume) as sum_buy_volume,SUM(sum_sell_volume) as sum_sell_volume,SUM(sum_buy_value_cost) as sum_buy_value_cost,SUM(sum_sell_value_in) as sum_sell_value_in,SUM(s_pal) as s_pal')
            ->join(['__USER__' => 'u'], 'op.user_id=u.id')
            ->where('u.agent_id', $this->adminId)
            ->where($map)->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 获取经济人持仓统计列表
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByBroker()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        // 获取持仓列表
        $orderPositionList = OrderPosition
            ::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee',
                'op.stock_id', 'op.s_pal', 'op.stop_loss_price', 'op.sum_sell_pal', 'op.primary_account', 'op.is_monthly', 'op.monthly_expire_date', 'u.mobile', 'u.real_name',
            ))
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('u.broker_id', $this->adminId)
            ->order('op.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = $Data = [];
        // 系统证券类型 转 上游证券类型
        $marketList = BasicData::MARKET_LIST;
        $securityType = $strategyBalance = [];
        foreach ($marketList as $k => $v) {
            $securityType[$k] = BasicData::marketToSecurityType($k);
        }
        // 从缓存中获取股票详情
        if ($orderPositionList) {
            $strategyBalanceArr = [];
            foreach ($orderPositionList->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
                $strategyBalanceArr[] = $v['user_id'];
                $Data[$k] = $v['stock_code'] . '_' . $securityType[$v['market']];
            }
            $strategyBalanceStr = implode(",", $strategyBalanceArr);
            $strategyBalance = UserAccount::where('user_id', 'in', $strategyBalanceStr)->column('strategy_balance,frozen', 'user_id');
            foreach ($orderPositionList as $key => $item) {
                $orderPositionList[$key]['strategy'] = bcsub($strategyBalance[$item['user_id']]['strategy_balance'], $strategyBalance[$item['user_id']]['frozen'], 2);
            }
        }

        return $orderPositionList ? $this->message(1, '', ['orderPositionList' => $orderPositionList, 'stockInfo' => $stockInfo, 'securityType' => $securityType, 'webSocket' => json_encode(['key' => 'Subscribe', 'Data' => $Data]), 'strategyBalance' => $strategyBalance, 'num' => count($Data)]) : $this->message(0, '信息为空');
    }

    /**
     * 持仓列表详情统计
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerPositionStatistic()
    {
        $map[] = ['is_finished', '=', false];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        $positionStatistic = OrderPosition::alias('op')
            ->field('SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_value_in) as sum_sell_value_in,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_buy_value_cost) as sum_buy_value_cost,SUM(op.sum_sell_value) as sum_sell_value,SUM(op.sum_buy_volume) as sum_buy_volume,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.sum_buy_value) as sum_buy_value,SUM(op.sum_sell_volume) as sum_sell_volume,SUM(op.volume_position) as volume_position,SUM(op.volume_for_sell) as volume_for_sell,SUM(op.volume_today) as volume_today,SUM(op.sum_deposit) as sum_deposit,SUM(op.sum_back_profit) as sum_back_profit,SUM(op.s_pal) as totalpal,SUM(op.sum_management_fee) as sum_management_fee')
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('u.broker_id', $this->adminId)
            ->where($map)
            ->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 获取经济人平仓统计列表
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function brokerClosePosition()
    {
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        // 获取持仓列表
        $orderPositionList = OrderPosition
            ::alias('op')
            ->field(array(
                'op.id', 'op.user_id', 'op.market', 'op.stock_code', 'op.sum_buy_volume', 'op.sum_sell_volume', 'op.volume_position',
                'op.volume_today', 'op.volume_for_sell', 'op.position_price', 'op.sum_buy_value', 'op.sum_buy_value_cost',
                'op.sum_sell_value', 'op.sum_sell_value_in', 'op.sum_deposit', 'op.last_close_price', 'op.sum_back_profit',
                'op.xrxd_volume', 'op.xrxd_dividend', 'op.is_suspended', 'op.suspension_days', 'op.update_time', 'op.is_finished', 'op.sum_management_fee', 'u.mobile',
                'op.stock_id', 'op.s_cost_price', 'op.stop_loss_price', 'op.s_pal', 'op.s_time', 'op.primary_account', 'u.real_name',
            ))
            ->join(['__USER__' => 'u'], 'u.id=op.user_id')
            ->where('u.broker_id', $this->adminId)
            ->order('op.id DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = [];
        // 从缓存中获取股票详情
        if ($orderPositionList) {
            foreach ($orderPositionList as $key => $item) {
                $stockInfo[$item['market'] . $item['stock_code']] = RedisUtil::getStockData($item['stock_code'], $item['market']);
            }
        }

        return $orderPositionList ? $this->message(1, '', ['orderPositionList' => $orderPositionList, 'stockInfo' => $stockInfo]) : $this->message(0, '信息为空');
    }

    /**
     * 平仓列表详情统计
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerClosePositionStatistic()
    {
        $map[] = ['is_finished', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['mobile'] = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['id'] = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['primary_account'] = input('primary_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['op.stock_code', '=', $data['stock_code']];
        }
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['id']) {
            $map[] = ['op.id', '=', $data['id']];
        }
        if ($data['primary_account']) {
            $map[] = ['op.primary_account', '=', $data['primary_account']];
        }
        $positionStatistic = OrderPosition
            ::alias('op')
            ->field('COUNT(*) as total,SUM(sum_buy_volume) as sum_buy_volume,SUM(sum_sell_volume) as sum_sell_volume,SUM(sum_buy_value_cost) as sum_buy_value_cost,SUM(sum_sell_value_in) as sum_sell_value_in,SUM(s_pal) as s_pal')
            ->join(['__USER__' => 'u'], 'op.user_id=u.id')
            ->where('u.broker_id', $this->adminId)
            ->where($map)->find();

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 单笔持仓强平
     * @return bool|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException]
     */
    public function forcedSell()
    {
        // 检测交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return $this->message(0, '当前日期不是交易日');

        // 检测是否在交易时间内
        if (!TradingUtil::isInTradingTime()) return $this->message(0, '不在交易时间内');

        $positionID = input('positionID', '', FILTER_SANITIZE_NUMBER_INT);
        if (!$positionID) return $this->message(0, '参数错误');

        // 获取持仓数据
        $positionData = OrderRedis::getPosition($positionID);
        if (!$positionData) return $this->message(0, '未获得持仓数据');

        $userID = $positionData['user_id'];
        $market = $positionData['market'];
        $stockCode = $positionData['stock_code'];
        $volumePosition = $positionData['volume_position'];

        // 如果持仓为0，不检测
        if ($volumePosition == 0) return $this->message(0, '持仓数量为0不能平仓');

        // 取最新价
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        $nowPrice = $quotation['Price'];

        // 当前市值
        $nowStockValue = round(bcmul($nowPrice, $volumePosition, 4), 2);

        // 获取用户账户信息
        $uAccount = UserAccount::where('user_id', $userID)->field('strategy_balance,frozen')->find();
        if (!$uAccount) return false;

        // 获取持仓的信息
        $position = OrderPosition::where('id', $positionID)
            ->where('is_finished', false)
            ->field('id,user_id,market,stock_code,volume_position,volume_for_sell,is_suspended,stop_loss_price')
            ->find();
        if (!$position) return $this->message(0, '未获取持仓的信息');

        // 二次检测，如果不满足条件，不执行强平
        $strategyBalance = $uAccount['strategy_balance'];
        $frozen = $uAccount['frozen'];
        $strategy = bcsub($strategyBalance, $frozen, 2);

        // 触发强平时的用户账户信息
        $userAccount = [
            'user_id' => $userID,
            'strategy_balance' => $uAccount['strategy_balance'],
            'frozen' => $uAccount['frozen'],
            'strategy' => $strategy,
            'additional_deposit' => 0,
        ];

        // 触发源持仓信息
        $originPosition = [
            'position_id' => $position['id'],
            'stock' => $position['market'] . $position['stock_code'],
            'stock_value' => $nowStockValue,
            'volume_position' => $position['volume_position'],
            'volume_for_sell' => $position['volume_for_sell'],
            'price' => $nowPrice,
            'stop_loss_price' => $position['stop_loss_price'],
            'is_suspended' => $position['is_suspended'],
        ];

        // 执行强平
        $result = ForcedSellLogic::forcedSell($positionID, $originPosition, $userAccount);
        if ($result === true) {
            return $this->message(1, '强平成功');
        } else {
            return $this->message(0, $result);
        }
    }

}
