<?php
namespace app\stock\validate;

use think\Validate;

class UserAccount extends Validate
{

    protected $rule = [
        'user_id'      => 'require|gt:0',
        'change_money' => 'require|float',
        'remark'       => 'require',
        'ctype'        => 'in:1,2',
    ];

    protected $message = [
        'user_id.require'      => '用户id不能为空',
        'user_id.gt'           => '用户id必须大于0',
        'change_money.require' => '策略金金额不能为空',
        'change_money.float'   => '策略金金额格式不对',
        'remark.require'       => '备注信息不能为空',
        'ctype.in'             => '变动类型的值应为1或2',
    ];

    protected $scene = [
        'saveStrategyBalance' => ['user_id', 'strategy_balance', 'remark','ctype'],
    ];

}
