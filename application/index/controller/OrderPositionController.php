<?php

namespace app\index\controller;

use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\index\logic\AccountLog;
use app\index\logic\Commission;
use app\index\logic\Calc;
use util\RedisUtil;
use think\Db;
use app\cli\exception\TradingCheckException;

class OrderPositionController extends BaseController
{

    /**
     * 当前持仓列表
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = OrderPosition::where('user_id', $this->userId)
            ->where('is_finished', false)
            ->where('market', 'NOT NULL')
            ->where('stock_code', 'NOT NULL')
            ->field('id,market,stock_code,stock_id,volume_position,volume_for_sell,position_price,stop_loss_price,sum_sell_pal,sum_buy_value_cost,sum_sell_value_in,is_monthly,monthly_expire_date,is_cash_coupon')
            ->order('id', 'ASC')
            ->select()
            ->toArray();
        if(!$list)return $this->message(0, '没有持仓数据', $list);
        // 一些计算值、名称、现价
        foreach ($list as $key => $item) {
            // 基本数据
            $basicData = RedisUtil::getStockData($item['stock_code'], $item['market']);
            if(empty($basicData)) continue;
            //print_r($basicData);exit;
            // 行情数据
            $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);

            // 当前价
            $nowPrice = $quotation['Price'];

            // 持仓数量
            $volumePosition = $item['volume_position'];

            // 持仓均价
            $positionPrice = $item['position_price'];

            // 当前市值
            $nowValue = Calc::calcStockValue($nowPrice, $volumePosition);

            $list[$key]['is_kechuang']     = $basicData['is_kechuang'] ?: 'false';
            $list[$key]['security_type']   = $basicData['security_type'] ?: 'false';
            $list[$key]['stock_name']      = $basicData['stock_name'] ?: '--';
            $list[$key]['Price']           = $quotation['Price'] ?: '0.00';
            $list[$key]['nowValue']        = $nowValue ?: 0;
            $list[$key]['pal']             = Calc::calcFloatPAL($quotation['Price'], $item['position_price'], $item['volume_position']) ?: '0.00';
            $list[$key]['pal_rate']        = round((floatval($quotation['Price']) - $item['position_price']) * 100 / $item['position_price'], 2) . '%' ?: '0.00%';
            $list[$key]['sum_pal']         = round($list[$key]['pal'] + $item['sum_sell_pal'], 2) ?: '0.00';
            $list[$key]['position_income'] = Calc::calcPositionIncome($nowPrice, $positionPrice, $volumePosition) ?: 0;
        }

        return $this->message(1, '', $list);
    }

    /**
     * 历史结算单
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function settlement_history()
    {
        $startDate = input('start_date', '', ['filter_date', 'strtotime']);
        $endDate = input('end_date', '', 'filter_date');
        $endDate = $endDate != '' ? $endDate . ' 23:59:59' : $endDate;
        $endDate = strtotime($endDate);

        $map[] = ['user_id', '=', $this->userId];
        $map[] = ['is_finished', '=', true];

        if ($startDate) {
            $map[] = ['s_time', '>=', $startDate];
        }
        if ($endDate) {
            $map[] = ['s_time', '<=', $endDate];
        }

        $list = OrderPosition::where($map)
            ->field('id,market,stock_code,s_time,sum_buy_volume,b_cost_price,position_price,sum_buy_value_cost,sum_sell_volume,s_cost_price,sum_sell_value_in,s_pal,is_monthly,monthly_expire_date')
            ->order('id', 'DESC')
            ->paginate();

        foreach ($list as $key => $item) {
            $list[$key]['stock_name'] = RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] ?? '';
            $list[$key]['s_time'] = date('Y-m-d H:i:s', $item['s_time']);
        }

        return $this->message(1, '', $list);
    }

    /**
     * 获取持仓用户自选
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function simple()
    {
        $list = OrderPosition::where('user_id', $this->userId)
            ->where('is_finished', false)
            ->where('market', 'NOT NULL')
            ->where('stock_code', 'NOT NULL')
            ->field('id,market,stock_code')
            ->select()
            ->toArray();
        foreach ($list as $key => $item) {
            // 基本数据
            $basicData = RedisUtil::getStockData($item['stock_code'], $item['market']);

            // 行情数据
            $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);

            $list[$key]['stock_name'] = $basicData['stock_name'];
            $list[$key]['security_type'] = $basicData['security_type'];
            $list[$key]['is_kechuang'] = $basicData['is_kechuang'];
            $list[$key]['Bp1'] = $quotation['Bp1'];
            $list[$key]['Sp1'] = $quotation['Sp1'];
        }

        return $this->message(1, '', $list);
    }


    /**
     * 用户管理费续费接口
     * @return \think\response\Json
     */
    public function monthly_fee()
    {
        $id = input('post.id', 0, FILTER_SANITIZE_NUMBER_INT);

        Db::startTrans();
        try {
            // 查询次仓订单信息
            $orderPosition = OrderPosition::where('id', $id)
                ->where('is_monthly', true)
                ->field('id,market,stock_id,stock_code,position_price,volume_position,volume_for_sell,is_suspended,is_monthly,monthly_expire_date')
                ->find();

            if (!$orderPosition) throw new TradingCheckException('未找到订单信息');

            if((strtotime($orderPosition['monthly_expire_date']) - time()) > 259200) throw new TradingCheckException('订单信息暂时不能充值');
            // 取行情中的【现价】
            $quotation = RedisUtil::getQuotationData($orderPosition['stock_code'], $orderPosition['market']);
            $price = $quotation['Price'];

            $positionID = $orderPosition['id'];
            $userID = $this->userId;
            $stockID = $orderPosition['stock_id'];
            $market = $orderPosition['market'];
            $stockCode = $orderPosition['stock_code'];
            $volumePosition = $orderPosition['volume_position'];

            // 当前持仓市值
            $tradedValue = bcmul($orderPosition['volume_position'], $price, 2);

            //需要计算管理费
            $managementFee = Calc::calcMonthlyManagementFee($tradedValue);

            // 查询用户的账户信息
            $account = UserAccount::where('user_id', $this->userId)->field('wallet_balance,strategy_balance,deposit,frozen')->find();
            $strategyBalance = $account['strategy_balance'];
            if ($strategyBalance < $managementFee) return $this->message(0, '策略金余额不足请及时续费！');

            $slRet = false;
            $aiRet = false;
            $opRet = false;
            if (UserAccount::where('user_id', $this->userId)->setDec('strategy_balance', $managementFee)) {
                // 写入策略金变动日志（收取持仓管理费）
                $slRet = AccountLog::strategySubMonthlyManagementFee($userID, $market, $stockID, $stockCode, $positionID, 0, $price, $volumePosition, $managementFee, $strategyBalance);

                // 写返佣记录
                $tradedID = 0;
                $aiRet = Commission::tradedCommissionMonthly($userID, $managementFee, $positionID, $tradedID, $market, $stockCode, $volumePosition);

                //续费到期日
                $beforeMonthlyExpireDate = $orderPosition['monthly_expire_date'];
                $monthlyExpireDate = (new \DateTime($beforeMonthlyExpireDate))->add(\DateInterval::createFromDateString("1 month 1 day"))->format('Y-m-d H:i');
                // 持仓：增加总管理费
                $opRet = OrderPosition::update([
                    'sum_management_fee'    => Db::raw("sum_management_fee+{$managementFee}"),
                    'monthly_expire_date'   => $monthlyExpireDate
                ], [
                    ['id', '=', $positionID],
                ]);
            }

            if ($slRet && $aiRet && $opRet) {
                // 提交事务
                Db::commit();
                // 返回成功信息
                return $this->message(1, '续费成功！');
            } else {
                // 提交事务
                Db::rollback();
                // 返回失败信息
                return $this->message(0, '续费失败！');
            }
        } catch (\Exception $e) {
            Db::rollback();
            //dump($e->getFile());
            //dump($e->getLine());
            //dump($e->getMessage());
            //dump($e->getTraceAsString());
            // 返回失败信息
            return $this->message(0, '续费失败！');
        }

    }
}