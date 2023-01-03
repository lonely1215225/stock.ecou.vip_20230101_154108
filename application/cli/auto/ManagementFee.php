<?php

namespace app\cli\auto;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use app\index\logic\Commission;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use util\RedisUtil;
use util\ScriptRedis;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 每日收取管理费脚本
 * -- 交易日 08:00 ~ 09:00 仅执行一次
 *
 * -- 策略金扣钱
 * -- 写策略金变动日志
 * -- 持仓增加总管理费
 * -- 管理费收取依据【现价】
 *
 * @package app\cli\auto
 */
class ManagementFee extends Command
{

    protected function configure()
    {
        $this->setName('management_fee')->setDescription('每日收取管理费');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(60000, function () {
            // 交易日(非交易日不执行)
            $tradingDate = TradingUtil::currentTradingDate();

            // 当前时分
            $nowHours = intval(date('Hi'));

            // 条件满足，收取管理费
            if ($nowHours >= 800 && $nowHours <= 900 && TradingRedis::isTradingDate($tradingDate)) {
                if (ScriptRedis::isNotTakeManagementFee()) {
                    $this->takeManagementFee();
                }
            }
        });
    }

    private function takeManagementFee()
    {
        // 取所有持仓的数据
        $positionList = OrderPosition::where('is_finished', false)
            ->where('is_monthly', false)
            ->column('user_id,stock_id,market,stock_code,volume_position,is_suspended,is_cash_coupon', 'id');

        // 计算管理费并收取
        foreach ($positionList as $positionID => $item) {
            Db::startTrans();
            try {
                $userID = $item['user_id'];
                $stockID = $item['stock_id'];
                $market = $item['market'];
                $stockCode = $item['stock_code'];
                $volumePosition = $item['volume_position'];
                $isSuspended = $item['is_suspended'];
                $isCashCoupon = $item['is_cash_coupon'];

                // 取行情中的【现价】
                $quotation = RedisUtil::getQuotationData($stockCode, $market);
                $price = $quotation['Price'];
                // 当前市值
                $stockValue = Calc::calcStockValue($price, $volumePosition);

                if ($item['is_suspended']) {
                    // 停牌管理费
                    $money = Calc::calcSuspendedManagementFee($stockValue);
                } else {
                    // 正常管理费
                    $money = Calc::calcManagementFee($stockValue);
                }

                // 查询账户
                $userAccount = UserAccount::where('user_id', $userID)->field('strategy_balance,cash_coupon')->find();

                $opRet = true;
                $aiRet = true;
                if ($isCashCoupon) {
                    // 策略金变动前
                    $beforeStrategy = $userAccount['cash_coupon'];
                    // 账户变动：减少策略金（扣除管理费）
                    $userAccount['cash_coupon'] = Db::raw("cash_coupon-{$money}");
                    // 保存账户
                    $uaRet = $userAccount->save();

                    // 写入策略金变动日志（收取持仓管理费）
                    $slRet = AccountLog::cashCouponSubDailyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $price, $volumePosition, $money, $isSuspended, $beforeStrategy);
                } else {
                    // 策略金变动前
                    $beforeStrategy = $userAccount['strategy_balance'];
                    // 账户变动：减少策略金（扣除管理费）
                    $userAccount['strategy_balance'] = Db::raw("strategy_balance-{$money}");
                    // 保存账户
                    $uaRet = $userAccount->save();

                    // 写入策略金变动日志（收取持仓管理费）
                    $slRet = AccountLog::strategySubDailyManagementFee($userID, $market, $stockID, $stockCode, $positionID, $price, $volumePosition, $money, $isSuspended, $beforeStrategy);

                    // 写返佣记录
                    $tradedID = 0;
                    $aiRet = Commission::tradedCommission($userID, $money, $positionID, $tradedID, $market, $stockCode, $stockValue);

                    // 持仓：增加总管理费
                    $opRet = OrderPosition::update([
                        'sum_management_fee' => Db::raw("sum_management_fee+{$money}"),
                    ], [
                        ['id', '=', $positionID],
                    ]);
                }


                if ($uaRet && $slRet && $aiRet && $opRet) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $e) {
                Db::rollback();
                $this->output->writeln($e->getFile());
                $this->output->writeln($e->getLine());
                $this->output->writeln($e->getMessage());
                $this->output->writeln($e->getTraceAsString());
            }
        }
    }

}
