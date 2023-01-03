<?php
namespace app\payment\controller;

use think\Controller;
use think\facade\Session;
use think\facade\Request;

class BaseController extends Controller
{

    // 当前管理员的id
    public $adminId;
    // 当前管理员角色
    public $adminRole;

    /**
     * 设置无需登陆的action
     * 格式：[
     *     '控制器名称(驼峰)' => ['action1', 'action2', ...],
     *     ...
     * ]
     */
    protected $noLogin = [];

    public function initialize()
    {
        parent::initialize();

        // 管理员ID
        $this->adminId   = Session::has('admin_id') ? Session::get('admin_id') : 0;
        // 管理员角色
        $this->adminRole = Session::has('admin_role') ? Session::get('admin_role') : '';

        /**
         * @var string $controller 当前控制器
         * @var string $action 当前方法(action)
         */
        $controller = Request::controller();
        $action     = Request::action();

        // 传入当前控制器
        $this->assign('controller', $controller);

        // 传入当前action
        $this->assign('action', $action);

        // 验证用户是否登录，排除无需登录的action
        if (!(isset($this->noLogin[$controller]) && in_array($action, $this->noLogin[$controller]))) {
            if ($this->adminId == 0) {
                $this->redirect('passport/index');
            }
        }
    }

    /**
     * AJAX返回统一数据返回格式
     * - 约定 1 => 成功 0 => 失败
     *
     * @param int $code 状态码
     * @param string $msg 消息
     * @param array $data 数据
     * @param string $desc 接口描述
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
