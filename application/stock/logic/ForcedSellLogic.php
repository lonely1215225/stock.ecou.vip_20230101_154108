<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/2 0002
 * Time: 22:46
 */

namespace app\stock\logic;

use app\common\model\ForcedSellLog;
use app\common\model\OrderPosition;
use app\cli\sims\logic\OrderSell;
use util\OrderRedis;
use util\TradingUtil;
use util\QuotationRedis;
use util\RedisUtil;

class ForcedSellLogic
{
    /**
     * 解析强平数据，并发起强平
     *
     * @param array $recMsg
     * @param int $clientID
     */
    public static function forcedSell($positionID, $originPosition, $userAccount)
    {
        $originPositionID = $positionID;
        if (!$positionID) return '参数错误';

        // 触发前的持仓数据，用户账户数据
        $forcedSellType = FORCED_SELL_TYPE_HAND;
        $originPositionData = $originPosition;
        $uAccountData = $userAccount;

        // 获取持仓信息
        $position = OrderPosition::where('id', $positionID)
            ->where('is_finished', false)
            ->field('id,user_id,market,stock_code,stock_id,primary_account,volume_position,volume_for_sell,sum_buy_value_cost,sum_sell_value,sum_deposit,sum_deposit,stop_loss_price,is_finished,is_suspended,is_monthly')
            ->find();

        $userID = $position['user_id'];
        $volumeForSell = $position['volume_for_sell'];
        $isSuspended = $position['is_suspended'];

        // 本持仓有可卖数量，卖掉本持仓的所有可卖数量，停牌股票不能卖出
        if ($volumeForSell <= 0) return '持仓可卖数量不足，不能被卖出';
        if ($isSuspended) return '停牌股票不能被卖出';

        // 创建强平委托单，并向上游发起强平委托
        $isSystem = false;
        $result = OrderSell::create($positionID, $userID, $volumeForSell, $isSystem);

        // 如果已经创建了委托单
        if (isset($result) && is_array($result)) {
            // 解析委托单结果
            list ($orderID, $stockID, $market, $stockCode, $volume) = $result;

            // 记录强平日志
            try {
                // 被强平的持仓数据
                $targetPositionData = [
                    'target_position_id' => $positionID,
                    'target_stock' => $position['market'] . $position['stock_code'],
                    'sell_volume' => $volume,
                    'sell_order' => $positionID == $originPositionID ? FORCED_SELL_ORDER_SELF : FORCED_SELL_ORDER_IN_ORDER,
                    'order_id' => $orderID,
                ];
                //创建强平记录
                $result = self::create($forcedSellType, $uAccountData, $originPositionData, $targetPositionData);
                if ($result == false) return '强平日志创建失败';
            } catch (\Exception $e) {
                //$e->getFile();
                //$e->getLine();
                //$e->getMessage();
                //$e->getTraceAsString();
                return '强平失败';
            }

            // 成交
            $direction = TRADE_DIRECTION_SELL;
            self::addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);

            OrderRedis::cachePosition($positionID);
            OrderRedis::cacheUserStrategy($userID);
            OrderRedis::cacheAllPosition();
            OrderRedis::cachePositionUserStrategy();

            return true;
        }

        return $result;

    }

    public static function create($type, $userAccount, $position, $targetPosition)
    {
        // 解析触发源信息
        $positionID = $position['position_id'];
        $stock = $position['stock'];
        $volumePosition = $position['volume_position'];
        $volumeForSell = $position['volume_for_sell'];
        $nowPrice = $position['price'];
        $stopLossPrice = $position['stop_loss_price'];
        $isSuspended = $position['is_suspended'];
        $stockValue = round(bcmul($nowPrice, $volumePosition, 4), 2);

        // 解析用户账户信息
        $userID = $userAccount['user_id'];
        $strategyBalance = $userAccount['strategy_balance'];
        $frozen = $userAccount['frozen'];

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
            'additional_deposit' => 0,

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

    /**
     * 加入待成交列表
     *
     * @param int $orderID
     * @param int $volume
     * @param string $direction
     * @param string $market
     * @param string $stockCode
     * @param bool $delay
     */
    public static function addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode, $delay = true)
    {
        // 加入持仓订阅列表
        QuotationRedis::addPositionSubscribe($market, $stockCode);

        // 加入待成交列表
        $key = 'waiting_deal_' . $market . $stockCode;
        $value = implode(',', [$orderID, $volume, $direction, $market, $stockCode]);
        RedisUtil::redis()->rPush($key, $value);
        RedisUtil::redis()->expireAt($key, RedisUtil::midnight());
    }

    /**
     * 统一数据返回格式
     * - 约定 1 => 成功
     *       0 => 失败
     *       403 => 未登录
     *
     * @param int $code 状态码
     * @param string $msg 消息
     * @param array $data 数据
     * @param string $desc 接口描述
     *
     * @return \think\response\Json
     */
    protected static function message($code, $msg = '', $data = [], $desc = '')
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');

        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'desc' => $desc,
        ]);
    }
}