<?php

namespace app\index\logic;

use app\common\model\ForcedSellLog;
use util\TradingUtil;

class ForcedSellLogic
{

    public static function create($type, $userAccount, $position, $targetPosition)
    {
        // 解析触发源信息
        $positionID = $position['position_id'];
        $isCashCoupon = $position['is_cash_coupon'];
        $stock = $position['stock'];
        $volumePosition = $position['volume_position'];
        $volumeForSell = $position['volume_for_sell'];
        $nowPrice = $position['price'];
        $stopLossPrice = $position['stop_loss_price'];
        $isSuspended = $position['is_suspended'];
        $stockValue = Calc::calcStockValue($nowPrice, $volumePosition);

        // 解析用户账户信息
        $userID = $userAccount['user_id'];
        if ($isCashCoupon) {
            $strategyBalance = $userAccount['cash_coupon'];
            $frozen = $userAccount['cash_coupon_frozen'];
        } else {
            $strategyBalance = $userAccount['strategy_balance'];
            $frozen = $userAccount['frozen'];
        }

        // 解析被平仓持仓信息
        $targetPositionID = $targetPosition['target_position_id'];
        $targetStock = $targetPosition['target_stock'];
        $sellVolume = $targetPosition['sell_volume'];
        $sellOrder = $targetPosition['sell_order'];
        $orderID = $targetPosition['order_id'];

        // 创建记录
        $ret = ForcedSellLog::create([
            // 交易日，触发类型，时间
            'trading_date' => TradingUtil::currentTradingDate(),
            'trigger_type' => $type,
            'trigger_time' => date('Y-m-d H:i:s'),

            // 用户及账户信息
            'user_id' => $userID,
            'strategy_balance' => $strategyBalance,
            'frozen' => $frozen,
            'strategy' => bcsub($strategyBalance, $frozen, 2),
            'additional_deposit' => $type == FORCED_SELL_TYPE_QUOTATION ? round(bcmul($stockValue, 0.01, 4), 2) : 0,

            // 触发源信息
            'position_id' => $positionID,
            'stock' => $stock,
            'stock_value' => $stockValue,
            'volume_position' => $volumePosition,
            'volume_for_sell' => $volumeForSell,
            'price' => $nowPrice,
            'stop_loss_price' => $stopLossPrice,
            'is_suspended' => $isSuspended,

            // 被平仓信息
            'target_position_id' => $targetPositionID,
            'target_stock' => $targetStock,
            'sell_volume' => $sellVolume,
            'sell_order' => $sellOrder,
            'order_id' => $orderID,
        ]);


        return $ret ? true : false;
    }

}
