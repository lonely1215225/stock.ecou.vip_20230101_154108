<?php
// +----------------------------------------------------------------------
// | 常量定义表
// +----------------------------------------------------------------------

// 定义上传目录
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public/uploads');
define('WS_SERVER_IP', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
/**
 * 系统常量
 * -- TOKEN_EXPIRE_TIME           用户登陆超时时间
 * -- SEARCH_CACHE_EXPIRE_TIME    搜索缓存过期时间
 * -- POSITION_VALUE_LIMIT        仓位限制配置(max,min的值指的是 动态资产)
 * -- LOCAL_TRADING_TOKEN         系统本地客户端交易（强平、自动撤单）时须带此TOKEN
 */
define('TOKEN_EXPIRE_TIME', 60*60*24*365);//600   ------60*60*24=1天
define('SEARCH_CACHE_EXPIRE_TIME', 60*60*24);//7800
define('POSITION_VALUE_LIMIT', [
    // <= 10万，单票100%仓位
    ['min' => 0, 'max' => 100000, 'limit' => 1],
    // > 10万 <= 50万，单票限制90%仓位
    ['min' => 100000, 'max' => 500000, 'limit' => 0.9],
    // > 50万 <= 100万，单票限制80%仓位
    ['min' => 500000, 'max' => 1000000, 'limit' => 0.8],
    // > 100万 <= 300万，单票限制70%仓位
    ['min' => 1000000, 'max' => 3000000, 'limit' => 0.7],
]);
define('LOCAL_STOCK_TIMES', 3);//行情更新频率，不得低于5秒

define('LOCAL_TRADING_TOKEN', 'cRQEEFzjDXASltkjvprHiBOSiNZNtRXP');//数据请求密钥
define('LOCAL_STOCK_HOST', '154.222.31.39');
define('LOCAL_STOCK_PORT', 8080);
/**
 * 定义Redis服务器常量
 * -- REDIS_SERVER_IP        服务器IP地址
 * -- REDIS_SERVER_PASSWORD  密码
 */
define('REDIS_SERVER_IP', '127.0.0.1');
define('REDIS_SERVER_PORT', 6379);


/**
 * 定义交易方向常量
 * -- TRADE_DIRECTION_BUY   买入
 * -- TRADE_DIRECTION_SELL  卖出
 */
define('TRADE_DIRECTION_BUY', 'buy');
define('TRADE_DIRECTION_SELL', 'sell');


/**
 * 定义委托价格类型
 * -- PRICE_TYPE_LIMIT   限价
 * -- PRICE_TYPE_MARKET  市价
 */
define('PRICE_TYPE_LIMIT', 'limit_price');
define('PRICE_TYPE_MARKET', 'market_price');


/**
 * 定义委托单状态常量(委托状态及成功状态)
 * -- ORDER_WAITING        等待
 * -- ORDER_SUBMITTED      委托成功
 * -- ORDER_ALL_TRADED     全部成交
 * -- ORDER_PART_TRADED    部分成交
 * -- ORDER_INVALID        废单
 */
define('ORDER_WAITING', 'waiting');
define('ORDER_SUBMITTED', 'submitted');
define('ORDER_PART_TRADED', 'part_traded');
define('ORDER_ALL_TRADED', 'all_traded');
define('ORDER_INVALID', 'invalid');


/**
 * 定义委托单的撤单状态常量
 * -- CANCEL_NONE       可撤
 * -- CANCEL_SUBMITTED  已提交撤单
 * -- CANCEL_SUCCESS    撤单成功
 * -- CANCEL_FAILED     撤单失败
 */
define('CANCEL_NONE', 'none');
define('CANCEL_SUBMITTED', 'submitted');
define('CANCEL_SUCCESS', 'success');
define('CANCEL_FAILED', 'failed');


/**
 * 撤单类型常量
 * -- CANCEL_TYPE_NONE  无（默认）
 * -- CANCEL_TYPE_USER  用户撤单
 * -- CANCEL_TYPE_AUTO  系统撤单
 * -- CANCEL_TYPE_CLOSE 休市撤单
 */
define('CANCEL_TYPE_NONE', 'none');
define('CANCEL_TYPE_USER', 'user');
define('CANCEL_TYPE_AUTO', 'auto');
define('CANCEL_TYPE_CLOSE', 'close');


/**
 * 定义后台用户角色常量
 *  - ADMIN_ROLE_SUPER      超级管理员
 *  - ADMIN_ROLE_ADMIN      管理员（用于新开平台，暂时不是用）
 *  - ADMIN_ROLE_SUB_ADMIN  子管理员（分权限管理，暂不使用）
 *  - ADMIN_ROLE_AGENT      代理商
 *  - ADMIN_ROLE_BROKER     经纪人
 */
define('ADMIN_ROLE_SUPER', 'super');
define('ADMIN_ROLE_ADMIN', 'admin');
define('ADMIN_ROLE_SUB_ADMIN', 'sub_admin');
define('ADMIN_ROLE_AGENT', 'agent');
define('ADMIN_ROLE_BROKER', 'broker');


/**
 * 定义证券公司常量
 *  - MARKET_SH  上证
 *  - MARKET_SZ  深证
 *  - MARKET_BJ  北证
 *  - MARKET_KC  上证科创板
 */
define('MARKET_SH', 'SH');
define('MARKET_SZ', 'SZ');
define('MARKET_BJ', 'BJ');
define('MARKET_KC', 'KC');

/**
 * 定义股票风险等级常量
 *  - STOP_RISK_LOW     股票风险等级低
 *  - STOP_RISK_MIDDLE  股票风险等级中
 *  - STOP_RISK_HIGH    股票风险等级高
 */
define('STOCK_RISK_LOW', 1);
define('STOCK_RISK_MIDDLE', 2);
define('STOCK_RISK_HIGH', 3);


/**
 * 定义代理商、经纪人账户的变动类型常量
 * -- ORG_ACCOUNT_MANAGEMENT      佣金
 * -- ORG_ACCOUNT_WITHDRAW        提现
 * -- ORG_ACCOUNT_WITHDRAW_FAILED 提现失败
 */
define('ORG_ACCOUNT_MANAGEMENT', 'management');
define('ORG_ACCOUNT_WITHDRAW', 'withdraw');
define('ORG_ACCOUNT_WITHDRAW_FAILED', 'withdraw_failed');


/**
 * 定义代理商、经纪人提现状态常量
 * -- ORG_WITHDRAW_WAITING        待审核
 * -- ORG_WITHDRAW_AGENT_CHECKED  代理商已审核
 * -- ORG_WITHDRAW_ADMIN_CHECKED  管理员已审核
 * -- ORG_WITHDRAW_PAYING         待付中
 * -- ORG_WITHDRAW_SUCCESS        提现成功
 * -- ORG_WITHDRAW_FAILED         提现失败
 * -- RG_WITHDRAW_AGENT_REFUSED   代理商拒绝
 * -- ORG_WITHDRAW_ADMIN_REFUSED  管理员拒绝
 */
define('ORG_WITHDRAW_WAITING', 'waiting');
define('ORG_WITHDRAW_AGENT_CHECKED', 'agent_checked');
define('ORG_WITHDRAW_ADMIN_CHECKED', 'admin_checked');
define('ORG_WITHDRAW_PAYING', 'paying');
define('ORG_WITHDRAW_FINISHED', 'success');
define('ORG_WITHDRAW_FAILED', 'failed');
define('ORG_WITHDRAW_AGENT_REFUSED', 'agent_refused');
define('ORG_WITHDRAW_ADMIN_REFUSED', 'admin_refused');


/**
 * 定义用户（股民）提现申请常量
 * -- USER_WITHDRAW_WAITING  待审核
 * -- USER_WITHDRAW_CHECKED  已审核
 * -- USER_WITHDRAW_PAYING   待付中
 * -- USER_WITHDRAW_SUCCESS  提现成功
 * -- USER_WITHDRAW_FAILED   提现失败
 * -- USER_WITHDRAW_REFUSED  管理员拒绝
 */
define('USER_WITHDRAW_WAITING', 'waiting');
define('USER_WITHDRAW_CHECKED', 'checked');
define('USER_WITHDRAW_PAYING', 'paying');
define('USER_WITHDRAW_SUCCESS', 'success');
define('USER_WITHDRAW_FAILED', 'failed');
define('USER_WITHDRAW_ADMIN_REFUSED', 'admin_refused');


/**
 * 用户钱包资金变动类型常量
 * -- USER_WALLET_CHANGE_TYPE_RECHARGE       充值
 * -- USER_WALLET_CHANGE_TYPE_WITHDRAW       提现
 * -- USER_WALLET_CHANGE_TYPE_TO_STRATEGY    转出到策略金
 * -- USER_WALLET_CHANGE_TYPE_FROM_STRATEGY  策略金转入
 * -- USER_WALLET_ADMIN                      管理员调整（已弃用）
 * -- USER_WALLET_WITHDRAW_FAILED            提现失败
 * -- USER_WALLET_SYSTEM_IN                  第三方转入（管理员增加）
 * -- USER_WALLET_SYSTEM_OUT                 第三方转出（管理员减少）
 * -- USER_WALLET_COMMISSION
 * -- USER_WALLET_YUEBAO                     账户收益宝
 */
define('USER_WALLET_RECHARGE', 'recharge');
define('USER_WALLET_TO_STRATEGY', 'to_strategy');
define('USER_WALLET_FROM_STRATEGY', 'from_strategy');
define('USER_WALLET_ADMIN', 'admin');
define('USER_WALLET_WITHDRAW', 'withdraw');
define('USER_WALLET_WITHDRAW_FAILED', 'withdraw_failed');
define('USER_WALLET_SYSTEM_IN', 'system_in');
define('USER_WALLET_SYSTEM_OUT', 'system_out');
define('USER_WALLET_COMMISSION', 'commission');
define('USER_WALLET_YUEBAO', 'yuebao');


/**
 * 用户策略金变动类型常量
 * -- USER_STRATEGY_BUY                买入股票（暂时无用）
 * -- USER_STRATEGY_SELL               卖出股票（卖出结算入账）
 * -- USER_CASH_COUPON_SELL            卖出股票（代金券结算入账）
 * -- USER_STRATEGY_FROM_WALLET        钱包转入
 * -- USER_STRATEGY_TO_WALLET          转出到钱包
 * -- USER_STRATEGY_MANAGEMENT_FEE     收取管理费
 * -- USER_STRATEGY_ADD_DEPOSIT        追加保证金
 * -- USER_STRATEGY_SETTLEMENT         结算盈亏
 * -- USER_STRATEGY_SUSPENDED_DEPOSIT  追加停牌保证金
 * -- USER_STRATEGY_EX_DIVIDEND        送股利金
 * -- USER_STRATEGY_REFUND             系统退款
 * -- USER_STRATEGY_MONTHLY_M_FEE      月管理费
 */
define('USER_STRATEGY_BUY', 'buy');
define('USER_STRATEGY_SELL', 'sell');
define('USER_CASH_COUPON_SELL', 'cash_coupon_sell');
define('USER_STRATEGY_FROM_WALLET', 'from_wallet');
define('USER_STRATEGY_TO_WALLET', 'to_wallet');
define('USER_STRATEGY_MANAGEMENT_FEE', 'management_fee');
define('USER_STRATEGY_ADD_DEPOSIT', 'add_deposit');
define('USER_STRATEGY_SETTLEMENT', 'settlement');
define('USER_STRATEGY_SUSPENDED_DEPOSIT', 'suspended_deposit');
define('USER_STRATEGY_EX_DIVIDEND', 'ex_dividend');
define('USER_STRATEGY_REFUND', 'refund');
define('USER_STRATEGY_SUBTRACT', 'system_subtract');
define('USER_STRATEGY_MONTHLY_M_FEE', 'monthly_m_fee');

/**
 * 用户冻结资金变动类型常量定义
 * -- FROZEN_BUY        委托买入冻结（增加冻结）
 * -- FROZEN_BUY        委托失败解冻（减少冻结）
 * -- FROZEN_TRADED     买入成交解冻（减少冻结）
 * -- FROZEN_CANCEL     撤单解冻（增加冻结）
 * -- USER_FROZEN_RESET 清零（每日休市后）
 */
define('USER_FROZEN_BUY', 'buy');
define('USER_FROZEN_FAILED', 'failed');
define('USER_FROZEN_TRADED', 'traded');
define('USER_FROZEN_CANCEL', 'cancel');
define('USER_FROZEN_RESET', 'reset');


/**
 * 充值支付状态常量
 * -- RECHARGE_PAY_WAIT     待支付
 * -- RECHARGE_PAY_SUCCESS  支付成功
 * -- RECHARGE_PAY_MANUAL   手动入账
 * -- RECHARGE_PAY_FAILED   支付失败
 */
define('RECHARGE_PAY_WAIT', 'wait');
define('RECHARGE_PAY_SUCCESS', 'success');
define('RECHARGE_PAY_MANUAL', 'manual');
define('RECHARGE_PAY_OFFLINE', 'offline');
define('RECHARGE_PAY_FAILED', 'failed');


/**
 * 平台收入、代理商、经纪人  收入（佣金）类型  常量
 * -- ORG_INCOME_BUY          买入佣金（管理员：买入管理费）
 * -- ORG_INCOME_POSITION     过夜费佣金（管理员：持仓管理费）
 * -- ORG_INCOME_SERVICE_BUY  买入手续费
 * -- ORG_INCOME_SERVICE_SELL 卖出手续费
 * -- ORG_INCOME_MONTHLY_BUY  买入佣金(月管理费)
 */
define('ORG_INCOME_BUY', 'management_buy');
define('ORG_INCOME_POSITION', 'management_position');
define('ORG_INCOME_SERVICE_BUY', 'service_fee_buy');
define('ORG_INCOME_SERVICE_SELL', 'service_fee_sell');
define('ORG_INCOME_MONTHLY_BUY', 'monthly_m_buy');

/**
 * 银行卡状态
 * -- BANK_CARD_BIND    已绑定
 * -- BANK_CARD_UNBIND  未绑定
 */
define('BANK_CARD_BIND', 'bind');
define('BANK_CARD_UNBIND', 'unbind');


/**
 * 强平触发类型常量
 * -- FORCED_SELL_TYPE_QUOTATION  不足追加保证金
 * -- FORCED_SELL_TYPE_REALTIME   实时检测
 * -- FORCED_SELL_TYPE_MONTHLY    月费到期强平
 * -- FORCED_SELL_TYPE_HAND       手动强平
 * -- FORCED_SELL_TYPE_CASH_COUPON 代金券到期平仓
 */
define('FORCED_SELL_TYPE_QUOTATION', 'quotation');
define('FORCED_SELL_TYPE_REALTIME', 'realtime');
define('FORCED_SELL_TYPE_MONTHLY', 'monthly');
define('FORCED_SELL_TYPE_HAND', 'hand');
define('FORCED_SELL_TYPE_CASH_COUPON', 'cash_coupon');


/**
 * 强平平仓顺序常量
 * -- FORCED_SELL_ORDER_SELF      本持仓
 * -- FORCED_SELL_ORDER_IN_ORDER  顺序平仓
 */
define('FORCED_SELL_ORDER_SELF', 'self');
define('FORCED_SELL_ORDER_IN_ORDER', 'in_order');


/**
 * 条件单状态
 * -- CONDITION_STATE_NONE    未运行
 * -- CONDITION_STATE_ING     未触发
 * -- CONDITION_STATE_END     已触发
 * -- CONDITION_STATE_EXPIRE  过期
 */
define('CONDITION_STATE_NONE', 'none');
define('CONDITION_STATE_ING', 'ing');
define('CONDITION_STATE_END', 'end');
define('CONDITION_STATE_EXPIRE', 'expire');


/**
 * 条件单触发价比较条件
 * -- CONDITION_COMPARE_EGT  大于等于
 * -- CONDITION_COMPARE_ELT  小于等于
 */
define('CONDITION_COMPARE_EGT', 'egt');
define('CONDITION_COMPARE_ELT', 'elt');


/**
 * 支付方式
 * -- PAYMENT_WAY_ONLINE 线上转账
 * -- PAYMENT_WAY_BANK 线下银行转账
 * -- PAYMENT_WAY_ALIPAY 支付宝
 * -- PAYMENT_WAY_WECHATPAY 微信
 */
define('PAYMENT_WAY_ONLINE', 'online');
define('PAYMENT_WAY_BANK', 'offline_bank');
define('PAYMENT_WAY_ALIPAY', 'offline_alipay');
define('PAYMENT_WAY_WECHATPAY', 'offline_wechatpay');
define('PAYMENT_WAY_PUBLIC', 'offline_public');

/**
 * 排除代理商列表
 */
define('EXCLUDE_AGENT', [72]);