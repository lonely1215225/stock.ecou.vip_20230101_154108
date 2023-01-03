<?php
namespace app\cli\sims\logic;

use app\cli\exception\TradingCheckException;
use app\common\model\Order;
use app\common\model\OrderPosition;
use app\common\model\OrderTraded;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\AccountLogic;
use app\index\logic\Calc;
use app\index\logic\Commission;
use app\index\logic\OrderLogic;
use think\Db;
use util\BasicData;
use util\OrderRedis;
use util\QuotationRedis;
use util\SystemRedis;

/**
 * 成交回报处理类
 *
 * @package app\index\logic
 */
class TradedMsg
{
    /**
     * 处理成交回报的主入口
     *
     * @param int $orderID
     * @param int $volume
     *
     * @return array|false
     */
    public static function execute($orderID, $price, $volume)
    {
        Db::startTrans();
        try {
            // 取出委托单
            $order = Order::where('id', $orderID)
                ->field('id,create_time,user_id,market,stock_id,stock_code,direction,order_position_id,volume,volume_success,is_finished,state,trading_date,deposit100,is_monthly,cancel_state,is_cash_coupon')
                ->find();

            // 找不到委托单
            if (!$order) {
                throw new TradingCheckException('order not found');
            }

            // 如果已经撤单成功，不予成交
            if ($order['cancel_state'] == CANCEL_SUCCESS) {
                throw new TradingCheckException('already canceled.');
            }

            $userID      = $order['user_id'];
            $market      = $order['market'];
            $stockCode   = $order['stock_code'];
            $stockID     = $order['stock_id'];
            $direction   = $order['direction'];
            $tradingDate = $order['trading_date'];
            $isMonthly   = $order['is_monthly'];
            $isCashCoupon = $order['is_cash_coupon'];

            // 成交时的信息
            $code = 1;
            $dir  = BasicData::TRADE_DIRECTION_LIST[$direction] ?? '';
            $msg  = "{$market}{$stockCode} {$dir}成交{$volume}股";

            // 已完成委托单
            if ($order['is_finished'] == true) {
                throw new TradingCheckException();
            }

            // 成交价 [ 取一挡对手价 ]
            //$stall = $direction == TRADE_DIRECTION_BUY ? -1 : 1;
            //$price = QuotationRedis::getStallsPrice($market, $stockCode, $stall, 'only');

            // 成交价异常
            if ($price <= 0) {
                throw new TradingCheckException();
            }

            // 成交金额（成交市值）
            $tradedValue = bcmul($volume, $price, 2);

            /**
             * 计算值
             * -- $serviceFee    手续费：双向收取
             * -- $stampTax      印花税：仅卖出收取
             * -- $transferFee   过户费：仅卖出收取
             * -- $managementFee 管理费：仅买入收取
             * -- $deposit       保证金：仅买入收取
             * -- $buyValueCost  总买入市值
             * -- $sellValueIn   总卖出市值
             */
            $serviceFee    = Calc::calcServiceFee($tradedValue);
            $stampTax      = 0;
            $transferFee   = 0;
            $managementFee = 0;
            $deposit       = 0;
            $buyValueCost  = 0;
            $sellValueIn   = 0;
            
            if ($order['direction'] == TRADE_DIRECTION_SELL) {
                // 方向：卖出，需要计算过户费，印花税
                $stampTax    = Calc::calcStampTax($tradedValue);
                $transferFee = Calc::calcTransferFee($tradedValue);
            } else {
                // 方向：买入，需要计算管理费，保证金
                $managementFee = $isMonthly ? Calc::calcMonthlyManagementFee($tradedValue) : Calc::calcManagementFee($tradedValue);
                $deposit       = Calc::calcDeposit($tradedValue);
            }

            // 综合交易费用 = 手续费 + 印花税 + 过户费
            $totalFee = $serviceFee + $stampTax + $transferFee;
            
            // 计算均价
            if ($order['direction'] == TRADE_DIRECTION_SELL) {
                // 总卖出市值（卖出收入 = 成交市值 -  综合费用）
                $sellValueIn = $tradedValue - $totalFee;
                // 卖出均价
                $avg_price = bcdiv($sellValueIn, $volume, 4);
            } else {
                // 总买入市值（买入成本 = 成交市值 + 综合费用）
                $buyValueCost = $tradedValue + $totalFee;
                // 买入均价
                $avg_price = bcdiv($buyValueCost, $volume, 4);
            }
            
            // 成交单的数据
            $tradedData = [
                'order_position_id' => 0,
                'order_id'          => $orderID,
                'user_id'           => $userID,
                'direction'         => $direction,
                'market'            => $market,
                'stock_id'          => $stockID,
                'stock_code'        => $stockCode,
                'trading_date'      => $tradingDate,
                'trading_time'      => date('H:i:s'),
                'volume'            => $volume,
                'price'             => $price,
                'cost_price'        => $avg_price,
                'traded_value'      => $tradedValue,
                'buy_value_cost'    => $buyValueCost,
                'sell_value_in'     => $sellValueIn,
                'deposit'           => $deposit,
                'total_fee'         => $totalFee,
                'service_fee'       => $serviceFee,
                'stamp_tax'         => $stampTax,
                'transfer_fee'      => $transferFee,
                'management_fee'    => $managementFee,
                'is_monthly'        => $isMonthly,
                'is_cash_coupon'    => $isCashCoupon
            ];
            
            if ($order['direction'] == TRADE_DIRECTION_BUY) {
                // 买入成交单
                $dealRet = self::buyTraded($order, $tradedData);
            } else {
                // 卖出成交单
                $dealRet = self::sellTraded($order, $tradedData);
            }
            //file_put_contents("/data/wwwroot/trade", "执行成交订单||||{$dealRet}".PHP_EOL, FILE_APPEND);
            if ($dealRet) {
                Db::commit();
                $ret = [$userID, $stockID, $market, $stockCode, $volume, $code, $msg, $isCashCoupon];
            } else {
                Db::rollback();
                $ret = false;
            }
            
        } catch (TradingCheckException $e) {
            Db::rollback();
            $ret = false;
        } catch (\Exception $e) {
            Db::rollback();

            //dump($e->getFile());
            //dump($e->getLine());
            //dump($e->getMessage());
            //dump($e->getTraceAsString());

            $ret = false;
        }

        // 一些后续处理
        if (is_array($ret)) {
            list ($userID, $stockID, $market, $stockCode, $volume, $code, $msg, $isCashCoupon) = $ret;

            if($isCashCoupon){
                // 更新用户的策略金缓存
                OrderRedis::cacheUserCashCoupon($userID);
            } else {
                // 更新用户的总持仓占用保证金
                AccountLogic::updateTotalDeposit($userID);

                // 更新用户的策略金缓存
                OrderRedis::cacheUserStrategy($userID);
            }

            // 检查并设置委托单为完结状态
            OrderLogic::execFinished();

            // 添加到持仓股票行情订阅列表
            QuotationRedis::addPositionSubscribe($market, $stockCode);
        }

        return $ret;
    }

    /**
     * 买入成交单
     * -- 写入成交单
     * -- 持仓：总卖出数量，持仓数量，今仓数量，持仓均价，买入市值，总买入市值，保证金，管理费
     * -- 委托单:增加成交数量，状态 部分成交 或 全部成交
     * -- 策略金：扣除管理费，扣保证金
     * -- 管理费返佣
     * -- 计算止损价
     *
     * @param \think\Model $order 委托单对象
     * @param array        $tradedData 成交单数据
     *
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function buyTraded(&$order, $tradedData)
    {
        $userID              = $order['user_id'];
        $market              = $order['market'];
        $stockID             = $order['stock_id'];
        $stockCode           = $order['stock_code'];
        $deposit100          = $order['deposit100'];
        $isMonthly           = $order['is_monthly'];
        $isCashCoupon        = $order['is_cash_coupon'];
        $managementFee       = $tradedData['management_fee'];
        $tradedDeposit       = $tradedData['deposit'];
        $monthly_expire_date = (new \DateTime())->add(\DateInterval::createFromDateString("1 month"))->format('Y-m-d');

        // 获取取持仓
        if ($order['order_position_id']) {
            // 根据持仓ID，获取持仓信息
            $position = self::getPositionByID($order['order_position_id']);
        } else {
            // 根据【股票代码 和 主账号】获取持仓信息
            $position = self::getPositionBySymbol($userID, $market, $stockCode, $isMonthly, $monthly_expire_date, $isCashCoupon);
        }

        // 如果没有获取到持仓信息，创建一个新的持仓
        if (!$position) {
            // 创建持仓
            $position = self::createPosition($order);
        }

        // 成交单中需要记录对应的持仓ID
        $tradedData['order_position_id'] = $position['id'];
        // 写入成交单
        $traded = OrderTraded::create($tradedData);

        $orderID      = $order['id'];
        $positionID   = $position['id'];
        $tradedID     = $traded['id'];
        $price        = $traded['price'];
        $tradedVolume = $traded['volume'];

        // 卖出市值(累计卖出成交金额，不含成本)
        $sumSellValue = $position['sum_sell_value'];
        // 总卖出市值(累计卖出收入，去掉费用)
        $sumSellValueIn = $position['sum_sell_value_in'];
        // 买入成交后：持仓占用保证金
        $newSumDeposit = bcadd($position['sum_deposit'], $tradedDeposit, 2);
        // 买入成交后：持仓数量
        $newVolumePosition = $position['volume_position'] + $tradedVolume;
        // 买入成交后：总买入市值
        $newSumBuyValueCost = bcadd($position['sum_buy_value_cost'], $tradedData['buy_value_cost'], 2);
        // 买入成交后：总买入数量
        $newSumBuyVolume = $position['sum_buy_volume'] + $tradedVolume;
        // 计算持仓均价（用买入成交后的数量）
        $newPositionPrice = Calc::calcPositionPrice($newSumBuyValueCost, $sumSellValueIn, $newVolumePosition);
        // 计算买入均价
        $newBCostPrice = Calc::calcBCostPrice($newSumBuyValueCost, $newSumBuyVolume);
        // 计算补仓价
        $stopLossPrice = Calc::calcStopLossPrice($newSumBuyValueCost, $sumSellValue, $newSumDeposit, $newVolumePosition);

        // 持仓各项数据改变(总买入数量，持仓数量，今仓数量，持仓均价，买入市值，总买入市值，保证金，管理费)
        $position['sum_buy_volume']     = $newSumBuyVolume;
        $position['volume_position']    = $newVolumePosition;
        $position['volume_today']       = Db::raw("volume_today+{$tradedVolume}");
        $position['sum_buy_value']      = Db::raw("sum_buy_value+{$tradedData['traded_value']}");
        $position['sum_buy_value_cost'] = $newSumBuyValueCost;
        $position['position_price']     = $newPositionPrice;
        $position['sum_deposit']        = $newSumDeposit;
        $position['sum_management_fee'] = Db::raw("sum_management_fee+{$managementFee}");
        $position['stop_loss_price']    = $stopLossPrice;
        $position['b_cost_price']       = $newBCostPrice;
        $position['is_monthly']         = $order['is_monthly'];
        if ($order['is_monthly']) {
            $position['monthly_expire_date'] = $monthly_expire_date;
        }
        $position['is_cash_coupon']     = $order['is_cash_coupon'];
        // 保存持仓
        $poRet = $position->save();

        // 委托单中写入持仓编号
        if (!$order['order_position_id']) {
            $order['order_position_id'] = $position['id'];
        }

        // 委托单：增加成功数量，状态（全部成交 或 部分成交），是否完结
        $volumeSuccess           = $order['volume_success'] + $tradedVolume;
        $order['volume_success'] = $volumeSuccess;
        $order['state']          = $order['volume'] == $volumeSuccess ? ORDER_ALL_TRADED : ORDER_PART_TRADED;
        $order['is_finished']    = $order['state'] == ORDER_ALL_TRADED ? true : false;

        // 保存委托单
        $oRet = $order->save();

        // 解冻资金
        $unfrozenMoney = Calc::calcUnfrozenDeposit($deposit100, $tradedVolume);

        // 查询用户资金账户
        $uAccount = UserAccount::where('user_id', $order['user_id'])->field('strategy_balance,frozen,cash_coupon,cash_coupon_frozen')->find();

        $tradeValue = $tradedData['traded_value'];
        // 策略金变动前余额，冻结资金变动前余额
        if($isCashCoupon){
            $beforeStrategy = $uAccount['cash_coupon'];
            $beforeFrozen   = $uAccount['cash_coupon_frozen'];
            // 资金账户变动：减少策略金（扣除管理费，扣除保证金），解冻对应冻结资金
            $uAccount['cash_coupon'] = Db::raw("cash_coupon-{$managementFee}-{$tradeValue}");
            if ($beforeFrozen >= $unfrozenMoney) {
                $uAccount['cash_coupon_frozen'] = Db::raw("cash_coupon_frozen-{$unfrozenMoney}");
            }
        } else {
            $beforeStrategy = $uAccount['strategy_balance'];
            $beforeFrozen   = $uAccount['frozen'];
            // 资金账户变动：减少策略金（扣除管理费，扣除保证金），解冻对应冻结资金
            $uAccount['strategy_balance'] = Db::raw("strategy_balance-{$managementFee}-{$tradedDeposit}");
            if ($beforeFrozen >= $unfrozenMoney) {
                $uAccount['frozen'] = Db::raw("frozen-{$unfrozenMoney}");
            }
        }

        // 保存用户资金账户
        $uaRet = $uAccount->save();

        $cRet = true;
        if($isCashCoupon){
            //策略金流水（扣管理费记录）
            $beforeS = bcsub($beforeStrategy, $tradeValue, 2);
            // 写入代金券流水（扣保证金）
            $sdRet = AccountLog::cashCouponSubDeposit($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $tradedVolume, $tradeValue, $beforeStrategy, $stopLossPrice);
            // 写入代金券流水（扣管理费记录）
            $smRet   = AccountLog::cashCouponSubManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $tradedVolume, $managementFee, $beforeS);

            // 写入冻结资金变动日志（减少冻结资金）
            $fRet = true;
            if ($beforeFrozen >= $unfrozenMoney) {
                $type = USER_FROZEN_TRADED;
                $fRet = AccountLog::cashCouponFrozenAdd($userID, $orderID, $market, $stockID, $stockCode, $type, $unfrozenMoney, $beforeFrozen, $positionID, $tradedID);
            }
        } else {
            //策略金流水（扣管理费记录）
            $beforeS = bcsub($beforeStrategy, $tradedDeposit, 2);
            // 写入策略金流水（扣保证金）
            $sdRet = AccountLog::strategySubDeposit($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $tradedVolume, $tradedDeposit, $beforeStrategy, $stopLossPrice);

            // 写入策略金流水（扣管理费记录）
            $smRet   = $isMonthly ? AccountLog::strategySubMonthlyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $tradedVolume, $managementFee, $beforeS) : AccountLog::strategySubManagementFee($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $price, $tradedVolume, $managementFee, $beforeS);

            // 写入冻结资金变动日志（减少冻结资金）
            $fRet = true;
            if ($beforeFrozen >= $unfrozenMoney) {
                $type = USER_FROZEN_TRADED;
                $fRet = AccountLog::frozenSub($userID, $orderID, $market, $stockID, $stockCode, $type, $unfrozenMoney, $beforeFrozen, $positionID, $tradedID);
            }

            // 写入管理费返佣记录
            $cRet = Commission::tradedCommission($userID, $traded['management_fee'], $position['id'], $traded['id'], $market, $stockCode, $traded['traded_value']);
        }

        if ($position && $poRet && $traded && $oRet && $uaRet && $smRet && $cRet && $sdRet && $fRet) {
            $ret = true;
            // 管理费返佣由脚本执行
            // 更新持仓数据缓存
            OrderRedis::cachePosition($positionID);
        } else {
            $ret = false;
        }

        return $ret;
    }

    /**
     * 卖出成交单
     * -- 写入成交单
     * -- 持仓:总卖出数量，持仓数量，卖出市值，总卖出市值（总卖出收入）
     * -- 结算盈亏
     * -- 委托单：增加成交数量，状态 部分成交 或 全部成交
     * -- 计算止损价
     *
     * @param \think\Model $order 委托单对象
     * @param array        $tradedData 成交单数据
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function sellTraded(&$order, $tradedData)
    {
        // 持仓ID
        $positionID = $order['order_position_id'];
        // 成交单中需要记录对应的持仓ID
        $tradedData['order_position_id'] = $positionID;
        // 写入成交单
        $traded = OrderTraded::create($tradedData);

        $sellPrice  = $tradedData['price'];
        $sellVolume = $tradedData['volume'];
        $userID     = $tradedData['user_id'];
        $stockID    = $tradedData['stock_id'];
        $stockCode  = $tradedData['stock_code'];
        $market     = $tradedData['market'];
        $tradedID   = $traded['id'];

        // 取持仓数据
        $position = self::getPositionByID($positionID);
        // 总买入市值
        $sumBuyValueCost = $position['sum_buy_value_cost'];
        // 持仓占用保证金
        $sumDeposit = $position['sum_deposit'];

        /**
         * 卖出后计算各项数值
         * -- $newVolumePosition  卖出后，持仓数量
         * -- $newSumSellVolume   卖出后，总卖出数量
         * -- $newSumSellValue    卖出后，卖出市值（累计卖出成交额）
         * -- $newSumSellValueIn  卖出后，总卖出市值（累计卖出收入）
         * -- $pal                卖出后，本次卖出成交的盈亏 = 卖出收入 - 持仓均价 * 卖出数量
         * -- $newSumSellPAL      卖出后，累计卖出盈亏
         */
        $newVolumePosition = $position['volume_position'] - $sellVolume;
        $newSumSellVolume  = $position['sum_sell_volume'] + $sellVolume;
        $newSumSellValue   = bcadd($position['sum_sell_value'], $tradedData['traded_value'], 2);
        $newSumSellValueIn = bcadd($position['sum_sell_value_in'], $tradedData['sell_value_in'], 2);
        $pal               = round(bcsub($tradedData['sell_value_in'], bcmul($position['position_price'], $sellVolume, 4), 4), 2);
        $newSumSellPAL     = bcadd($position['sum_sell_pal'], $pal, 2);

        // 委托单：增加成交数量，状态 => 部分成交 或 全部成交
        $volumeSuccess           = $order['volume_success'] + $sellVolume;
        $order['volume_success'] = $volumeSuccess;
        $order['state']          = $order['volume'] == $volumeSuccess ? ORDER_ALL_TRADED : ORDER_PART_TRADED;
        $order['is_finished']    = $order['state'] == ORDER_ALL_TRADED ? true : false;
        // 保存委托单
        $oRet = $order->save();

        // 结算盈亏
        $settlePAL = Calc::settlePAL($sellPrice, $newVolumePosition, $newSumSellValueIn, $sumBuyValueCost, $sumDeposit);

        // 入账金额
        $settleMoney = 0;

        // 保证金变动后(默认不变动)
        $newSumDeposit = $sumDeposit;

        // 结算盈亏大于0时：返还结算盈亏
        if ($settlePAL > 0) {
            $settleMoney   = $settlePAL;
            $newSumDeposit = $sumDeposit - $settleMoney;
        }

        // 全部平仓时一律入账（盈利返钱，亏损扣钱）
        if ($newVolumePosition == 0) {
            $settleMoney   = $settlePAL;
            $newSumDeposit = 0;
        }

        // 卖出后，计算补仓价格（止损价），全部平仓时止损价 = 0
        $newStopLossPrice = $newVolumePosition == 0 ? 0 : Calc::calcStopLossPrice($sumBuyValueCost, $newSumSellValue, $newSumDeposit, $newVolumePosition);

        // 计算持仓均价（用买入成交后的数量）
        $newPositionPrice = Calc::calcPositionPrice($sumBuyValueCost, $newSumSellValueIn, $newVolumePosition);

        // 持仓：增加总卖出数量，减少持仓数量，增加卖出市值，增加总卖出市值（总卖出收入）
        $position['sum_sell_volume']   = $newSumSellVolume;
        $position['volume_position']   = $newVolumePosition;
        $position['sum_sell_value']    = $newSumSellValue;
        $position['sum_sell_value_in'] = $newSumSellValueIn;
        $position['sum_sell_pal']      = $newSumSellPAL;
        $position['stop_loss_price']   = $newStopLossPrice;
        $position['position_price']    = $newPositionPrice;

        // 持仓完结：计算全部平仓盈亏 = 卖出收入 - 总买入市值
        if ($newVolumePosition == 0) {
            $position['is_finished']  = true;
            $position['s_cost_price'] = bcdiv($newSumSellValueIn, $newSumSellVolume, 4);
            $position['s_pal']        = bcsub($newSumSellValueIn, $sumBuyValueCost, 2);
            $position['s_time']       = time();
        }
        // 持仓：变动保证金
        $position['sum_deposit'] = $newSumDeposit;
        // 持仓：增加累计提走盈利
        if ($settleMoney > 0) {
            $position['sum_back_profit'] = Db::raw("sum_back_profit+{$settleMoney}");
        }
        // 保存持仓
        $opRet = $position->save();

        // 用户账户
        $userAccount = UserAccount::where('user_id', $tradedData['user_id'])->field('strategy_balance,cash_coupon,cash_coupon_time')->find();
        // 策略金变动前
        $beforeStrategy = $userAccount['strategy_balance'];

        // 如果入账金额不为0，则入账（入账金额可正可负,部分卖出负数不入账）
        $sLogRet = true;
        $uaRet   = true;
        if ($settleMoney != 0) {
            if($order['is_cash_coupon']) {
                $cashCouponSet = SystemRedis::getCashCoupon();

                if($cashCouponSet['in_loss'] == 1 && $pal < 0) {
                    // 写入策略金变动日志（卖出结算日志）
                    $sLogRet = AccountLog::strategySellSettlement($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $sellPrice, $sellVolume, $pal, $beforeStrategy, $newStopLossPrice, true);

                    // 账户变动
                    $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$pal}");
                    // 保存账户变动
                    $uaRet = $userAccount->save();
                } elseif ($pal > 0) {
                    // 写入策略金变动日志（卖出结算日志）
                    $sLogRet = AccountLog::strategySellSettlement($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $sellPrice, $sellVolume, $pal, $beforeStrategy, $newStopLossPrice, true);

                    // 账户变动
                    $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$pal}");
                    // 保存账户变动
                    $uaRet = $userAccount->save();
                }

            } else {
                // 写入策略金变动日志（卖出结算日志）
                $sLogRet = AccountLog::strategySellSettlement($userID, $market, $stockID, $stockCode, $positionID, $tradedID, $sellPrice, $sellVolume, $settleMoney, $beforeStrategy, $newStopLossPrice);
                // 账户变动
                $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$settleMoney}");
                // 保存账户变动
                $uaRet = $userAccount->save();
            }
        }


        if ($traded && $opRet && $oRet && $sLogRet && $uaRet) {
            $ret = true;
            // 更新持仓数据缓存
            OrderRedis::cachePosition($positionID);
        } else {
            $ret = false;
        }

        return $ret;
    }

    /**
     * 根据【持仓ID】获取持仓信息
     *
     * @param \think\model $positionID
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function getPositionByID($positionID)
    {
        $position = OrderPosition::where('id', $positionID)
            ->field([
                'id',
                'user_id',
                'market',
                'stock_id',
                'stock_code',
                'volume_position',
                'volume_today',
                'sum_buy_volume',
                'position_price',
                'sum_buy_value',
                'sum_buy_value_cost',
                'sum_deposit',
                'sum_management_fee',
                'sum_sell_volume',
                'sum_sell_value',
                'sum_sell_value_in',
                'sum_back_profit',
                'stop_loss_price',
                'sum_sell_pal',
                'is_cash_coupon'
            ])
            ->find();

        return $position;
    }

    /**
     * 根据【股票代码】【主账号】获取持仓信息
     *
     * @param $userID
     * @param $market
     * @param $stockCode
     * @param $isMonthly
     * @param $monthlyExpireDate
     * @param $isCashCoupon
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private static function getPositionBySymbol($userID, $market, $stockCode, $isMonthly = false, $monthlyExpireDate = null, $isCashCoupon = false)
    {
        $monthlyExpireDate = $isMonthly ? $monthlyExpireDate : null;
        $position          = OrderPosition::where('stock_code', $stockCode)
            ->where('market', $market)
            ->where('user_id', $userID)
            ->where('is_monthly', $isMonthly)
            ->where('monthly_expire_date', $monthlyExpireDate)
            ->where('is_finished', false)
            ->where('is_cash_coupon', $isCashCoupon)
            ->field([
                'id',
                'user_id',
                'market',
                'stock_id',
                'stock_code',
                'volume_position',
                'volume_today',
                'sum_buy_volume',
                'position_price',
                'sum_buy_value',
                'sum_buy_value_cost',
                'sum_deposit',
                'sum_management_fee',
                'sum_sell_volume',
                'sum_sell_value',
                'sum_sell_value_in',
                'sum_back_profit',
                'stop_loss_price',
                'sum_sell_pal',
            ])
            ->find();

        return $position;
    }

    /**
     * 创建持仓
     *
     * @param \think\Model $order 委托单对象
     *
     * @return \think\Model OrderPosition
     */
    private static function createPosition(&$order)
    {
        $position = OrderPosition::create([
            'user_id'            => $order['user_id'],
            'market'             => $order['market'],
            'stock_id'           => $order['stock_id'],
            'stock_code'         => $order['stock_code'],
            'volume_position'    => 0,
            'volume_today'       => 0,
            'sum_buy_volume'     => 0,
            'position_price'     => 0,
            'sum_buy_value'      => 0,
            'sum_buy_value_cost' => 0,
            'sum_sell_value'     => 0,
            'sum_sell_value_in'  => 0,
            'sum_management_fee' => 0,
            'sum_deposit'        => 0,
            'sum_back_profit'    => 0,
        ]);

        return $position;
    }

    /**
     * 判断代金券有效期
     *
     * @param $createData
     * @return int
     */
    public static function isValidCashCoupon($createData)
    {
        $createtime = strtotime($createData);
        $cashCoupon = SystemRedis::getCashCoupon();
        $cashCouponTime = intval($cashCoupon['expiry_time']);
        $cashCouponUnit = $cashCoupon['expiry_unit'];

        if ($cashCouponUnit == 1) {
            $expiryDate = date("Y-m-d", strtotime("+{$cashCouponTime} month", $createtime));
            $expiryTime = strtotime($expiryDate);
        } else {
            $expiryDate = get_days($createtime, $cashCouponTime);
            $expiryTime = strtotime($expiryDate. ' 15:00');
        }

        $isValid = time() < $expiryTime ? true : false;

        return $isValid;
    }
}
