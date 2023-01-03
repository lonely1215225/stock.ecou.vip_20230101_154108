<?php
namespace app\stock\validate;

use think\Validate;

class Stock extends Validate
{

    protected $rule = [
        'stock_code'  => 'require',
        'stock_name'  => 'require',
        'id'          => 'require|number',
    ];

    protected $message = [
        'stock_code.require'  => '股票代码不能为空',
        'stock_name.require'  => '股票名称不能为空',
        'market_time.date'    => '日期格式无效',
        'id.require'          => 'ID不能为空',
        'id.number'           => 'ID必须为数字',
    ];

    protected $scene = [
        'saveStock'    => ['stock_code', 'stock_name'],
        'toggleStatus' => ['id'],
    ];

}
