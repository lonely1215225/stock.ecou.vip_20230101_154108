<?php
namespace app\index\logic;

use app\common\model\OrderPosition;
use util\RedisUtil;

class Funds
{

    /**
     * 返回用户所有持仓的统计数据
     * - 当$stockCode和$market均不为空时，同时返回对应股票的统计信息
     *
     * @param        $userId
     * @param string $stockCode
     * @param string $market
     *
     * @return array
     */
    public static function userAllPositionStatistic($userId, $stockCode = '', $market = '')
    {
        $positionList = OrderPosition::where('user_id', $userId)
            ->where('is_finished', false)
            ->where('market', 'NOT NULL')
            ->where('stock_code', 'NOT NULL')
            ->column('volume_position,position_price,market,stock_code,sum_sell_pal,sum_buy_value_cost,sum_sell_value_in,is_suspended,is_monthly', 'id');

        // 统计数据
        $ret = [
            'sumTotalPAL'          => 0, // 所有持仓的浮动盈亏汇总
            'sumNowValue'          => 0, // 所有持仓的当前市值
            'sumPositionIncome'    => 0, // 所有持仓收益汇总
            'sumNowValueSuspended' => 0, // 所有停牌持仓的当前市值
            'tomorrowMFee'         => 0, // 明日管理费
        ];

        // 指定股票的统计数据
        if ($stockCode && $market) {
            $ret['specific'] = [
                'totalPAL'       => 0, // 累计盈亏
                'nowValue'       => 0, // 当前市值
                'positionIncome' => 0, // 持仓收益
            ];
        }

        foreach ($positionList as $item) {
            // 行情数据
            $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);

            $nowPrice        = $quotation['Price'];
            $volumePosition  = $item['volume_position'];
            $positionPrice   = $item['position_price'];

            // 盈亏
            $totalPal           = Calc::calcTotalPAL($nowPrice, $positionPrice, $volumePosition, $item['sum_sell_pal']);
            $ret['sumTotalPAL'] = bcadd($ret['sumTotalPAL'], $totalPal, 2);

            // 当前市值
            $nowValue           = Calc::calcStockValue($nowPrice, $volumePosition);
            $ret['sumNowValue'] = bcadd($ret['sumNowValue'], $nowValue, 2);

            // 停盘股票市值
            if ($item['is_suspended']) {
                $ret['sumNowValueSuspended'] = bcadd($ret['sumNowValueSuspended'], $nowValue, 2);
            }

            // 明日管理费（不算月费）
            if ($item['is_monthly'] == false) {
                $isMonthly           = false;
                $mFee                = Calc::calcManagementFee($nowValue, $isMonthly, $item['is_suspended']);
                $ret['tomorrowMFee'] = bcadd($ret['tomorrowMFee'], $mFee, 2);
            }

            // 持仓收益 = 当前市值 + 总卖出市值 - 总买入市值
            $positionIncome           = Calc::calcPositionIncome($nowPrice, $positionPrice, $volumePosition);
            $ret['sumPositionIncome'] = bcadd($ret['sumPositionIncome'], $positionIncome, 2);

            // 指定股票的统计
            if ($market == $item['market'] && $stockCode == $item['stock_code']) {
                $ret['specific']['totalPAL']       = bcadd($ret['specific']['totalPAL'], $totalPal, 2);
                $ret['specific']['nowValue']       = bcadd($ret['specific']['nowValue'], $nowValue, 2);
                $ret['specific']['positionIncome'] = bcadd($ret['specific']['positionIncome'], $positionIncome, 2);
            }
        }

        return $ret;
    }

}
