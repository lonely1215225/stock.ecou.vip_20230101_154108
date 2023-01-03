<?php
namespace app\stock\validate;

use think\Validate;

class NonTradingDate extends Validate
{

    protected $rule = [
        'non_trading_date' => 'require|date',
    ];

    protected $message = [
        'non_trading_date.require' => '日期不能为空',
        'non_trading_date.date'    => '不是有效的日期',
    ];

    protected $scene = [
        'setNonTradingDate' => ['non_trading_date'],
    ];

}
