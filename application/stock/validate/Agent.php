<?php
namespace app\stock\validate;

use think\Validate;

class Agent extends Validate
{

    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);

        // 自定义验证器 - 验证用户名是否已经存在
        self::extend('checkExistUser', function ($value) {
            $adminModel   = new \app\common\model\AdminUser();
            $isExistUser = $adminModel->where('username', $value)->find();

            return $isExistUser ? '该用户名已经存在，请重新输入' : true;
        });
    }

    protected $rule = [
        'username' => 'require|checkExistUser',
        'password' => 'require',
        'mobile'   => 'require|mobile',
        'id'       => 'require|number',
    ];

    protected $message = [
        'username.require' => '用户名不能为空',
        'password.require' => '密码不能为空',
        'mobile.require'   => '手机号不能为空',
        'mobile.mobile'    => '手机号输入有误',
        'id.require'       => 'id不能为空',
        'id.number'        => 'id只能为数字',
    ];

    protected $scene = [
        'addAgent'     => ['username', 'password', 'mobile'],
        'editAgent'    => ['mobile'],
        'toggleStatus' => ['id'],
        'saveNewPwd'   => ['id','password'],
    ];

}
