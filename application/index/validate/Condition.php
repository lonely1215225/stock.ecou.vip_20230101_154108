<?php
namespace app\index\validate;

use think\Validate;

class Condition extends Validate
{

    protected $rule = [
        'stock_code'      => 'require|number|length:6',
        'market'          => 'require|in:SH,SZ',
        'trigger_compare' => 'require|in:egt,elt',
        'trigger_price'   => 'require|float',
        'direction'       => 'require|in:buy,sell',
        'volume'          => 'require|number|gt:0',
    ];

    protected $message = [
        'stock_code.require'      => '请选择股票',
        'stock_code.number'       => '股票代码格式错误',
        'stock_code.length'       => '股票代码格式错误',
        'market.require'          => '请选择证券市场',
        'market.in'               => '证券市场格式错误',
        'trigger_compare.require' => '请选择当前价格比较条件',
        'trigger_compare.in'      => '当前价格比较条件格式错误',
        'trigger_price.require'   => '请填写当前价格',
        'trigger_price.float'     => '当前价格式错误',
        'direction.require'       => '请选择委托方向',
        'direction.in'            => '委托方向格式错误',
        'volume.require'          => '请填写委托股数',
        'volume.number'           => '委托股数格式错误',
        'volume.gt'               => '委托股数格式错误',
    ];

    protected $scene = [
        'create' => ['stock_code', 'market', 'trigger_compare', 'trigger_price', 'direction', 'volume', 'price', 'price_type'],
    ];

}
