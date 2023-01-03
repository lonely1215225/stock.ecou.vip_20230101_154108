<?php
namespace app\stock\controller;

use app\common\model\OrgAccountLog;
use think\App;
use util\BasicData;

class OrgAccountLogController extends BaseController
{

    protected $orgAccountLogModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orgAccountLogModel = new OrgAccountLog();
    }

    /**
     * 获取登录用户账户变动日志
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $AccountLog = $this->orgAccountLogModel->field('change_money,change_type,change_time')
            ->where('change_type', ORG_ACCOUNT_MANAGEMENT)
            ->where('admin_id', $this->adminId)
            ->paginate();

        return $AccountLog ? $this->message(1, '', $AccountLog) : $this->message(0, '');
    }

    /**
     * 获取账户变动类型常量
     *
     * @return \think\response\Json
     */
    public function getChangeType()
    {
        return $this->message(1, '', BasicData::ORG_ACCOUNT_CHANGE_TYPE_LIST);
    }

    /**
     * 获取代理商资金明细
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAgentAccountLog()
    {
        $map[]               = ['au.pid', '=', $this->adminId];
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['agent_id']) {
            $map[] = ['oal.admin_id', '=', $data['agent_id']];
        }
        if ($data['change_type']) {
            $map[] = ['oal.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['oal.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['oal.change_time', '<=', $data['end_date']];
        }
        $AccountLog = $this->orgAccountLogModel
            ->alias('oal')
            ->field('oal.change_money,oal.change_type,oal.change_time,au.username')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->order('change_time DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $AccountLog ? $this->message(1, '', $AccountLog) : $this->message(0, '');
    }

    /**
     * 获取经济人资金明细
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getBrokerAccountLog()
    {
        $map[]               = ['au.role', '=', 'broker'];
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['agent_id']) {
            $map[] = ['au.pid', '=', $data['agent_id']];
        }
        if ($data['change_type']) {
            $map[] = ['oal.change_type', '=', $data['change_type']];
        }
        if ($data['broker_id']) {
            $map[] = ['au.id', '=', $data['broker_id']];
        }
        if ($data['start_date']) {
            $map[] = ['oal.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['oal.change_time', '<=', $data['end_date']];
        }
        $AccountLog = $this->orgAccountLogModel
            ->alias('oal')
            ->field('oal.change_money,oal.change_type,oal.change_time,au.username')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->order('change_time DESC')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $AccountLog ? $this->message(1, '', $AccountLog) : $this->message(0, '');
    }

    /**
     * 获取代理商总变动金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentTotalChangeMoney()
    {
        $map[]               = ['au.pid', '=', $this->adminId];
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['agent_id']) {
            $map[] = ['oal.admin_id', '=', $data['agent_id']];
        }
        if ($data['change_type']) {
            $map[] = ['oal.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['oal.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['oal.change_time', '<=', $data['end_date']];
        }
        $totalChangeMoney = $this->orgAccountLogModel
            ->alias('oal')
            ->field('sum(change_money) as changemoney')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalChangeMoney);
    }

    /**
     * 获取经济人总变动金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerTotalChangeMoney()
    {
        $map[]               = ['au.role', '=', 'broker'];
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id']   = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($data['agent_id']) {
            $map[] = ['au.pid', '=', $data['agent_id']];
        }
        if ($data['change_type']) {
            $map[] = ['oal.change_type', '=', $data['change_type']];
        }
        if ($data['broker_id']) {
            $map[] = ['au.id', '=', $data['broker_id']];
        }
        if ($data['start_date']) {
            $map[] = ['oal.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['oal.change_time', '<=', $data['end_date']];
        }
        $totalChangeMoney = $this->orgAccountLogModel
            ->alias('oal')
            ->field('sum(change_money) as changemoney')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalChangeMoney);
    }

    /**
     * 获取登录用户资金明细
     * 代理商、经济人后台
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function accountLogByself()
    {
        $map[] = ['au.id', '=', $this->adminId];
        // 获取查询提交数据
        $data['change_type'] = input('change_type', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['change_type']) {
            $map[] = ['oal.change_type', '=', $data['change_type']];
        }
        if ($data['start_date']) {
            $map[] = ['oal.change_time', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['oal.change_time', '<=', $data['end_date']];
        }
        $AccountLog = $this->orgAccountLogModel
            ->alias('oal')
            ->field('oal.change_money,oal.change_type,oal.change_time,au.username')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $AccountLog ? $this->message(1, '', $AccountLog) : $this->message(0, '');
    }

    /**
     * 获取代理商、经济人总变动金额
     * 代理商、经济人后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeMoneyBySelf()
    {
        $totalChangeMoney = $this->orgAccountLogModel
            ->alias('oal')
            ->field('sum(change_money) as changemoney')
            ->join(['__ADMIN_USER__' => 'au'], 'oal.admin_id=au.id')
            ->where('au.id', $this->adminId)
            ->find();

        return $this->message(1, '', $totalChangeMoney);
    }

}
