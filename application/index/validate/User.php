<?php
namespace app\index\validate;

use think\Validate;

class User extends Validate
{

    protected $rule = [
        'mobile'   => 'require|mobile|unique:user',
        //'captcha'  => 'require',
        'password' => 'require',
        'confirm'  => 'require|confirm:password',
        'code'     => 'require',
    ];

    protected $message = [
        'mobile.require'   => '请输入手机号',
        'mobile.unique'    => '该手机号已被注册',
        //'captcha.require'  => '请输入短信验证码',
        'password.require' => '请输入密码',
        'confirm.require'  => '请输入确认密码',
        'confirm.confirm'  => '您两次输入的密码不一致',
//        'code.require'     => '请输入推荐吗',
    ];

    // 验证场景 - 重置密码
    public function sceneResetPassword()
    {
        return $this->only(['mobile', 'captcha', 'password', 'confirm'])->remove('mobile', 'unique');
    }

    // 验证场景 - 注册
    public function sceneRegister()
    {
        //'captcha',
        return $this->only(['mobile', 'captcha', 'password', 'confirm']);
    }

    // 验证场景 - 登录
    public function sceneSignIn()
    {
        return $this->only(['mobile', 'password'])->remove('mobile', 'unique');
    }

    // 验证场景 - 发送验证码
    public function sceneSendSms()
    {
        return $this->only(['mobile'])->remove('mobile', 'unique');
    }

}
