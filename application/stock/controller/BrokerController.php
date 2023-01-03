<?php
namespace app\stock\controller;

use app\common\model\AdminUser;
use app\common\model\OrgAccount;
use app\stock\logic\BrokerLogic;
use think\App;
use think\Db;
use util\BasicData;

class BrokerController extends BaseController
{

    protected $adminUserModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->adminUserModel = new AdminUser();
    }

    /**
     * 获取单个经纪人信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBrokerInfoById()
    {
        // 获取股票信息id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $brokerInfo = $this->adminUserModel
                ->field('id,role,username,password,org_name,mobile,commission_rate,is_deny_login,is_deny_cash,remark')
                ->where('id', $id)->find();

            return $brokerInfo ? $this->message(1, '', $brokerInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取自己的分成比例
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selfCommissionRate()
    {
        // 获取自己的分成比例
        $selfInfo = $this->adminUserModel
            ->where('id', $this->adminId)
            ->field('commission_rate')
            ->find();

        return $selfInfo ? $this->message(1, '', $selfInfo) : $this->message(0, '');
    }

    /**
     *  保存经纪人信息
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveBroker()
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
        if ($result !== true) return $this->message(0, $result);

        $data['pid']             = $this->adminId;
        $data['org_name']        = input('post.org_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['role']            = ADMIN_ROLE_BROKER;
        $data['commission_rate'] = input('post.commission_rate', 0, 'filter_float');
        $data['is_deny_login']   = input('post.is_deny_login', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['is_deny_cash']    = input('post.is_deny_cash', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['remark']          = input('post.remark', '');

        // 获取【代理商的分成比例】，【经纪人的分成比例】【不得大于】【代理商的分成比例】
        $selfCommissionRate = $this->selfCommissionRate()->getData();
        if ($data['commission_rate'] > $selfCommissionRate['data']['commission_rate']) {
            return $this->message(0, '最高分成比例为' . $selfCommissionRate['data']['commission_rate'] . '%');
        }

        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            $ret        = $this->adminUserModel->isUpdate(true)->save($data);

            return $ret ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
        } else {
            Db::startTrans();
            try {
                // 密码加密
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $broker           = AdminUser::create($data);

                // 经纪人ID
                $brokerID = $broker['id'];

                // 添加代理商账户表信息
                $orgAccount = OrgAccount::create(['admin_id' => $brokerID]);

                // 生成推广码，及二维码图片
                $qrRet = BrokerLogic::setPromotionCode($brokerID, $this->request);

                if ($broker && $orgAccount && $qrRet) {
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
     * 保存经济人新密码
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
     * 获取代理商下的经纪人列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAgentBroker()
    {
        $map[] = ['pid', '=', $this->adminId];
        $map[] = ['role', '=', 'broker'];
        // 获取查询提交数据
        $data['id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['id']) {
            $map[] = ['id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        // 获取代理商
        $brokerList = $this->adminUserModel
            ->field('id,role,username,org_name,mobile,commission_rate,is_deny_login,is_deny_cash,code')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $brokerList ? $this->message(1, '', $brokerList) : $this->message(0, '经济人信息为空');
    }

    /**
     * 获取所有经济人信息
     * 超级管理员权限
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getBrokerAll()
    {
        $map[] = ['role', '=', 'broker'];
        // 获取查询提交数据
        $data['pid']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['pid']) {
            $map[] = ['pid', '=', $data['pid']];
        }
        if ($data['id']) {
            $map[] = ['id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        // 获取所有经济人信息
        $brokerList = $this->adminUserModel
            ->field('id,role,username,org_name,mobile,commission_rate,is_deny_login,is_deny_cash,code')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $brokerList ? $this->message(1, '', $brokerList) : $this->message(0, '经济人信息为空');
    }

    /**
     * 获取该管理员所有的代理商信息
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgentByAdminid()
    {
        $agentInfo = $this->adminUserModel->field('id,username')->where('role', 'agent')->where('pid', $this->adminId)->select();

        return $agentInfo ? $this->message(1, '', $agentInfo) : $this->message(0, '信息为空');
    }

    /**
     * 获取推广码
     * 仅限经济人后台登录
     *
     * @return \think\response\Json
     */
    public function promotionCode()
    {
        // 获取推广码
        $code = AdminUser::where('id', $this->adminId)->value('code');

        // 如果不存在则生成
        if (!$code) {
            BrokerLogic::setPromotionCode($this->adminId, $this->request);
        }

        // 推广连接地址
        $url = $this->request->scheme() . '://www.' . $this->request->rootDomain() . '/#/register?code=' . $code;

        // 二维码图片地址
        $img = $this->request->domain() . '/uploads/qr_code/' . $code . '.png';

        return $this->message(1, '', [
            'code' => $code,
            'url'  => $url,
            'img'  => $img,
        ]);
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
     * 获取经济人列表统计详情
     * 超级管理员后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerStatistic()
    {
        $map[] = ['au.role', '=', 'broker'];
        // 获取查询提交数据
        $data['pid']    = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['pid']) {
            $map[] = ['au.pid', '=', $data['pid']];
        }
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['au.mobile', '=', $data['mobile']];
        }
        // 获取经济人总数
        $brokerTotal = $this->adminUserModel->alias('au')->where($map)->count();
        // 获取所有经济人统计详情
        $OrgCountModel = new OrgAccount();
        $totalMoney    = $OrgCountModel->alias('oc')
            ->field('SUM(oc.balance) as totalBalance,SUM(oc.total_commission) as totalCommission,SUM(oc.total_withdraw) as totalWithdraw')
            ->join(['__ADMIN_USER__' => 'au'], 'oc.admin_id=au.id')
            ->where($map)->find();

        return $this->message(1, '', ['brokerTotal' => $brokerTotal, 'totalMoney' => $totalMoney]);
    }

    /**
     * 获取经济人列表统计详情
     * 代理商后台
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AgentBrokerStatistic()
    {
        $map[] = ['pid', '=', $this->adminId];
        $map[] = ['role', '=', 'broker'];
        // 获取查询提交数据
        $data['id']     = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['mobile'] = input('mobile', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['id']) {
            $map[] = ['au.id', '=', $data['id']];
        }
        if ($data['mobile']) {
            $map[] = ['au.mobile', '=', $data['mobile']];
        }
        // 获取所有经济人统计详情
        $OrgCountModel = new OrgAccount();
        $totalMoney    = $OrgCountModel->alias('oc')
            ->field('SUM(oc.balance) as totalBalance,SUM(oc.total_commission) as totalCommission,SUM(oc.total_withdraw) as totalWithdraw')
            ->join(['__ADMIN_USER__' => 'au'], 'oc.admin_id=au.id')
            ->where($map)
            ->find();

        return $this->message(1, '', $totalMoney);
    }

    /**
     * 编辑推广码
     *
     * @return null|\think\response\Json
     */
    public function saveNewCode()
    {
        if (!$this->request->isPost()) return null;

        $brokerID = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        $code     = input('code', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($brokerID <= 0 || $code == '') return $this->message(0, '参数错误');

        // 检测推广码是否存在
        $isExit = AdminUser::where('code', $code)->count();
        if ($isExit) {
            return $this->message(0, '该推广码已经存在，请重新输入');
        }
        //获取原推荐码
        $codeName = AdminUser::where('id',$brokerID)->value('code');
        // 更新推广码
        $saveInfo = AdminUser::update([
            'code' => $code,
        ], [
            ['id', '=', $brokerID,],
        ]);

        // 生成二维码图片
        BrokerLogic::createQrImg($code, $this->request);
        //删除原二维码图片
        if($saveInfo){
            // 二维码图片路径
            $img = $_SERVER['DOCUMENT_ROOT'] . '/uploads/qr_code/' . $codeName . '.png';
            @unlink($img);
        }

        return $saveInfo ? $this->message(1, '更新成功') : $this->message(0, '更新失败');
    }

}
