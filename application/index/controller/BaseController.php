<?php

namespace app\index\controller;

use think\Controller;
use think\facade\Request;
use util\RedisUtil;
use util\SystemRedis;

class BaseController extends Controller
{

    // 当前用户的ID
    public $userId = 0;

    // 当前用户的手机号
    public $mobile = '';

    // 上级经纪人
    public $brokerID = 0;

    // 上级代理商
    public $agentID = 0;

    // token
    public $token = null;

    /**
     * 设置无需登陆的action
     * 格式：[
     *     '控制器名称(驼峰)' => ['action1', 'action2', ...],
     *     ...
     * ]
     */
    protected $noLogin = [
        'Auth'      => ['sign_in', 'sign_out', 'register', 'send_captcha_sms', 'reset_password'],
        'Stock'     => ['search', 'read', 'black', 'index', 'stock_index', 'active', 'market','getactives'],
        'News'      => ['index', 'read', 'notice','upchina'],
        'Notice'    => ['index','read', 'read_list'],
        'Server'    => ['index', 'down'],
    ];

    public function initialize()
    {
        // token
        $this->token = input('token', '');

        // 根据token获取用户的数据
        $userData = $this->token ? RedisUtil::getToken($this->token) : [];

        // 当前用户的ID
        $this->userId = $userData['user_id'] ?? 0;

        // 当前用户的手机号
        $this->mobile = $userData['mobile'] ?? '';

        $this->agentID = $userData['agent_id'] ?? 0;
        $this->brokerID = $userData['broker_id'] ?? 0;

        /**
         * @var string $controller 当前控制器
         * @var string $action 当前方法(action)
         */
        $controller = Request::controller();
        $action     = Request::action();

        // 验证用户是否登录，排除无需登录的action
        if (!(isset($this->noLogin[$controller]) && in_array($action, $this->noLogin[$controller]))) {
            if ($this->userId == 0) {
                $this->result('', 403, '请登陆', 'json');
            }
        }
    }

    /**
     * 统一数据返回格式
     * - 约定 1   => 成功
     *       0   => 失败
     *       403 => 未登录
     *
     * @param        $code
     * @param string $msg
     * @param array $data
     *
     * @return \think\response\Json
     */
    protected function message($code, $msg = '', $data = [])
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

}
