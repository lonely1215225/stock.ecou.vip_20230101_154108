<?php

namespace app\cli\sims\logic;

use app\cli\exception\TradingCheckException;
use app\common\model\Order;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use think\Db;
use util\BasicData;
use util\OrderRedis;
use util\RedisUtil;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 委托买入业务逻辑类
 *
 * @package app\index\logic
 */
class OrderBuy
{

    /**
     * 用户买入
     * -- 用户提交的【委托买入】，务必调用该方法执行
     * -- 检测各项条件是否达标
     * -- 调用create方法写入系统数据并向上游发起委托
     *
     * @param array $message
     *
     * @return array|string
     * -- 当且仅当返回值为array时，表明成功
     * -- 返回值为string类型时，为错误信息
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function execute($message)
    {
        // 解析委托单数据
        $orderData = self::parseRecMsg($message);
        if (!is_array($orderData)) return $orderData;
        
        list ($market, $stockCode, $buyPrice, $volume, $token, $isMonthly, $isCashCoupon) = $orderData;
        
        // 检测交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return '当前日期不是交易日';

        // 检测是否在交易时间内
        if (!TradingUtil::isInTradingTime()) return '不在交易时间内';

        // 检测股票是否存在
        if (!RedisUtil::isStockExist($stockCode, $market)) return '股票不存在';

        // 获取用户数据
        $userData = $token ? RedisUtil::getToken($token) : [];

        $userID = $userData['user_id'] ?? 0;
        // 用户信息检测
        if (!$userID) return '非法操作';

        $userData = OrderRedis::getUserData($userID);
        if ($userData['is_deny_buy'] == 1) return '用户被禁止买入';
        
        // 股票基础数据
        $stockData = RedisUtil::getStockData($stockCode, $market);
        
        if (!$stockData) return '股票异常不可交易';

        if ($stockData['is_kechuang'] == 1 && $volume < 200) return '科创板块交易量不能小于200股';

        // 股票ID
        $stockID = $stockData['stock_id'];
        
        // 股票行情数据
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        
        if(!$quotation) return '股票行情异常';
        // 昨日收盘价
        $preClosePrice = $quotation['Close'];
        // 当前价
        $nowPrice      = $quotation['Price'];
        if($buyPrice > 0) {
            if($buyPrice > $quotation['Highest']) return '出价不得高于涨停价！';
            if($buyPrice < $quotation['Lowest'])  return '出价不得低于跌停价！';
        }
        // 检测股票是否可交易
        if($stockData['is_buy_able'] == 0) return '股票被禁止交易';

        // 检测【大盘价】是否超过涨跌幅禁买线
        if(TradingUtil::isOverBuyLimitLine($nowPrice, $preClosePrice)) return '大盘价超出涨跌幅禁买线';

        // 获取用户的资金信息
        $uAccount = UserAccount::where('user_id', $userID)
            ->field('strategy_balance,deposit,frozen,cash_coupon,cash_coupon_frozen')
            ->find();

        // 策略金（不含冻结资金）
        if($isCashCoupon) {
            // 代金券余额
            $strategyBalance = $uAccount['cash_coupon'];
            // 冻结资金
            $frozen = $uAccount['cash_coupon_frozen'];
            // 检测资金
            $strategy = bcsub($strategyBalance, $frozen, 2);
            if ($strategy <= 0) return '代金券资金不足，不能下单';

            // 计算最高可买
            $maxBuyVolume = Calc::calcCashCouponMaxBuyVolume($strategyBalance, $frozen, $quotation['Price']);
        } else {
            // 策略金余额
            $strategyBalance = $uAccount['strategy_balance'];
            // 冻结资金
            $frozen = $uAccount['frozen'];
            // 检测资金
            $strategy = bcsub($strategyBalance, $frozen, 2);
            if ($strategy <= 0) return '策略资金不足，不能下单';

            // 计算最高可买
            $maxBuyVolume = Calc::calcMaxBuyVolume($strategyBalance, $frozen, $quotation['Price'], $isMonthly);
        }

        if ($volume > $maxBuyVolume) return '最高可买' . $maxBuyVolume . '股';

        // 写入各种数据,并向上游提交委托
        $createResult = self::create($userID, $stockID, $stockCode, $market, $volume, $buyPrice, false, false, $isMonthly, $isCashCoupon);

        if (is_array($createResult)) {
            // 更新用户的策略金缓存
            OrderRedis::cacheUserStrategy($userID);
        }

        return $createResult;
    }

    /**
     * 委托买入主逻辑
     * -- 写入委托表
     * -- 写入账户变动日志
     * -- 增加冻保证金
     * -- 写入冻结资金日志
     * 说明：
     * -- 如果是用户发起的委托，必须调用execute方法，而不能直接调用该方法
     * -- 提交委托时冻结保证金
     *
     * @param int $userID 用户ID
     * @param int $stockID 股票ID
     * @param string $stockCode 股票代码
     * @param string $market 证券市场代码
     * @param int $volume 委托数量
     * @param float $price 卖一价（用于计算）
     * @param bool $isSystem 是否系统下单，默认false
     * @param bool $isCondition 是否条件单，默认false
     * @param bool $isMonthly 是否月费
     * @param bool $isCashCoupon 是否代金券交易
     *
     * @return array|string
     * -- 当且仅当返回值为array时，表明业务逻辑执行成功，否则不成功
     * -- 返回值为string类型时，为错误信息
     */
    public static function create($userID, $stockID, $stockCode, $market, $volume, $price, $isSystem = false, $isCondition = false, $isMonthly = false, $isCashCoupon = false)
    {
        // 事务处理：写入委托表，冻结保证金(账户表，预扣流水表)
        Db::startTrans();
        try {
            // 计算每100股的保证金，以此为基础计算总冻结保证金
            $deposit100 = $isCashCoupon ? Calc::calcCashCouponDeposit100($price) :Calc::calcDeposit100($price);
            // 保证金金额（冻结金额）
            $frozenMoney = bcmul($deposit100, bcdiv($volume, 100, 2), 2);

            // 用户资金账户
            $uAccount = UserAccount::where('user_id', $userID)->field('strategy_balance,frozen,cash_coupon,cash_coupon_frozen,cash_coupon_uptime')->find();

            if($isCashCoupon){
                // 代金券策略金余额
                $strategyBalance = $uAccount['cash_coupon'];
                // 代金券冻结资金变动前
                $beforeFrozen = $uAccount['cash_coupon_frozen'];
                $accoutMessage = '代金券资金';
            } else {
                // 策略金余额
                $strategyBalance = $uAccount['strategy_balance'];
                // 冻结资金变动前
                $beforeFrozen = $uAccount['frozen'];
                $accoutMessage = '策略资金';
            }

            // 检测资金（冻结金额不能大于可用策略金）
            if ($frozenMoney > $strategyBalance - $beforeFrozen) {
                throw new TradingCheckException($accoutMessage.'不足');
            }

            // order表的数据
            $orderData = [
                'user_id' => $userID,
                'stock_id' => $stockID,
                'market' => $market,
                'stock_code' => $stockCode,
                'direction' => TRADE_DIRECTION_BUY,
                'price' => $price,
                'price_type' => $price > 0 ? PRICE_TYPE_LIMIT : PRICE_TYPE_MARKET,
                'volume' => $volume,
                'state' => ORDER_SUBMITTED,
                'trading_date' => TradingUtil::currentTradingDate(),
                'deposit100' => $deposit100,
                'is_system' => $isSystem,
                'is_condition' => $isCondition,
                'is_monthly' => $isMonthly,
                'is_cash_coupon' => $isCashCoupon
            ];

            // 写入委托单表
            $order = Order::create($orderData);

            if($isCashCoupon) {
                // 用户账户表，增加冻结代金券保证金
                $updateDate['cash_coupon_frozen'] = Db::raw("cash_coupon_frozen+{$frozenMoney}");
                if($uAccount['cash_coupon_uptime'] == 0) $updateDate['cash_coupon_uptime'] = time();
            } else {
                $updateDate = [
                    'frozen' => Db::raw("frozen+{$frozenMoney}"),
                ];
            }
            // 用户账户表，增加冻结保证金
            UserAccount::update($updateDate, [
                ['user_id', '=', $userID],
            ]);
            $uaRows = $uAccount->getNumRows();

            if($isCashCoupon){
                // 写入冻结资金变动日志（增加冻结资金）
                $fRet = AccountLog::cashCouponFrozenAdd($userID, $order['id'], $market, $stockID, $stockCode, $frozenMoney, $beforeFrozen);
            } else {
                // 写入冻结资金变动日志（增加冻结资金）
                $fRet = AccountLog::frozenAdd($userID, $order['id'], $market, $stockID, $stockCode, $frozenMoney, $beforeFrozen);
            }

            if ($order && $uaRows && $fRet) {
                Db::commit();
                $ret = [$order['id'], $stockID, $market, $stockCode, $volume];
            } else {
                Db::rollback();
                $ret = '委托失败E:1';
            }
        } catch (TradingCheckException $e) {
            Db::rollback();
            $ret = $e->getMessage();
        } catch (\Exception $e) {
            Db::rollback();
            $ret = '委托失败E:2';
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
        $market = $message['Data']['market'] ?? '';
        $stockCode = $message['Data']['stock_code'] ?? '';
        $buyPrice  = $message['Data']['price'] ?? 0;
        $volume = $message['Data']['volume'] ?? 0;
        $token = $message['Token'] ?? '';
        $isMonthly = $message['Data']['is_monthly'] ?? false;
        $isCashCoupon = $message['Data']['is_cash_coupon'] ?? false;

        // 过滤
        $market = strtoupper($market);
        $stockCode = filter_var($stockCode, FILTER_SANITIZE_NUMBER_INT);
        $buyPrice  = round($buyPrice,2);
        $volume = filter_var($volume, FILTER_SANITIZE_NUMBER_INT);
        $isMonthly = strtoupper(trim($isMonthly)) == 'Y';
        $isCashCoupon = strtoupper(trim($isCashCoupon)) == 'Y';

        // 验证
        if (!in_array($market, array_keys(BasicData::MARKET_LIST))) return '参数错误';
        if (!$stockCode)   return '参数错误';
        if ($buyPrice<0)   return '参数错误';
        if ($volume <= 0)  return '参数错误';
        if (empty($token)) return '参数错误';

        return [$market, $stockCode, $buyPrice, $volume, $token, $isMonthly, $isCashCoupon];
    }

}
