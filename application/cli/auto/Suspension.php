<?php
namespace app\cli\auto;

use app\common\model\OrderPosition;
use app\common\model\Stock;
use app\common\model\StockSuspension AS SuspensionModel;
use app\common\model\StockSuspensionLog;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use util\OrderRedis;
use util\RedisUtil;
use util\TradingRedis;
use util\TradingUtil;

/**
 * 停牌复牌脚本
 * --交易日每5秒检测一次
 *
 * @package app\cli\auto
 */
class Suspension extends Command
{

    protected function configure()
    {
        $this->setName('suspension')->setDescription('停牌复牌');
    }

    protected function execute(Input $input, Output $output)
    {
        swoole_timer_tick(5000, function () {
            $tradingDate = TradingUtil::currentTradingDate();
            if (TradingRedis::isTradingDate($tradingDate)) {
                // 检测执行停牌操作
                $this->executeSuspension();

                // 检测执行复牌操作
                $this->executeResume();

                // 检测执行追加停牌保证金
                $this->executeDeposit();
            }
        });
    }

    // 执行停牌操作
    public function executeSuspension()
    {
        $start = date('Y-m-d H:i:s', time() - 60);
        $end   = date('Y-m-d H:i:s', time() + 5);
        // 获取停牌列表
        $suspensionList = SuspensionModel::where('suspension_date', '>=', $start)
            ->where('suspension_date', '<=', $end)
            ->where('is_suspension_run', false)
            ->column('id,market,stock_id,stock_code,suspension_date', 'id');

        if (count($suspensionList)) {
            foreach ($suspensionList as $suspensionID => $item) {
                Db::startTrans();
                try {
                    $market    = $item['market'];
                    $stockCode = $item['stock_code'];

                    // 设置股票已停牌
                    $stockRet = Stock::update([
                        'is_suspended' => true,
                    ], [
                        ['market', '=', $market],
                        ['stock_code', '=', $stockCode],
                    ]);

                    // 写入需要追加保证金的列表
                    $susLogRet = OrderPosition::where('market', $market)
                        ->where('stock_code', $stockCode)
                        ->where('is_finished', false)
                        ->fieldRaw("{$suspensionID},id,floor(extract(epoch from(now())))")
                        ->selectInsert('stock_suspension_id,order_position_id,update_time', '__STOCK_SUSPENSION_LOG__');

                    // 设置对应持仓的状态为：已停牌
                    $pRet = OrderPosition::update([
                        'is_suspended' => true,
                    ], [
                        ['market', '=', $market],
                        ['stock_code', '=', $stockCode],
                    ]);

                    // 设置停牌已执行
                    $susRet = SuspensionModel::update([
                        'is_suspension_run'   => true,
                        'suspension_run_time' => date('Y-m-d H:i:s'),
                    ], [
                        ['id', '=', $suspensionID],
                    ]);

                    if ($stockRet && $susLogRet && $pRet && $susRet) {
                        Db::commit();

                        // 更新股票缓存
                        RedisUtil::cacheStockData($stockCode, $market);

                        // 更新所有持仓的缓存
                        OrderRedis::cacheAllPosition();
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

    // 执行复牌操作
    public function executeResume()
    {
        $start = date('Y-m-d H:i:s', time() - 60);
        $end   = date('Y-m-d H:i:s', time() + 5);
        // 获取复牌列表
        $suspensionList = SuspensionModel::where('resumption_date', '>=', $start)
            ->where('resumption_date', '<=', $end)
            ->where('is_resume_run', false)
            ->column('market,stock_code', 'id');

        if (count($suspensionList)) {
            foreach ($suspensionList as $suspensionID => $item) {
                Db::startTrans();
                try {
                    $market    = $item['market'];
                    $stockCode = $item['stock_code'];

                    // 设置股票已复牌
                    $stockRet = Stock::update([
                        'is_suspended' => false,
                    ], [
                        ['market', '=', $market],
                        ['stock_code', '=', $stockCode],
                    ]);

                    // 设置对应持仓的状态为：已复牌
                    $pRet = OrderPosition::update([
                        'is_suspended' => false,
                    ], [
                        ['market', '=', $market],
                        ['stock_code', '=', $stockCode],
                    ]);

                    // 设置复牌已执行
                    $susRet = SuspensionModel::update([
                        'is_resume_run'   => true,
                        'resume_run_time' => date('Y-m-d H:i:s'),
                    ], [
                        ['id', '=', $suspensionID],
                    ]);

                    if ($stockRet && $pRet && $susRet) {
                        Db::commit();

                        // 更新股票缓存
                        RedisUtil::cacheStockData($stockCode, $market);

                        // 更新所有持仓的缓存
                        OrderRedis::cacheAllPosition();
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

    // 执行追加保证金操作
    public function executeDeposit()
    {
        $list = StockSuspensionLog::where('is_add_deposit', false)
            ->order('id', 'ASC')
            ->column('order_position_id', 'id');

        if (count($list)) {
            foreach ($list as $logID => $positionID) {
                Db::startTrans();
                try {
                    // 获取持仓信息
                    $position = OrderPosition::where('id', $positionID)
                        ->field('id,user_id,stock_id,market,stock_code,volume_position,sum_buy_value_cost,sum_sell_value,sum_deposit')
                        ->find();

                    $userID          = $position['user_id'];
                    $market          = $position['market'];
                    $stockCode       = $position['stock_code'];
                    $stockID         = $position['stock_id'];
                    $volumePosition  = $position['volume_position'];
                    $sumBuyValueCost = $position['sum_buy_value_cost'];
                    $sumSellValue    = $position['sum_sell_value'];
                    $sumDeposit      = $position['sum_deposit'];

                    // 获取行情
                    $quotation = RedisUtil::getQuotationData($stockCode, $market);
                    // 当前价
                    $price = $quotation['Price'];
                    // 当前市值
                    $nowValue = Calc::calcStockValue($price, $volumePosition);
                    // 计算追加保证金金额
                    $addDeposit = Calc::calcSuspensionDeposit($nowValue);
                    // 追加后的保证金
                    $sumDepositAfter = bcadd($sumDeposit, $addDeposit, 2);
                    // 计算止损价
                    $stopLossPrice = Calc::calcStopLossPrice($sumBuyValueCost, $sumSellValue, $sumDepositAfter, $volumePosition);

                    // 获取用户账户信息
                    $account = UserAccount::where('user_id', $userID)->field('strategy_balance')->find();
                    // 策略金变动前
                    $beforeStrategy = $account['strategy_balance'];
                    // 账户：减少策略金
                    $account['strategy_balance'] = Db::raw("strategy_balance-{$addDeposit}");
                    // 保存账户
                    $uaRet = $account->save();

                    // 持仓：增加保证金，更新止损价
                    $position['sum_deposit']     = $sumDepositAfter;
                    $position['stop_loss_price'] = $stopLossPrice;
                    // 保存持仓
                    $pRet = $position->save();

                    // 写入策略金变动日志（追加停牌保证金）
                    $sLogRet = AccountLog::strategySuspensionDeposit($userID, $market, $stockID, $stockCode, $positionID, $volumePosition, $addDeposit, $beforeStrategy);

                    // 停牌追加保证金日志：设置已追加
                    $susLogRet = StockSuspensionLog::update([
                        'is_add_deposit' => true,
                    ], [
                        ['id', '=', $logID],
                    ]);

                    if ($uaRet && $sLogRet && $pRet && $susLogRet) {
                        Db::commit();

                        // 更新用户的总保证金
                        AccountLogic::updateTotalDeposit($userID);

                        // 更新用户的策略金缓存（不含冻结资金）
                        OrderRedis::cacheUserStrategy($userID);
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
