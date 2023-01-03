<?php
namespace app\stock\controller;

use app\common\model\AdminUser;
use think\facade\Session;
use util\SysWsRedis;
use util\RedisUtil;

class AuthController extends BaseController
{

    /**
     * 用户登录
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function signIn()
    {
        if (!$this->request->isPost()) {
            return null;
        }

        // 获取表单提交数据
        $data['username'] = input('post.username', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['password'] = input('post.password', '');

        // 验证用户名密码不能为空
        $result = $this->validate($data, 'AdminUser.login');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 新建数据库模型并查询数据
        $adminUserModel = new AdminUser();
        $rs             = $adminUserModel->field("id,username,password,role,mobile")->where('username', $data['username'])->where('role','super')->find();
        //echo password_hash($data['password'], PASSWORD_DEFAULT);
        // 验证用户名密码
        if ($rs && password_verify($data['password'], $rs['password'])) {
            // 缓存token及用户数据
            $token = md5($rs['id'] . $rs['username'] . time());
            Session::set('admin_id', $rs['id']);
            Session::set('admin_name', $rs['username']);
            Session::set('admin_role', 'super');
            Session::set('token', $token);

            $data  = [
                'admin_id'           => $rs['id'],
                'admin_name'         => $rs['username'],
                'mobile'             => $rs['mobile'],
                'role'               => $rs['role'],
            ];
            RedisUtil::cacheClientIp($this->request->ip());
            RedisUtil::cacheAdminToken($token, $data);
            return $this->message(1, 'success');
        } else {
            return $this->message(0, '用户名或密码错误');
        }
    }

    /**
     * 更新密码
     *
     * @return \think\response\Json
     */
    public function update_password()
    {
        $data['old_password'] = input('post.old_password', '');
        $data['new_password'] = input('post.new_password', '');
        $data['new_confirm']  = input('post.new_confirm', '');

        // 验证数据
        $result = $this->validate($data, 'AdminUser.modify_password');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 保存密码
        $passwordHash   = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $adminUserModel = new AdminUser();
        $ret            = $adminUserModel->isUpdate(true)->save([
            'id'       => $this->adminId,
            'password' => $passwordHash,
        ]);
        if($data['old_password'] != $data['new_password']){
            // 清除登录session
            Session::destroy();
        }
        return $ret ? $this->message(1, '密码修改成功,请重新登录') : $this->message(0, '密码修改失败');
    }

    /**
     * 用户注销
     *
     * @return \think\response\Json
     */
    public function signOut()
    {
        // 清除登录session
        Session::destroy();

        return $this->message(1, '注销成功');
    }

    /**
     * 检查用户是否已登录
     *
     * @return \think\response\Json
     */
    public function isSignedIn()
    {
        // 登录用户ID
        $adminId   = Session::has('admin_id') ? Session::get('admin_id') : 0;
        if ($adminId == 0) {
            return $this->message(0, '您还未登录系统');
        } else {
            $ret = AdminUser::update([
                'login_ip'    => $this->request->ip(),
                'update_time' => time(),
            ], [
                ['id', '=', $adminId],
            ]);
            return $this->message(1, '您已登录系统');
        }
    }

    /**
     * 代理商登录
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentSignIn()
    {
        if (!$this->request->isPost()) {
            return null;
        }

        // 获取表单提交数据
        $data['username'] = input('post.username', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['password'] = input('post.password', '');

        // 验证用户名密码不能为空
        $result = $this->validate($data, 'AdminUser.login');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 新建数据库模型并查询数据
        $adminUserModel = new AdminUser();
        $rs             = $adminUserModel->field("id,username,password")->where('role', 'agent')->where('username', $data['username'])->find();

        // 验证用户名密码
        if ($rs && password_verify($data['password'], $rs['password'])) {
            Session::set('admin_id', $rs['id']);
            Session::set('admin_name', $rs['username']);
            Session::set('admin_role', 'agent');

            return $this->message(1, 'success');
        } else {
            return $this->message(0, '用户名或密码错误');
        }
    }

    /**
     * 经纪人登录
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brokerSignIn()
    {
        if (!$this->request->isPost()) {
            return null;
        }

        // 获取表单提交数据
        $data['username'] = input('post.username', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['password'] = input('post.password', '');

        // 验证用户名密码不能为空
        $result = $this->validate($data, 'AdminUser.login');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 新建数据库模型并查询数据
        $adminUserModel = new AdminUser();
        $rs             = $adminUserModel->field("id,username,password")->where('role', 'broker')->where('username', $data['username'])->find();

        // 验证用户名密码
        if ($rs && password_verify($data['password'], $rs['password'])) {
            Session::set('admin_id', $rs['id']);
            Session::set('admin_name', $rs['username']);
            Session::set('admin_role', 'broker');

            return $this->message(1, 'success');
        } else {
            return $this->message(0, '用户名或密码错误');
        }
    }

}
