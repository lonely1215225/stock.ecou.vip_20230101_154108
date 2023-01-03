<?php
namespace app\stock\validate;

use think\Validate;

class System extends Validate
{

    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);

        // 扩展验证器 - 验证是否为有效的小时分钟格式
        self::extend('isHHmm', function ($value) {
            $params = explode(':', $value);
            if (count($params) != 2) return '不是正确的小时分钟格式';
            if (intval($params[0]) < 0 || intval($params[0] > 23)) return '不是正确的小时分钟格式';
            if (intval($params[1]) < 0 || intval($params[1] > 59)) return '不是正确的小时分钟格式';

            return true;
        });

        /**
         * 扩展验证器 - 验证时间范围
         *
         * @param mixed $value 要验证字段的值
         * @param array $params 接收三个参数'start':开始时间,'end':结束时间,'name':本字段的名称
         */
        self::extend('timeBetween', function ($value, $params) {
            list($start, $end, $name) = explode(',', $params);
            $t      = intval(str_replace(':', '', $value));
            $startT = intval(str_replace(':', '', $start));
            $endT   = intval(str_replace(':', '', $end));

            if ($t < $startT || $t > $endT) return $name . "只能在{$start}~{$end}之间";

            return true;
        });

        /**
         * 扩展验证器 - 休市时间不能早于开市时间
         *
         * @param mixed $value 要验证字段的值
         * @param array $params 接收两个参数'key':要对比的字段名名,'name':本字段的名称
         */
        self::extend('timeLT', function ($value, $params, $data) {
            list($key, $name) = explode(',', $params);
            if (intval(str_replace(':', '', $value)) <= intval(str_replace(':', '', $data[$key]))) return $name . '不能早于开市时间';

            return true;
        });
    }

    protected $rule = [
        // 交易时间
        'am_market_open_time'  => 'require|isHHmm|timeBetween:09:15,11:30,上午开市时间',
        'am_market_close_time' => 'require|isHHmm|timeBetween:09:15,11:30,上午休市时间|timeLT:am_market_open_time,上午休市时间',
        'pm_market_open_time'  => 'require|isHHmm|timeBetween:13:00,15:00,下午开市时间',
        'pm_market_close_time' => 'require',
        // 交易费用
        'service_fee'          => 'require|float|gt:0',
        'service_fee_min'      => 'require|float|gt:0',
        'management_fee'       => 'require|float|gt:0',
        'stamp_tax'            => 'require|float|gt:0',
        'transfer_fee'         => 'require|float|gt:0',
        'management_fee_s'     => 'require|float|egt:0',
        'deposit_rate'         => 'require|float|gt:0',
        'deposit_rate_s'       => 'require|float|gt:0',
        // 涨跌幅禁买线
        'limit_rate'           => 'require|float|between:0,0.1',
        'yuebao_fee'           => 'require|float|gt:0',
    ];

    protected $message = [
        'am_market_open_time.require'  => '请填写上午开市时间',
        'am_market_close_time.require' => '请填写上午休市时间',
        'pm_market_open_time.require'  => '请填写下午开市时间',
        'pm_market_close_time.require' => '请填写下午休市时间',
        // 交易费用
        'service_fee.require'          => '请填写手续费比例',
        'service_fee_min.require'      => '请填写手续费最低收取',
        'management_fee.require'       => '请填写管理费比例',
        'stamp_tax.require'            => '请填写印花税比例',
        'transfer_fee.require'         => '请填写过户费比例',
        'management_fee_s.require'     => '请填写停牌管理费比例',
        'deposit_rate.require'         => '请填写履约保证金比例',
        'deposit_rate_s.require'       => '请填写停牌履约保证金比例',
        'service_fee.float'            => '手续费比例格式不正确',
        'service_fee_min.float'        => '手续费最低收取格式不正确',
        'management_fee.float'         => '管理费比例格式不正确',
        'stamp_tax.float'              => '印花税比例格式不正确',
        'transfer_fee.float'           => '过户费比例格式不正确',
        'management_fee_s.float'       => '停牌管理费比例格式不正确',
        'deposit_rate.float'           => '履约保证金比例格式不正确',
        'deposit_rate_s.float'         => '停牌履约保证金比例格式不正确',
        'service_fee.gt'               => '手续费比例必须大于0',
        'service_fee_min.gt'           => '手续费最低收取必须大于0',
        'management_fee.gt'            => '管理费比例必须大于0',
        'stamp_tax.gt'                 => '印花税比例必须大于0',
        'transfer_fee.gt'              => '过户费比例必须大于0',
        'management_fee_s.egt'         => '停牌管理费比例必须大于0',
        'deposit_rate.gt'              => '履约保证金比例必须大于0',
        'deposit_rate_s.gt'            => '停牌履约保证金比例必须大于0',
        'yuebao_fee.gt'                => '每万元收益比例必须大于0',
        // 涨跌幅禁买线
        'limit_rate.require'           => '请填写涨跌幅禁买线',
        'limit_rate.float'             => '涨跌幅禁买线格式不正确',
        'limit_rate.between'           => '涨跌幅禁买线应介于0到0.1之间',

        // 科创板涨跌幅禁买线
        'kec_limit_rate.require'       => '请填写科创板涨跌幅禁买线',
        'kec_limit_rate.float'         => '科创板涨跌幅禁买线格式不正确',
        'kec_limit_rate.between'       => '科创板涨跌幅禁买线应介于0到0.2之间',
    ];

    protected $scene = [
        'saveTradingTime' => ['am_market_open_time', 'am_market_close_time', 'pm_market_open_time', 'pm_market_close_time'],
        'saveTradingFee'  => ['service_fee', 'service_fee_min', 'management_fee', 'stamp_tax', 'transfer_fee'],
        'saveLimitRate'   => ['limit_rate'],
        'saveYuebao'      => ['is_open','yuebao_fee'],
        'saveCashCoupon'  => ['is_open', 'cash_coupon_money', 'expiry_time', 'expiry_unit', 'in_loss', 'close_position_time'],
    ];

}
