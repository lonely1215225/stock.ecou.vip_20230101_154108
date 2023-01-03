<?php
namespace think;
require __DIR__ . '/../thinkphp/base.php';
Container::get('app')->run();

use authllpay\lib\LLpayNotify;
use app\payment\config\llpyConfig;

//计算得出通知验证结果
$llpyConfig  = llpyConfig::getinfo();
$llpayNotify = new LLpayNotify($llpyConfig);
$llpayNotify->verifyNotify();
if ($llpayNotify->result) {
    //验证成功
    //获取连连支付的通知返回参数，可参考技术文档中服务器异步通知参数列表
    $no_order    = $llpayNotify->notifyResp['no_order'];//商户订单号
    $oid_paybill = $llpayNotify->notifyResp['oid_paybill'];//连连支付单号
    $result_pay  = $llpayNotify->notifyResp['result_pay'];//支付结果，SUCCESS：为支付成功
    $money_order = $llpayNotify->notifyResp['money_order'];// 支付金额
    if ($result_pay == "SUCCESS") {
        // 更新用户提现申请表
        $upInfo = Db::table('md_user_withdraw')->where('withdraw_sn', $no_order)->update([
            'state'          => USER_WITHDRAW_SUCCESS,
            'success_time'   => time(),
            'third_order_sn' => $oid_paybill,
            'money'          => $money_order,
        ]);
        die("{'ret_code':'0000','ret_msg':'交易成功'}"); //请不要修改或删除
    }
} else {
    //验证失败
    die("{'ret_code':'9999','ret_msg':'交易失败'}");
}
