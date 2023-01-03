<?php
namespace app\stock\controller;

use think\Controller;
use think\facade\Session;
use think\facade\Request;

class BaseController extends Controller
{

    // 当前管理员的id
    public $adminId;

    // 当前用户角色
    public $role;

    /**
     * 设置无需登陆的action
     * 格式：[
     *     '控制器名称(驼峰)' => ['action1', 'action2', ...],
     *     ...
     * ]
     */
    protected $noLogin = [
        'Auth' => ['signIn', 'signOut', 'isSignedIn'],
    ];

    public function initialize()
    {
        // 管理员ID
        $this->adminId = Session::has('admin_id') ? Session::get('admin_id') : 0;

        // 角色
        $this->role = Session::has('admin_role') ? Session::get('admin_role') : '';

        /**
         * @var string $controller 当前控制器
         * @var string $action 当前方法(action)
         */
        $controller = Request::controller();
        $action     = Request::action();

        // 验证用户是否登录，排除无需登录的action
        if (!(isset($this->noLogin[$controller]) && in_array($action, $this->noLogin[$controller]))) {
            if ($this->adminId == 0) {
                return $this->message(403, '未登录');
            }
        }
    }

    /**
     * 统一数据返回格式
     * - 约定 1 => 成功
     *       0 => 失败
     *       403 => 未登录
     *
     * @param int    $code 状态码
     * @param string $msg 消息
     * @param array  $data 数据
     * @param string $desc 接口描述
     *
     * @return \think\response\Json
     */
    protected function message($code, $msg = '', $data = [], $desc = '')
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');

        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'desc' => $desc,
        ]);
    }

}

