<?php
namespace app\stock\controller;

use app\common\model\AdminUser;
use app\common\model\OrgAccount;
use think\App;
use think\Db;
use util\BasicData;

class AgentController extends BaseController
{

    protected $adminUserModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserModel = new AdminUser();
    }

    /**
     * 获取代理商列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAgentList()
    {
        $map[]          = ['role', '=', 'agent'];
        $data['id']     = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($data['id']) {
            $map[] = ['id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        // 获取代理商
        $agentList = $this->adminUserModel
            ->field('id,role,username,org_name,mobile,commission_rate,is_deny_login,is_deny_cash')
            ->where('pid', $this->adminId)
            ->where($map)
            ->order('id', 'DESC')
            ->paginate(15, false, ['query' => request()->param()]);

        return $agentList ? $this->message(1, '', $agentList) : $this->message(0, '代理商信息为空');
    }

    /**
     * 获取单个代理商信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgentInfoById()
    {
        // 获取股票信息id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $agentInfo = $this->adminUserModel
                ->field('id,role,username,password,org_name,mobile,commission_rate,user_rate,is_deny_login,is_deny_cash,remark')
                ->where('id', $id)->find();

            return $agentInfo ? $this->message(1, '', $agentInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     *  添加编辑代理商
     *
     * @return null|\think\response\Json
     */
    public function saveAgent()
    {
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $id             = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        if ($id) {
            $result = $this->validate($data, 'Agent.editAgent');
        } else {
            $data['username'] = input('post.username', '', [FILTER_SANITIZE_STRING, 'trim']);
            $data['password'] = input('post.password', '');
            $result           = $this->validate($data, 'Agent.addAgent');
        }
        if ($result !== true) {
            return $this->message(0, $result);
        }

        $data['pid']             = $this->adminId;
        $data['org_name']        = input('post.org_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['role']            = ADMIN_ROLE_AGENT;
        $data['commission_rate'] = input('post.commission_rate', 0, function ($value) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        });
        $data['user_rate'] = input('post.user_rate', 0, function ($value) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        });
        $data['is_deny_login']   = input('post.is_deny_login', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['is_deny_cash']    = input('post.is_deny_cash', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['remark']          = input('post.remark', '');
        // 不允许代理商推广用户
        $data['is_permit_investor'] = false;
        
        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            // 获取代理商最低分成比例
            $agentInfo            = $this->agentLowestCommissionRate()->getData();
            $LowestCommissionRate = $agentInfo['code'] == 1 ? $agentInfo['data']['commission_rate'] : '';

            if ($LowestCommissionRate !== '' && $data['commission_rate'] < $LowestCommissionRate) {
                return $this->message(0, '该代理商最低分成比例为' . $LowestCommissionRate . '%');
            }
            $saveInfo = $this->adminUserModel->isUpdate(true)->save($data);

            return $saveInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
        } else {
            Db::startTrans();
            try {
                // 实例化代理商账户表
                $OrgCountModel = new OrgAccount();
                // 密码加密
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $saveInfo         = $this->adminUserModel->save($data);
                // 获取插入数据自增id
                $insertId = $this->adminUserModel->getLastInsID();
                // 添加代理商账户表信息
                $addOrgCount = $OrgCountModel->save(['admin_id' => $insertId]);
                if ($saveInfo && $addOrgCount) {
                    // 提交事务
                    Db::commit();

                    // 返回成功信息
                    return $this->message(1, '操作成功');
                } else {
                    // 提交事务
                    Db::rollback();

                    // 返回成功信息
                    return $this->message(0, '操作失败');
                }
            } catch (\Exception $e) {
                Db::rollback();

                // 返回失败信息
                return $this->message(0, '操作失败2');
            }
        }

    }

    /**
     * 编辑代理商时获取其最低分成比例
     *
     * @return \think\response\Json
     */
    public function agentLowestCommissionRate()
    {
        // 获取代理商最低分成比例
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(1, '参数错误');
        $agentInfo = $this->adminUserModel
            ->field('commission_rate')
            ->where('pid', $id)
            ->order('commission_rate desc')->find();

        return $agentInfo ? $this->message(1, '', $agentInfo) : $this->message(0, '信息为空');
    }

    /**
     * 保存代理商新密码
     *
     * @return null|\think\response\Json
     */
    public function saveNewPwd()
    {
        if (!$this->request->isPost()) return null;

        $data['id']       = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['password'] = input('post.password', '');

        // 提交数据校验
        $result = $this->validate($data, 'Agent.saveNewPwd');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // 更新密码
        $saveInfo = $this->adminUserModel->isUpdate(true)->save([
            'id'       => $data['id'],
            'password' => $data['password'],
        ]);

        return $saveInfo ? $this->message(1, '更新成功') : $this->message(0, '更新失败');
    }

    /**
     * 获取代理商下经济人信息
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getBrokerByAgent()
    {
        // 获取代理商id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $brokerInfo = $this->adminUserModel
                ->field('id,role,username,password,org_name,mobile,commission_rate,is_deny_login,is_deny_cash,remark')
                ->where('pid', $id)->paginate();

            return $brokerInfo ? $this->message(1, '', $brokerInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }

    }

    /**
     * 获取提现申请处理状态
     *
     * @return \think\response\Json
     */
    public function getWithdrawState()
    {
        return $this->message(1, '', BasicData::ORG_WITHDRAW_STATE_LIST);
    }

    /**
     * 代理商列表显示统计详情
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentStatistic()
    {
        $map[]          = ['role', '=', 'agent'];
        $data['id']     = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['au.mobile', '=', $data['mobile']];
        }
        // 获取代理商账户统计详情
        $OrgCountModel = new OrgAccount();
        $totalMoney    = $OrgCountModel->alias('oa')
            ->field('SUM(oa.balance) as totalBalance,SUM(oa.total_commission) as totalCommission,SUM(oa.total_withdraw) as totalWithdraw')
            ->join(['__ADMIN_USER__' => 'au'], 'oa.admin_id=au.id')
            ->where('pid', $this->adminId)
            ->where($map)
            ->find();

        return $this->message(1, '', $totalMoney);
    }

}
