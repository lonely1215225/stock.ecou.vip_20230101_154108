<?php
namespace app\pay\controller;

use app\common\model\UserBankCard;
use app\common\model\UserRecharge;
use authllpay\lib\LianLianPayNew;
use authllpay\lib\LLpayNotify;
use app\pay\config\llpyConfig;
use app\common\model\UserAccount;
use app\common\model\UserWalletLog;
use app\common\model\User;
use util\Debug;
use think\Db;

class  LianLianPayController extends BaseController
{

    /**
     * 连连支付入口
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        header('Access-Control-Allow-Origin:*');
        $payid = input('payid');
        $res   = UserRecharge::where(['id' => $payid, 'pay_state' => RECHARGE_PAY_WAIT])->find();

        if (!$res) {
            $this->result('', 0, '该订单不存在或已经支付不需再次支付', 'json');
        }
        $cardInfo = UserBankCard::where('user_id', $res['user_id'])->field('user_id,bank_number,real_name,id_card_number')->find();
        if ($cardInfo) {
            $acct_name = $cardInfo['real_name'];
            $id_no     = $cardInfo['id_card_number'];
            $card_no   = $cardInfo['bank_number'];
        } else {
            $acct_name = input('acct_name', '', [FILTER_SANITIZE_STRING, 'trim']);
            $id_no     = input('id_no', '', 'filter_id_card_number');
            $card_no   = input('card_no', '', FILTER_SANITIZE_NUMBER_INT);
            if (!$acct_name) return $this->message(0, '请输入用户名');
            if (!$id_no) return $this->message(0, '请输入银行卡开户人身份证号');
            if (!$card_no) return $this->message(0, '请输入银行卡号');

            // 保存用户银行卡信息
            UserBankCard::create([
                'user_id'        => $res['user_id'],
                'real_name'      => $acct_name,
                'id_card_number' => $id_no,
                'bank_number'    => $card_no,
                'state'          => BANK_CARD_UNBIND,
            ]);
            // 用户表中写入姓名
            User::update([
                'real_name' => $acct_name,
            ], [
                ['id', '=', $res['user_id']],
            ]);
        }
        // 订单号
        $no_order = date('YmdH') . str_pad($payid, 22, '0', STR_PAD_LEFT);
        // 生成32位充值流水号
        $upUser = UserRecharge::where(['id' => $payid, 'pay_state' => RECHARGE_PAY_WAIT])->update(['recharge_sn' => $no_order]);

        if (!$upUser) {
            $this->result('', 0, '订单生成失败,请重试', 'json');
        }
        $UserInfo = User::where(['id' => $res['user_id']])->find();
        //填写支付风控参数
        $risk_item = '{"frms_ware_category":"2026","user_info_mercht_userno":"' . $res['user_id'] . '","user_info_dt_register":"' . date('YmdHis', strtotime($UserInfo['create_time'])) . '","user_info_bind_phone":"' . $UserInfo['mobile'] . '","user_info_full_name":"' . $acct_name . '","user_info_id_no":"' . $id_no . '","user_info_identify_state":"1","user_info_identify_type":"3"}';
        /*$risk_item=["frms_ware_category"=>2026,
                      "user_info_mercht_userno"=>$res["user_id"],
                      "user_info_dt_register"=>date('YmdHis',strtotime($UserInfo["create_time"])),
                      "user_info_bind_phone"=>$UserInfo["mobile"],
                      "user_info_full_name"=>$acct_name,
                      "user_info_id_no"=>$id_no,
                      "user_info_identify_sate"=>1,
                      "user_info_identify_type"=>3];*/
        //        $risk_item="{'frms_ware_category':2026,'user_info_mercht_userno':'".$res["user_id"]."','user_info_dt_register':'".date('YmdHis',strtotime($UserInfo["create_time"]))."','user_info_bind_phone':'".$UserInfo["mobile"]."','user_info_full_name':'".$acct_name."','user_info_id_no':'".$id_no."','user_info_identify_sate':1,'user_info_identify_type':3}";
        //      dump(json_decode($risk_item,true));exit;
        //       echo $risk_item;exit;
        $llpyConfig = llpyConfig::getinfo();

        //拼接参数
        $parameter   = [
            'version'      => trim($llpyConfig['version']),
            'oid_partner'  => trim($llpyConfig['oid_partner']),
            'app_request'  => trim($llpyConfig['app_request']),
            'valid_order'  => trim($llpyConfig['valid_order']),
            'user_id'      => $res["user_id"],
            'id_type'      => 0,
            'id_no'        => $id_no,
            'busi_partner' => "101001",
            'no_order'     => $no_order,
            'dt_order'     => date('YmdHis', time()),
            'name_goods'   => '履约保证金',
            'info_order'   => 'lianlianpay',
            'money_order'  => $res['money'],
            'notify_url'   => url('lian_lian_pay/notify_url', '', '', true),
            // 返回商家
            'url_return'   => $this->request->scheme() . '://www.' . $this->request->rootDomain() . '/#/recharge',
            'card_no'      => $card_no,
            'acct_name'    => $acct_name,
            'risk_item'    => $risk_item,
            'pay_type'     => 'D',
            'sign_type'    => 'RSA',
            // 左上角返回地址
            "back_url"     => $this->request->scheme() . '://www.' . $this->request->rootDomain() . '/#/recharge',
        ];
        $llpaySubmit = new LianLianPayNew($llpyConfig);
        $html_text   = $llpaySubmit->buildRequestForm($parameter, 'post', '确认');
        echo $html_text;
    }

    /**
     * 连连支付回调函数
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notify_url()
    {

        $llpyConfig  = llpyConfig::getinfo();
        $llpayNotify = new LLpayNotify($llpyConfig);
        $llpayNotify->verifyNotify();
        if (!$llpayNotify->notifyResp) {
            return $this->message('9999', '交易失败');
        }

        $no_order = $llpayNotify->notifyResp['no_order'];//商户订单号
        //判断该订单是否已经支付成功
        $res = UserRecharge::where(['recharge_sn' => $no_order])
            ->find();

        if ($res['pay_state'] == RECHARGE_PAY_SUCCESS) {  //该订单已经支付成功
            return $this->message('0000', '交易成功');
        }

        Debug::debugArr($llpayNotify->notifyResp, 'lianlianpay_notifyUrl', null, 'paylog');

        if ($llpayNotify->result) {
            $oid_paybill = $llpayNotify->notifyResp['oid_paybill'];//连连支付单号
            $result_pay  = $llpayNotify->notifyResp['result_pay'];//支付结果，SUCCESS：为支付成功
            $money_order = $llpayNotify->notifyResp['money_order'];// 支付金额
            if ($result_pay != 'SUCCESS') {
                return $this->message('9999', '交易失败');
            }
            Db::startTrans();
            try {
                $upUser          = UserRecharge::where(['recharge_sn' => $no_order, 'pay_state' => RECHARGE_PAY_WAIT])
                    ->update(['pay_state' => RECHARGE_PAY_SUCCESS, 'third_order_sn' => $oid_paybill, 'real_money' => $money_order, 'pay_time' => time()]);
                $userAccountInfo = UserAccount::where('user_id', $res['user_id'])
                    ->find();

                $wallent_balance = $userAccountInfo['wallet_balance'] + $money_order;
                $total_recharge  = $userAccountInfo['total_recharge'] + $money_order;
                //更新余额 更新累计充值
                $accountRes = UserAccount::where('user_id', $res['user_id'])
                    ->update(['wallet_balance' => $wallent_balance, 'total_recharge' => $total_recharge]);

                //生成充值流水
                $payLog['user_id']        = $res['user_id'];
                $payLog['change_money']   = $money_order;
                $payLog['change_type']    = USER_WALLET_RECHARGE;
                $payLog['recharge_id']    = $res['id'];
                $payLog['before_balance'] = $userAccountInfo['wallet_balance'];
                $payLog['after_balance']  = $wallent_balance;
                $payLog['change_time']    = date('Y-m-d H:i:s', time());
                $walletlogRes             = UserWalletLog::create($payLog);

                if ($upUser && $accountRes && $walletlogRes) {
                    // 提交事务
                    Db::commit();

                    // 返回成功信息
                    return $this->message(1, '交易成功');
                } else {
                    // 提交事务
                    Db::rollback();

                    // 返回成功信息
                    return $this->message(0, '交易失败');
                }
            } catch (\Exception $e) {
                Db::rollback();

                // 返回失败信息
                return $this->message(0, '交易失败2');
            }

        }
    }

}
