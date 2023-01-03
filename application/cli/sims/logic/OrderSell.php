<?php
namespace app\cli\sims\logic;

use app\cli\exception\TradingCheckException;
use app\common\model\Order;
use app\common\model\OrderPosition;
use util\OrderRedis;
use util\TradingRedis;
use util\TradingUtil;
use util\RedisUtil;
use think\Db;

/**
 * 委托卖出逻辑类
 *
 * @package app\index\logic
 */
class OrderSell
{

    /**
     * 用户卖出
     * -- 用户提交的【委托卖出】，务必调用该方法执行
     * -- 检测各项条件是否达标
     * -- 调用create方法写入系统数据并向上游发起委托
     *
     * @param $message
     *
     * @return array|string
     * -- 当且仅当返回值为array时，表明成功
     * -- 返回值为string类型时，为错误信息
     */
    public static function execute($message)
    {
        // 解析委托单数据
        $orderData = self::parseRecMsg($message);
        if (!is_array($orderData)) return $orderData;
        list ($positionID, $price, $volume, $token) = $orderData;

        // 检测交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return '当前日期不是交易日';

        // 检测是否在交易时间内
        if (!TradingUtil::isInTradingTime()) return '不在交易时间内';

        // 获取用户数据
        $userData = $token ? RedisUtil::getToken($token) : [];
        $userID   = $userData['user_id'] ?? 0;
        // 检测用户信息
        if (!$userID) return '非法操作';
        // 创建委托单
        $createResult = self::create($positionID, $userID, $price, $volume);

        return $createResult;
    }

    /**
     * 委托卖出主逻辑
     * -- 写入委托表
     * -- 持仓表中减去相应可卖数量
     * 说明：
     * -- 如果是用户发起的委托，必须调用execute方法，而不能直接调用该方法
     * -- 如果时系统发起的强平委托，直接调用该方法即可
     *
     * @param int  $positionID 持仓ID
     * @param int  $userID 用户ID
     * @param int  $volume 委托卖出数量
     * @param bool $isSystem 是否系统委托单
     * @param bool $isCondition 是否条件单，默认否
     *
     * @return array|string
     * -- 当且仅当返回值为array时，表明业务逻辑执行成功，否则不成功
     * -- 返回值为string类型时，为错误信息
     */
    public static function create($positionID, $userID, $price, $volume, $isSystem = false, $isCondition = false)
    {
        Db::startTrans();
        try {
            /** @var \think\Model $position 持仓对象 */
            $position = OrderPosition::where('id', $positionID)
                ->where('is_finished', false)
                ->where('user_id', $userID)
                ->field('id,user_id,stock_id,stock_code,market,volume_position,volume_for_sell,primary_account,volume_today,is_suspended,is_finished,is_monthly,is_cash_coupon')
                ->find();

            // 检测有无持仓
            if (!$position) {
                throw new TradingCheckException('没有持仓');
            }

            // 如果是强平，则卖出所有【可卖数量】
            if ($isSystem) {
                $volume = $position['volume_for_sell'];
            }

            // 没有可卖数量
            if ($position['volume_position'] <= 0 || $position['is_finished'] == true || $position['volume_for_sell'] <= 0) {
                throw new TradingCheckException('可卖数量不足');
            }

            // 超出可买数量
            if ($volume > $position['volume_for_sell']) {
                throw new TradingCheckException('最高可卖' . $position['volume_for_sell'] . '股');
            }

            // 股票标识
            $stockID   = $position['stock_id'];
            $stockCode = $position['stock_code'];
            $market    = $position['market'];
            
            // 检测是否停牌
            if ($position['is_suspended'] == 1) {
                throw new TradingCheckException('停牌股票不可卖出');
            };
            // 股票行情数据
            if($price > 0){
                $quotation = RedisUtil::getQuotationData($stockCode, $market);
                if (!$quotation) return '股票行情异常';
                if($price > $quotation['Highest']) throw new TradingCheckException ('出价不得高于涨停价！');
                if($price < $quotation['Lowest'])  throw new TradingCheckException ('出价不得低于跌停价！');
            }else{
                $price = 0;
            }
            // 委托单数据
            $orderData = [
                'user_id'           => $position['user_id'],
                'stock_id'          => $position['stock_id'],
                'stock_code'        => $stockCode,
                'market'            => $market,
                'primary_account'   => $position['primary_account'],
                'order_position_id' => $position['id'],
                'direction'         => TRADE_DIRECTION_SELL,
                'price'             => $price,
                'price_type'        => $price > 0 ? PRICE_TYPE_LIMIT : PRICE_TYPE_MARKET,
                'volume'            => $volume,
                'trading_date'      => TradingUtil::currentTradingDate(),
                'state'             => CANCEL_SUBMITTED,
                'is_system'         => $isSystem,
                'is_condition'      => $isCondition,
                'is_monthly'        => $position['is_monthly'],
                'is_cash_coupon'    => $position['is_cash_coupon']
            ];

            // 写入委托表
            $order = Order::create($orderData);

            // 减去持仓表的可卖数量
            $position['volume_for_sell'] = Db::raw("volume_for_sell-{$volume}");
            // 保存持仓信息
            $state = $position->save();

            if ($order && $state) {
                Db::commit();
                // 更新持仓数据缓存
                OrderRedis::cachePosition($position['id']);

                // 证券代码，证券类型，价格，数量，编号，主账号，卖出标志
                $ret = [$order['id'], $stockID, $market, $stockCode, $volume];
            } else {
                Db::rollback();
                $ret = '委托失败(E:1)';
            }
        } catch (TradingCheckException $e) {
            Db::rollback();
            $ret = $e->getMessage();
        } catch (\Exception $e) {
            Db::rollback();
            $ret = '委托失败(E:2)';
        }

        return $ret;
    }

    /**
     * 解析用户提交的数据
     *
     * @param array $message
     *
     * @return array|string
     * -- 当且仅当返回值是array类型时，表示用户数据可用，否者不可用
     * -- 当返回值是string类型时，为错误信息
     */
    public static function parseRecMsg($message)
    {
        if (!isset($message['Data'])) return '参数错误';
        
        // 取数据
        $positionID = $message['Data']['position_id'] ?? 0;
        $price      = $message['Data']['price'] ?? 0;
        $volume     = $message['Data']['volume'] ?? 0;
        $token      = $message['Token'] ?? '';

        // 过滤
        $positionID = filter_var($positionID, FILTER_SANITIZE_NUMBER_INT);
        $volume     = filter_var($volume, FILTER_SANITIZE_NUMBER_INT);

        // 验证
        if ($positionID <= 0) return '参数错误';
        if ($volume <= 0) return '参数错误';
        if (empty($token)) return '参数错误';

        return [$positionID, $price, $volume, $token];
    }

}
