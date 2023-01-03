<?php

namespace app\index\controller;

use app\common\model\OrderPosition;
use app\common\model\PayCompany;
use app\common\model\UserAccount;
use app\common\model\UserBankCard;
use app\common\model\UserRecharge;
use app\common\model\UserStrategyLog;
use app\common\model\UserWalletLog;
use app\common\model\UserWithdraw;
use app\index\logic\AccountLog;
use app\index\logic\Calc;
use app\index\logic\Funds;
use think\Db;
use util\BasicData;
use util\OrderRedis;
use util\RedisUtil;
use util\SystemRedis;
use util\SysWsRedis;
use util\TradingRedis;
use util\TradingUtil;


class AccountController extends BaseController
{

    /**
     * 用户资金详情
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 查询用户的账户信息
        $account = UserAccount::where('user_id', $this->userId)->field('wallet_balance,strategy_balance,deposit,frozen,cash_coupon,cash_coupon_time,cash_coupon_frozen')->find();

        // 统计历史结算盈亏（仅统计已完结持仓）
        $historyPAL = OrderPosition::where('user_id', $this->userId)->where('is_finished', true)->sum('s_pal');

        // 用户的持仓统计
        $statistic = Funds::userAllPositionStatistic($this->userId);
        // 所有持仓的盈亏
        $sumTotalPAL = $statistic['sumTotalPAL'];
        // 所有持仓当前市值
        $sumNowValue = $statistic['sumNowValue'];
        // 所有已停牌持仓的市值
        $sumNowValueSuspended = $statistic['sumNowValueSuspended'];

        // 可用策略金余额= 策略金余额 - 冻结资金
        $availableStrategy = bcsub($account['strategy_balance'], $account['frozen'], 2);

        $usercashCouponData = isBuyCashCoupon($this->userId);
        $cashCouponData = SystemRedis::getCashCoupon();

        $data = [
            // 账户资金
            'wallet_balance' => $account['wallet_balance'],
            // 冻结（预扣保证金）
            'frozen' => $account['frozen'],
            //代金券金额
            'cash_coupon' => $usercashCouponData['buyCapital'],
            //代金券冻结
            'cash_coupon_frozen' => $account['cash_coupon_frozen'],
            //代金券有效期
            'cash_expiry_time' => $usercashCouponData['expiryDate'],
            // 策略金余额
            'strategy_money' => $availableStrategy,
            // 动态资产
            'dynamic_money' => Calc::calcDynamic($account['strategy_balance'], $account['deposit'], $statistic['sumPositionIncome']),
            // 实盘可买
            'real_buy_money' => $availableStrategy < 0 ? 0 : bcmul($availableStrategy, 10, 2),
            // 证券市值
            'sum_now_value' => $sumNowValue,
            // 累计持仓盈亏（已弃用）
            //'sum_total_pal' => $sumTotalPAL,
            // 持仓总收益 SUM(当前市值 + 总卖出市值 - 总买入市值)
            'sum_position_income' => $statistic['sumPositionIncome'],
            // 明日管理费
            'tomorrow' => $statistic['tomorrowMFee'],
            // 持仓总保证金
            'deposit' => $account['deposit'],
            // 历史结算盈亏
            'history_pal' => $historyPAL,
            //是否开启代金券
            'is_open' => $cashCouponData['is_open'],
            //代金券金额
            'cash_coupon_money' => $cashCouponData['cash_coupon_money'],
            //代金券有效期
            'expiry_time' => $cashCouponData['expiry_time'],
            //代金券有效期单位
            'expiry_unit' => $cashCouponData['expiry_unit'] == 1 ? '月' : '天',
            //代金券成交亏损是否计入策略金
            'in_loss' => $cashCouponData['in_loss'],
            //代金券强制平仓时间
            'close_position_time' => $cashCouponData['close_position_time'],
        ];

        return $this->message(1, '', $data);
    }
    public function myAccount()
    {
        // 查询用户的账户信息
        $account = UserAccount::where('user_id', $this->userId)->field('wallet_balance')->find();
        $data = [
            // 账户资金
            'wallet_balance' => $account['wallet_balance'],
        ];
        return $this->message(1, '', $data);
    }
    /**
     * 最大可买市值
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function buy_capital()
    {
        // 是否按月收取管理费
        $isMonthly = input('is_monthly', '', [FILTER_SANITIZE_STRING, 'trim', 'strtoupper']);
        //证券市场
        $market = input('market', '', [FILTER_SANITIZE_STRING, 'trim', 'strtoupper']);
        //合约编号
        $stockCode = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);

        //是否按代金券购买
        $isCashCoupon = input('is_cash_coupon', 'N', [FILTER_SANITIZE_STRING, 'trim', 'strtoupper']);
        $isCashCoupon = $isCashCoupon == 'Y';

        // 获取策略金余额
        $account = UserAccount::where('user_id', $this->userId)->field('strategy_balance,frozen,cash_coupon,cash_coupon_time,cash_coupon_frozen,cash_coupon_uptime')->find();

        //代金券购买
        $buyType = '';
        //代金券有效期
        $isValid = isBuyCashCoupon($this->userId);

        if ($isCashCoupon) {
            $buyCapital = $isValid['buyCapital'];
        } else {
            // 策略金余额（含冻结）
            $strategyBalance = $account['strategy_balance'];
            // 冻结资金
            $frozen = $account['frozen'];

            $buyCapital = $isValid['buyCapital'];
            $maxCashCouponBuyVolume = 0;
            if($market && $stockCode){
                // 获取行情数据
                $quotation = RedisUtil::getQuotationData($stockCode, $market);
                $price = $quotation['Price'];
                // 计算最高可买
                $maxCashCouponBuyVolume = (bcdiv($buyCapital, $price, 2) / 100) * 100;
            }

            if ($maxCashCouponBuyVolume >= 100 && $isMonthly != 'Y' && $isMonthly != 'N' && $isValid['buy'] == true) {
                $buyCapital = $isValid['buyCapital'];
                $buyType = 'cash_coupon_buy';
            } else {
                $isMonthly = $isMonthly == 'Y';
                // 计算可用于买入股票的资金（操盘可买 - 管理费）
                $buyCapital = $strategyBalance <= 0 ? 0 : Calc::calcBuyCapital($strategyBalance, $frozen, $isMonthly);
            }
        }

        return $this->message(1, '', ['buy_capital' => $buyCapital, 'buy_type' => $buyType]);
    }

    /**
     * 是否显示代金券购买
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function show_cash_coupon()
    {
        $cashCoupon = isBuyCashCoupon($this->userId);
        $isShow = false;

        if ($cashCoupon['buyCapital']) {
            $isShow = true;
        }

        return $isShow ? $this->message(1, '') : $this->message(0, '');
    }

    /**
     * 获取钱包余额接口
     *
     * @return \think\response\Json
     */
    public function wallet_balance()
    {
        $balance = UserAccount::where('user_id', $this->userId)->value('wallet_balance', 0);
        $balance = $balance ?: 0;

        return $this->message(1, '', ['wallet_balance' => $balance]);
    }

    /**
     * 获取策略金余额接口
     *
     * @return \think\response\Json
     */
    public function strategy_balance()
    {
        $balance = UserAccount::where('user_id', $this->userId)->value('strategy_balance', 0);
        $balance = $balance ?: 0;

        return $this->message(1, '', ['strategy_balance' => $balance]);
    }

    /**
     * 用户充值记录
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function recharge_history()
    {
        $list = UserRecharge::where('user_id', $this->userId)
            ->field('id,money,pay_state,pay_time,create_time')
            ->order('id', 'DESC')
            ->paginate();

        // 处理返回数据
        foreach ($list as $key => $item) {
            $list[$key]['id'] = $item['id'];
            $list[$key]['time'] = $item['pay_time'] == '' ? $item['create_time'] : date('Y-m-d H:i:s', $item['pay_time']);
            $list[$key]['money'] = $item['money'];
            $list[$key]['pay_state'] = BasicData::RECHARGE_PAY_STATE_LIST[$item['pay_state']];
        }

        return $this->message(1, '', $list);
    }

    /**
     * 提交提现申请
     * -- 钱包扣钱
     * -- 写入钱包变动日志
     * -- 写入提现申请记录，并记录【对应钱包流水号】
     * -- 注意：提现失败后，扣除的钱需要返回
     *
     * @return \think\response\Json
     */
    public function withdraw()
    {
        //判断是交易时间
        $nowHi = date('Hi');
        if ($nowHi < 930 || $nowHi > 1500) return $this->message(0, '提现时间为9:30 ~ 15:00！');

        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return $this->message(0, '当前日期为非交易日');

        //检测用户是否绑定银行卡
        $card = UserBankCard::where('user_id', $this->userId)->value('id');
        if (!$card) {
            return $this->message(0, '提现申请失败，请您先绑定银行卡再申请提现！');
        }
        // 接收并验证数据
        $money = input('post.money', 0, 'filter_float');
        $result = $this->validate(['money' => $money], 'UserAccount.Withdraw');
        if ($result !== true) return $this->message(0, $result);

        Db::startTrans();
        try {
            // 用户账户
            $account = UserAccount::where('user_id', $this->userId)->field('wallet_balance,total_withdraw')->find();
            if (!$account) return $this->message(0, '非法操作');

            // 钱包余额
            $beforeWallet = $account['wallet_balance'];
            
            // 单笔最低100，最高不能超过5万
            if ($money < 100) return $this->message(0, '单笔提现最低100元');
            if ($money > 500000) return $this->message(0, '单笔提现最高100万元');

            // 账户资金是否充足
            if ($money > $beforeWallet) return $this->message(0, '余额不足');

            // 账户：钱包扣钱
            $account['wallet_balance'] = Db::raw("wallet_balance-{$money}");
            // 保存账户
            $uaRet = $account->save();

            // 写入钱包变动日志（钱包提现）
            $wLogID = AccountLog::walletWithdraw($this->userId, $money, $beforeWallet);

            // 扣除提现手续费
            $applyMoney = $money;
            $serviceFee = 0;
            $money = bcsub($applyMoney, $serviceFee, 2);

            // 写入提现申请
            $wRet = UserWithdraw::create([
                'user_id' => $this->userId,
                'state' => USER_WITHDRAW_WAITING,
                'apply_log_id' => $wLogID,
                'money' => $money,
                'apply_time' => time(),
                'apply_money' => $applyMoney,
                'service_fee' => $serviceFee,
            ]);

            if ($uaRet && $wLogID && $wRet) {
                Db::commit();
                // 缓存提现提醒标识
                SysWsRedis::cacheWithdrawPrompt();

                return $this->message(1, '提现申请已提交');
            } else {
                Db::rollback();

                return $this->message(0, '提现申请失败，请稍后再试');
            }
        } catch (\Exception $e) {
            Db::rollback();

            return $this->message(0, '提现申请失败，请稍后再试');
        }
    }

    /**
     * 用户提现记录
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function withdraw_history()
    {
        $list = UserWithdraw::where('user_id', $this->userId)
            ->where('is_delete', false)
            ->field('id,money,state,apply_time,operation_time')
            ->order('id', 'DESC')
            ->paginate();

        // 处理返回数据
        foreach ($list as &$item) {
            // 变动类型
            $item['state'] = BasicData::USER_WITHDRAW_STATE_LIST[$item['state']];
            // 申请时间
            $item['apply_time'] = date('Y-m-d H:i:s', $item['apply_time']);
            // 操作时间
            $item['operation_time'] = $item['operation_time'] ? date('Y-m-d H:i:s', $item['operation_time']) : '';
        }

        return $this->message(1, '', $list);
    }

    /**
     * 策略金流水明细
     *
     * @throws \think\exception\DbException
     */
    public function strategy_history()
    {
        $list = UserStrategyLog::where('user_id', $this->userId)
            ->where('change_type', 'not null')
            ->field('id,market,stock_code,order_position_id,change_time,change_type,change_money')
            ->order('id', 'DESC')
            ->paginate();

        // 处理返回数据
        foreach ($list as $key => $item) {
            // 股票名称
            $list[$key]['stock_name'] = '';
            if ($item['stock_code'] != '' && $item['market'] != '') {
                $basicData = RedisUtil::getStockData($item['stock_code'], $item['market']);
                $list[$key]['stock_name'] = $basicData['stock_name'];
            }

            // 变动类型
            $list[$key]['change_type'] = BasicData::USER_STRATEGY_CHANGE_TYPE_LIST[$item['change_type']];
        }

        return $this->message(1, '', $list);
    }

    /**
     * 策略金详情
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function strategy_detail()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        $detail = UserStrategyLog::where('id', $id)->field([
            'id',
            'market',
            'stock_code',
            'order_id',
            'order_position_id',
            'order_traded_id',
            'change_time',
            'change_type',
            'change_money',
            'before_balance',
            'after_balance',
            'remark',
        ])->find();

        if ($detail) {
            // 变动类型
            $detail['change_type'] = BasicData::USER_STRATEGY_CHANGE_TYPE_LIST[$detail['change_type']];
            // 证券市场类型
            $detail['security_type'] = BasicData::marketToSecurityType($detail['market']);
            // 股票名称
            $detail['stock_name'] = '';
            if ($detail['stock_code'] != '' && $detail['market'] != '') {
                $basicData = RedisUtil::getStockData($detail['stock_code'], $detail['market']);
                $detail['stock_name'] = $basicData['stock_name'];
            }
        }

        return $this->message(1, '', $detail);
    }

    /**
     * 钱包资金流水明细
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function wallet_history()
    {
        $list = UserWalletLog::where('user_id', $this->userId)
            ->where('change_type', 'not null')
            ->field('change_money,change_type,change_time')
            ->order('id', 'DESC')
            ->paginate();

        // 处理返回数据
        foreach ($list as $key => $item) {
            $list[$key]['change_type'] = BasicData::USER_WALLET_CHANGE_TYPE_LIST[$item['change_type']];
        }

        return $this->message(1, '', $list);
    }

    /**
     * 钱包转入策略金
     *
     * @return \think\response\Json
     */
    public function addStrategy()
    {
        // 变动资金
        $money = input('post.money', 0, 'filter_float');

        if ($money <= 0) return $this->message(0, '金额必须大于0');

        Db::startTrans();
        try {
            // 用户账户
            $userAccount = UserAccount::where('user_id', $this->userId)->field('wallet_balance,strategy_balance')->find();

            // 账户（钱包）余额变动前
            $beforeWallet = $userAccount['wallet_balance'];
            // 策略金变动前
            $beforeStrategy = $userAccount['strategy_balance'];

            if ($money > $userAccount['wallet_balance']) {
                Db::rollback();

                return $this->message(0, '钱包余额不足，请充值');
            }

            // 写入策略金变动日志（转入，增加）
            $sLogRet = AccountLog::strategyFromWallet($this->userId, $money, $beforeStrategy);

            // 写入钱包变动日志（转出，减少）
            $wLogRet = AccountLog::walletToStrategy($this->userId, $money, $beforeWallet);

            // 增加策略金余额，减少钱包余额
            $userAccount['wallet_balance'] = Db::raw("wallet_balance-{$money}");
            $userAccount['strategy_balance'] = Db::raw("strategy_balance+{$money}");
            // 保存账户信息
            $uRet = $userAccount->save();

            if ($sLogRet && $wLogRet && $uRet) {
                Db::commit();

                // 更新用户的策略金缓存（不含冻结资金）
                OrderRedis::cacheUserStrategy($this->userId);

                return $this->message(1, '策略金转入成功');
            } else {
                Db::rollback();

                return $this->message(0, '策略金转入失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            return $this->message(0, '策略金转入失败');
        }
    }

    /**
     * 策略金转出到钱包（账户资金）
     * -- 减少 策略金
     * -- 增加 钱包余额
     *
     * @return \think\response\Json
     */
    public function subStrategy()
    {
        // 变动资金
        $money = input('post.money', 0, 'filter_float');

        if ($money <= 0) return $this->message(0, '转出金额必须大于0');


        Db::startTrans();
        try {
            // 用户账户
            $userAccount = UserAccount::where('user_id', $this->userId)->field('wallet_balance,strategy_balance')->find();

            // 账户（钱包）余额变动前
            $beforeWallet = $userAccount['wallet_balance'];
            // 策略金变动前
            $beforeStrategy = $userAccount['strategy_balance'];

            if ($money > $userAccount['strategy_balance']) {
                Db::rollback();

                return $this->message(0, '转出金额不能大于策略金余额');
            }

            // 写入钱包变动日志（转入，增加）
            $wLogRet = AccountLog::walletFromStrategy($this->userId, $money, $beforeWallet);

            // 写入策略金变动日志（转出，减少）
            $sLogRet = AccountLog::strategyToWallet($this->userId, $money, $beforeStrategy);

            // 增加策略金余额，减少钱包余额
            $userAccount['wallet_balance'] = Db::raw("wallet_balance+{$money}");
            $userAccount['strategy_balance'] = Db::raw("strategy_balance-{$money}");
            // 保存账户信息
            $uRet = $userAccount->save();

            if ($sLogRet && $wLogRet && $uRet) {
                Db::commit();

                // 更新用户的策略金缓存（不含冻结资金）
                OrderRedis::cacheUserStrategy($this->userId);

                return $this->message(1, '策略金转出成功');
            } else {
                Db::rollback();

                return $this->message(0, '策略金转出失败');
            }
        } catch (\Exception $e) {
            Db::rollback();

            return $this->message(0, '策略金转出失败');
        }
    }

    /**
     * 线下充值 - 转入账号列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offline_account()
    {
        // 支付通道（路由绑定的参数，无需客户端传值）
        $channel = input('channel', '', FILTER_SANITIZE_STRING);
        $channel = in_array($channel, ['offline_bank', 'offline_alipay', 'offline_wechatpay', 'offline_public']) ? $channel : '';
        if($channel == '') return $this->message(0, '转账方式错误');
        //print_r($channel);exit;
        $account = PayCompany::field('id AS pay_company_id,to_name, to_org_name, to_branch, to_account, to_qrcode, explain')
            ->where('pay_channel', $channel)
            ->where('is_open', true)
            ->order('id', 'ASC')
            ->select();
        if(empty($account)) return $this->message(0, '未获取到充值方式');   
        //$json = json_decode($account,true);
        //print_r($json[0]['to_qrcode']);exit;
        $account[0]['to_qrcode'] = $account[0]['to_qrcode']?'http://'.$_SERVER['HTTP_HOST'].$account[0]['to_qrcode']:'';
        
        return $this->message(1, '', $account);
    }

    /**
     * 充值 - 线下银行转账
     */
    public function offline_recharge()
    {
        $data['pay_company_id'] = input('post.pay_company_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['money'] = input('post.money', 0, 'filter_float');
        $data['offline_name'] = input('post.offline_name', '', FILTER_SANITIZE_STRING);
        $data['transfer_account'] = input('transfer_account', '', FILTER_SANITIZE_STRING);
        $data['user_id'] = $this->userId;
        $data['pay_state'] = RECHARGE_PAY_WAIT;

        // 验证数据
        $result = $this->validate($data, 'Recharge.Offline');
        if ($result !== true) return $this->message(0, $result);

        // 获取转账方式对应的账号信息
        $toAccount = PayCompany::where('id', $data['pay_company_id'])->field('to_name, to_org_name, to_account')->find();
        if (!$toAccount) return $this->message(0, '转账方式错误');

        // 记录转账方式信息
        $data['offline_to_account'] = $toAccount['to_name'] . '<br>' . $toAccount['to_org_name'] . '<br>' . $toAccount['to_account'];

        // // 上传图片（Base64方式）
        // $base64Img = input('post.file');
        // $upInfo = saveBase64Img($base64Img, 'offline_recharge');
        // if ($upInfo['code'] == 0) {
        //     return $this->message(0, $upInfo['msg']);
        // }
        // $data['offline_img'] = $upInfo['path'];

        // 写入记录
        $uRecharge = UserRecharge::create($data);
        // 缓存充值提醒标识
        SysWsRedis::cacheRechargePrompt();

        return $uRecharge ? $this->message(1, '提交成功') : $this->message(0, '提交失败');
    }

}
