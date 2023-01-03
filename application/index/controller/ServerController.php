<?php
namespace app\index\controller;
use util\SystemRedis;
/**
 * 服务器信息接口
 */
class ServerController extends BaseController
{

    public function index()
    {
        $pass   = 'Xi8dko4dfpOnfVp0';
        $iv     = 'Xi8dko4dfpOnfVp0';
        $method = 'aes-128-cbc';
        $option = OPENSSL_RAW_DATA;
        $data   = json_encode([
            'quotation'   => '',
            'transaction' => '',
        ]);
        $cipher = base64_encode(openssl_encrypt($data, $method, $pass, $option, $iv));

        // 解密
        // dump(json_decode(openssl_decrypt(base64_decode($cipher), $method, $pass, 1, $iv), true));

        return $cipher;
    }

    public function down(){
        $download = '';
        $service  = '';

        return $this->message(1, '', ['down' => $download, 'service' => $service]);
    }
    /*APP版本更新*/
    public function updata(){
        $data = SystemRedis::getAppConfig();
        return $this->message(1, '', $data);
    }
}
