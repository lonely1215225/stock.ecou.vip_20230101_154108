<?php
namespace app\dash\validate;

use think\Validate;

class User extends Validate
{

    protected $rule = [
        'mobile'   => 'require|mobile|unique:user',
        'password' => 'require',
        'confirm'  => 'require|confirm:password',
        'code'     => 'require',
        'agent_id'=> 'require',
        'broker_id'=> 'require'
    ];

    protected $message = [
        'mobile.require'   => '请输入手机号',
        'mobile.unique'    => '该手机号已被注册',
        'mobile.mobile'    => '手机号格式不正确',
        'password.require' => '请输入密码',
        'confirm.require'  => '请输入确认密码',
        'confirm.confirm'  => '您两次输入的密码不一致',
        'agent_id.require' => '请选择代理商',
        'broker_id.require'=> '请选择经纪人',
        ''
    ];


    // 验证场景 - 注册
    public function sceneRegister()
    {
        return $this->only(['mobile', 'password', 'confirm']);
    }


}
