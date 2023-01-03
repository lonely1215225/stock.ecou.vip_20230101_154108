<?php
namespace app\stock\validate;

use think\Validate;

class OrgAccount extends Validate
{

    protected $rule = [
        'id'             => 'require',
        'total_withdraw' => 'gt:0',

    ];

    protected $message = [
        'id.require'        => '用户id不能为空',
        'total_withdraw.gt' => '提现金额应大于0',
    ];

    protected $scene = [
        'withdraw' => ['id', 'total_withdraw'],
    ];

}
