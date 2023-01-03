<?php
namespace app\index\logic;

use util\RedisUtil;

class Quotation
{

    /**
     * 依次取股票的五档行情价格
     * -- 用于成交价
     * -- 买入方向取卖1到卖5价
     * -- 卖出方向取买1到买5价
     *
     * @param $stockCode
     * @param $market
     * @param $direction
     *
     * @return int
     */
    public static function getQuotationPrice($stockCode, $market, $direction)
    {
        try {
            $quotation = RedisUtil::getQuotationData($stockCode, $market);
            if ($direction == TRADE_DIRECTION_BUY) {
                $sp5 = $quotation['Sp5'];
                $sp4 = $quotation['Sp4'];
                $sp3 = $quotation['Sp3'];
                $sp2 = $quotation['Sp2'];
                $sp1 = $quotation['Sp1'];

                $qPrice = $sp1;
                $qPrice = $qPrice ?: $sp2;
                $qPrice = $qPrice ?: $sp3;
                $qPrice = $qPrice ?: $sp4;
                $qPrice = $qPrice ?: $sp5;
                $qPrice = $qPrice ?: 0;
            } else {
                $bp5 = $quotation['Bp5'];
                $bp4 = $quotation['Bp4'];
                $bp3 = $quotation['Bp3'];
                $bp2 = $quotation['Bp2'];
                $bp1 = $quotation['Bp1'];

                $qPrice = $bp1;
                $qPrice = $qPrice ?: $bp2;
                $qPrice = $qPrice ?: $bp3;
                $qPrice = $qPrice ?: $bp4;
                $qPrice = $qPrice ?: $bp5;
                $qPrice = $qPrice ?: 0;
            }
        } catch (\Exception $e) {
            $qPrice = 0;
        }

        return $qPrice;
    }

}
