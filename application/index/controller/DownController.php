<?php

namespace app\index\controller;

use util\SystemRedis;
use think\Controller;

class DownController extends Controller
{

    public function index()
    {
        $config = systemRedis::getAppConfig();
        $data = [
            'app_icon'     => $config['app_icon'] ?? "",
            'apk_down_url' => $config['apk_down_url'] ?? "",
            'ios_down_url' => $config['ios_down_url'] ?? "",
        ];
        $this->assign("inWechat", $this->isWechat());
        $this->assign('down', $data);
        return $this->fetch();
    }
    protected function isWechat()
    {
        if( !preg_match('/MicroMessenger/i', strtolower($_SERVER['HTTP_USER_AGENT'])) ) {
            return 0;
        }
        return 1;
    }
    public function getdown()
    {
        $config = systemRedis::getAppConfig();
        $data = [
            'apk_down_url' => $config['apk_down_url']   ?? "",
            'ios_down_url' => $config['ios_down_url']   ?? "",
        ];
        return json(['code' => 1,'msg' => '获取成功','data' => $data]);
    }
}
