<?php
// +----------------------------------------------------------------------
// | index模块路由配置
// +----------------------------------------------------------------------

use think\facade\Route;

// 注册提交接口
Route::post('register', 'index/auth/register')->completeMatch()->allowCrossDomain();
// 登陆接口
Route::post('sign_in', 'index/auth/sign_in')->completeMatch()->allowCrossDomain();
// 退出登录
Route::get('sign_out', 'index/auth/sign_out')->completeMatch()->allowCrossDomain();
// 检查是否已登录
Route::get('is_login', 'index/auth/is_login')->completeMatch()->allowCrossDomain();
// 找回密码提交
Route::post('password/reset', 'index/auth/reset_password')->completeMatch()->allowCrossDomain();
// 修改密码提交
Route::post('password/modify', 'index/auth/update_password')->completeMatch()->allowCrossDomain();


// 发送注册验证码
Route::get('sms/reg', 'index/auth/send_captcha_sms')->append(['type' => \sms\SmsUtil::CAPTCHA_REGISTER])->completeMatch()->allowCrossDomain();
// 发送忘记密码验证码
Route::get('sms/reset', 'index/auth/send_captcha_sms')->append(['type' => \sms\SmsUtil::CAPTCHA_RESET_PASSWORD])->completeMatch()->allowCrossDomain();
// 发送提现验证码
Route::get('sms/withdraw', 'index/auth/send_captcha_sms')->append(['type' => \sms\SmsUtil::CAPTCHA_WITHDRAW])->completeMatch()->allowCrossDomain();

// 今日优选
Route::get('selective', 'index/index/selective')->completeMatch()->allowCrossDomain();
// 获取用户的银行卡信息
Route::get('bank', 'index/bank_card/read')->completeMatch()->allowCrossDomain();
// 获取完整银行卡绑定信息
Route::get('bank/full', 'index/bank_card/full')->completeMatch()->allowCrossDomain();
// 保存银行卡信息接口
Route::post('bank', 'index/bank_card/save')->completeMatch()->allowCrossDomain();
// 解绑银行卡
Route::post('bank/delete', 'index/bank_card/delete')->completeMatch()->allowCrossDomain();
// 银行列表
Route::get('bank/banks', 'index/bank_card/banks')->completeMatch()->allowCrossDomain();


// JSON城市tree
Route::get('city/tree', 'index/city/tree')->completeMatch()->allowCrossDomain();
// JSON城市tree根据选中的省获得所有城市
Route::get('city/cities', 'index/city/cities')->completeMatch()->allowCrossDomain();

// 获取股票基础数据
Route::get('stock/read', 'index/stock/read')->completeMatch()->allowCrossDomain();
// 搜索股票
Route::get('stock/search', 'index/stock/search')->completeMatch()->allowCrossDomain();
// 禁售列表
Route::get('stock/black', 'index/stock/black')->completeMatch()->allowCrossDomain();
// 获取股票代码列表
Route::get('stock/list', 'index/stock/index')->completeMatch()->allowCrossDomain();
// 获取指数列表
Route::get('stock/index', 'index/stock/stock_index')->completeMatch()->allowCrossDomain();
// 设置活跃股票
Route::post('stock/active', 'index/stock/active')->completeMatch()->allowCrossDomain();
// 设置活跃股票
Route::post('stock/getactive', 'index/stock/getactive')->completeMatch()->allowCrossDomain();
// 获取科创版列表
Route::get('stock/kechuang', 'index/stock/kechuang')->completeMatch()->allowCrossDomain();

//获取个股数据
Route::post('stock/market', 'index/stock/market')->completeMatch()->allowCrossDomain();

// 获取自选列表
Route::get('favorite', 'index/favorite/index')->completeMatch()->allowCrossDomain();
// 简版：自选列表，供添加条件单使用
Route::get('favorite/simple', 'index/favorite/simple')->completeMatch()->allowCrossDomain();
// 添加自选
Route::post('favorite/add', 'index/favorite/save')->completeMatch()->allowCrossDomain();
// 删除自选
Route::post('favorite/del', 'index/favorite/delete')->completeMatch()->allowCrossDomain();
// 判断是否为自选
Route::post('favorite/verdict', 'index/favorite/verdict')->completeMatch()->allowCrossDomain();
// 用户资金余额
Route::get('account/myAccount', 'index/account/myAccount')->completeMatch()->allowCrossDomain();
// 用户资金详情（带持仓统计）
Route::get('account', 'index/account/index')->completeMatch()->allowCrossDomain();
// 检测是否有可用代金券
Route::get('showCashCoupon', 'index/account/show_cash_coupon')->completeMatch()->allowCrossDomain();
// 实际可买资金
Route::get('account/buy_capital', 'index/account/buy_capital')->completeMatch()->allowCrossDomain();
// 用户钱包余额
Route::get('account/wallet/balance', 'index/account/wallet_balance')->completeMatch()->allowCrossDomain();
// 用户策略金余额
Route::get('account/strategy/balance', 'index/account/strategy_balance')->completeMatch()->allowCrossDomain();
// 用户充值记录
Route::get('account/recharge/history', 'index/account/recharge_history')->completeMatch()->allowCrossDomain();
// 提交用户的提现申请
Route::post('account/withdraw', 'index/account/withdraw')->completeMatch()->allowCrossDomain();
// 用户提现记录
Route::get('account/withdraw/history', 'index/account/withdraw_history')->completeMatch()->allowCrossDomain();
// 策略金明细
Route::get('account/strategy/history', 'index/account/strategy_history')->completeMatch()->allowCrossDomain();
// 策略金详情
Route::get('account/strategy/detail', 'index/account/strategy_detail')->completeMatch()->allowCrossDomain();
// 钱包明细
Route::get('account/wallet/history', 'index/account/wallet_history')->completeMatch()->allowCrossDomain();
// 钱包转入策略金
Route::post('account/strategy/recharge', 'index/account/addStrategy')->completeMatch()->allowCrossDomain();
// 策略金转入钱包
Route::post('account/strategy/withdraw', 'index/account/subStrategy')->completeMatch()->allowCrossDomain();

//首页轮播
Route::get('slide/show', 'index/index/slideshow')->completeMatch()->allowCrossDomain();
//热门行业
Route::get('hotIndustry/show', 'index/index/sinahy')->completeMatch()->allowCrossDomain();
//热门行业
Route::get('getNodeclass/show', 'index/index/sinaNodeclass')->completeMatch()->allowCrossDomain();
// 公司公告
Route::get('article/notice', 'index/news/notice')->completeMatch()->allowCrossDomain();
// 交易提示
Route::get('article/trading_tips', 'index/news/index')->append(['cat_id' => 1])->completeMatch()->allowCrossDomain();
// 常见问题
Route::get('article/faq', 'index/news/index')->append(['cat_id' => 2])->completeMatch()->allowCrossDomain();
// 新手指引
Route::get('article/guidelines', 'index/news/index')->append(['cat_id' => 3])->completeMatch()->allowCrossDomain();
// 法律声明
Route::get('article/falv', 'index/news/read')->append(['id' => 8])->completeMatch()->allowCrossDomain();
// 交易规则
Route::get('article/guize', 'index/news/read')->append(['id' => 4])->completeMatch()->allowCrossDomain();
// 风险告知
Route::get('article/gaozhi', 'index/news/read')->append(['id' => 5])->completeMatch()->allowCrossDomain();
// 媒体报道
Route::get('article/media_report', 'index/news/index')->append(['cat_id' => 4])->completeMatch()->allowCrossDomain();
// 文章内容(参数：id)
Route::get('article', 'index/news/read')->completeMatch()->allowCrossDomain();
// 联系我们
Route::get('contacts', 'index/news/read')->append(['id' => 1])->completeMatch()->allowCrossDomain();
// 关于我们
Route::get('about/us', 'index/news/read')->append(['id' => 9])->completeMatch()->allowCrossDomain();
// 首页新闻
Route::post('news', 'index/news/upChina')->completeMatch()->allowCrossDomain();
// 公告內容接口
Route::get('notice_read', 'index/notice/read')->completeMatch()->allowCrossDomain();
// 跑马灯公告內容接口
Route::get('notice_read_list', 'index/notice/read_list')->completeMatch()->allowCrossDomain();

// 持仓列表
Route::get('order/position', 'index/OrderPosition/index')->completeMatch()->allowCrossDomain();
// 用户月管理费续费
Route::post('order/monthly/fee', 'index/OrderPosition/monthly_fee')->completeMatch()->allowCrossDomain();
// 简版：持仓列表
Route::get('order/position/simple', 'index/OrderPosition/simple')->completeMatch()->allowCrossDomain();
// 历史结算
Route::get('order/settlement/history', 'index/OrderPosition/settlement_history')->completeMatch()->allowCrossDomain();
// 委托列表
Route::get('order', 'index/Order/index')->completeMatch()->allowCrossDomain();
// 历史委托
Route::get('order/history', 'index/Order/history')->completeMatch()->allowCrossDomain();
// 今日成交
Route::get('order/traded', 'index/OrderTraded/index')->completeMatch()->allowCrossDomain();
// 历史成交
Route::get('order/traded/history', 'index/OrderTraded/history')->completeMatch()->allowCrossDomain();
// 委托买入
Route::post('order/buy', 'index/Order/buy')->completeMatch()->allowCrossDomain();
// 委托卖出
Route::post('order/sell', 'index/Order/sell')->completeMatch()->allowCrossDomain();
// 撤销委托
Route::post('order/cancel', 'index/Order/cancel')->completeMatch()->allowCrossDomain();

// 上级经纪人的推广二维码图片地址
Route::get('promotion', 'index/My/promotion')->completeMatch()->allowCrossDomain();
// 用户邀请
Route::get('invite', 'index/My/invite')->completeMatch()->allowCrossDomain();
//我的推广会员列表
Route::get('generalize', 'index/My/generalize_user_list')->completeMatch()->allowCrossDomain();
// 用户返佣列表
Route::get('commission', 'index/My/commission')->completeMatch()->allowCrossDomain();
//收益宝收益
Route::get('income', 'index/My/yuebao')->completeMatch()->allowCrossDomain();

// 条件单：未触发列表
Route::get('condition', 'index/condition/index')->append(['type' => 'ing'])->completeMatch()->allowCrossDomain();
// 条件单：历史列表
Route::get('condition/history', 'index/condition/index')->append(['type' => 'history'])->completeMatch()->allowCrossDomain();
// 添加条件单
Route::post('condition', 'index/condition/create')->completeMatch()->allowCrossDomain();
// 删除条件单
Route::post('condition/delete', 'index/condition/delete')->completeMatch()->allowCrossDomain();


// 银行卡线下转账账号接口
Route::get('recharge/offline/account/bank', 'index/Account/offline_account')->append(['channel' => 'offline_bank'])->completeMatch()->allowCrossDomain();
//支付宝扫码支付接口
Route::get('recharge/offline/account/alipay', 'index/Account/offline_account')->append(['channel' => 'offline_alipay'])->completeMatch()->allowCrossDomain();
//微信扫码支付接口
Route::get('recharge/offline/account/wechatpay', 'index/Account/offline_account')->append(['channel' => 'offline_wechatpay'])->completeMatch()->allowCrossDomain();
//对公账号转账接口
Route::get('recharge/offline/account/public', 'index/Account/offline_account')->append(['channel' => 'offline_public'])->completeMatch()->allowCrossDomain();
// 线下转账凭证提交入口
Route::post('recharge/offline', 'index/Account/offline_recharge')->completeMatch()->allowCrossDomain();
// 公告列表接口
Route::get('notice', 'index/notice/index')->completeMatch()->allowCrossDomain();

//二维码列表
Route::get('qrcode', 'index/index/get_qrcode')->completeMatch()->allowCrossDomain();

// 服务器信息（用于PC软件）
Route::get('server', 'index/Server/index')->completeMatch()->allowCrossDomain();
Route::get('down', 'index/down/index')->completeMatch()->allowCrossDomain();
Route::get('getdown', 'index/down/getdown')->completeMatch()->allowCrossDomain();
Route::get('updata', 'index/Server/updata')->completeMatch()->allowCrossDomain();
//系统设置-基础设置
Route::get('config', 'index/index/config')->completeMatch()->allowCrossDomain();

Route::rule('stock/getactive', 'index/stock/getactive')->completeMatch()->allowCrossDomain();
Route::rule('stock/getactives', 'index/stock/getactives')->completeMatch()->allowCrossDomain();