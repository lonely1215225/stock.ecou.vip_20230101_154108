<?php
namespace app\stock\validate;

use think\Validate;

class OrgBankCard extends Validate
{

    protected $rule = [
        'admin_id'            => 'require',
        'real_name'           => 'require',
        'id_card_number'      => 'require|idCard',
        'mobile'              => 'require|mobile',
        'bank_id'             => 'require',
        'province'            => 'require',
        'city'                => 'require',
        'branch'              => 'require',
        'bank_number'         => 'require',
        'confirm_bank_number' => 'require|confirm:bank_number',
    ];

    protected $message = [
        'admin_id.require'            => '用户id不能为空',
        'real_name.require'           => '请填写持卡人姓名',
        'id_card_number.require'      => '请填写身份证号',
        'id_card_number.idCard'       => '身份证号码不正确',
        'mobile.require'              => '请填写银行预留手机号码',
        'bank_id.require'             => '请选择开户行',
        'province.require'            => '请选择开户行所在省',
        'city.require'                => '请选择开户行所在市',
        'branch.require'              => '请填写开户支行名称',
        'bank_number.require'         => '请填写银行卡号',
        'confirm_bank_number.require' => '请填写确认卡号',
        'confirm_bank_number.confirm' => '您两次输入的银行卡号不一致',
    ];

}
