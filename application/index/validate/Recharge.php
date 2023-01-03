<?php
namespace app\index\validate;

use think\Validate;

class Recharge extends Validate
{

    protected $rule = [
        'pay_company_id' => 'require|gt:0',
        'money'          => 'require|float|gt:0',
    ];

    protected $message = [
        'pay_company_id.require' => '转账方式错误',
        'pay_company_id.gt'      => '转账方式错误',
        'money.require'          => '请填写转账金额',
        'money.float'            => '转账金额格式不正确',
        'money.gt'               => '转账金额必须大于0元',
    ];

    // 线下银行转账验证器
    public function sceneOffline()
    {
        return $this->only(['money', 'pay_company_id']);
    }

}
