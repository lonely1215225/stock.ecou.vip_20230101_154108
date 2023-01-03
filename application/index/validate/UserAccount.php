<?php
namespace app\index\validate;

use think\Validate;

class UserAccount extends Validate
{

    protected $rule = [
        'money'   => 'require|float|egt:100|elt:500000',
    ];

    protected $message = [
        'money.require'   => '请输入提现金额',
        'money.float'     => '提现金额格式错误',
        'money.lt'        => '最低提现金额不能低于100元',
        'money.gt'        => '最高现金额50万元',
    ];

    protected $scene = [
        'withdraw' => ['money'],
    ];

    public function sceneWithdraw()
    {
        return $this->only(['money']);
    }

}
