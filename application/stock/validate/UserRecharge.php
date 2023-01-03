<?php
namespace app\stock\validate;

use think\Validate;

class UserRecharge extends Validate
{

    protected $rule = [
        'id'    => 'require|gt:0',
        'money' => 'require|float',
    ];

    protected $message = [
        'id.require'    => '用户id不能为空',
        'id.gt'         => '用户id必须大于0',
        'money.require' => '充值金额不能为空',
        'money.float'   => '充值金额格式不对',
    ];

    protected $scene = [
        'manualByAdmin' => ['id', 'money'],
    ];

}
