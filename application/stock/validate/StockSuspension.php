<?php
namespace app\stock\validate;

use think\Validate;

class StockSuspension extends Validate
{

    protected $rule = [
        'stock_code' => 'require',
    ];

    protected $message = [
        'stock_code.require' => '股票代码不能为空',
    ];

    protected $scene = [
        'saveStockSus' => ['stock_code'],
    ];

}
