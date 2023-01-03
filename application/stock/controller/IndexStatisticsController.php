<?php
namespace app\stock\controller;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use app\common\model\OrderPosition;
use app\common\model\OrderTraded;
use app\common\model\PayCompany;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\UserBankCard;
use app\common\model\UserRecharge;
use app\common\model\UserStrategyLog;
use app\common\model\UserWalletLog;
use app\common\model\UserWithdraw;
use util\RedisUtil;
use util\BasicData;
use util\Excel;

class IndexStatisticsController extends BaseController
{

    /**
     * 获取今日注册人数
     *
     * @return \think\response\Json
     */
    public function registeredToday()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['id', 'not in', $excludeUserIds];
        }
        $count = User::where('create_time', '>=', strtotime('today'))
            ->where('create_time', '<', strtotime('tomorrow'))
            ->where($map)
            ->count();

        return $this->message(1, '', $count);
    }

    /**
     * 累计注册人数
     *
     * @return \think\response\Json
     */
    public function registeredTotal()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['id', 'not in', $excludeUserIds];
        }
        $count = User::where($map)->count();

        return $this->message(1, '', $count);
    }

    /**
     * 获取今日累计充值金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userRechargeToday()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $userRechargeToday = UserRecharge::where('pay_time', '>=', mktime(0, 0, 0, date('m'), date('d'), date('Y')))
            ->where('pay_time', '<=', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1)
            ->where(function (\think\Db\Query $query) {
                $query->where('pay_state', RECHARGE_PAY_SUCCESS)->whereor('pay_state', RECHARGE_PAY_OFFLINE);
            })
            ->where('is_delete', false)
            ->where($map)
            ->field('SUM(real_money) as real_money')->find();

        return $this->message(1, '', $userRechargeToday);
    }

    /**
     * 获取累计充值金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function totalRecharge()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $totalRecharge = UserRecharge::where('is_delete', false)
            ->where(function (\think\Db\Query $query) {
                $query->where('pay_state', RECHARGE_PAY_SUCCESS)->whereor('pay_state', RECHARGE_PAY_OFFLINE);
            })
            ->where($map)
            ->field('SUM(real_money) as real_money')->find();

        return $this->message(1, '', $totalRecharge);
    }

    /**
     * 获取今天成功提现总金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userWithdrawToday()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        // 获取今日用户成功提现金额
        $userWithdrawToday = UserWithdraw::where('success_time', '>=', strtotime('today'))
            ->where('success_time', '<', strtotime('tomorrow'))
            ->where($map)
            ->where('state', USER_WITHDRAW_SUCCESS)->where('is_delete', false)->field('SUM(money) as money')->find();

        return $this->message(1, '', $userWithdrawToday);
    }

    /**
     * 获取今天成功提现总金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function totalWithdraw()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        // 获取今日用户成功提现金额
        $totalWithdraw = UserWithdraw::where('state', USER_WITHDRAW_SUCCESS)
            ->where('is_delete', false)
            ->where($map)
            ->field('SUM(money) as money')
            ->find();

        return $this->message(1, '', $totalWithdraw);
    }

    /**
     * 统计持仓列表数据
     *
     * @return \think\response\Json
     */
    public function positionStatistics()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $positionStatistic = OrderPosition::where('is_finished', false)
            ->where($map)
            ->group('primary_account')
            ->column('SUM(sum_buy_value) as sum_buy_value,SUM(sum_buy_value_cost) as sum_buy_value_cost,SUM(sum_sell_value) as sum_sell_value,SUM(sum_sell_value_in) as sum_sell_value_in,SUM(sum_deposit) as sum_deposit', 'primary_account');

        return $this->message(1, '', $positionStatistic);
    }

    /**
     * 买入成交明细统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function TradedStatistics()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $buyStatistics = OrderTraded::where('trading_date', date('Y-m-d'))
            ->where($map)
            ->field('SUM(total_fee) as total_fee')->find();

        return $this->message(1, '', $buyStatistics);
    }

    /**
     * 累计综合费用收入
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function TradedTotal()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $buyStatistics = OrderTraded::where($map)->field('SUM(total_fee) as total_fee')->find();

        return $this->message(1, '', $buyStatistics);
    }

    /**
     * 佣金明细统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function incomeStatistics()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $incomeStatistics = AdminIncome::where('income_time', '>=', date('Y-m-d 00:00:00'))
            ->where('income_time', '<', date('Y-m-d 23:59:59'))
            ->where($map)
            ->field('SUM(money) as money,SUM(platform_money) as platform_money,SUM(agent_money) as agent_money,SUM(broker_money) as broker_money')
            ->find();

        return $this->message(1, '', $incomeStatistics);
    }

    /**
     * 累计佣金明细统计
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function incomeTotal()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $incomeStatistics = AdminIncome::where($map)
            ->field('SUM(money) as money,SUM(platform_money) as platform_money,SUM(agent_money) as agent_money,SUM(broker_money) as broker_money')
            ->find();

        return $this->message(1, '', $incomeStatistics);
    }

    /**
     * 获取所有用户账户资金、策略金金额
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userAccount()
    {
        $excludeUserIds = $this->exclude_user();
        $map            = [];
        if ($excludeUserIds) {
            $map[] = ['user_id', 'not in', $excludeUserIds];
        }
        $totalAccount = UserAccount::where($map)
            ->field('SUM(wallet_balance) as totalWallet,SUM(strategy_balance) as totalStrategy')->find();

        return $this->message(1, '', $totalAccount);
    }

    /**
     * 排除用户列表
     *
     * @return array
     */
    public function exclude_user()
    {
        $user = User::where('agent_id', 'in', EXCLUDE_AGENT)->column('id');

        return $user;
    }

    /**
     * 根据手机号导出用户信息
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportUserInfo()
    {
        $mobile = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        if ($mobile == '') return false;
        $fileName = date('Y-m-d') . '-' . $mobile . '.xlsx';
        $excel    = new Excel($fileName);
        // 获取用户基本信息
        $userInfo = User::where('mobile', $mobile)->field('id,real_name,mobile,agent_id,broker_id')->find();
        if (!$userInfo) return '该手机号不存在';
        $id = $userInfo['id'];
        // 银行卡信息
        $userBankInfo = UserBankCard::where('user_id', $id)->field('id_card_number')->find();
        // 账户、策略金信息
        $countInfo         = UserAccount::where('user_id', $id)->field('wallet_balance,strategy_balance')->find();
        $agentInfo         = AdminUser::where('id', $userInfo['agent_id'])->field('org_name')->find();
        $brokerInfo        = AdminUser::where('id', $userInfo['broker_id'])->field('org_name')->find();
        $userArr[0][]      = $userInfo['real_name'];
        $userArr[0][]      = $userInfo['mobile'];
        $userArr[0][]      = isset($userBankInfo['id_card_number']) ? ' ' . $userBankInfo['id_card_number'] : '';
        $userArr[0][]      = isset($countInfo['wallet_balance']) ? $countInfo['wallet_balance'] : '';
        $userArr[0][]      = isset($countInfo['strategy_balance']) ? $countInfo['strategy_balance'] : '';
        $userArr[0][]      = $agentInfo['org_name'];
        $userArr[0][]      = $brokerInfo['org_name'];
        $userArr[0][]      = date('Y-m-d H:i:s');
        $userHead['text']  = array('姓名', '手机号', '身份证号', '钱包余额', '策略金余额', '代理商', '经济人', '导出时间');
        $userHead['width'] = ['B' => 15, 'C' => 20, 'H' => 20];
        $excel->setActiveSheet(0, '用户基本信息');
        $excel->setData($userArr, $userHead);
        // 获取用户持仓信息
        $positionInfo = OrderPosition::where('user_id', $id)->where('is_finished', false)
            ->field('stock_code,market,sum_buy_value,sum_sell_value,sum_deposit,position_price,volume_position')
            ->select();
        $positionArr  = [];
        if ($positionInfo->toArray()) {
            foreach ($positionInfo as $key => $item) {
                // 行情数据并计算持仓盈亏
                $quotation           = RedisUtil::getQuotationData($item['stock_code'], $item['market']);
                $pal                 = ($quotation['Price'] - $item['position_price']) * $item['volume_position'];
                $pal                 = round($pal, 2);
                $positionArr[$key][] = $item['market'] . $item['stock_code'];
                $positionArr[$key][] = isset($item['stock_code']) ? RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] : '';
                $positionArr[$key][] = $item['sum_buy_value'];
                $positionArr[$key][] = $item['sum_sell_value'];
                $positionArr[$key][] = $pal;
                $positionArr[$key][] = $item['sum_deposit'];
            }
        } else {
            $positionArr[0][] = '';
        }
        $positionHead['text']  = array('股票代码', '股票名称', '买入市值', '卖出市值', '持仓盈亏', '累计保证金');
        $positionHead['width'] = [];
        $excel->setActiveSheet(null, '用户持仓信息');
        $excel->setData($positionArr, $positionHead);
        // 用户成交明细
        $tradedInfo = OrderTraded::where('user_id', $id)
            ->field('order_position_id,order_id,trading_date,trading_time,stock_code,market,direction,volume,price,traded_value,total_fee,management_fee,deposit')
            ->select();
        $tradedArr  = [];
        if ($tradedInfo->toArray()) {
            foreach ($tradedInfo as $key => $item) {
                $tradedArr[$key][] = $item['order_position_id'];
                $tradedArr[$key][] = $item['order_id'];
                $tradedArr[$key][] = $item['trading_date'] . ' ' . $item['trading_time'];
                $tradedArr[$key][] = $item['market'] . $item['stock_code'];
                $tradedArr[$key][] = isset($item['stock_code']) ? RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] : '';
                $tradedArr[$key][] = $item['direction'] == 'buy' ? '买入' : '卖出';
                $tradedArr[$key][] = $item['volume'];
                $tradedArr[$key][] = $item['price'];
                $tradedArr[$key][] = $item['traded_value'];
                $tradedArr[$key][] = $item['total_fee'];
                $tradedArr[$key][] = $item['management_fee'];
                $tradedArr[$key][] = $item['deposit'];
            }
        } else {
            $tradedArr[0][] = '';
        }
        $tradedHead['text']  = array('持仓编号', '委托编号', '成交时间', '股票代码', '股票名称', '方向', '成交数量', '成本价', '成交市值', '综合费用', '管理费', '履约保证金');
        $tradedHead['width'] = ['C' => 20];
        $excel->setActiveSheet(null, '用户成交明细');
        $excel->setData($tradedArr, $tradedHead);
        // 平仓信息
        $closePosition = OrderPosition::where('user_id', $id)->where('is_finished', true)
            ->field('id,stock_code,market,s_time,sum_buy_volume,sum_sell_volume,b_cost_price,s_cost_price,sum_buy_value_cost,sum_sell_value_in,s_pal')
            ->select();
        $cPositionArr  = [];
        if ($closePosition->toArray()) {
            foreach ($closePosition as $key => $item) {
                $cPositionArr[$key][] = $item['id'];
                $cPositionArr[$key][] = $item['market'] . $item['stock_code'];
                $cPositionArr[$key][] = isset($item['stock_code']) ? RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] : '';
                $cPositionArr[$key][] = date('Y-m-d H:i:s', $item['s_time']);
                $cPositionArr[$key][] = $item['sum_buy_volume'];
                $cPositionArr[$key][] = $item['sum_sell_volume'];
                $cPositionArr[$key][] = $item['b_cost_price'];
                $cPositionArr[$key][] = $item['s_cost_price'];
                $cPositionArr[$key][] = $item['sum_buy_value_cost'];
                $cPositionArr[$key][] = $item['sum_sell_value_in'];
                $cPositionArr[$key][] = $item['s_pal'];
            }
        } else {
            $cPositionArr[0][] = '';
        }
        $cPHead['text']  = array('持仓编号', '股票代码', '股票名称', '结算时间', '买入数量', '卖出数量', '买入均价', '卖出均价', '总买入市值', '总卖出市值', '结算盈亏');
        $cPHead['width'] = ['D' => 20];
        $excel->setActiveSheet(null, '用户平仓信息');
        $excel->setData($cPositionArr, $cPHead);
        // 账户资金流水
        $walletLog = UserWalletLog::where('user_id', $id)
            ->field('id,change_type,change_time,change_money,before_balance,after_balance')
            ->select();
        $walletArr = [];
        if ($walletLog->toArray()) {
            foreach ($walletLog as $key => $item) {
                $walletArr[$key][] = $item['id'];
                $walletArr[$key][] = isset($item['change_type']) ? BasicData::USER_WALLET_CHANGE_TYPE_LIST[$item['change_type']] : '';
                $walletArr[$key][] = $item['change_time'];
                $walletArr[$key][] = $item['change_money'];
                $walletArr[$key][] = $item['before_balance'];
                $walletArr[$key][] = $item['after_balance'];
            }
        } else {
            $walletArr[0][] = '';
        }
        $walletHead['text']  = array('流水号', '变动类型', '变动时间', '变动金额', '发生前金额', '发生后金额');
        $walletHead['width'] = ['B' => 15, 'C' => 20];
        $excel->setActiveSheet(null, '账户资金流水');
        $excel->setData($walletArr, $walletHead);
        // 策略金流水
        $strategyLog = UserStrategyLog::where('user_id', $id)
            ->field('id,order_position_id,stock_code,market,change_time,change_type,change_money,before_balance,after_balance')
            ->select();
        $strategyArr = [];
        if ($strategyLog->toArray()) {
            foreach ($strategyLog as $key => $item) {
                $strategyArr[$key][] = $item['id'];
                $strategyArr[$key][] = $item['order_position_id'];
                $strategyArr[$key][] = $item['market'] . $item['stock_code'];
                $strategyArr[$key][] = isset($item['stock_code']) ? RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] : '';
                $strategyArr[$key][] = $item['change_time'];
                $strategyArr[$key][] = isset($item['change_type']) ? BasicData::USER_STRATEGY_CHANGE_TYPE_LIST[$item['change_type']] : '';
                $strategyArr[$key][] = $item['change_money'];
                $strategyArr[$key][] = $item['before_balance'];
                $strategyArr[$key][] = $item['after_balance'];
            }
        } else {
            $strategyArr[0][] = '';
        }
        $strategyHead['text']  = array('流水号', '持仓编号', '股票代码', '股票名称', '发生时间', '变动类型', '变动金额', '发生前金额', '发生后金额');
        $strategyHead['width'] = ['E' => 20, 'F' => 15];
        $excel->setActiveSheet(null, '策略金流水');
        $excel->setData($strategyArr, $strategyHead);
        // 充值记录
        $rechargeLog = UserRecharge::where('user_id', $id)
            ->field('money,real_money,pay_state,pay_time,third_order_sn,pay_company_id')
            ->select();
        $rechargeArr = [];
        if ($rechargeLog->toArray()) {
            // 获取支付机构信息
            $payCompany = PayCompany::column('name,pay_type', 'id');
            foreach ($rechargeLog as $key => $item) {
                $rechargeArr[$key][] = $item['money'];
                $rechargeArr[$key][] = $item['real_money'];
                $rechargeArr[$key][] = isset($item['pay_state']) ? BasicData::RECHARGE_PAY_STATE_LIST[$item['pay_state']] : '';
                $rechargeArr[$key][] = date('Y-m-d H:i:s', $item['pay_time']);
                $rechargeArr[$key][] = ' ' . $item['third_order_sn'];
                $rechargeArr[$key][] = isset($payCompany[$item['pay_company_id']]) ? $payCompany[$item['pay_company_id']]['name'] . '(' . $payCompany[$item['pay_company_id']]['pay_type'] . ')' : '';
            }
        } else {
            $rechargeArr[0][] = '';
        }
        $rechargeHead['text']  = array('充值金额', '实际充值到账', '支付状态', '到账时间', '第三方流水号', '支付机构');
        $rechargeHead['width'] = ['D' => 20, 'E' => '20', 'F' => 20];

        $excel->setActiveSheet(null, '用户充值记录');
        $excel->setData($rechargeArr, $rechargeHead);
        $excel->export();
    }

}
