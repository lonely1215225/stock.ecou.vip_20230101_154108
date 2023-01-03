<?php
namespace util;

class BasicData
{

    // 股票风险等级列表
    const STOCK_RISK_LIST = [
        STOCK_RISK_LOW    => '低',
        STOCK_RISK_MIDDLE => '中',
        STOCK_RISK_HIGH   => '高',
    ];

    // 证券公司列表
    const MARKET_LIST = [
        MARKET_SH => '上证',
        MARKET_SZ => '深证',
        MARKET_BJ => '北证',
        MARKET_KC => '科创',
    ];

    // 证券公司列表
    const MARKET_SIGN_LIST = [
        MARKET_SH => '上证',
        MARKET_SZ => '深证',
        MARKET_BJ => '北证',
        MARKET_KC => '科创',
    ];

    // 交易方向
    const TRADE_DIRECTION_LIST = [
        TRADE_DIRECTION_BUY  => '买入',
        TRADE_DIRECTION_SELL => '卖出',
    ];

    // 委托价格类型
    const PRICE_TYPE_LIST = [
        PRICE_TYPE_LIMIT  => '限价',
        PRICE_TYPE_MARKET => '市价',
    ];

    // 委托单状态列表(委托状态及成交状态)
    const ORDER_STATE_LIST = [
        ORDER_WAITING     => '委托中',
        ORDER_SUBMITTED   => '委托成功',
        ORDER_PART_TRADED => '部分成交',
        ORDER_ALL_TRADED  => '全部成交',
        ORDER_INVALID     => '废单',
    ];

    // 撤单状态列表
    const CANCEL_STATE_LIST = [
        CANCEL_NONE      => '可撤',
        CANCEL_SUBMITTED => '已提交撤单',
        CANCEL_SUCCESS   => '撤单成功',
        CANCEL_FAILED    => '撤单失败',
    ];

    // 撤单类型列表
    const CANCEL_TYPE_LIST = [
        CANCEL_TYPE_NONE  => '无',
        CANCEL_TYPE_USER  => '用户撤单',
        CANCEL_TYPE_AUTO  => '系统撤单',
        CANCEL_TYPE_CLOSE => '休市撤单',
    ];

    // 代理商、经纪人账户变动类型列表
    const ORG_ACCOUNT_CHANGE_TYPE_LIST = [
        ORG_ACCOUNT_MANAGEMENT      => '佣金',
        ORG_ACCOUNT_WITHDRAW        => '提现',
        ORG_ACCOUNT_WITHDRAW_FAILED => '提现失败',
    ];

    // 代理商、经纪人申请提现状态
    const ORG_WITHDRAW_STATE_LIST = [
        ORG_WITHDRAW_WAITING       => '代理商未审核',
        ORG_WITHDRAW_AGENT_CHECKED => '代理商已审核',
        ORG_WITHDRAW_ADMIN_CHECKED => '提现中',
        ORG_WITHDRAW_PAYING        => '提现中',
        ORG_WITHDRAW_FINISHED      => '提现成功',
        ORG_WITHDRAW_FAILED        => '提现失败',
        ORG_WITHDRAW_AGENT_REFUSED => '代理商拒绝',
        ORG_WITHDRAW_ADMIN_REFUSED => '管理员拒绝',
    ];

    // 用户申请提现状态列表
    const USER_WITHDRAW_STATE_LIST = [
        USER_WITHDRAW_WAITING       => '待审核',
        USER_WITHDRAW_CHECKED       => '提现中',
        USER_WITHDRAW_PAYING        => '提现中',
        USER_WITHDRAW_SUCCESS       => '提现成功',
        USER_WITHDRAW_FAILED        => '提现失败',
        USER_WITHDRAW_ADMIN_REFUSED => '管理员拒绝',
    ];

    // 用户钱包资金变动类型列表
    const USER_WALLET_CHANGE_TYPE_LIST = [
        USER_WALLET_RECHARGE        => '充值',
        USER_WALLET_TO_STRATEGY     => '转出到策略金',
        USER_WALLET_FROM_STRATEGY   => '策略金转入',
        USER_WALLET_ADMIN           => '管理员调整',
        USER_WALLET_WITHDRAW        => '提现',
        USER_WALLET_WITHDRAW_FAILED => '提现失败',
        USER_WALLET_SYSTEM_IN       => '转入',
        USER_WALLET_SYSTEM_OUT      => '转出',
        USER_WALLET_COMMISSION      => '返佣',
        USER_WALLET_YUEBAO          => '收益宝',
    ];

    // 用户策略金变动类型列表
    const USER_STRATEGY_CHANGE_TYPE_LIST = [
        USER_STRATEGY_BUY               => '买入保证金',
        USER_STRATEGY_SELL              => '卖出股票结算',
        USER_CASH_COUPON_SELL           => '代金券卖出结算',
        USER_STRATEGY_FROM_WALLET       => '账户资金转入',
        USER_STRATEGY_TO_WALLET         => '转出到账户资金',
        USER_STRATEGY_MANAGEMENT_FEE    => '收取管理费',
        USER_STRATEGY_ADD_DEPOSIT       => '追加保证金',
        USER_STRATEGY_SETTLEMENT        => '每日结算盈利',
        USER_STRATEGY_SUSPENDED_DEPOSIT => '追加停牌保证金',
        USER_STRATEGY_EX_DIVIDEND       => '送股利金',
        USER_STRATEGY_REFUND            => '系统赠金',
        USER_STRATEGY_MONTHLY_M_FEE     => '月管理费',
        USER_STRATEGY_SUBTRACT          => '系统追加扣费'
    ];

    // 用户冻结资金变动类型
    const USER_FROZEN_CHANGE_TYPE_LIST = [
        USER_FROZEN_BUY    => '委托买入冻结',
        USER_FROZEN_FAILED => '委托失败解冻',
        USER_FROZEN_TRADED => '买入成交解冻',
        USER_FROZEN_CANCEL => '撤单解冻',
    ];

    // 充值支付状态列表
    const RECHARGE_PAY_STATE_LIST = [
        RECHARGE_PAY_WAIT    => '未支付',
        RECHARGE_PAY_SUCCESS => '支付成功',
        RECHARGE_PAY_MANUAL  => '手动入账',
        RECHARGE_PAY_OFFLINE => '线下银行转账',
        RECHARGE_PAY_FAILED  => '支付失败',
    ];

    // 代理商、经纪人佣金类型
    const ORG_INCOME_TYPE_LIST = [
        ORG_INCOME_BUY      => '买入管理费',
        ORG_INCOME_POSITION => '过夜管理费',
    ];

    // 平台收入类型
    const ADMIN_INCOME_TYPE_LIST = [
        ORG_INCOME_BUY          => '买入管理费',
        ORG_INCOME_POSITION     => '持仓管理费',
        ORG_INCOME_SERVICE_BUY  => '买入手续费',
        ORG_INCOME_SERVICE_SELL => '卖出手续费',
        ORG_INCOME_MONTHLY_BUY  => '买入月管理费',
    ];

    // 银行卡状态
    const BANK_CARD_STATE_LIST = [
        BANK_CARD_BIND   => '已绑定',
        BANK_CARD_UNBIND => '未绑定',
    ];

    // 强平触发类型列表
    const FORCED_SELL_TYPE_LIST = [
        FORCED_SELL_TYPE_QUOTATION => '不足追加保证金',
        FORCED_SELL_TYPE_REALTIME  => '实时检测',
        FORCED_SELL_TYPE_MONTHLY   => '月费到期强平',
        FORCED_SELL_TYPE_HAND      => '手动强平',
        FORCED_SELL_TYPE_CASH_COUPON => '代金券到期强平'
    ];

    // 强平平仓顺序列表
    const FORCED_SELL_ORDER_LIST = [
        FORCED_SELL_ORDER_SELF     => '本持仓',
        FORCED_SELL_ORDER_IN_ORDER => '顺序平仓',
    ];

    // 条件单状态列表
    const CONDITION_STATE_LIST = [
        CONDITION_STATE_NONE   => '未运行',
        CONDITION_STATE_ING    => '未触发',
        CONDITION_STATE_END    => '已触发',
        CONDITION_STATE_EXPIRE => '过期',
    ];

    // 条件单触发价比较条件
    const CONDITION_COMPARE_LIST = [
        CONDITION_COMPARE_EGT => '>=',
        CONDITION_COMPARE_ELT => '<=',
    ];

    // 支付方式
    const PAYMENT_WAY_LIST = [
        PAYMENT_WAY_ONLINE    => '线上转账',
        PAYMENT_WAY_BANK      => '银行转账',
        PAYMENT_WAY_ALIPAY    => '支付宝',
        PAYMENT_WAY_WECHATPAY => '微信',
        PAYMENT_WAY_PUBLIC    => '对公转账',
    ];

    /**
     * 上游证券类型 转 系统证券类型
     *
     * @param int $securityType 上游证券类型
     *
     * @return string
     */
    public static function securityTypeToMarket($securityType)
    {
        return str_replace([0, 1, 2, 3], [MARKET_SZ, MARKET_SH, MARKET_KC, MARKET_BJ], $securityType);
    }

    /**
     * 系统证券类型 转 上游证券类型
     *
     * @param $market
     *
     * @return mixed
     */
    public static function marketToSecurityType($market)
    {
        return str_replace([MARKET_SZ, MARKET_SH, MARKET_KC, MARKET_BJ], [0, 1, 2, 3], $market);
    }

}
