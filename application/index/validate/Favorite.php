<?php
namespace app\index\validate;

use app\common\model\Favorite as FavoriteModel;
use think\Validate;

class Favorite extends Validate
{

    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);

        // 自定义验证器 - 验证自选记录是否唯一
        self::extend('unique_favorite', function ($market, $params, $data) {
            list($user_id, $stock_code) = explode(',', $params);
            $stockModel = new FavoriteModel();
            $count      = $stockModel
                ->where('market', $market)
                ->where('user_id', $data[$user_id])
                ->where('stock_code', $data[$stock_code])
                ->count();
            return $count ? '自选已存在' : true;
        });
    }

    protected $rule = [
        'stock_code' => 'require|number|length:6',
        'market'     => 'require|in:SH,SZ,BJ|unique_favorite:user_id,stock_code',
    ];

    protected $message = [
        'stock_code.require' => '没有code参数',
        'stock_code.number'  => 'code必须为数字',
        'stock_code.length'  => '代码长度不合法',
        'market.require'     => '没有market参数',
        'market.in'          => 'market参数格式错误',
    ];

    protected $scene = [
        'add' => ['stock_code', 'market'],
    ];
}
