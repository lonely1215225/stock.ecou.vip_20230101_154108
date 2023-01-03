<?php
namespace sms;

use think\facade\Session;
use util\CaptchaRedis;
use util\SystemRedis;
class SmsUtil extends Sms
{

    const CAPTCHA_REGISTER = 'register';
    const CAPTCHA_RESET_PASSWORD = 'reset_password';
    const CAPTCHA_WITHDRAW = 'withdraw';
    const CAPTCHA_ADMIN_LOGIN = 'admin_login';
	private static $model   = 'smsbao';//当前使用通道 可选qidian或smsbao
	private static $value   = ['Status'=>1,'ret_code'=>0];
	private static $channel = ['qidian'=>'Status','smsbao'=>'ret_code']; //返回成功的标识
    /**
     * 发送验证码
     * -- 发送注册验证码
     * -- 发送找回密码验证码
     * -- 发送提现验证码
     *
     * @param string $mobile 11位手机号码
     * @param string $type 验证码类型
     *
     * @return bool
     */
    public static function sendCaptcha($mobile, $type)
    {
        $smsdata = SystemRedis::getConfig();
        $smsname = $smsdata['sms_name']??'';
        // 生成验证码
        $captcha = Session::has('sms_captcha') ? Session::get('sms_captcha')['code'] : create_captcha();

        // 设置Session
        Session::set('sms_captcha', ['code' => $captcha, 'time' => time()]);

        // 短信内容
        if (self::CAPTCHA_REGISTER == $type) {
            $content = "【{$smsname}】您的注册验证码是：{$captcha}。该验证码仅用于身份验证，请勿泄露给他人使用。";
        } elseif (self::CAPTCHA_RESET_PASSWORD == $type) {
            $content = "【{$smsname}】验证码：{$captcha}。您正在使用找回密码功能，仅用于身份验证，请勿泄露给他人使用。";
        } elseif (self::CAPTCHA_WITHDRAW == $type) {
            $content = "【{$smsname}】验证码：{$captcha}。该验证码仅用于身份验证，请勿泄露给他人使用。";
        } elseif (self::CAPTCHA_ADMIN_LOGIN == $type) {
            $content = "【{$smsname}】验证码：{$captcha}。该验证码仅用于身份验证，请勿泄露给他人使用。";
        } else {
            $content = '';
        }
        $username = $smsdata['sms_use']??'';
        $password = $smsdata['sms_pwd']??'';
        if(!$username)return false;
        if(!$password)return false;
        if(self::$model=='qidian')$res = self::sendSmsQidian($username,$password,$mobile, $content);//极讯
        if(self::$model=='smsbao')$res = self::sendSmsDuanxinBao($username,$password,$mobile, $content);//短信宝
        $flag = self::$channel[self::$model];
        //print_r(self::$value[$flag]);exit;
        if ($res[$flag] == self::$value[$flag]) {
            // Redis 缓存短信验证码
            CaptchaRedis::cacheCaptcha($mobile, $captcha);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送履约金不足警告短信
     *
     * @param $mobile
     * @param $market
     * @param $stockCode
     * @param $deposit
     * @param $loss
     *
     * @return bool
     */
    public static function sendWarningSms($mobile, $market, $stockCode, $deposit, $loss)
    {
        $smsdata = SystemRedis::getConfig();
        $smsname = $smsdata['sms_name']??'';
        $username = $smsdata['sms_use']??'';
        $password = $smsdata['sms_pwd']??'';
        
        $content = "【{$smsname}】尊敬的客户:您的{$market}{$stockCode}履约金:{$deposit}已亏损:{$loss}元,请及时关注可用余额是否充足。";
        
        if(!$username)return false;
        if(!$password)return false;
        if(self::$model=='qidian')$res = self::sendSmsQidian($username,$password,$mobile, $content);//极讯
        if(self::$model=='smsbao')$res = self::sendSmsDuanxinBao($username,$password,$mobile, $content);//短信宝
        //$res     = json_decode($res, true);
        $flag = self::$channel[self::$model];
        if ($res[$flag] == self::$value[$flag]) { //起点
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送追加保证金短信
     *
     * @param $mobile
     * @param $positionID
     * @param $market
     * @param $stockCode
     * @param $deposit
     * @param $volume
     * @param $stopLossPrice
     *
     * @return bool
     */
    public static function sendAddDeposit($mobile, $positionID, $market, $stockCode, $deposit, $volume, $stopLossPrice)
    {
        $smsdata = SystemRedis::getConfig();
        $smsname = $smsdata['sms_name']??'';
        $username = $smsdata['sms_use']??'';
        $password = $smsdata['sms_pwd']??'';
        
        $content = "【{$smsname}资管】{$positionID}自动追加履约金:{$deposit}({$market}{$stockCode} 股数{$volume})止损参数:{$stopLossPrice}";
        if(!$username)return false;
        if(!$password)return false;
        if(self::$model=='qidian')$res = self::sendSmsQidian($username,$password,$mobile, $content);//极讯
        if(self::$model=='smsbao')$res = self::sendSmsDuanxinBao($username,$password,$mobile, $content);//短信宝
        //$res     = json_decode($res, true);
        $flag = self::$channel[self::$model];
        if ($res[$flag] == self::$value[$flag]) { //起点
            return true;
        } else {
            return false;
        }
    }

}
