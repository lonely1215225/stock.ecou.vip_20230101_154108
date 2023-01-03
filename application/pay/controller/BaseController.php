<?php
namespace app\pay\controller;

use think\facade\Request;
use util\RedisUtil;
use think\Controller;

class BaseController extends Controller
{

    public $userId;
    public $token;

    protected $noLogin = [
        'Index'       => ['back_url'],
        'LianLianPay' => ['notify_url', 'index'],
    ];

    public function initialize()
    {
        // token
        $this->token = input('token', '');

        // 根据token获取用户的数据
        $userData = $this->token ? RedisUtil::getToken($this->token) : [];

        // 用户id
        $this->userId = $userData['user_id'] ?? 0;

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
     * AJAX返回统一数据返回格式
     * - 约定 1 => 成功 0 => 失败
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
