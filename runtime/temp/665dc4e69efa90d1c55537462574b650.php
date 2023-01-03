<?php /*a:2:{s:71:"/www/wwwroot/stock.ecou.vip/application/dash/view/user/user_detail.html";i:1666168884;s:66:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/layout.html";i:1665562605;}*/ ?>
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
.detail-table{width:100%;}
.detail-table tr{border-top:1px solid #ddd;border-bottom:1px solid #ddd;}
.detail-table tr th,.detail-table tr td{padding:8px 15px;}
.detail-table tr th{text-align:right;}
.detail-table tr td{border-left:1px solid #ddd !important;}
</style>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            用户详情
            <small></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">用户管理</a></li>
            <li class="active">用户详情</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <!-- 用户账户信息 -->
            <div class="col-sm-6">
                <div class="box box-info">
                    <div class="box-header"><strong>用户账户信息</strong></div>
                    <div class="box-body no-padding">
                        <table class="detail-table">
                            <tr>
                                <th width="170">钱包余额</th>
                                <td><?php echo !empty($userList['userAccountList']['wallet_balance']) ? htmlentities($userList['userAccountList']['wallet_balance']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>策略金金额</th>
                                <td><?php echo !empty($userList['userAccountList']['strategy_balance']) ? htmlentities($userList['userAccountList']['strategy_balance']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>累计充值</th>
                                <td><?php echo !empty($userList['userAccountList']['total_recharge']) ? htmlentities($userList['userAccountList']['total_recharge']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>累计提现</th>
                                <td><?php echo !empty($userList['userAccountList']['total_withdraw']) ? htmlentities($userList['userAccountList']['total_withdraw']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>累计盈亏</th>
                                <td><?php echo !empty($userList['userAccountList']['total_pal']) ? htmlentities($userList['userAccountList']['total_pal']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>创建时间</th>
                                <td><?php echo !empty($userList['userAccountList']['create_time']) ? htmlentities($userList['userAccountList']['create_time']) : ''; ?></td>
                            </tr>
                            <tr>
                                <th>更新时间</th>
                                <td><?php echo !empty($userList['userAccountList']['update_time']) ? htmlentities($userList['userAccountList']['update_time']) : ''; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 调整策略金金额 -->
            <div class="col-sm-6">
                <div class="box box-info">
                    <form id="editForm" action="" class="form-horizontal">
                        <div class="box-header"><strong>调整账户资金</strong></div>
                        <div class="box-body no-padding">
                            <table class="detail-table">
                                <tr>
                                    <th width="170">调整类型</th>
                                    <td>
                                        <select class="form-control" id="ctype" name="ctype" onchange="setreamrk(this.value);" style="">
                                            <option value="">请选择</option>
                                            <option value="1">增加</option>
                                            <option value="2">减少</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170">账户资金</th>
                                    <td>
                                        <input type="text" value="" name="change_money" id="changeMoney" class="form-control" style="">
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170">备注</th>
                                    <td>
                                        <textarea class="form-control"  id="remark" name="remark" style="height:89px;"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170"></th>
                                    <td>
                                        <button type="submit" class="btn btn-primary">提交</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 no-padding">
                <!-- 用户信息 -->
                <div class="col-sm-12">
                    <div class="box box-info">
                        <div class="box-header"><strong>用户信息</strong></div>
                        <div class="box-body no-padding">
                            <table class="detail-table">
                                <tr>
                                    <th width="170">手机号</th>
                                    <td><?php echo htmlentities($userList['userList']['mobile']); ?></td>
                                </tr>
                                <tr>
                                    <th>是否禁止登陆</th>
                                    <td><?php echo !empty($userList['userList']['is_deny_login']) ? '是' : '否'; ?></td>
                                </tr>
                                <tr>
                                    <th>是否禁止提现</th>
                                    <td><?php echo !empty($userList['userList']['is_deny_cash']) ? '是' : '否'; ?></td>
                                </tr>
                                <tr>
                                    <th>备注</th>
                                    <td><?php echo htmlentities($userList['userList']['remark']); ?></td>
                                </tr>
                                <tr>
                                    <th>注册IP/登录IP</th>
                                    <td><?php echo htmlentities($userList['userList']['reg_ip']); ?> —— <?php echo htmlentities($userList['userList']['login_ip']); ?></td>
                                </tr>
                                <tr>
                                    <th>创建时间</th>
                                    <td><?php echo htmlentities($userList['userList']['create_time']); ?></td>
                                </tr>
                                <tr>
                                    <th>更新时间</th>
                                    <td><?php echo htmlentities($userList['userList']['update_time']); ?></td>
                                </tr>
                                <tr>
                                    <th>是否绑定银行卡</th>
                                    <td><?php echo !empty($userList['userList']['is_bound_bank_card']) ? '是' : '否'; ?></td>
                                </tr>
                                <tr>
                                    <th>代理商</th>
                                    <td><?php echo !empty($userList['adminUser']['agent']) ? htmlentities($userList['adminUser']['agent']) : ''; ?></td>
                                <tr>
                                <tr>
                                    <th>经济人</th>
                                    <td><?php echo !empty($userList['adminUser']['broker']) ? htmlentities($userList['adminUser']['broker']) : ''; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 绑定银行卡信息 -->
                <div class="col-sm-12">
                    <div class="box box-info">
                        <div class="box-header">
                            <strong>银行卡<?php if(!$userList['userBankCardList']): ?>（未绑定）管理员绑定<?php endif; ?></strong>
                        </div>
                        <div class="box-body no-padding">
                            <table class="detail-table">
                                <?php if($userList['userBankCardList']): ?><?php endif; ?>
                                <tr>
                                    <th width="170">持卡人姓名</th>
                                    <td><input type="text" value="<?php echo !empty($userList['userBankCardList']['real_name']) ? htmlentities($userList['userBankCardList']['real_name']) : ''; ?>" name="real_name" id="real_name" class="form-control" /></td>
                                </tr>
                                <tr>
                                    <th>开户行名称</th>
                                    <td>
                                        <select class="form-control" id="bank_id" name="bank_id">
                                            <option value="">请选择</option>
                                            <?php foreach($banksList as $v): ?>
                                            <option value="<?php echo htmlentities($v['id']); ?>" <?php echo $userList['userBankCardList']['bank_name']==$v['bank_name'] ? 'selected' : ''; ?>><?php echo htmlentities($v['bank_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>开户支行名称</th>
                                    <td><input type="text" value="<?php echo !empty($userList['userBankCardList']['branch']) ? htmlentities($userList['userBankCardList']['branch']) : ''; ?>" name="branch" id="branch" class="form-control" /></td>
                                </tr>
                                <tr>
                                    <th>开户行所在省</th>
                                    <td>
                                        <select class="form-control" id="province" name="province" onchange="showCity();">
                                            <option value="">请选择</option>
                                            <?php foreach($city as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($v['id']); ?>" <?php echo !empty($userList['userBankCardList']['province']) ? ($cityInfo[$userList['userBankCardList']['province']]==$v['name']?'selected' : ''):''; ?> data-key="<?php echo htmlentities($k); ?>"><?php echo htmlentities($v['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>开户行所在市</th>
                                    <td>
                                        <select id="city" name="city" class="form-control" >
                                            <option value="<?php echo htmlentities($userList['userBankCardList']['city']); ?>" selected><?php echo !empty($userList['userBankCardList']['city']) ? htmlentities($cityInfo[$userList['userBankCardList']['city']]) : ''; ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>银行卡号</th>
                                    <td><input type="text" value="<?php echo !empty($userList['userBankCardList']['bank_number']) ? htmlentities($userList['userBankCardList']['bank_number']) : ''; ?>" name="bank_number" id="bank_number" class="form-control" /></td>
                                </tr>
                                <tr>
                                    <th>身份证号</th>
                                    <td><input type="text" value="<?php echo !empty($userList['userBankCardList']['id_card_number']) ? htmlentities($userList['userBankCardList']['id_card_number']) : ''; ?>" name="id_card_number" id="id_card_number" class="form-control" /></td>
                                </tr>
                                <tr>
                                    <th>银行预留电话</th>
                                    <td><input type="text" value="<?php echo !empty($userList['userBankCardList']['mobile']) ? htmlentities($userList['userBankCardList']['mobile']) : ''; ?>" name="mobile" id="mobile" class="form-control" /></td>
                                </tr>
                                <tr>
                                    <th>创建时间</th>
                                    <td><?php echo !empty($userList['userBankCardList']['create_time']) ? htmlentities($userList['userBankCardList']['create_time']) : ''; ?></td>
                                </tr>
                                <tr>
                                    <th>更新时间</th>
                                    <td><?php echo !empty($userList['userBankCardList']['update_time']) ? htmlentities($userList['userBankCardList']['update_time']) : ''; ?></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <a class="btn btn-sm btn-primary" href="#" onclick="editBankCard()"><?php if($userList['userBankCardList']): ?>修改<?php else: ?>绑定<?php endif; ?>银行卡</a>
                                        <a class="btn btn-sm btn-primary" href="#" onclick="delBankNUm('<?php echo htmlentities($userList['userList']['id']); ?>')">删除银行卡</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 调整账户策略资金 -->
            <div class="col-sm-6">
                <div class="box box-info">
                    <form id="editStrategyForm" action="" class="form-horizontal">
                        <div class="box-header"><strong>调整账户策略资金</strong></div>
                        <div class="box-body no-padding">
                            <table class="detail-table">
                                <tr>
                                    <th width="170">调整类型</th>
                                    <td>
                                        <select class="form-control" id="ctypeStrategy" name="ctypeStrategy" onchange="setreamrk(this.value);" style="">
                                            <option value="">请选择</option>
                                            <option value="3">增加</option>
                                            <option value="4">减少</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170">账户策略资金</th>
                                    <td>
                                        <input type="text" value="" name="change_strategy" id="changeStrategy" class="form-control" style="">
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170">备注</th>
                                    <td>
                                        <textarea class="form-control"  id="remarkStrategy" name="remarkStrategy" style="height:89px;"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th width="170"></th>
                                    <td>
                                        <button type="submit" class="btn btn-primary">提交</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 转移经济人 -->
            <div class="col-sm-6">
                <div class="box box-info">
                    <div class="box-header"><strong>重置密码</strong></div>
                    <div class="box-body no-padding">
                        <table class="detail-table">
                            <tr>
                                <th width="170">密码</th>
                                <td>
                                    <input type="password" value="" name="password" id="password" class="form-control" style="">
                                </td>
                            </tr>
                            <tr>
                                <th>确认密码</th>
                                <td>
                                    <input type="password" value="" name="rePassword" id="rePassword" class="form-control" style="">
                                </td>
                            </tr>
                            <tr>
                                <th></th>
                                <th><button id="changePwd" type="button" class="btn btn-primary pull-left">提交</button></th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <!-- 转移经济人 -->
                <div class="box box-info">
                    <div class="box-header"><strong>转移经济人</strong></div>
                    <div class="box-body no-padding">
                        <table class="detail-table">
                            <tr>
                                <th width="170">代理商</th>
                                <td>
                                    <select class="form-control" id="agentID" name="agent_id" onchange="showBroker(this.value);">
                                        <option value="">请选择</option>
                                        <?php foreach($agentList as $k=>$v): ?>
                                        <option value="<?php echo htmlentities($k); ?>" <?php echo isset($agent_id) && $k==$agent_id?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>经济人</th>
                                <td>
                                    <select id="brokerID" name="broker_id" class="form-control" ></select>
                                </td>
                            </tr>
                            <tr>
                                <th></th>
                                <th><button id="changeBroker" type="button" class="btn btn-primary pull-left">转移</button></th>
                            </tr>
                        </table>
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
function setreamrk(v)
{
    if(v==1){
        $("#remark").text("转入");
    }else if(v==2){
        $("#remark").text("转出");
    }
    if(v==3){
        $("#remarkStrategy").text("系统退款");
    }else if(v==4){
        $("#remarkStrategy").text("系统追加扣费");
    }
}

function showCity() {
    var k= $("#province").find("option:selected").attr("data-key");
    var cityAll=<?php echo json_encode($city); ?>;
    var cityArr=cityAll[k];
    var str = "";
    $.each(cityArr.cities, function (i, el) {
        str +="<option value='"+el.id+"' selected>"+el.name+"</option>";
    });

    $("#city").html(str);
}

//修改银行卡信息
function editBankCard() {
    var real_name      = $("#real_name").val();
    var bank_id        = $("#bank_id").val();
    var bank_name      = $("#bank_id").find("option:selected").text();
    var branch         = $("#branch").val();
    var province       = $("#province").val();
    var city           = $("#city").val();
    var bank_number    = $("#bank_number").val();
    var re_bank_number = $("#re_bank_number").val();
    var id_card_number = $("#id_card_number").val();
    var mobile         = $("#mobile").val();
    var userId          = "<?php echo !empty($userList['userAccountList']['user_id']) ? htmlentities($userList['userAccountList']['user_id']) : 0; ?>";

    if (real_name == "") {
        layer.msg("请填写持卡人姓名", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (bank_name == "") {
        layer.msg("请填写开户行名称", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (branch == "") {
        layer.msg("开户支行名称", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (isNaN(bank_number)) {
        layer.msg("请填写正确的银行卡号", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (isNaN(bank_number)) {
        layer.msg("请填写正确的银行卡号", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (id_card_number == '') {
        layer.msg("请填写身份证号", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    }
    if (mobile == "") {
        layer.msg("请填写银行预留电话", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        return;
    } else {
        $.post("<?php echo url('stock/user/edit_bank_card'); ?>", {
            user_id: userId,
            real_name: real_name,
            bank_id: bank_id,
            bank_name: bank_name,
            branch: branch,
            province: province,
            city: city,
            bank_number: bank_number,
            id_card_number: id_card_number,
            mobile: mobile
        }, function (data) {
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
}

// 删除银行卡
function delBankNUm(userId) {
    layer.confirm('确定要删除银行卡吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        if (userId == "") {
            layer.msg("用户id不能为空", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        }else{
            $.post("<?php echo url('stock/user/delete_bank_card'); ?>", {
                user_id: userId,
            }, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.href = "<?php echo url('user_detail', ['id' =>$id]); ?>";
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }
        return false;
    }, null);

}

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
            $("#brokerID").html(str);
        }
    });
}

$(function () {
    $("#editForm").submit(function () {
        var changeMoney = parseFloat($("#changeMoney").val());
        var ctype       = $("#ctype").val();
        var remark      = $("#remark").val();
        var userId      = "<?php echo !empty($userList['userAccountList']['user_id']) ? htmlentities($userList['userAccountList']['user_id']) : 0; ?>";
        if (ctype == "") {
            layer.msg("请选择调整类型", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        }
        if (isNaN(changeMoney)) {
            layer.msg("请填写正确的账户资金金额", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        }
        if (remark == "") {
            layer.msg("请填写备注信息", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        } else {
            $.post("<?php echo url('stock/userAccount/changeWallet'); ?>", {
                user_id: userId,
                change_money: changeMoney,
                remark: remark,
                ctype:ctype,
            }, function (data) {
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
    });

    $("#editStrategyForm").submit(function () {
        var changeStrategy = parseFloat($("#changeStrategy").val());
        var ctypeStrategy  = $("#ctypeStrategy").val();
        var remarkStrategy = $("#remarkStrategy").val();
        var userId         = "<?php echo !empty($userList['userAccountList']['user_id']) ? htmlentities($userList['userAccountList']['user_id']) : 0; ?>";
        if (ctypeStrategy == "") {
            layer.msg("请选择调整类型", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        }
        if (isNaN(changeStrategy)) {
            layer.msg("请填写正确的账户策略金金额", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        }
        if (remarkStrategy == "") {
            layer.msg("请填写备注信息", {icon: 2, time: 1500, shade: .3, shadeClose: true});
            return;
        } else {
            $.post("<?php echo url('stock/userAccount/changeStrategy'); ?>", {
                user_id: userId,
                change_money: changeStrategy,
                remark: remarkStrategy,
                ctype:ctypeStrategy,
            }, function (data) {
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
    });

    // 转移用户
    $("#changePwd").on("click", function () {
        var password = $("#password").val();
        var rePassword = $("#rePassword").val();
        var userID = "<?php echo !empty($userList['userAccountList']['user_id']) ? htmlentities($userList['userAccountList']['user_id']) : 0; ?>";

        if (!password) {
            layer.msg("请填写您的密码", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if(!rePassword) {
            layer.msg("请填写确认密码", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/user/change_pwd'); ?>", {password:password,rePassword:rePassword, userID:userID}, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }
    });

    // 转移用户
    $("#changeBroker").on("click", function () {
        console.log("dddd");
        var agentID = $("#agentID").val();
        var brokerID = $("#brokerID").val();
        var userID = "<?php echo !empty($userList['userAccountList']['user_id']) ? htmlentities($userList['userAccountList']['user_id']) : 0; ?>";

        if (!agentID) {
            layer.msg("请选择代理商", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if(!brokerID) {
            layer.msg("请选择经纪人", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/user/change_broker'); ?>", {userID:userID,agentID:agentID, brokerID:brokerID}, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }
    });
});
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