<?php
// +----------------------------------------------------------------------
// | 命令行定义文件
// +----------------------------------------------------------------------

return [
    // 股票行情
    'stocks_line'         => 'app\cli\command\StocksLine',
    // 交易服务器
    'ws:sims'             => 'app\cli\sims\WebSocket',
    // 后台服务器
    'ws:admin'            => 'app\cli\server\SysWs',
    // 交易
    'Tran'                => 'app\cli\sims\Tran',
    // 获取新闻列表
    'get_news'            => 'app\cli\command\newsList',
    // 休市脚本：撤单、持仓可卖
    'market_closed'       => 'app\cli\auto\MarketClosed',
    // 每日结算
    'settlement'          => 'app\cli\auto\Settlement',
    // 每日收取管理费
    'management_fee'      => 'app\cli\auto\ManagementFee',
    // 除权除息脚本
    'xr_xd'               => 'app\cli\auto\XrXd',
    // 停牌复牌脚本
    'suspension'          => 'app\cli\auto\Suspension',
    //每日收益管理
    'yuebao'              => 'app\cli\auto\Yuebao',
    //条件单处理
    'condition'           => 'app\cli\auto\AtuoCondition',
    // 返佣
    'return_commission'   => 'app\cli\auto\ReturnCommission',
    // 强平检测脚本
    'forced_sell'         => 'app\cli\auto\ForcedSell',
    // 月管理费强平检测脚本
    'forced_sell_monthly' => 'app\cli\auto\ForcedSellMonthly',
    // 代金券强平检测脚本
    'forced_sell_cash_coupon' => 'app\cli\auto\ForcedSellCashCoupon',
    
    // 测试
    //'cli_test'            => 'app\cli\command\CliTest',
    //'test_ws_client'      => 'app\cli\client\TestWsClient',
];
