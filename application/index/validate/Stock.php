<?php
namespace app\index\validate;

use think\Validate;

class Stock extends Validate
{

    protected $rule = [
        'stock_code' => 'require|number|length:6',
        'market'     => 'require|in:SH,SZ,BJ',
        'keyword'    => 'require',
    ];

    protected $message = [
        'stock_code.require' => '参数格式错误',
        'stock_code.number'  => '参数格式错误',
        'stock_code.length'  => '参数格式错误',
        'market.require'     => '参数格式错误',
        'market.in'          => '参数格式错误',
        'keyword.require'    => '参数格式错误',
    ];

    protected $scene = [
        'read'   => ['stock_code', 'market'],
        'search' => ['keyword'],
        'market' => ['stock_code'],
    ];

}
