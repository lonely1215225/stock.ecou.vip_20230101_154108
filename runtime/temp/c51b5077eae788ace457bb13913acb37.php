<?php /*a:2:{s:72:"/www/wwwroot/stock.ecou.vip/application/dash/view/user/withdraw_log.html";i:1550554086;s:66:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/layout.html";i:1665562605;}*/ ?>
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
<link rel="stylesheet" href="/static/dash/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
<script src="/static/dash/lib/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js"></script>
<script src="/static/dash/lib/tinyselect/tinyselect.min.js"></script>
<link rel="stylesheet" type="text/css" href="/static/dash/lib/tinyselect/tinyselect.min.css"/>
<div class="content-wrapper">
    <section class="content-header">
        <h1>提现申请</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">用户管理</a></li>
            <li class="active">提现申请</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <form id="searchInfo" action="<?php echo url('withdraw_log'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">处理状态</label>
                                    <div class="search-body">
                                        <select class="form-control" name="state" id="state">
                                            <option value="">请选择</option>
                                            <?php foreach($stateList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo isset($state) && $k==$state?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">代理商</label>
                                    <div class="search-body">
                                        <select id="agentId" name="agent_id" class="form-control"
                                                onchange="showBroker(this.value);">
                                            <option value="">请选择</option>
                                            <?php foreach($agentInfo as $ak=>$av): ?>
                                            <option value="<?php echo htmlentities($ak); ?>" <?php echo isset($agent_id) &&
                                                    $ak==$agent_id?'selected':''; ?>><?php echo htmlentities($av); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">经纪人</label>
                                    <div class="search-body" id="brokerInfo">
                                        <select id="brokerId" name="broker_id" class="form-control">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">手机号</label>
                                    <div class="search-body">
                                        <input type="text" value="<?php echo !empty($mobile) ? htmlentities($mobile) : ''; ?>" name="mobile" id="mobile" class="form-control">
                                    </div>
                                </div>

                                <div class="search-box">
                                    <label class="text-right">开始申请时间</label>
                                    <div class="search-body">
                                        <div class="input-group date">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" name="start_date" id="startDate" value="<?php echo !empty($start_date) ? htmlentities($start_date) : ''; ?>" class="form-control pull-right">
                                        </div>
                                    </div>

                                </div>

                                <div class="search-box">
                                    <label class="text-right">最后申请时间</label>
                                    <div class="search-body">
                                        <div class="input-group date">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" name="end_date" id="endDate" value="<?php echo !empty($end_date) ? htmlentities($end_date) : ''; ?>" class="form-control pull-right">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">排除代理商</label>
                                    <div class="search-body">
                                        <select id="noAgentId" name="no_agent_id[]" multiple="multiple">
                                            <?php foreach($agentInfo as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo $no_agent_id && in_array($k,$no_agent_id)?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="submit_flag" id="submitFlag" value="2">
                                </div>
                                <div class="search-box">
                                    <label class="text-right"></label>
                                    <div class="search-body"><input type="submit" class="btn btn-primary" value="查找">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- 顶部横向滚动条开始 -->
                    <!--
                    <script type="text/javascript">
                        function scrollWindow(obj,id)
                        {
                            document.getElementById(id).scrollLeft=obj.scrollLeft
                        }
                    </script>
                    <div id="div1" class="table-scroll-x-top" onscroll="scrollWindow(this,'scroll_tab')">
                        <div class="table-scroll-x-top2"></div>
                    </div>
                    -->
                    <!-- 顶部横向滚动条结束 -->
                    <div class="box-body no-padding table-scroll-x" id="scroll_tab">
                        <table class="table table-bordered table-middle table-center">
                            <thead>
                            <tr class="tj">
                                <td>总计</td>
                                <td></td>
                                <td><?php echo !empty($totalWithdrawMoney['money']) ? htmlentities($totalWithdrawMoney['money']) : 0; ?></td>
                                <td><?php echo !empty($successWithdrawMoney['money']) ? htmlentities($successWithdrawMoney['money']) : 0; ?></td>
                                <td><?php echo !empty($successWithdrawMoney['servicefee']) ? htmlentities($successWithdrawMoney['servicefee']) : 0; ?></td>
                                <td colspan="8"></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>NO</th>
                                <th>用户信息</th>
                                <th>申请提现金额</th>
                                <th>实际到账金额</th>
                                <th>手续费</th>
                                <th>状态</th>
                                <th>银行账户</th>
                                <th>申请时间</th>
                                <th>到账时间</th>
                                <th>代理商/经纪人</th>
                                <th>审核操作</th>
                                <th>提现操作</th>
                                <th>删除</th>
                            </tr>
                            <?php if(isset($withdrawLog['withdrawInfo'])): foreach($withdrawLog['withdrawInfo'] as $nl): ?>
                            <tr>
                                <td><?php echo htmlentities($nl['id']); ?></td>
                                <td>
                                    <a href="<?php echo url('user/index',['mobile'=>$nl['mobile']]); ?>" target="_blank" title="点击打开用户详情">
                                        <?php echo !empty($nl['mobile']) ? htmlentities($nl['mobile']) : ''; ?><br><?php echo !empty($nl['real_name']) ? htmlentities($nl['real_name']) : '未实名'; ?>
                                    </a>
                                </td>
                                <td><?php echo !empty($nl['apply_money']) ? htmlentities($nl['apply_money']) : ''; ?></td>
                                <td><?php echo !empty($nl['money']) ? htmlentities($nl['money']) : ''; ?></td>
                                <td><?php echo !empty($nl['service_fee']) ? htmlentities($nl['service_fee']) : ''; ?></td>
                                <td><?php echo !empty($stateList[$nl['state']]) ? htmlentities($stateList[$nl['state']]) : ''; ?></td>
                                <td>
                                    <p>持卡人：<?php echo htmlentities($nl['bank_user_name']); ?></p>
                                    <p>开户行: <?php echo htmlentities($nl['bank_name']); ?></p>
                                    <p>卡号：<?php echo htmlentities($nl['bank_number']); ?></p>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s',$nl['apply_time']); ?></td>
                                <td><?php echo $nl['success_time']?date('Y-m-d H:i:s',$nl['success_time']):''; ?></td>
                                <td>
                                    <?php echo !empty($withdrawLog['adminInfo'][$nl['agent_id']]) ? htmlentities($withdrawLog['adminInfo'][$nl['agent_id']]) : ''; ?>
                                    <br>
                                    <?php echo !empty($withdrawLog['adminInfo'][$nl['broker_id']]) ? htmlentities($withdrawLog['adminInfo'][$nl['broker_id']]) : ''; ?>
                                </td>
                                <td>
                                    <?php if(!$nl['operation_time']): ?>
                                    <a class="btn btn-sm btn-primary mb5"
                                       href="<?php echo url('do_withdraw', ['id' => $nl['id'],'money'=>$nl['money'],'username'=>$nl['user_id']]); ?>">提现处理</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($nl['state']=='checked'): ?>
                                    <!--
                                    <a class="btn btn-sm btn-primary mb5" href="#" onclick="doPay('<?php echo htmlentities($nl['id']); ?>')">代付</a>
                                    -->
                                    <a class="btn btn-sm btn-primary mb5" href="#" onclick="manualPay('<?php echo htmlentities($nl['id']); ?>')">手动支付</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-primary mb5" href="#" onclick="delItem('<?php echo htmlentities($nl['id']); ?>')">删除</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="box-footer">
                        <div class="col-sm-12 no-padding"><?php echo $withdrawLog['withdrawInfo']->render(); ?></div>
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
$(function () {
    $('#startDate').datetimepicker({
        format: 'yyyy-mm-dd 00:00:00',//显示格式
        minView: "month",//设置只显示到月份
        initialDate: new Date(),
        autoclose: true,//选中自动关闭
        todayBtn: true,//显示今日按钮
    });
    $('#endDate').datetimepicker({
        format: 'yyyy-mm-dd 23:59:59',//显示格式
        minView: "month",//设置只显示到月份
        initialDate: new Date(),
        autoclose: true,//选中自动关闭
        todayBtn: true,//显示今日按钮!
    });
})
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

function doPay(id) {
    layer.confirm('确定要代付吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        if (id == "") {
            layer.msg("提现申请id不能为空", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/userWithdraw/doPay'); ?>", {id: id}, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }
        return false;
    }, null);
}
function manualPay(id) {
    layer.confirm('确定要手动支付吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        if (id == "") {
            layer.msg("提现申请id不能为空", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/userWithdraw/manualPay'); ?>", {id: id}, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }
        return false;
    }, null);
}
function delItem(v) {
    layer.confirm('确定要删除吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        if(v == "") {
            layer.msg("ID不能为空", {icon:2, time:1500, shade:.3, shadeClose:true});
        } else {
            $.post("<?php echo url('stock/userWithdraw/delete'); ?>", {id:v}, function(data){
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