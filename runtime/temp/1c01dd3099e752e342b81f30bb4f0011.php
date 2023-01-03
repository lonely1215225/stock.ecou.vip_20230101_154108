<?php /*a:2:{s:69:"/www/wwwroot/stock.ecou.vip/application/dash/view/order/position.html";i:1605761956;s:66:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/layout.html";i:1665562605;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理后台</title>
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="/static/dash/AdminLTE/components/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/static/dash/AdminLTE/components/font-awesome/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="/static/dash/AdminLTE/components/Ionicons/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/static/dash/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/static/dash/AdminLTE/dist/css/skins/_all-skins.min.css">
    <!--自定义样式-->
    <link rel="stylesheet" href="/static/dash/css/app.css">
    <!-- bootstrap wysihtml5 - text editor -->
    <!--<link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">-->
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="http://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="http://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- jQuery 3 -->
    <script src="/static/dash/lib/jquery-3.3.1.min.js"></script>
    <!-- Layer -->
    <script src="/static/dash/lib/layer/layer.js"></script>
    <script type="text/javascript" src="/static/dash/lib/notify/js/jquery.notify.js"></script>
    <link rel="stylesheet" type="text/css" href="/static/dash/lib/notify/css/jquery.notify.css">
    <script src="/static/dash/lib/sockjs.min.js"></script>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <header class="main-header">
        <!-- Logo -->
        <a href="index.html" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini"><b>A</b>LT</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">管理后台</span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                <span class="sr-only">Toggle navigation</span>
            </a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-user"></i>
                            <span class="hidden-xs"><?php echo htmlentities(app('session')->get('username')); ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:signOut();"><i class="fa fa-power-off"></i></a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- 左侧菜单  -->
    <aside class="main-sidebar">
        <section class="sidebar">
            <ul class="sidebar-menu" data-widget="tree">
                <li class="<?php echo $controller=='Index' ? 'active' : ''; ?>">
                    <a href="<?php echo url('index/index'); ?>" class="<?php echo $action=='index' ? 'active' : ''; ?>"><i class="fa fa-home"></i> <span>系统首页</span></a>
                </li>

				<li class="treeview <?php echo $controller=='Slide' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-edit"></i> <span>幻灯片管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('Slide/index'); ?>"><i class="fa fa-circle-o"></i> 幻灯片管理</a></li>
                    </ul>
                </li>
				
                <li class="treeview <?php echo $controller=='News' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-edit"></i> <span>内容管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='cat_list' ? 'active' : ''; ?>"><a href="<?php echo url('news/cat_list'); ?>"><i class="fa fa-circle-o"></i> 栏目管理</a></li>
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('news/index'); ?>"><i class="fa fa-circle-o"></i> 文章管理</a></li>
                        <li class="<?php echo $action=='contact' ? 'active' : ''; ?>"><a href="<?php echo url('news/contact'); ?>"><i class="fa fa-circle-o"></i> 联系方式</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Notice' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-edit"></i> <span>公告管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='notice_list' ? 'active' : ''; ?>"><a href="<?php echo url('notice/index'); ?>"><i class="fa fa-circle-o"></i> 公告列表</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Risk' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-flash"></i> <span>股票管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('risk/index'); ?>"><i class="fa fa-circle-o"></i> 个股管理</a></li>
                        <li class="<?php echo $action=='black_list' ? 'active' : ''; ?>"><a href="<?php echo url('risk/black_list'); ?>"><i class="fa fa-circle-o"></i> 禁买列表</a></li>
                        <li class="<?php echo $action=='xrxd' ? 'active' : ''; ?>"><a href="<?php echo url('risk/xrxd'); ?>"><i class="fa fa-circle-o"></i> 除权除息列表</a></li>
                        <li class="<?php echo $action=='suspension' ? 'active' : ''; ?>"><a href="<?php echo url('risk/suspension'); ?>"><i class="fa fa-circle-o"></i> 停牌复牌列表</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Order' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa  fa-book"></i> <span>订单管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='position' ? 'active' : ''; ?>"><a href="<?php echo url('order/position'); ?>"><i class="fa fa-circle-o"></i> 持仓列表</a></li>
                        <li class="<?php echo $action=='order' ? 'active' : ''; ?>"><a href="<?php echo url('order/order'); ?>"><i class="fa fa-circle-o"></i> 委托列表</a></li>
                        <li class="<?php echo $action=='traded' ? 'active' : ''; ?>"><a href="<?php echo url('order/traded'); ?>"><i class="fa fa-circle-o"></i> 成交明细</a></li>
                        <li class="<?php echo $action=='close_position' ? 'active' : ''; ?>"><a href="<?php echo url('order/close_position'); ?>"><i class="fa fa-circle-o"></i> 平仓结算</a></li>
                        <li class="<?php echo $action=='forced_sell_log' ? 'active' : ''; ?>"><a href="<?php echo url('order/forced_sell_log'); ?>"><i class="fa fa-circle-o"></i> 平仓日志</a></li>
                        <li class="<?php echo $action=='condition' ? 'active' : ''; ?>"><a href="<?php echo url('order/condition'); ?>"><i class="fa fa-circle-o"></i> 条件单列表</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='User' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>用户管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('user/index'); ?>"><i class="fa fa-circle-o"></i> 用户列表</a></li>
                        <li class="<?php echo $action=='add_user' ? 'active' : ''; ?>"><a href="<?php echo url('user/add_user'); ?>"><i class="fa fa-circle-o"></i> 添加用户</a></li>
                        <li class="<?php echo $action=='recharge_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/recharge_log'); ?>"><i class="fa fa-circle-o"></i> 充值记录</a></li>
                        <li class="<?php echo $action=='withdraw_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/withdraw_log'); ?>"><i class="fa fa-circle-o"></i> 提现申请</a></li>
                        <li class="<?php echo $action=='wallet_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/wallet_log'); ?>"><i class="fa fa-circle-o"></i> 账户资金流水</a></li>
                        <li class="<?php echo $action=='strategy_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/strategy_log'); ?>"><i class="fa fa-circle-o"></i> 策略金流水</a></li>
                        <li class="<?php echo $action=='cash_coupon_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/cash_coupon_log'); ?>"><i class="fa fa-circle-o"></i> 代金券资金流水</a></li>
                        <li class="<?php echo $action=='frozen_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/frozen_log'); ?>"><i class="fa fa-circle-o"></i> 冻结策略金流水</a></li>
                        <li class="<?php echo $action=='yuebao_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/yuebao_log'); ?>"><i class="fa fa-circle-o"></i> 收益宝收益明细</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Agent' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>代理商管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('agent/index'); ?>"><i class="fa fa-circle-o"></i> 代理商列表</a></li>
                        <li class="<?php echo $action=='account_log' ? 'active' : ''; ?>"><a href="<?php echo url('agent/account_log'); ?>"><i class="fa fa-circle-o"></i> 资金明细</a></li>
                        <li class="<?php echo $action=='withdraw_log' ? 'active' : ''; ?>"><a href="<?php echo url('agent/withdraw_log'); ?>"><i class="fa fa-circle-o"></i> 提现申请</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Broker' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>经纪人管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('broker/index'); ?>"><i class="fa fa-circle-o"></i> 经纪人列表</a></li>
                        <li class="<?php echo $action=='account_log' ? 'active' : ''; ?>"><a href="<?php echo url('broker/account_log'); ?>"><i class="fa fa-circle-o"></i> 资金明细</a></li>
                        <li class="<?php echo $action=='withdraw_log' ? 'active' : ''; ?>"><a href="<?php echo url('broker/withdraw_log'); ?>"><i class="fa fa-circle-o"></i> 提现申请</a></li>
                        <li class="<?php echo $action=='strategy_log' ? 'active' : ''; ?>"><a href="<?php echo url('user/strategy_log'); ?>"><i class="fa fa-circle-o"></i> 策略金流水</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Income' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-money"></i> <span>佣金管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('income/index'); ?>"><i class="fa fa-circle-o"></i> 管理费返佣</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Settings' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-cog"></i> <span>系统设置</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='config' ? 'active' : ''; ?>"><a href="<?php echo url('settings/config'); ?>"><i class="fa fa-circle-o"></i>基础设置</a></li>
                        <li class="<?php echo $action=='trading_time' ? 'active' : ''; ?>"><a href="<?php echo url('settings/trading_time'); ?>"><i class="fa fa-circle-o"></i> 交易时间设置</a></li>
                        <li class="<?php echo $action=='non_trading_date' ? 'active' : ''; ?>"><a href="<?php echo url('settings/non_trading_date'); ?>"><i class="fa fa-circle-o"></i> 非交易日管理</a></li>
                        <li class="<?php echo $action=='trading_fee' ? 'active' : ''; ?>"><a href="<?php echo url('settings/trading_fee'); ?>"><i class="fa fa-circle-o"></i> 交易费用</a></li>
                        <li class="<?php echo $action=='yuebao_set' ? 'active' : ''; ?>"><a href="<?php echo url('settings/yuebao_set'); ?>"><i class="fa fa-circle-o"></i> 收益宝配置</a></li>
                        <li class="<?php echo $action=='buy_limit_rate' ? 'active' : ''; ?>"><a href="<?php echo url('settings/buy_limit_rate'); ?>"><i class="fa fa-circle-o"></i>涨跌幅禁买线</a></li>
                        <li class="<?php echo $action=='payment_way' ? 'active' : ''; ?>"><a href="<?php echo url('settings/payment_way'); ?>"><i class="fa fa-circle-o"></i> 支付方式</a></li>
                        <!--li class="<?php echo $action=='qrcode_set' ? 'active' : ''; ?>"><a href="<?php echo url('settings/qrcode_set'); ?>"><i class="fa fa-circle-o"></i>二维码设置</a></li-->
                        <li class="<?php echo $action=='cash_coupon_set' ? 'active' : ''; ?>"><a href="<?php echo url('settings/cash_coupon_set'); ?>"><i class="fa fa-circle-o"></i>代金券设置</a></li>
                        <li class="<?php echo $action=='app_config' ? 'active' : ''; ?>"><a href="<?php echo url('settings/app_config'); ?>"><i class="fa fa-circle-o"></i>APP设置</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Passport' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>管理员</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='modify_password' ? 'active' : ''; ?>"><a href="<?php echo url('passport/modify_password'); ?>"><i class="fa fa-circle-o"></i> 修改密码</a></li>
                    </ul>
                </li>
            </ul>
        </section>
    </aside>

    <!--  内容主体 -->
    
<style>
.tj{
    background-color: #ecf0f5;
    font-weight:bold;
}
.ui-multiselect{line-height:10px;height:35px}
</style>
<script src="/static/dash/lib/sockjs.min.js"></script>
<script src="/static/dash/lib/tinyselect/tinyselect.min.js"></script>
<link rel="stylesheet" type="text/css" href="/static/dash/lib/tinyselect/tinyselect.min.css"/>
<div class="content-wrapper">
    <section class="content-header">
        <h1>持仓列表</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">订单管理</a></li>
            <li class="active">持仓列表</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <form id="searchInfo" action="<?php echo url('position'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">股票代码</label>
                                    <div class="search-body"><input class="form-control" name="stock_code" id="stockCode" type="text"  value="<?php echo !empty($stock_code) ? htmlentities($stock_code) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">代理商</label>
                                    <div class="search-body">
                                        <select class="form-control" id="agentId" name="agent_id" onchange="showBroker(this.value);">
                                            <option value="">请选择</option>
                                            <?php foreach($agentList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo isset($agent_id) && $k==$agent_id?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">经纪人</label>
                                    <div class="search-body" id="brokerInfo">
                                        <select id="brokerId" name="broker_id" class="form-control" >
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">手机号</label>
                                    <div class="search-body"><input class="form-control" name="mobile" id="mobile" type="text"  value="<?php echo !empty($mobile) ? htmlentities($mobile) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">持仓编号</label>
                                    <div class="search-body"><input class="form-control" name="id" id="id" type="text"  value="<?php echo !empty($id) ? htmlentities($id) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">资金账号</label>
                                    <div class="search-body"><input class="form-control" name="primary_account" id="primaryAccount" type="text"  value="<?php echo !empty($primary_account) ? htmlentities($primary_account) : ''; ?>"></div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">排除代理商</label>
                                    <div class="search-body">
                                        <select id="noAgentId" name="no_agent_id[]" multiple="multiple">
                                            <?php foreach($agentList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo $no_agent_id && in_array($k,$no_agent_id)?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="submit_flag" id="submitFlag" value="2">
                                </div>
                                <div class="search-box">
                                    <label class="text-right">月管理费</label>
                                    <div class="search-body">
                                        <select id="is_monthly" name="is_monthly" class="form-control" >
                                            <option value="">全部</option>
                                            <option value="false" <?php echo isset($is_monthly) && $is_monthly=='false'?'selected':''; ?>>否</option>
                                            <option value="true" <?php echo isset($is_monthly) && $is_monthly=='true'?'selected':''; ?>>是</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right"></label>
                                    <div class="search-body"><input type="submit" class="btn btn-primary" value="查找"></div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <script type="text/javascript">
                        function scrollWindow(obj,id)
                        {
                            document.getElementById(id).scrollLeft=obj.scrollLeft
                        }
                    </script>
                    <div id="div1" class="table-scroll-x-top" onscroll="scrollWindow(this,'scroll_tab')">
                        <div class="table-scroll-x-top2"></div>
                    </div>
                    <div class="box-body no-padding table-scroll-x" id="scroll_tab" onscroll="scrollWindow(this,'div1')">
                        <table class="table table-bordered table-middle table-center width-130%">
                            <thead>
                            <tr class="tj">
                                <td>总计</td>
                                <td></td>
                                <td></td>
                                <td>
                                        <span class="text-red"><?php echo !empty($positionStatistic['volume_position']) ? htmlentities($positionStatistic['volume_position']) : 0; ?></span>
                                        <br>
                                        <span class="text-green"><?php echo !empty($positionStatistic['volume_for_sell']) ? htmlentities($positionStatistic['volume_for_sell']) : 0; ?></span>
                                        <br>
                                        <span class="text-blue"><?php echo !empty($positionStatistic['volume_today']) ? htmlentities($positionStatistic['volume_today']) : 0; ?></span>
                                </td>
                                <td>
                                        <?php echo !empty($positionStatistic['sum_buy_volume']) ? htmlentities($positionStatistic['sum_buy_volume']) : 0; ?>
                                        <br>
                                        <?php echo !empty($positionStatistic['sum_sell_volume']) ? htmlentities($positionStatistic['sum_sell_volume']) : 0; ?>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><?php echo !empty($positionStatistic['sum_buy_value']) ? htmlentities($positionStatistic['sum_buy_value']) : 0; ?></td>
                                <td><?php echo !empty($positionStatistic['sum_buy_value_cost']) ? htmlentities($positionStatistic['sum_buy_value_cost']) : 0; ?></td>
                                <td><?php echo !empty($positionStatistic['sum_sell_value']) ? htmlentities($positionStatistic['sum_sell_value']) : 0; ?></td>
                                <td><?php echo !empty($positionStatistic['sum_sell_value_in']) ? htmlentities($positionStatistic['sum_sell_value_in']) : 0; ?></td>
                                <td id="total_market"></td>
                                <td id="total_profit_loss"></td>
                                <td></td>
                                <td><?php echo !empty($positionStatistic['sum_back_profit']) ? htmlentities($positionStatistic['sum_back_profit']) : '0'; ?></td>
                                <td>
                                    <?php echo htmlentities($positionStatistic['sum_xrxd_volume']); ?>
                                    <br>
                                    <?php echo htmlentities($positionStatistic['sum_xrxd_dividend']); ?>
                                </td>
                                <td></td>
                                <td><?php echo !empty($positionStatistic['sum_management_fee']) ? htmlentities($positionStatistic['sum_management_fee']) : '0'; ?></td>
                                <td><?php echo !empty($positionStatistic['sum_deposit']) ? htmlentities($positionStatistic['sum_deposit']) : '0'; ?></td>
                                <td><?php echo !empty($positionStatistic['sum_strategy']) ? htmlentities($positionStatistic['sum_strategy']) : '0'; ?></td>
                                <td colspan="4"></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>持仓编号</th>
                                <th>用户信息</th>
                                <th>股票详情</th>
                                <th><span class="text-red">持仓</span><br><span class="text-green">可卖</span><br><span class="text-blue">今仓</span></th>
                                <th>累买/累卖</th>
                                <th>最新价</th>
                                <th>补仓价</th>
                                <th>持仓均价</th>
                                <th>买入市值</th>
                                <th>总买入市值</th>
                                <th>卖出市值</th>
                                <th>总卖出市值</th>
                                <th>最新市值</th>
                                <th>持仓盈亏</th>
                                <th>盈亏比例</th>
                                <th>累提盈利</th>
                                <th>除权除息</th>
                                <th>停牌天数</th>
                                <th>管理费</th>
                                <th>保证金</th>
                                <th>策略金</th>
                                <th>代金券</th>
                                <th>月管理费</th>
                                <th>月管理到期时间</th>
                                <th>操作管理</th>
                            </tr>
                            <?php foreach($positionList['orderPositionList'] as $k=>$v): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url('order/traded', ['order_position_id' => $v['id']]); ?>" target="_blank" title="成交明细"><?php echo htmlentities($v['id']); ?></a>
                                </td>
                                <td>
                                    <a href="<?php echo url('user/index', ['mobile' => $v['mobile']]); ?>" target="_blank" title="用户信息">
                                        <?php echo htmlentities($v['mobile']); ?><br><?php echo !empty($v['real_name']) ? htmlentities($v['real_name']) : '未实名'; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlentities($v['market']); ?><?php echo htmlentities($v['stock_code']); ?>
                                    <br>
                                    <?php echo !empty($positionList['stockInfo'][$v['market'].$v['stock_code']]) ? htmlentities($positionList['stockInfo'][$v['market'].$v['stock_code']]['stock_name']) : ''; ?>
                                    <br>
                                    <?php echo htmlentities($v['primary_account']); ?>
                                </td>
                                <td>
                                    <span class="text-red"><?php echo htmlentities($v['volume_position']); ?></span>
                                    <br>
                                    <span class="text-green"><?php echo htmlentities($v['volume_for_sell']); ?></span>
                                    <br>
                                    <span class="text-blue"><?php echo htmlentities($v['volume_today']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlentities($v['sum_buy_volume']); ?>
                                    <br>
                                    <?php echo htmlentities($v['sum_sell_volume']); ?>
                                </td>
                                <td class="newprice<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>"></td>
                                <td><?php echo htmlentities($v['stop_loss_price']); ?></td>
                                <td><?php echo htmlentities($v['position_price']); ?></td>
                                <td><?php echo htmlentities($v['sum_buy_value']); ?></td>
                                <td><?php echo htmlentities($v['sum_buy_value_cost']); ?></td>
                                <td><?php echo htmlentities($v['sum_sell_value']); ?></td>
                                <td><?php echo htmlentities($v['sum_sell_value_in']); ?></td>
                                <td class="now-value" id="<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>" data-flag="<?php echo htmlentities($v['id']); ?>" data-num="<?php echo htmlentities($v['volume_position']); ?>" data-buy="<?php echo htmlentities($v['sum_buy_value_cost']); ?>" data-sell="<?php echo htmlentities($v['sum_sell_value_in']); ?>"></td>
                                <td class="pal" id="pal<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>" data-flag="<?php echo htmlentities($v['id']); ?>" data-avg="<?php echo htmlentities($v['position_price']); ?>" data-num="<?php echo htmlentities($v['volume_position']); ?>"></td>
                                <td class="avg" id="avg<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>" data-flag="<?php echo htmlentities($v['id']); ?>" data-avg="<?php echo htmlentities($v['position_price']); ?>"></td>
                                <td><?php echo htmlentities($v['sum_back_profit']); ?></td>
                                <td>
                                    <?php echo htmlentities($v['xrxd_volume']); ?>
                                    <br>
                                    <?php echo htmlentities($v['xrxd_dividend']); ?>
                                </td>
                                <td><?php echo htmlentities($v['suspension_days']); ?></td>
                                <td><?php echo htmlentities($v['sum_management_fee']); ?></td>
                                <td>
                                    <?php echo htmlentities($v['sum_deposit']); ?>
                                </td>
                                <td><?php echo htmlentities($v['strategy']); ?></td>
                                <td><?php echo !empty($v['is_cash_coupon']) ? '是' : '否'; ?></td>
                                <td><?php echo !empty($v['is_monthly']) ? '是' : '否'; ?></td>
                                <td><?php echo htmlentities($v['monthly_expire_date']); ?></td>
                                <td><a class="btn btn-sm btn-primary mb5" href="#" onclick="forcedSell('<?php echo htmlentities($v['id']); ?>')">强制平仓</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <input type="hidden" id="webSocket" value="<?php echo htmlentities($positionList['webSocket']); ?>">
                    <div class="box-footer">
                        <div class="col-sm-12 no-padding"><?php echo $positionList['orderPositionList']->render(); ?></div>
                    </div>

                </div>
            </div>

        </div>
    </section>
</div>

</div>

<!-- Bootstrap 3.3.7 -->
<script src="/static/dash/AdminLTE/components/bootstrap/js/bootstrap.min.js"></script>
<!-- FastClick -->
<script src="/static/dash/AdminLTE/components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="/static/dash/AdminLTE/dist/js/adminlte.min.js"></script>

<!-- 页面自定义JS内容部分 -->

<script>
tinyselect('#noAgentId',{
    result: {
        style: {
            height: '34px',
            border: '1px solid #ccc'
        }
    }
});
$(function(){
    var agentId  = "<?php echo $agent_id; ?>";
    var brokerId = "<?php echo $broker_id; ?>";
    if (agentId) {
        $.post("<?php echo url('stock/orgFilter/broker'); ?>", {agent_id:agentId,brokerId:brokerId}, function(data){
            if(data.code == 1){
                var str = "<select id=\"brokerId\" name=\"broker_id\" class=\"form-control\"><option value=''>请选择</option>";
                $.each(data.data, function (i, el) {
                    if(i == brokerId){
                        str +="<option value='"+i+"' selected>"+el+"</option>";
                    }else{
                        str +="<option value='"+i+"'>"+el+"</option>";
                    }
                });
                str +="</select>";
                $("#brokerInfo").html(str);
            }
        });
    }
});
function showBroker(v) {
    var brokerId=0;
    $.post("<?php echo url('stock/orgFilter/broker'); ?>", {agent_id:v,brokerId:brokerId}, function(data){
        if(data.code == 1){
            $("#brokerInfo").empty();
            var str = "<select id=\"brokerId\" name=\"broker_id\" class=\"form-control\"><option value=''>请选择</option>";
            $.each(data.data, function (i, el) {
                str +="<option value='"+i+"'>"+el+"</option>";
            });
            str +="</select>";
            $("#brokerInfo").html(str);
        }
    });
}
var wsUriPostion ="ws://47.114.91.240:21280";
function createWebsocketPosition() {
    websockets = new WebSocket(wsUriPostion);
    websockets.onopen = function(evt) {
        console.log('行情服务器连接成功！');
        onOpenSocket(evt)
    };
    websockets.onclose = function(evt) {
        onClose(evt)
    };
    websockets.onmessage = function(evt) {
        onMessagePosition(evt)
    };
    websockets.onerror = function(evt) {
        onError(evt)
    };
}

function onOpenSocket(evt) {
    var msg = $("#webSocket").val();
    doSendData(msg);
}


function onMessagePosition(evt) {
    var data=JSON.parse(evt.data);
    data=JSON.parse(data.Data.data);
    // 最新价
    var nowPrice=data.Price;
    $(".newprice"+data.SecurityCode+'_'+data.SecurityType).text(nowPrice);
    // 持仓均价
    var resavg=0;
    // 持仓股数
    var pal=0;
    var newPrice=0;
    var sumTotal=0;
    var total_market=0;
    var total_profit_loss=0;
    // 计算最新市值并赋值
    $(".now-value").each(function () {
        newPrice=(nowPrice*$(this).data('num')).toFixed(2);
        $("#"+data.SecurityCode+'_'+data.SecurityType+'_'+$(this).data('flag')).text(newPrice);
        sumTotal=(parseFloat($(this).data('sell'))-parseFloat($(this).data('buy'))+parseFloat(newPrice)).toFixed(2);
        $("#sumpal"+data.SecurityCode+'_'+data.SecurityType+'_'+$(this).data('flag')).text(sumTotal);
        total_market += sumTotal;
    });


    // 计算持仓盈亏、累计盈亏并赋值
    $(".pal").each(function () {
        pal=((nowPrice-$(this).data('avg'))*$(this).data('num')).toFixed(2);
        $("#pal"+data.SecurityCode+'_'+data.SecurityType+'_'+$(this).data('flag')).text(pal);
        //$("#sumpal"+data.SecurityCode+'_'+data.SecurityType+'_'+$(this).data('flag')).text((parseFloat(pal)+parseFloat($(this).data('sum'))).toFixed(2));
    });
    //$('#total_profit_loss').text(total_market);

    // 计算盈亏比例并赋值
    $(".avg").each(function () {
        resavg=(((nowPrice-$(this).data('avg'))/$(this).data('avg'))*100).toFixed(2);
        $("#avg"+data.SecurityCode+'_'+data.SecurityType+'_'+$(this).data('flag')).text(resavg+'%');
    });
}

function doSendData(message) {
    websockets.send(message)
}
window.addEventListener("load", createWebsocketPosition, false);

function forcedSell(positionID) {
    layer.confirm('确定要将持仓编号【'+ positionID +'】的持仓订单强制平仓吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        if(positionID == "") {
            layer.msg("持仓信息不能为空", {icon:2, time:1500, shade:.3, shadeClose:true});
        } else {
            $.get("<?php echo url('stock/OrderPosition/forcedSell'); ?>", {positionID:positionID}, function(data){
                if(data.code == 1){
                    layer.msg(data.msg, {icon:1, time:1500, shade:.3, shadeClose:true}, function(){
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon:2, time:1500, shade:.3, shadeClose:true});
                }
            });
        }
        return false;
    }, null);
}
</script>

<script type="text/javascript">
    function signOut() {
        $.post("<?php echo url('stock/auth/signOut'); ?>", function(data){
            if(data.code == 1){
                layer.msg(data.msg, {icon:1, time:1500, shade:.3, shadeClose:true}, function(){
                    window.location.href = "<?php echo url('index/index'); ?>";
                });
            } else {
                layer.msg(data.msg, {icon:2, time:1500, shade:.3, shadeClose:true});
            }
        });
    }

    var wsUri = "wss://<?php echo htmlentities($hostip); ?>/wss";
    var websocket = null;
    var wsLock = false;
    var heartCheck = {
        timeout: 3000,
        intervalObj: null,
        data: {"Key": "Heartbeat","Token": "<?php echo htmlentities($_SESSION['think']['token']); ?>"},
        reset: function () {
            this.intervalObj && clearInterval(this.intervalObj);
            heartCheck.start();
        },
        start: function () {
            console.log('充值、提现服务器连接成功');
            this.intervalObj && clearInterval(this.intervalObj);
            this.intervalObj = setInterval(function () {
                websocket.send(JSON.stringify(heartCheck.data));
            }, heartCheck.timeout)
        },
        stop: function () {
            clearInterval(this.intervalObj);
        }
    };

    function createWebsocket() {
        websocket = new WebSocket(wsUri);
        websocket.onopen = function(evt) {
            onOpen(evt)
        };
        websocket.onclose = function(evt) {
            onClose(evt)
        };
        websocket.onmessage = function(evt) {
            onMessage(evt)
        };
        websocket.onerror = function(evt) {
            onError(evt)
        };
    }
    createWebsocket();
    function onOpen(evt) {
        heartCheck.start();
        var token = "<?php echo htmlentities($_SESSION['think']['token']); ?>";
        var data={
            "Key": "Heartbeat",
            "Token": token
        };
        var message=JSON.stringify(data);
        setInterval(doSend(message),1000);
    }

    function onMessage(evt) {
        var data=JSON.parse(evt.data);
        var str = data.data;
        // 提现申请提醒
        if(str.indexOf('withdrawRemind') != -1){
            // 提现提醒弹窗
            checkDialog(1);
            removeRemind('withdrawRemind');
        }
        if(str.indexOf('rechargeRemind') != -1){
            // 提现提醒弹窗
            checkDialog(2);
            removeRemind('rechargeRemind');
        }
    }

    function onClose(evt) {
        heartCheck.stop();
        restartWebSocket();
    }

    function onError(evt) {
        conosole.log('充值提现服务器连接失败');
        heartCheck.stop();
        restartWebSocket();
    }

    // 重连
    function restartWebSocket() {
        if (wsLock == true) return false;
        wsLock == true;

        setTimeout(function () {
            createWebsocket();
            wsLock == false;
        }, 3000);
    }

    function doSend(message) {
        websocket.send(message);
    }

    function checkDialog(v) {
        $.notifySetup({sound: '/static/dash/lib/notify/audio/notify.mp3'});
        if(v == 1){
            $('<p>您有新的提现申请，请及时处理！</p>').notify();
        }
        if(v == 2){
            $('<p>您有新的充值记录，请及时查看！</p>').notify();
        }
    }
    function removeRemind(remind) {
        $.post("<?php echo url('stock/userWithdraw/removeFlag'); ?>", {remind: remind}, function (data) {
        });
    }
    $(document).ready(function () {
        var hover_index = 0;
        $("table.table td").hover(function () {
            hover_index = $(this).parent().find('td').index(this);
            $("table.table tr").find("td:eq(" + hover_index + ")").addClass("hewenqi");
            $(this).addClass("hewenqi");
        }, function () {
            $("table.table tr").find("td:eq(" + hover_index + ")").removeClass("hewenqi");
            $(this).removeClass("hewenqi");
        });

        $("table.table tbody tr").hover(function () {
            $(this).addClass("hewenqi");
        }, function () {
            $(this).removeClass("hewenqi");
        });
    });
</script>
</body>
</html>