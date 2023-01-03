<?php /*a:2:{s:66:"/www/wwwroot/stock.ecou.vip/application/agent/view/user/index.html";i:1551262888;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>代理商后台</title>

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
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <header class="main-header">
        <!-- Logo -->
        <a href="index.html" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini"><b>A</b>LT</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">代理商后台</span>
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

                <li class="treeview <?php echo $controller=='My' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>我的</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='modify_password' ? 'active' : ''; ?>"><a href="<?php echo url('my/modify_password'); ?>"><i class="fa fa-circle-o"></i> 修改密码</a></li>
                        <li class="<?php echo $action=='bind_bankcard' ? 'active' : ''; ?>"><a href="<?php echo url('my/bind_bankcard'); ?>"><i class="fa fa-circle-o"></i> 绑定银行卡</a></li>
                        <li class="<?php echo $action=='do_withdraw' ? 'active' : ''; ?>"><a href="<?php echo url('my/do_withdraw'); ?>"><i class="fa fa-circle-o"></i> 提现申请</a></li>
                        <li class="<?php echo $action=='account_log' ? 'active' : ''; ?>"><a href="<?php echo url('my/account_log'); ?>"><i class="fa fa-circle-o"></i> 资金明细</a></li>
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
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Order' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-edit"></i> <span>订单管理</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='position' ? 'active' : ''; ?>"><a href="<?php echo url('order/position'); ?>"><i class="fa fa-circle-o"></i> 持仓列表</a></li>
                        <li class="<?php echo $action=='order' ? 'active' : ''; ?>"><a href="<?php echo url('order/order'); ?>"><i class="fa fa-circle-o"></i> 委托列表</a></li>
                        <li class="<?php echo $action=='traded' ? 'active' : ''; ?>"><a href="<?php echo url('order/traded'); ?>"><i class="fa fa-circle-o"></i> 成交明细</a></li>
                        <li class="<?php echo $action=='close_position' ? 'active' : ''; ?>"><a href="<?php echo url('order/close_position'); ?>"><i class="fa fa-circle-o"></i> 平仓结算</a></li>
                    </ul>
                </li>

                <li class="treeview <?php echo $controller=='Broker' ? 'active' : ''; ?>">
                    <a href="#">
                        <i class="fa fa-user"></i> <span>经纪人</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('broker/index'); ?>"><i class="fa fa-circle-o"></i> 经纪人列表</a></li>
                        <li class="<?php echo $action=='withdraw_log' ? 'active' : ''; ?>"><a href="<?php echo url('broker/withdraw_log'); ?>"><i class="fa fa-circle-o"></i> 经济人提现申请</a></li>
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
                        <li class="<?php echo $action=='index' ? 'active' : ''; ?>"><a href="<?php echo url('income/index'); ?>"><i class="fa fa-circle-o"></i> 佣金明细</a></li>
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
</style>
<div class="content-wrapper">
    <section class="content-header">
        <h1>用户列表</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">用户管理</a></li>
            <li class="active">用户列表</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <form id="searchInfo" action="<?php echo url('index'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">手机号</label>
                                    <div class="search-body"><input class="form-control" type="text" id="mobile" name="mobile" value="<?php echo isset($mobile)?$mobile:''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">经济人</label>
                                    <div class="search-body">
                                        <select id="brokerId" name="broker_id" class="form-control">
                                            <option value="">请选择</option>
                                            <?php foreach($brokerInfo as $bk=>$bv): ?>
                                            <option value="<?php echo htmlentities($bk); ?>" <?php echo isset($broker_id) && $bk==$broker_id?'selected':''; ?>><?php echo htmlentities($bv); ?></option>
                                            <?php endforeach; ?>
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

                    <div class="box-body no-padding">
                        <table class="table table-bordered table-middle table-center">
                            <thead>
                            <tr class="tj">
                                <td>总计</td>
                                <td><?php echo !empty($userTotal['userTotal']) ? htmlentities($userTotal['userTotal']) : ''; ?></td>
                                <td></td>
                                <td><?php echo !empty($userTotal['totalAccount']['totalwallet']) ? htmlentities($userTotal['totalAccount']['totalwallet']) : 0; ?></td>
                                <td><?php echo !empty($userTotal['totalAccount']['totalstrategy']) ? htmlentities($userTotal['totalAccount']['totalstrategy']) : 0; ?></td>
                                <td><?php echo !empty($userTotal['totalAccount']['frozen']) ? htmlentities($userTotal['totalAccount']['frozen']) : 0; ?></td>
                                <td><?php echo !empty($userTotal['totalSpal']['totalspal']) ? htmlentities($userTotal['totalSpal']['totalspal']) : 0; ?>
                                <td><?php echo !empty($userTotal['totalAccount']['deposit']) ? htmlentities($userTotal['totalAccount']['deposit']) : 0; ?></td>
                                <td><?php echo !empty($userTotal['totalAccount']['totalrecharge']) ? htmlentities($userTotal['totalAccount']['totalrecharge']) : 0; ?></td>
                                <td><?php echo !empty($userTotal['successWithdraw']['money']) ? htmlentities($userTotal['successWithdraw']['money']) : 0; ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>用户ID</th>
                                <th>姓名</th>
                                <th>手机号</th>
                                <th>账户资金</th>
                                <th>策略金余额(含冻结)</th>
                                <th>冻结资金</th>
                                <th>平仓结算盈亏</th>
                                <th>持仓总保证金</th>
                                <th>累计充值</th>
                                <th>累计提现</th>
                                <th>银行卡</th>
                                <th>经纪人</th>
                                <th>操作</th>
                            </tr>
                            <?php if(isset($userList['userList'])): foreach($userList['userList'] as $k=>$nl): ?>
                            <tr>
                                <td><?php echo htmlentities($nl['id']); ?></td>
                                <td><?php echo !empty($nl['real_name']) ? htmlentities($nl['real_name']) : '未实名'; ?></td>
                                <td><?php echo htmlentities($nl['mobile']); ?></td>
                                <td>
                                    <a  href="<?php echo url('wallet_log', ['mobile' => $nl['mobile']]); ?>" target="_blank" style="cursor: pointer;border-bottom:1px dotted #333333;" title="点击打开用户钱包流水">
                                        <font color="#333333">
                                            <?php echo !empty($userList['userAccountList'][$nl['id']]['wallet_balance']) ? htmlentities($userList['userAccountList'][$nl['id']]['wallet_balance']) : ''; ?>
                                        </font>
                                    </a>
                                </td>
                                <td>
                                    <a  href="<?php echo url('strategy_log', ['mobile' => $nl['mobile']]); ?>" target="_blank" style="cursor: pointer;border-bottom:1px dotted #333333;" title="点击打开用户钱包流水">
                                        <font color="#333333">
                                            <?php echo !empty($userList['userAccountList'][$nl['id']]['strategy_balance']) ? htmlentities($userList['userAccountList'][$nl['id']]['strategy_balance']) : ''; ?>
                                        </font>
                                    </a>
                                </td>
                                <td><?php echo !empty($userList['userAccountList'][$nl['id']]['frozen']) ? htmlentities($userList['userAccountList'][$nl['id']]['frozen']) : ''; ?></td>
                                <td><?php echo !empty($userList['spalList'][$nl['id']]) ? htmlentities($userList['spalList'][$nl['id']]) : 0; ?></td>
                                <td><?php echo !empty($userList['userAccountList'][$nl['id']]['deposit']) ? htmlentities($userList['userAccountList'][$nl['id']]['deposit']) : ''; ?></td>
                                <td><?php echo !empty($userList['userAccountList'][$nl['id']]['total_recharge']) ? htmlentities($userList['userAccountList'][$nl['id']]['total_recharge']) : ''; ?></td>
                                <td>
                                    已提现:<?php echo !empty($userList['totalWithdraw'][$nl['id']]) ? htmlentities($userList['totalWithdraw'][$nl['id']]) : 0; ?>
                                </td>
                                <td><?php echo !empty($nl['is_bound_bank_card']) ? '已绑定' : '未绑定'; ?></td>
                                <td><?php echo !empty($userList['brokerInfo'][$nl['broker_id']]) ? htmlentities($userList['brokerInfo'][$nl['broker_id']]) : ''; ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary mb5" href="<?php echo url('user_detail', ['id' => $nl['id']]); ?>">详情</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="box-footer">
                        <div class="col-sm-12 no-padding"><?php echo $userList['userList']->render(); ?></div>
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
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!--<script src="/static/dash/AdminLTE/dist/js/pages/dashboard.js"></script>-->
<!-- AdminLTE for demo purposes -->
<!--<script src="/static/dash/AdminLTE/dist/js/demo.js"></script>-->

<!-- 页面自定义JS内容部分 -->



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
</script>
</body>
</html>