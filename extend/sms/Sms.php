<?php

namespace sms;

class Sms
{

    //protected static $username = "ryifeng2020";
    //protected static $pwd = "QQii123456..";
    //protected static $urlInt = 'http://www.ussns.com/Api/sendInt'; //国际短信
    protected static $jixun_url   = 'http://www.96xun.com/Api/Sms'; //国内短信
    protected static $smsbao_url  = 'https://api.smsbao.com/sms'; //国内短信
    protected static $smsbao_wurl = 'https://api.smsbao.com/wsms'; //国际短信
    /**
     * 获得当前时间的正确时间戳格式，格式为MMddHHmmss
     *
     * @return false|string
     */
    public static function formatTime()
    {
        return date('mdHis');
    }

    /**
     * 获得当前的毫秒值，因为smsid不能为空，所以使用此数值
     *
     * @return float
     */
    public static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /*极讯旧版*/
    public static function sendSms($username,$password,$mobile, $content)
    {
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        $post_data   = array(
            'username' => $username,
            'pwd'      => $password,
            'msg'      => urlencode($content),
            'phone'    => $mobile,
            'timeout'  => 5 * 60
        );

        $postdata = http_build_query($post_data);
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, self::$jixun_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);//返回相应的标识
        curl_close($ch);

        return $result;
    }

    /**极迅 */
    public static function sendSmsQidian($username,$password,$mobile, $content)
    {
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        $post_data   = array(
            'Action'    => 'Add',
            'Uname'     => $username,
            'Upass'     => md5($password),
            'Mobile'    => $mobile,
            'Content'   => $content
        );

        $postdata = http_build_query($post_data);
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, self::$jixun_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);//返回相应的标识
        curl_close($ch);

        $result = json_decode($result, true);

        return $result;
    }

    /*短信宝*/
    public static function sendSmsDuanxinBao($username, $password, $mobile, $content)
    {
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        //$code = '852';
        $url = self::$smsbao_wurl;
        if (strlen($mobile) == 11) {
            $url = self::$smsbao_url;
        }
        $post_data = array(
            'u' => $username,
            'p' => md5($password),
            'm' => $mobile,
            'c' => $content
        );
        $postdata = http_build_query($post_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $code = curl_exec($ch);//返回相应的标识
        curl_close($ch);
        $result = self::getResult($code);
        return $result;
    }
    /**
     * 根据短信宝返回的代码返回短信信息
     *
     * @param $result
     * @return array
     */
    public static function getResult($result)
    {
        $returnCode = '';
        $returnMsg  = '';
        switch ($result) {
            case '0':
                $returnMsg = '短信发送成功';
                $returnCode = 0;
                break;
            case '-1':
                $returnMsg = '参数不全';
                $returnCode = -1;
                break;
            case '-2':
                $returnMsg = '服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！';
                $returnCode = -2;
                break;
            case '30':
                $returnMsg = '密码错误';
                $returnCode = 30;
                break;
            case '40':
                $returnMsg = '账号不存在';
                $returnCode = 40;
                break;
            case '41':
                $returnMsg = '余额不足';
                $returnCode = 41;
                break;
            case '42':
                $returnMsg = '帐户已过期';
                $returnCode = 42;
                break;
            case '43':
                $returnMsg = 'IP地址限制';
                $returnCode = 43;
                break;
            case '50':
                $returnMsg = '内容含有敏感词';
                $returnCode = 50;
                break;
            case '51':
                $returnMsg = '手机号码不正确';
                $returnCode = 51;
        }

        return array('ret_code' => $returnCode, 'ret_msg' => $returnMsg);
    }
}