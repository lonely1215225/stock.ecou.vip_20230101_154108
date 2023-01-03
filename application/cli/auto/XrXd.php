<?php
namespace app\cli\auto;

use app\common\model\OrderPosition;
use app\common\model\StockXrxd;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use util\RedisUtil;
use util\TradingUtil;

/**
 * 除权除息脚本
 * -- 在除权除息日期当天 07:40 ~ 07:55 执行
 * -- 缓存【当前价】 = 计算出来的当前价
 *
 * @package app\cli\auto
 */
class XrXd extends Command
{

    protected function configure()
    {
        $this->setName('xr_xd')->setDescription('除权除息');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(5000, function () {
            $nowHi = intval(date('Hi'));
            if ($nowHi >= 740 && $nowHi <= 755) {
                $this->main();
            }
        });
    }

    /**
     * 除权除息执行主入口
     */
    protected function main()
    {
        $tradingDate = TradingUtil::currentTradingDate();
        // 需要执行除权除息的列表
        $list = StockXrxd::where('execute_date', $tradingDate)
            ->where('is_finished', false)
            ->column('stock_id,market,stock_code,execute_date,base_volume,give_volume,transfer_volume,dividend', 'id');

        if (count($list)) {
            foreach ($list as $id => $data) {
                // 将除权除息记录设置为已执行状态
                $ret = StockXrxd::update([
                    'is_finished' => true,
                ], [
                    ['id', '=', $id],
                ]);

                // 执行除权除息
                if ($ret) {
                    $giveVolume     = $data['give_volume'];
                    $transferVolume = $data['transfer_volume'];
                    $dividend       = $data['dividend'];
                    if (($giveVolume > 0 || $transferVolume > 0) && $dividend > 0) {
                        // 除权 和 除息
                        $this->xrXd($data);
                    } elseif ($giveVolume > 0 || $transferVolume > 0) {
                        // 仅除权
                        $this->exRight($data);
                    } elseif ($dividend > 0) {
                        // 仅除息
                        $this->exDividend($data);
                    }
                }
            }
        }
    }

    /**
     * 除权操作
     * -- 仅除权
     *
     * @param $data
     */
    protected function exRight($data)
    {
        $market         = $data['market'];
        $stockCode      = $data['stock_code'];
        $stockID        = $data['stock_id'];
        $baseVolume     = $data['base_volume'];
        $giveVolume     = $data['give_volume'];
        $transferVolume = $data['transfer_volume'];
        $dividend       = $data['dividend'];
        $xrVolume       = $giveVolume + $transferVolume;

        // 股票行情
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        // 昨收价
        $closePrice = $quotation['Close'];

        // 计算当前价 = 昨日收盘价 / ( 1 + (送 + 转) / 基础股票)
        $r     = bcadd(1, bcdiv($xrVolume, $baseVolume, 4), 4);
        $price = bcdiv($closePrice, $r, 4);

        // 更新缓存中的当前价
        $quotation['Price'] = $price;
        RedisUtil::cacheQuotation($quotation);

        // 获取所有对应的持仓
        $positionList = OrderPosition::where('market', $market)
            ->where('stock_code', $stockCode)
            ->where('volume_position', '>', 0)
            ->where('is_finished', false)
            ->column('id,user_id,position_price,volume_position,sum_buy_value_cost,sum_sell_value,sum_sell_value_in,sum_deposit', 'id');

        // 除权
        foreach ($positionList as $positionID => $item) {
            // 总买入市值
            $sumBuyValueCost = $item['sum_buy_value_cost'];
            // 卖出市值
            $sumSellValue = $item['sum_sell_value'];
            // 总卖出市值
            $sumSellValueIn = $item['sum_sell_value_in'];
            // 保证金
            $sumDeposit = $item['sum_deposit'];
            // 原持仓数量
            $volumePosition = $item['volume_position'];
            // 送转数量
            $addVolume = $volumePosition / $baseVolume * $xrVolume;
            // 除权后：持仓数量 = 原股数 + 原股数/基础股票 *（送股+转股）
            $newVolumePosition = $volumePosition + $addVolume;
            // 计算持仓均价（用除权之后的股数）
            $newPositionPrice = Calc::calcPositionPrice($sumBuyValueCost, $sumSellValueIn, $newVolumePosition);
            // 计算补仓价（止损价）
            $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDeposit, $newVolumePosition);

            // 更新持仓：持仓数量，可卖数量，送转数量，持仓均价，补仓价
            OrderPosition::update([
                'volume_position' => $newVolumePosition,
                'volume_for_sell' => $newVolumePosition,
                'position_price'  => $newPositionPrice,
                'stop_loss_price' => $stopLossPrice,
                'xrxd_volume'     => Db::raw("xrxd_volume+{$addVolume}"),
            ], [
                ['id', '=', $positionID],
            ]);
        }
    }

    /**
     * 除息操作
     * -- 仅除息
     *
     * @param $data
     */
    protected function exDividend($data)
    {
        $market         = $data['market'];
        $stockCode      = $data['stock_code'];
        $stockID        = $data['stock_id'];
        $baseVolume     = $data['base_volume'];
        $giveVolume     = $data['give_volume'];
        $transferVolume = $data['transfer_volume'];
        $dividend       = $data['dividend'];
        $xrVolume       = $giveVolume + $transferVolume;

        // 股票行情
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        // 昨收价
        $closePrice = $quotation['Close'];

        // 计算当前价 = 当日收盘价 - 股利金 / 基础股票
        $r     = bcdiv($dividend, $baseVolume, 4);
        $price = bcsub($closePrice, $r, 4);

        // 更新缓存中的当前价
        $quotation['Price'] = $price;
        RedisUtil::cacheQuotation($quotation);

        // 获取所有对应的持仓
        $positionList = OrderPosition::where('market', $market)
            ->where('stock_code', $stockCode)
            ->where('volume_position', '>', 0)
            ->where('is_finished', false)
            ->column('id,user_id,position_price,volume_position,sum_buy_value_cost,sum_sell_value,sum_sell_value_in,sum_deposit', 'id');

        // 除息
        foreach ($positionList as $positionID => $item) {
            $userID = $item['user_id'];
            // 总买入市值
            $sumBuyValueCost = $item['sum_buy_value_cost'];
            // 卖出市值
            $sumSellValue = $item['sum_sell_value'];
            // 总卖出市值
            $sumSellValueIn = $item['sum_sell_value_in'];
            // 保证金
            $sumDeposit = $item['sum_deposit'];
            // 持仓数量
            $volumePosition = $item['volume_position'];
            // 计算持仓均价
            $newPositionPrice = Calc::calcPositionPrice($sumBuyValueCost, $sumSellValueIn, $volumePosition);
            // 返股利金金额
            $backDividend = $this->calcBackDividend($volumePosition, $baseVolume, $dividend);
            // 计算补仓价（止损价）
            $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDeposit, $volumePosition);

            Db::startTrans();
            try {
                // 持仓更新：持仓均价，增加除息，补仓价
                $pRet = OrderPosition::update([
                    'position_price'  => $newPositionPrice,
                    'stop_loss_price' => $stopLossPrice,
                    'xrxd_dividend'   => Db::raw("xrxd_dividend+{$backDividend}"),
                ], [
                    ['id', '=', $positionID],
                ]);

                // 用户账户
                $account = UserAccount::where('user_id', $userID)->field('strategy_balance')->find();
                // 策略金变动前
                $beforeStrategy = $account['strategy_balance'];
                // 账户：增加策略金（股利金入账）
                $account['strategy_balance'] = Db::raw("strategy_balance+{$backDividend}");
                // 保存账户
                $uaRet = $account->save();

                // 写入策略金变动日志（股利金入账）
                $slRet = AccountLog::strategyBackDividend($userID, $market, $stockID, $stockCode, $positionID, $volumePosition, $backDividend, $beforeStrategy);

                if ($pRet && $uaRet && $slRet) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }

    /**
     * 除权除息
     * -- 除权 和 除息
     *
     * @param $data
     */
    protected function xrXd($data)
    {
        $market         = $data['market'];
        $stockCode      = $data['stock_code'];
        $stockID        = $data['stock_id'];
        $baseVolume     = $data['base_volume'];
        $giveVolume     = $data['give_volume'];
        $transferVolume = $data['transfer_volume'];
        $dividend       = $data['dividend'];
        $xrVolume       = $giveVolume + $transferVolume;

        // 股票行情
        $quotation = RedisUtil::getQuotationData($stockCode, $market);
        // 昨收价
        $closePrice = $quotation['Close'];

        // 计算当前价 =（当日收盘价 - 股利金 / 基础股票） / (1 + (送 + 转) / 基础股票)
        $l     = bcsub($closePrice, bcdiv($dividend, $baseVolume, 4), 4);
        $r     = bcadd(1, bcdiv($xrVolume, $baseVolume, 4), 4);
        $price = bcdiv($l, $r, 4);

        // 更新缓存中的当前价
        $quotation['Price'] = $price;
        RedisUtil::cacheQuotation($quotation);

        // 获取所有对应的持仓
        $positionList = OrderPosition::where('market', $market)
            ->where('stock_code', $stockCode)
            ->where('volume_position', '>', 0)
            ->where('is_finished', false)
            ->column('id,user_id,position_price,volume_position,sum_buy_value_cost,sum_sell_value,sum_sell_value_in,sum_deposit', 'id');

        // 除权除息
        foreach ($positionList as $positionID => $item) {
            $userID = $item['user_id'];
            // 总买入市值
            $sumBuyValueCost = $item['sum_buy_value_cost'];
            // 卖出市值
            $sumSellValue = $item['sum_sell_value'];
            // 总卖出市值
            $sumSellValueIn = $item['sum_sell_value_in'];
            // 保证金
            $sumDeposit = $item['sum_deposit'];
            // 原持仓数量
            $volumePosition = $item['volume_position'];
            // 送转数量
            $addVolume = $volumePosition / $baseVolume * $xrVolume;
            // 返股利金金额（用除权之前的股数）
            $backDividend = $this->calcBackDividend($volumePosition, $baseVolume, $dividend);
            // 除权后：持仓数量 = 原股数 + 原股数/基础股票 *（送股+转股）
            $newVolumePosition = $volumePosition + $addVolume;
            // 计算持仓均价（用除权之后的股数）
            $newPositionPrice = Calc::calcPositionPrice($sumBuyValueCost, $sumSellValueIn, $newVolumePosition);
            // 计算补仓价（止损价）
            $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDeposit, $newVolumePosition);

            Db::startTrans();
            try {
                // 持仓更新：持仓数量，可卖数量，送转数量，持仓均价，增加除息
                $pRet = OrderPosition::update([
                    'volume_position' => $newVolumePosition,
                    'volume_for_sell' => $newVolumePosition,
                    'position_price'  => $newPositionPrice,
                    'stop_loss_price' => $stopLossPrice,
                    'xrxd_volume'     => Db::raw("xrxd_volume+{$addVolume}"),
                    'xrxd_dividend'   => Db::raw("xrxd_dividend+{$backDividend}"),
                ], [
                    ['id', '=', $positionID],
                ]);

                // 用户账户
                $account = UserAccount::where('user_id', $userID)->field('strategy_balance')->find();
                // 策略金变动前
                $beforeStrategy = $account['strategy_balance'];
                // 账户：增加策略金（股利金入账）
                $account['strategy_balance'] = Db::raw("strategy_balance+{$backDividend}");
                // 保存账户
                $uaRet = $account->save();

                // 写入策略金变动日志（股利金入账）
                $slRet = AccountLog::strategyBackDividend($userID, $market, $stockID, $stockCode, $positionID, $volumePosition, $backDividend, $beforeStrategy);

                if ($pRet && $uaRet && $slRet) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }

    /**
     * 计算总派送股利金
     * -- 总股利金 = 原股数 / 基础股票 * 股利金 * 0.8
     *
     * @param $volume
     * @param $baseVolume
     * @param $dividend
     *
     * @return float
     */
    protected function calcBackDividend($volume, $baseVolume, $dividend)
    {
        $backDividend = bcdiv($volume, $baseVolume, 4);
        $backDividend = bcmul($backDividend, $dividend, 4);
        $backDividend = bcmul($backDividend, 0.8, 4);

        return $backDividend;
    }

}
