<?php
namespace app\pay\controller;

use app\common\model\UserRecharge;
use app\common\model\PayCompany;

class  IndexController extends BaseController
{

    /**
     * 充值主入口
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');

        $payComID = input("id", 0, FILTER_SANITIZE_NUMBER_INT);
        $money    = input("money", 0, 'filter_float');

        // 查询是否存在该支付方式
        $payList = PayCompany::where('id', $payComID)
            ->where('is_open', true)
            ->field('id,name,pay_type,is_open,update_time,min,max,url')
            ->find();

        if ($payList) {
            // 判断该支付方式最低限额
            if ($money < $payList["min"]) {
                $this->result('', 0, '该支付金额单笔最低为' . $payList['min'] . '元', 'json');
            }

            // 判断该支付方式最低限额
            if ($money > $payList["max"]) {
                $this->result('', 0, '该支付金额单笔最高为' . $payList['max'] . '元', 'json');
            }

            // 写入充值记录
            $res = UserRecharge::create([
                'user_id'        => $this->userId,
                'money'          => $money,
                'pay_company_id' => $payComID,
            ]);

            if ($res) {
                $resUrl = url($payList['url'], ['money' => $money, 'payid' => $res['id']], '', true);
                $this->result($resUrl, 1, '跳转支付', 'json');
            } else {
                $this->result('', 0, '支付失败，请重试', 'json');
            }
        } else {
            $this->result('', 0, '不存在该支付方式', 'json');
        }
    }

    /**
     * 返回商家
     * -- 由于连连支付返回商家是POST方式
     * -- 故将源地址通过nginx转发至此地址，重新跳转
     */
    public function back_url()
    {
        $this->redirect($this->request->scheme() . '://www.' . $this->request->rootDomain() . '/#/recharge');
    }

}
