<?php
namespace app\payment\controller;

include_once(\think\facade\Env::get('root_path') . 'extend/instantllpay/lib/llpay_security.function.php');

use app\common\model\OrgBankCard;
use app\common\model\OrgWithdraw;
use app\common\model\UserWithdraw;
use app\common\model\UserBankCard;
use app\payment\config\llpyConfig;
use instantllpay\lib\LianLianinstantpay;

class  PayController extends BaseController
{

    // 用户提现申请操作
    public function userBackmoney($id)
    {
        $withdraw = UserWithdraw::where('id', $id)->find();

        // 判断是否存在该订单
        if (!$withdraw) {
            return ['status' => 0, 'msg' => '该订单不存在'];
        }
        // 判断是否是已审核状态
        if ($withdraw['state'] != USER_WITHDRAW_CHECKED) {
            return ['status' => 0, 'msg' => '该订单尚未审核或者已经代付'];
        }
        // 取银行卡信息
        $userBank = UserBankCard::where(['user_id' => $withdraw['user_id']])->find();
        // 生成商家单号，并保存
        $no_order                = date('YmdH') . str_pad($withdraw['id'], 22, '0', STR_PAD_LEFT);
        $withdraw['withdraw_sn'] = $no_order;
        $sRet                    = $withdraw->save();
        if (!$sRet) return $this->message(0, '生成商户订单号失败');

        // 获取配置参数
        $llpyConfig = llpyConfig::getinfo();
        //生成支付平台提交数剧
        $params['oid_partner'] = trim($llpyConfig['oid_partner']);//商户号
        $params['api_version'] = '1.0';//当前版本
        $params['sign_type']   = 'RSA'; //加密方式
        $params['no_order']    = $no_order; //商户订单号
        $params['dt_order']    = date('YmdHis', time()); //商户订单时间
        $params['money_order'] = $withdraw['apply_money'] - $withdraw['service_fee']; //提现金额
        $params['card_no']     = $userBank['bank_number']; //提现银行卡号
        $params['acct_name']   = $userBank['real_name'];//提现银行卡开户人姓名
        $params['info_order']  = '客户在线提现';
        $params['flag_card']   = '0';
        $params['notify_url']  = $this->request->domain() . '/notify_url.php';
        // 建立请求
        $llpaySubmit = new  LianLianinstantpay($llpyConfig);
        // 对参数排序加签名
        $sortPara = $llpaySubmit->buildRequestPara($params);
        // 传json字符串
        $json = json_encode($sortPara);
        // 支付接口地址
        $llpay_payment_url = 'https://instantpay.lianlianpay.com/paymentapi/payment.htm';
        $parameterRequest  = array(
            'oid_partner' => trim($llpyConfig['oid_partner']),
            'pay_load'    => ll_encrypt($json, $llpyConfig['LIANLIAN_PUBLICK_KEY']) //请求参数加密
        );
        //获取代付返回信息
        $request = $llpaySubmit->buildRequestJSON($parameterRequest, $llpay_payment_url);
        $request = json_decode($request, true);

        if ($request['ret_code'] == '0000') {
            return $this->message(1, '交易成功');
        } else {
            return $this->message($request['ret_code'], $request['ret_msg']);
        }
    }

    //代理商提现申请操作
    public function agentBackmoney($id)
    {
        $orgWithdrawInfo = OrgWithdraw::where('id', $id)->find();

        //判断是否存在该订单
        if (!$orgWithdrawInfo) {
            return ['status' => 0, 'msg' => '该订单不存在'];
        }
        //判断是否是已审核状态
        if ($orgWithdrawInfo['state'] != ORG_WITHDRAW_ADMIN_CHECKED) {
            return ['status' => 0, 'msg' => '该订单尚未审核或者已经代付'];
        }
        $orgBankInfo                    = OrgBankCard::where(['admin_id' => $orgWithdrawInfo['admin_id']])->find();
        $no_order                       = date('YmdH') . str_pad($orgWithdrawInfo['id'], 22, '0', STR_PAD_LEFT);
        $orgWithdrawInfo['withdraw_sn'] = $no_order;
        $sRet                           = $orgWithdrawInfo->save();
        if (!$sRet) return $this->message(0, '生成商户订单号失败');

        // 获取配置参数
        $llpyConfig = llpyConfig::getinfo();
        // 生成支付平台提交数剧
        $params['oid_partner'] = trim($llpyConfig['oid_partner']);//商户号
        $params['api_version'] = '1.0';//当前版本
        $params['sign_type']   = 'RSA'; //加密方式
        $params['no_order']    = $no_order; //商户订单号
        $params['dt_order']    = date('YmdHis', time()); //商户订单时间
        $params['money_order'] = $orgWithdrawInfo['money']; //提现金额
        $params['card_no']     = $orgBankInfo['bank_number']; //提现银行卡号
        $params['acct_name']   = $orgBankInfo['real_name'];//提现银行卡开户人姓名
        $params['info_order']  = '客户在线提现';
        $params['flag_card']   = '0';
        $params['notify_url']  = $this->request->domain() . '/agent_notify_url.php';
        // 建立请求
        $llpaySubmit = new  LianLianinstantpay($llpyConfig);
        // 对参数排序加签名
        $sortPara = $llpaySubmit->buildRequestPara($params);
        //传json字符串
        $json = json_encode($sortPara);
        // 支付接口地址
        $llpay_payment_url = 'https://instantpay.lianlianpay.com/paymentapi/payment.htm';
        $parameterRequest  = array(
            'oid_partner' => trim($llpyConfig['oid_partner']),
            'pay_load'    => ll_encrypt($json, $llpyConfig['LIANLIAN_PUBLICK_KEY']) //请求参数加密
        );
        // 获取代付返回信息
        $request = $llpaySubmit->buildRequestJSON($parameterRequest, $llpay_payment_url);
        $request = json_decode($request, true);

        if ($request['ret_code'] == '0000') {
            return $this->message(1, '交易成功');
        } else {
            return $this->message($request['ret_code'], $request['ret_msg']);
        }
    }

}
