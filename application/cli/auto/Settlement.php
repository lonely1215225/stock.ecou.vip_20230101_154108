<?php
namespace app\cli\auto;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\AccountLogic;
use app\index\logic\Calc;
use think\console\Command;
use think\console\Output;
use think\console\Input;
use think\Db;
use util\RedisUtil;
use util\ScriptRedis;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 每日结算脚本
 * -- 交易日 15:20 执行
 * -- 结算使用【今日收盘价】，今日休市后的大盘价就是【今日收盘价】
 * -- 停牌股票不结算
 *
 * @package app\cli\auto
 */
class Settlement extends Command
{

    protected function configure()
    {
        $this->setName('settlement')->setDescription('每日结算');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(60000, function () {
            // 交易日(非交易日不执行)
            $tradingDate = TradingUtil::currentTradingDate();

            // 当前时分
            $nowHours = intval(date('Hi'));

            // 条件满足，执行结算
            if ($nowHours >= 1520 && $nowHours <= 2300 && TradingRedis::isTradingDate($tradingDate)) {
                if (ScriptRedis::isNotSettlement()) {
                    $this->settle();
                }
            }
        });
    }

    private function settle()
    {
        // 取所有持仓的数据，未完结，非停牌
        $positionList = OrderPosition::where('is_finished', false)
            ->where('is_suspended', false)
            ->column('user_id,market,stock_id,stock_code,volume_position,sum_sell_value,sum_sell_value_in,sum_buy_value_cost,sum_deposit,sum_back_profit', 'id');

        // 计算结算盈亏
        foreach ($positionList as $positionID => $item) {
            $userID          = $item['user_id'];
            $stockID         = $item['stock_id'];
            $market          = $item['market'];
            $stockCode       = $item['stock_code'];
            $volumePosition  = $item['volume_position'];
            $sumSellValueIn  = $item['sum_sell_value_in'];
            $sumBuyValueCost = $item['sum_buy_value_cost'];
            $sumDeposit      = $item['sum_deposit'];
            $sumSellValue    = $item['sum_sell_value'];

            // 取今日收盘价（最后一条行情的大盘价即为今日收盘价）
            $quotation  = RedisUtil::getQuotationData($stockCode, $market);
            $todayClose = $quotation['Price'];

            $settlePAL = Calc::settlePAL($todayClose, $volumePosition, $sumSellValueIn, $sumBuyValueCost, $sumDeposit);

            // 【结算盈亏大于0】 入账
            if ($settlePAL > 0) {
                Db::startTrans();
                try {
                    // 保证金变动后
                    $sumDepositAfter = bcsub($sumDeposit, $settlePAL, 2);
                    // 新的止损价
                    $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDepositAfter, $volumePosition);
                    // 更新持仓：减少保证金，增加累计提走盈利，计算新的上损价
                    $pRet = OrderPosition::update([
                        'sum_deposit'     => $sumDepositAfter,
                        'sum_back_profit' => Db::raw("sum_back_profit+{$settlePAL}"),
                        'stop_loss_price' => $stopLossPrice,
                    ], [['id', '=', $positionID]]);

                    // 查询账户
                    $userAccount = UserAccount::where('user_id', $userID)->field('strategy_balance')->find();
                    // 策略金变动前
                    $beforeStrategy = $userAccount['strategy_balance'];
                    // 账户变动：增加策略金（结算入账）
                    $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$settlePAL}");
                    // 保存账户
                    $uaRet = $userAccount->save();

                    // 写入策略金变动日志
                    $slRet = AccountLog::strategyDailySettlement($userID, $market, $stockID, $stockCode, $positionID, $todayClose, $volumePosition, $sumSellValueIn, $sumBuyValueCost, $sumDeposit, $settlePAL, $beforeStrategy, $stopLossPrice);

                    if ($pRet && $uaRet && $slRet) {
                        Db::commit();

                        // 更新用户的总保证金
                        AccountLogic::updateTotalDeposit($userID);
                    } else {
                        Db::rollback();
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                    dump($e->getFile());
                    dump($e->getLine());
                    dump($e->getMessage());
                    dump($e->getTraceAsString());
                }
            }
        }
    }

}
