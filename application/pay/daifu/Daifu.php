<?php
namespace app\pay\daifu;

include_once(\Env::get("root_path") . 'extend/instantllpay/lib/llpay_security.function.php');

use app\common\model\UserWithdraw;
use app\common\model\UserBankCard;
use app\pay\config\llpyConfig;
use instantllpay\lib\LianLianinstantpay;


class  Daifu extends BaseController
{

    //用户提现申请操作
    public function userBackmoney($id)
    {
        $userWithdrawInfo = UserWithdraw::where("id", $id)
            ->find()
            ->toArray();
        //        dump($userWithdrawInfo);
        //判断是否存在该订单
        if (!$userWithdrawInfo) {
            return ["status" => 0, "msg" => "该订单不存在"];
        }
        //判断是否是已审核状态
        if ($userWithdrawInfo["state"] != USER_WITHDRAW_CHECKED) {
            return ["status" => 0, "msg" => "该订单尚未审核或者已经代付"];
        }
        $userBankInfo = UserBankCard::where(["user_id" => $userWithdrawInfo["user_id"]])
            ->find()
            ->toArray();
        // "6228480259060129576"
        $no_order = str_pad($userWithdrawInfo["id"], 32, '0', STR_PAD_LEFT);
        //获取配置参数
        $llpyConfig = llpyConfig::getinfo();
        //生成支付平台提交数剧
        $daifuList["oid_partner"] = trim($llpyConfig['oid_partner']);//商户号
        $daifuList["api_version"] = "1.0";//当前版本
        $daifuList["sign_type"]   = "RSA"; //加密方式
        $daifuList["no_order"]    = $no_order; //商户订单号
        $daifuList["dt_order"]    = date("YmdHis", time()); //商户订单时间
        $daifuList["money_order"] = $userWithdrawInfo["money"]; //提现金额
        $daifuList["card_no"]     = $userBankInfo["bank_number"]; //提现银行卡号
        $daifuList["acct_name"]   = $userBankInfo["real_name"];//提现银行卡开户人姓名
        $daifuList["info_order"]  = "客户在线提现";
        $daifuList["flag_card"]   = "0";
        $daifuList["notify_url"]  = url("pay/daifu/callBackUserWithdraw", ['no_order'=>$no_order], "", true);
        return $daifuList["notify_url"];
        //建立请求
        $llpaySubmit = new  LianLianinstantpay($llpyConfig);
        //对参数排序加签名
        $sortPara = $llpaySubmit->buildRequestPara($daifuList);
        //传json字符串
        $json = json_encode($sortPara);
        //支付接口地址
        $llpay_payment_url = 'https://instantpay.lianlianpay.com/paymentapi/payment.htm';
        $parameterRequest  = array(
            "oid_partner" => trim($llpyConfig['oid_partner']),
            "pay_load"    => ll_encrypt($json, $llpyConfig['LIANLIAN_PUBLICK_KEY']) //请求参数加密
        );
        //获取代付返回信息
        $request = $llpaySubmit->buildRequestJSON($parameterRequest, $llpay_payment_url);
        $request = json_decode($request, true);
        if ($request["ret_code"] = '0000') {
            return $this->message(1, '交易成功');
        } else {
            return $this->message($request["ret_code"], $request["ret_msg"]);
        }

    }

    //代理商提现申请操作
    public function agentBackmoney()
    {

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
