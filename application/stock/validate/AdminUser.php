<?php
namespace app\stock\validate;

use think\Validate;
use think\facade\Session;

class AdminUser extends Validate
{

    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);

        // 自定义验证器 - 验证旧密码是否输入正确
        self::extend('checkOldPassword', function ($value) {
            $adminModel   = new \app\common\model\AdminUser();
            $passwordHash = $adminModel->where('id', Session::get('admin_id'))->value('password');

            return password_verify($value, $passwordHash) ? true : '您输入的旧密码错误';
        });
    }

    protected $rule = [
        'username'     => 'require',
        'password'     => 'require',
        'old_password' => 'require|checkOldPassword',
        'new_password' => 'require',
        'new_confirm'  => 'require|confirm:new_password',
    ];

    protected $message = [
        'username.require'     => '用户名不能为空',
        'password.require'     => '密码不能为空',
        'old_password.require' => '请输入旧密码',
        'new_password.require' => '请输入新密码',
        'new_confirm.require'  => '新输入确认新密码',
        'new_confirm.confirm'  => '您两次输入的新密码不一致',
        'mobile.mobile'        => '手机号输入有误',
    ];

    protected $scene = [
        'login'           => ['username', 'password'],
        'modify_password' => ['old_password', 'new_password', 'new_confirm'],
    ];

}
