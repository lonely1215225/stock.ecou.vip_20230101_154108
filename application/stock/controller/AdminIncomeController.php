<?php
namespace app\stock\controller;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use think\db\Query;
use util\BasicData;
use util\RedisUtil;

class AdminIncomeController extends BaseController
{

    /**
     * 佣金明细列表
     * 总后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map = [];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['ai.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['ai.broker_id', '=', $data['broker_id']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        // 获取佣金明细列表
        $list = AdminIncome::alias('ai')
            ->field([
                'ai.user_id', 'ai.order_position_id', 'ai.order_traded_id', 'ai.income_type',
                'ai.market', 'ai.stock_code', 'ai.stock_value', 'ai.platform_id', 'ai.platform_money',
                'ai.agent_id', 'ai.agent_money', 'ai.broker_id', 'ai.broker_money', 'ai.is_return',
                'ai.money', 'ai.income_time', 'u.mobile', 'u.real_name',
            ])
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')
            ->order('ai.income_time DESC')
            ->paginate(15, false, ['query' => request()->param()]);

        // 获取经纪人信息、代理商信息、客户信息
        $brokerInfo = $agentInfo = $stockInfo = [];
        if ($list) {
            // 获取经济人信息
            $broker_arr = array_column($list->getCollection()->toArray(), 'broker_id');
            $brokerInfo = [];
            if ($broker_arr) {
                $brokerInfo = AdminUser::where('id', 'in', $broker_arr)->column('username', 'id');
            }

            // 获取代理商信息
            $agent_arr = array_column($list->getCollection()->toArray(), 'agent_id');
            $agentInfo = [];
            if ($agent_arr) {
                $agentInfo = AdminUser::where('id', 'in', $agent_arr)->column('username', 'id');
            }

            // 从缓存中获取股票详情
            foreach ($list->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $list ? $this->message(1, '', ['list' => $list, 'broker' => $brokerInfo, 'agent' => $agentInfo, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取佣金明细汇总
     * 总后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adminStatistic()
    {
        $map = [];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['no_agent_id'] = input('no_agent_id', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['submit_flag'] = input('submit_flag', 1, FILTER_SANITIZE_NUMBER_INT);
        if ($data['submit_flag'] == 1) {
            $map[] = ['u.agent_id', 'not in', EXCLUDE_AGENT];
        }

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['ai.agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['ai.broker_id', '=', $data['broker_id']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }
        if ($data['no_agent_id']) {
            $map[] = ['u.agent_id', 'not in', $data['no_agent_id']];
        }
        // 获取佣金明细汇总
        $adminStatistic = AdminIncome::alias('ai')
            ->field('SUM(ai.stock_value) as stock_value ,SUM(ai.money) as money,SUM(ai.platform_money) as platform_money,SUM(ai.agent_money) as agent_money,SUM(ai.broker_money) as broker_money')
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')->find();

        return $adminStatistic ? $this->message(1, '', $adminStatistic) : $this->message(0, '');
    }

    /**
     * 佣金明细列表
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByAgent()
    {
        $map[] = ['ai.agent_id', '=', $this->adminId];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['ai.broker_id', '=', $data['broker_id']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }

        // 获取佣金明细列表
        $list      = AdminIncome::alias('ai')
            ->field([
                'ai.user_id', 'ai.order_position_id', 'ai.order_traded_id', 'ai.income_type',
                'ai.market', 'ai.stock_code', 'ai.stock_value', 'ai.platform_id', 'ai.platform_money',
                'ai.agent_id', 'ai.agent_money', 'ai.broker_id', 'ai.broker_money', 'ai.is_return',
                'ai.money', 'ai.income_time', 'u.mobile', 'u.real_name',
            ])
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')
            ->order('ai.income_time', 'DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = [];
        if ($list) {
            // 从缓存中获取股票详情
            foreach ($list->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $list ? $this->message(1, '', ['list' => $list, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取佣金明细汇总
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentStatistic()
    {
        $map[] = ['ai.agent_id', '=', $this->adminId];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['broker_id']) {
            $map[] = ['ai.broker_id', '=', $data['broker_id']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }
        // 获取佣金明细汇总
        $agentStatistic = AdminIncome::alias('ai')
            ->field('SUM(ai.stock_value) as stock_value ,SUM(ai.agent_money) as agent_money,SUM(ai.broker_money) as broker_money')
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')->find();

        return $agentStatistic ? $this->message(1, '', $agentStatistic) : $this->message(0, '');
    }

    /**
     * 佣金明细列表
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function listByBroker()
    {
        $map[] = ['ai.broker_id', '=', $this->adminId];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }

        // 获取佣金明细列表
        $list      = AdminIncome::alias('ai')
            ->field([
                'ai.user_id', 'ai.order_position_id', 'ai.order_traded_id', 'ai.income_type',
                'ai.market', 'ai.stock_code', 'ai.stock_value', 'ai.platform_id', 'ai.platform_money',
                'ai.agent_id', 'ai.agent_money', 'ai.broker_id', 'ai.broker_money', 'ai.is_return',
                'ai.money', 'ai.income_time', 'u.mobile', 'u.real_name',
            ])
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')
            ->order('ai.income_time', 'DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $stockInfo = [];
        if ($list) {
            // 从缓存中获取股票详情
            foreach ($list->getCollection()->toArray() as $k => $v) {
                $stockInfo[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $list ? $this->message(1, '', ['list' => $list, 'stockInfo' => $stockInfo]) : $this->message(0, '');
    }

    /**
     * 获取佣金明细汇总
     * 经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerStatistic()
    {
        $map[] = ['ai.broker_id', '=', $this->adminId];

        // 获取查询提交数据
        $data['mobile']      = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['income_type'] = input('income_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['income_type']) {
            $map[] = ['ai.income_type', '=', $data['income_type']];
        }
        if ($data['start_date']) {
            $map[] = ['ai.income_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['ai.income_time', '<=', $data['end_date']];
        }
        // 获取佣金明细汇总
        $agentStatistic = AdminIncome::alias('ai')
            ->field('SUM(ai.stock_value) as stock_value ,SUM(ai.broker_money) as broker_money')
            ->where($map)
            ->where(function (Query $query) {
                $query->where('income_type', ORG_INCOME_BUY)->whereOr('income_type', ORG_INCOME_POSITION);
            })
            ->join(['__USER__' => 'u'], 'u.id=ai.user_id')->find();

        return $agentStatistic ? $this->message(1, '', $agentStatistic) : $this->message(0, '');
    }

    /**
     * 获取代理商经济人佣金类型
     *
     * @return \think\response\Json
     */
    public function orgIncomeTypeList()
    {
        return $this->message(1, '', BasicData::ORG_INCOME_TYPE_LIST);
    }

    /**
     * 获取平台佣金收入类型
     *
     * @return \think\response\Json
     */
    public function adminIncomeTypeList()
    {
        return $this->message(1, '', BasicData::ADMIN_INCOME_TYPE_LIST);
    }

    /**
     * 获取代理商下经济人信息
     *
     * @return \think\response\Json
     */
    public function brokerListByAgent()
    {
        // 获取登录代理商下所有经济人信息
        $brokerList = AdminUser::where('pid', $this->adminId)->column('id,username', 'id');

        return $brokerList ? $this->message(1, '', $brokerList) : $this->message(0, '');
    }

}
