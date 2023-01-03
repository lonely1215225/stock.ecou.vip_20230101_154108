<?php

namespace app\stock\controller;

use app\common\model\System;
use think\App;
use think\facade\Env;
use util\SystemRedis;
use Endroid\QrCode\QrCode;

class SystemController extends BaseController
{

    private $systemModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->systemModel = new System();
    }
    /**
         * 获取基础配置信息
     */
    public function getConfig()
    {
        //获取APP相关的配置
        $value = $this->get('SYSTEM_CONFIG');
        $data['is_regist'] = $value['is_regist'] ?? '';//注册功能是否开启 是：1，否：0
        $data['is_invita'] = $value['is_invita'] ?? '';//邀请功能是否开启 是：1，否：0
        $data['is_share']  = $value['is_share'] ?? '';//邀请功能是否开启 是：1，否：0
        $data['is_smsreg'] = $value['is_smsreg'] ?? '';//短信功能是否开启 是：1，否：0
        $data['sms_use']   = $value['sms_use'] ?? '';//注册功能是否开启 是：1，否：0
        $data['sms_pwd']   = $value['sms_pwd'] ?? '';//邀请功能是否开启 是：1，否：0
        $data['sms_name']  = $value['sms_name'] ?? '';//短信功能是否开启 是：1，否：0
        $data['h5_url']    = $value['h5_url'] ?? '';//H5网址
        $data['kefu_link'] = $value['kefu_link'] ?? '';//客服链接地址
        $data['kefuphone'] = $value['kefuphone'] ?? '';//客服电话号码
        $data['banner_url']= $value['banner_url'] ?? '';//推广海报地址
        return $this->message(1, '', $data);
    }
    /**
     * 保存基础配置
     */
    public function saveConfig()
    {
        // 用户数据
        $value['is_regist'] = input('post.is_regist', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['is_invita'] = input('post.is_invita', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['is_share']  = input('post.is_share', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['is_smsreg'] = input('post.is_smsreg', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['sms_use']   = input('post.sms_use', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['sms_pwd']   = input('post.sms_pwd', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['sms_name']  = input('post.sms_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['h5_url']    = input('post.h5_url', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['kefu_link'] = input('post.kefu_link', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['kefuphone'] = input('post.kefuphone', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['banner_url']= input('post.banner_url', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 验证数据
        //$result = $this->validate($value, 'System.saveAppConfig');
        //if ($result !== true) return $this->message(0, $result);
        $file = UPLOAD_DIR . '/qr_code/app_url.png';
        if($value['h5_url']) {
            $qrCode  = new QrCode($value['h5_url']);
            $qrCode->writeFile($file);
        }
        
        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_CONFIG']);

        // 缓存 基础设置
        SystemRedis::cacheConfig();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }
    /**
     * 获取交易时间配置
     *
     * @return \think\response\Json
     */
    public function getMarketTime()
    {
        // 获取交易时间
        $value = $this->get('SYSTEM_TRADING_TIME');

        // 上午开市休市时间
        $data['am_market_open_time'] = $value['am_market_open_time'] ?? '';
        $data['am_market_close_time'] = $value['am_market_close_time'] ?? '';

        // 下午开市休市时间
        $data['pm_market_open_time'] = $value['pm_market_open_time'] ?? '';
        $data['pm_market_close_time'] = $value['pm_market_close_time'] ?? '';

        return $this->message(1, '', $data);
    }

    /**
     * 保存交易时间配置
     *
     * @return \think\response\Json
     */
    public function saveMarketTime()
    {
        // 用户数据
        $value['am_market_open_time'] = input('post.am_market_open_time', '', 'trim');
        $value['am_market_close_time'] = input('post.am_market_close_time', '', 'trim');
        $value['pm_market_open_time'] = input('post.pm_market_open_time', '', 'trim');
        $value['pm_market_close_time'] = input('post.pm_market_close_time', '', 'trim');

        // 验证数据
        $result = $this->validate($value, 'System.saveTradingTime');
        if ($result !== true) return $this->message(0, $result);

        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_TRADING_TIME']);

        // 缓存 交易时间设置
        SystemRedis::cacheTradingTime();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }

    /**
     * 获取交易费用配置
     * -- service_fee 手续费
     * -- service_fee_min 手续费最低收取
     * -- management_fee 管理费
     * -- stamp_tax 印花税
     * -- transfer_fee 过户费
     * -- management_fee_s 停牌管理费
     * -- deposit_rate 履约保证金比例
     * -- deposit_rate_s 停牌履约保证金比例
     *
     * @return \think\response\Json
     */
    public function getTradingFee()
    {
        // 获取交易费用
        $value = $this->get('SYSTEM_TRADING_FEE');

        $data['service_fee'] = $value['service_fee'] ?? '';
        $data['service_fee_min'] = $value['service_fee_min'] ?? '';
        $data['management_fee'] = $value['management_fee'] ?? '';
        $data['monthly_m_fee'] = $value['monthly_m_fee'] ?? '';
        $data['stamp_tax'] = $value['stamp_tax'] ?? '';
        $data['transfer_fee'] = $value['transfer_fee'] ?? '';
        $data['management_fee_s'] = $value['management_fee_s'] ?? '';
        $data['deposit_rate'] = $value['deposit_rate'] ?? '';
        $data['deposit_rate_s'] = $value['deposit_rate_s'] ?? '';

        return $this->message(1, '', $data);
    }

    /**
     * 获取收益宝配置信息
     * -- SYSTEM_YUEBAO 余额宝 = 收益宝 --
     * -- yuebao_fee 每万份收益比例
     * -- is_open   是否开启收益宝
     * @return \think\response\Json
     */
    public function getYuebaoSet()
    {
        //获取收益宝的配置
        $value = $this->get('SYSTEM_YUEBAO');

        $data['yuebao_fee'] = $value['yuebao_fee'] ?? '';
        $data['is_open'] = $value['is_open'] ?? '';

        return $this->message(1, '', $data);
    }

    /**
     * 保存交易费用配置
     *
     * @return \think\response\Json
     */
    public function saveYuebao()
    {
        // 用户数据
        $value['is_open'] = input('post.is_open', '1', FILTER_SANITIZE_NUMBER_INT);
        $value['yuebao_fee'] = input('post.yuebao_fee', '', 'filter_float');

        // 验证数据
        $result = $this->validate($value, 'System.saveYuebao');
        if ($result !== true) return $this->message(0, $result);

        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_YUEBAO']);

        // 缓存 交易费用设置
        SystemRedis::cacheYuebao();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }
    /**
         * 获取APP配置信息
     */
    public function getAppConfig()
    {
        //获取APP相关的配置
        $value = $this->get('SYSTEM_APP');
        
        $data['android_power']       = $value['android_power'] ?? '';//安卓版强制更新 是：1，否：0
        $data['android_version']     = $value['android_version'] ?? '';//安卓版本号
        $data['android_version_name']= $value['android_version_name'] ?? '';//安卓版本CODE（用不到）
        $data['android_description'] = $value['android_description'] ?? '';//安卓版本描述
        $data['android_down']        = $value['android_down'] ?? '';//安卓更新地址
        $data['apk_down_url']        = $value['apk_down_url'] ?? '';//安卓下载地址
        $data['ios_power']           = $value['ios_power'] ?? '';//苹果强制更新 是：1，否：0
        $data['ios_version']         = $value['ios_version'] ?? '';//苹果版本号
        $data['ios_version_name']    = $value['ios_version_name'] ?? '';//苹果版本CODE（用不到）
        $data['ios_description']     = $value['ios_description'] ?? '';//苹果版本描述
        $data['ios_down']            = $value['ios_down'] ?? '';//苹果更新地址
        $data['ios_down_url']        = $value['ios_down_url'] ?? '';//安卓下载地址
        return $this->message(1, '', $data);
    }
    /**
     * 保存APP相关的配置
     */
    public function saveAppConfig()
    {
        // 用户数据
        $value['android_power']        = input('post.android_power', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['android_version']      = input('post.android_version', '', 'filter_float');
        $value['android_version_name'] = input('post.android_version_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['android_description']  = input('post.android_description', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['android_down']         = input('post.android_down', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['apk_down_url']         = input('post.apk_down_url', '', [FILTER_SANITIZE_STRING, 'trim']);
        
        $value['ios_power']            = input('post.ios_power', '0', FILTER_SANITIZE_NUMBER_INT);
        $value['ios_version']          = input('post.ios_version', '', 'filter_float');
        $value['ios_version_name']     = input('post.ios_version_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['ios_description']      = input('post.ios_description', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['ios_down']             = input('post.ios_down', '', [FILTER_SANITIZE_STRING, 'trim']);
        $value['ios_down_url']         = input('post.ios_down_url', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 验证数据
        //$result = $this->validate($value, 'System.saveAppConfig');
        //if ($result !== true) return $this->message(0, $result);
        
        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_APP']);

        // 缓存 APP设置
        SystemRedis::cacheAppConfig();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }
    
    /**
     * 保存交易费用配置
     *
     * @return \think\response\Json
     */
    public function saveTradingFee()
    {
        // 用户数据
        $value['service_fee']      = input('post.service_fee', '', 'filter_float');
        $value['service_fee_min']  = input('post.service_fee_min', '', 'filter_float');
        $value['management_fee']   = input('post.management_fee', '', 'filter_float');
        $value['monthly_m_fee']    = input('post.monthly_m_fee', '', 'filter_float');
        $value['stamp_tax']        = input('post.stamp_tax', '', 'filter_float');
        $value['transfer_fee']     = input('post.transfer_fee', '', 'filter_float');
        $value['management_fee_s'] = input('post.management_fee_s', '', 'filter_float');
        $value['deposit_rate']     = input('post.deposit_rate', '', 'filter_float');
        $value['deposit_rate_s']   = input('post.deposit_rate_s', '', 'filter_float');

        // 验证数据
        $result = $this->validate($value, 'System.saveTradingFee');
        if ($result !== true) return $this->message(0, $result);

        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_TRADING_FEE']);

        // 缓存 交易费用设置
        SystemRedis::cacheTradingFee();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }

    /**
     * 获取涨跌幅禁买线
     *
     * @return \think\response\Json
     */
    public function getBuyLimitRate()
    {
        return $this->message(1, '', $this->get('SYSTEM_BUY_LIMIT_RATE'));
    }

    /**
     * 设置涨跌幅禁买线比例
     *
     * @return \think\response\Json
     */
    public function saveBuyLimitRate()
    {
        $limit_rate = input('post.limit_rate', '', 'filter_float');

        // 验证数据
        $result = $this->validate(['limit_rate' => $limit_rate], 'System.saveLimitRate');
        if ($result !== true) return $this->message(0, $result);
        $ret = $this->systemModel->save(['v' => serialize($limit_rate)], ['k' => 'SYSTEM_BUY_LIMIT_RATE']);

//        $kec_limit_rate = input('post.kec_limit_rate', '', 'filter_float');
//        $futures_limit_rate = input('post.futures_limit_rate', '', 'filter_float');
//        $limit_rates = [
//            'limit_rate' => $limit_rate,
//            'kec_limit_rate' => $kec_limit_rate,
//            'futures_limit_rate' => $futures_limit_rate
//        ];
//
//        $ret = $this->systemModel->save(['v' => serialize($limit_rates)], ['k' => 'SYSTEM_FORBID_LIMIT_RATE']);

        // 缓存 涨跌幅禁买线比例
        SystemRedis::cacheBuyLimitRate();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
    }

    /**
     * 获取配置项的数据
     *
     * @param $k
     *
     * @return mixed
     */
    private function get($k)
    {
        $v = $this->systemModel->where('k', $k)->value('v');
        $data = $v ? unserialize($v) : '';

        return $data;
    }
    /**
     * 获取二维码配置信息
     * -- SYSTEM_YUEBAO 余额宝 = 收益宝 --
     * -- yuebao_fee 每万份收益比例
     * -- is_open   是否开启收益宝
     * @return \think\response\Json
     */
    public function getQrcodeSet()
    {
        //获取收益宝的配置
        $value = $this->get('SYSTEM_QRCODE');

        $data['wechat_customer_service'] = $value['wechat_customer_service'] ?? '';
        $data['wechat_official_account'] = $value['wechat_official_account'] ?? '';
        $data['wechat_android']          = $value['wechat_android'] ?? '';
        $data['wechat_ios']              = $value['wechat_ios'] ?? '';
        
        return $this->message(1, '', $data);
    }
    /**
     * 保存二维码数据
     * @return \think\response\Json
     */
    public function saveQrcode()
    {
        //try {
        $systemCode = SystemRedis::getQrcode();
        $oldData = $data = [
            'wechat_customer_service' => $systemCode['wechat_customer_service'] ?? '',
            'wechat_official_account' => $systemCode['wechat_official_account'] ?? '',
            'wechat_android' => $systemCode['wechat_android'] ?? '',
            'wechat_ios'     => $systemCode['wechat_ios'] ?? '',
        ];

        $upload_file = '/uploads/wechat_qrcode';
        $rootPath = Env::get('ROOT_PATH');
        $fullPath = $rootPath . 'public' . $upload_file;
        $serviceFile = '';
        $accountFile = '';
        $androidFile = '';
        $iosFile = '';

        //判断是否上传客服微信号
        if ($_FILES['wechat_customer_service']['name']) {
            $serviceFile = $this->request->file('wechat_customer_service');
        }

        //判断是否上传微信公众号
        /*if ($_FILES['wechat_official_account']['name']) {
            $accountFile = $this->request->file('wechat_official_account');
        }*/

        //判断是andorid下载二维码是否上传
        /*if ($_FILES['wechat_android']['name']) {
            $androidFile = $this->request->file('wechat_android');
        }*/

        //判断是ios下载二维码是否上传
        /*if ($_FILES['wechat_ios']['name']) {
            $iosFile = $this->request->file('wechat_ios');
        }*/
        
        if ($serviceFile) {
            $info = $serviceFile->move($fullPath);
            if (!$info) return $this->message(0, $serviceFile->getError());

            $data['wechat_customer_service'] = 'http://' . $_SERVER['HTTP_HOST'] . $upload_file . DIRECTORY_SEPARATOR . $info->getSaveName();
        }
        /*
        if ($accountFile) {
            $info1 = $accountFile->move($fullPath);
            if (!$info1) return $this->message(0, $accountFile->getError());

            $data['wechat_official_account'] = 'http://' . $_SERVER['HTTP_HOST'] . $upload_file . DIRECTORY_SEPARATOR . $info1->getSaveName();
        }
        
        if ($androidFile) {
            $info2 = $androidFile->move($fullPath);
            if (!$info2) return $this->message(0, $androidFile->getError());

            $data['wechat_android'] = 'http://' . $_SERVER['HTTP_HOST'] . $upload_file . DIRECTORY_SEPARATOR . $info2->getSaveName();
        }

        if ($iosFile) {
            $info3 = $iosFile->move($fullPath);
            if (!$info3) return $this->message(0, $iosFile->getError());

            $data['wechat_ios'] = 'http://' . $_SERVER['HTTP_HOST'] . $upload_file . DIRECTORY_SEPARATOR . $info3->getSaveName();
        }
        */
        $this->systemModel = new System();
        $ret = $this->systemModel->save(['v' => serialize($data)], ['k' => 'SYSTEM_QRCODE']);

        //删除原有的二维码图片
        if ($ret) {
            if ($serviceFile) {
                $servicePath = $rootPath . 'public' . parse_url($oldData['wechat_customer_service'])['path'];
                if (file_exists($servicePath)) @unlink($servicePath);
            }

            /*if ($accountFile) {
                $accountPath = $rootPath . 'public' . parse_url($oldData['wechat_official_account'])['path'];
                if (file_exists($accountPath)) @unlink($accountPath);
            }*/

        }
        // 缓存 上传的二维码
        SystemRedis::cacheQrcode();

        return $ret ? $this->message(1, '保存成功') : $this->message(0, '保存失败');
        /*} catch (\Exception $e) {

            return $this->message(0, '二维码更新失败2');
        }*/
    }

    /**
     * 删除二维码
     * @return \think\response\Json
     */
    public function deleteQrcode()
    {
        $type = input('post.type', '', FILTER_SANITIZE_STRING);
        $file_path = input('post.file_path', '', FILTER_SANITIZE_STRING);
        $path_array = parse_url($file_path);
        $path = Env::get('ROOT_PATH') . 'public' . $path_array['path'];

        $qrcode = systemRedis::getQrcode();
        if ($type == 'service') {
            $data['wechat_customer_service'] = '';
            $data['wechat_official_account'] = $qrcode['wechat_official_account'];
            $data['wechat_android'] = $qrcode['wechat_android'];
            $data['wechat_ios'] = $qrcode['wechat_ios'];
        }

        if ($type == 'account') {
            $data['wechat_customer_service'] = $qrcode['wechat_customer_service'];
            $data['wechat_official_account'] = '';
            $data['wechat_android'] = $qrcode['wechat_android'];
            $data['wechat_ios'] = $qrcode['wechat_ios'];
        }

        if ($type == 'wechat_android') {
            $data['wechat_customer_service'] = $qrcode['wechat_customer_service'];
            $data['wechat_official_account'] = $qrcode['wechat_official_account'];
            $data['wechat_android'] = '';
            $data['wechat_ios'] = $qrcode['wechat_ios'];
        }

        if ($type == 'wechat_ios') {
            $data['wechat_customer_service'] = $qrcode['wechat_customer_service'];
            $data['wechat_official_account'] = $qrcode['wechat_official_account'];
            $data['wechat_android'] = $qrcode['wechat_android'];
            $data['wechat_ios'] = '';
        }

        $ret = $this->systemModel->save(['v' => serialize($data)], ['k' => 'SYSTEM_QRCODE']);
        if ($ret) {
            if (file_exists($path)) unlink($path);
        }

        // 缓存 上传的二维码
        SystemRedis::cacheQrcode();

        return $ret ? $this->message(1, '二维码删除成功') : $this->message(0, '二维码删除失败');
    }
    
    public function getCashCoupon()
    {
        //获取收益宝的配置
        $value = $this->get('SYSTEM_CASH_COUPON');
        $data['is_open'] = $value['is_open'] ?? 0;
        $data['cash_coupon_money'] = $value['cash_coupon_money'] ?? '';
        $data['expiry_time'] = $value['expiry_time'] ?? '';
        $data['expiry_unit'] = $value['expiry_unit'] ?? '';
        $data['in_loss'] = $value['in_loss'] ?? '';
        $data['close_position_time'] = $value['close_position_time'] ?? '';

        return $this->message(1, '', $data);
    }

    /**
     * 保存交易费用配置
     *
     * @return \think\response\Json
     */
    public function saveCashCoupon()
    {
        // 用户数据
        $value['is_open'] = input('post.is_open', 0, FILTER_SANITIZE_NUMBER_INT);
        $value['cash_coupon_money'] = input('post.cash_coupon_money', 1, FILTER_SANITIZE_NUMBER_FLOAT);
        $value['expiry_time'] = input('post.expiry_time', 1, FILTER_SANITIZE_NUMBER_INT);
        $value['expiry_unit'] = input('post.expiry_unit', 0, FILTER_SANITIZE_NUMBER_INT);
        $value['in_loss'] = input('post.in_loss', 0, FILTER_SANITIZE_NUMBER_INT);
        $value['close_position_time'] = input('post.close_position_time', '', FILTER_SANITIZE_STRING);

        // 验证数据
        $result = $this->validate($value, 'System.saveCashCoupon');
        if ($result !== true) return $this->message(0, $result);

        $ret = $this->systemModel->save(['v' => serialize($value)], ['k' => 'SYSTEM_CASH_COUPON']);

        // 缓存 代金券设置
        SystemRedis::cacheCashCoupon();

        return $ret ? $this->message(1, '保存成功',serialize($value)) : $this->message(0, '保存失败',serialize($value));
    }

}
