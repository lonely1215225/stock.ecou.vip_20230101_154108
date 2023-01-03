<?php /*a:2:{s:76:"/www/wwwroot/stock.ecou.vip/application/agent/view/order/close_position.html";i:1543285040;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
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
        <h1>平仓结算</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">订单管理</a></li>
            <li class="active">平仓结算</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <form id="searchInfo" action="<?php echo url('close_position'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">股票代码</label>
                                    <div class="search-body"><input class="form-control" name="stock_code" id="stockCode" type="text"  value="<?php echo !empty($stock_code) ? htmlentities($stock_code) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">经济人</label>
                                    <div class="search-body">
                                        <select class="form-control" id="brokerId" name="broker_id">
                                            <option value="">请选择</option>
                                            <?php foreach($brokerList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo isset($broker_id) && $k==$broker_id?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">手机号</label>
                                    <div class="search-body"><input class="form-control" name="mobile" id="mobile" type="text"  value="<?php echo !empty($mobile) ? htmlentities($mobile) : ''; ?>"></div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">持仓编号</label>
                                    <div class="search-body"><input class="form-control" name="id" id="id" type="text"  value="<?php echo !empty($id) ? htmlentities($id) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">资金账号</label>
                                    <div class="search-body"><input class="form-control" name="primary_account" id="primaryAccount" type="text"  value="<?php echo !empty($primary_account) ? htmlentities($primary_account) : ''; ?>"></div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right"></label>
                                    <div class="search-body"><input type="submit" class="btn btn-primary" value="查找"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-bordered table-middle">
                            <tr>
                                <td>
                                    平仓总数:<?php echo !empty($closePositionStatistic['total']) ? htmlentities($closePositionStatistic['total']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                    总买入数量:<?php echo !empty($closePositionStatistic['sum_buy_volume']) ? htmlentities($closePositionStatistic['sum_buy_volume']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                    总卖出数量:<?php echo !empty($closePositionStatistic['sum_sell_volume']) ? htmlentities($closePositionStatistic['sum_sell_volume']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                    总买入市值:<?php echo !empty($closePositionStatistic['sum_buy_value_cost']) ? htmlentities($closePositionStatistic['sum_buy_value_cost']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                    总卖出市值:<?php echo !empty($closePositionStatistic['sum_sell_value_in']) ? htmlentities($closePositionStatistic['sum_sell_value_in']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                    总结算盈亏:<?php echo !empty($closePositionStatistic['s_pal']) ? htmlentities($closePositionStatistic['s_pal']) : '0'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-bordered table-middle table-center">
                            <thead>
                            <tr class="tj">
                                <td>总计</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><?php echo !empty($closePositionStatistic['sum_buy_volume']) ? htmlentities($closePositionStatistic['sum_buy_volume']) : '0'; ?></td>
                                <td><?php echo !empty($closePositionStatistic['sum_sell_volume']) ? htmlentities($closePositionStatistic['sum_sell_volume']) : '0'; ?></td>
                                <td></td>
                                <td></td>
                                <td><?php echo !empty($closePositionStatistic['sum_buy_value_cost']) ? htmlentities($closePositionStatistic['sum_buy_value_cost']) : '0'; ?></td>
                                <td><?php echo !empty($closePositionStatistic['sum_sell_value_in']) ? htmlentities($closePositionStatistic['sum_sell_value_in']) : '0'; ?></td>
                                <td><?php echo !empty($closePositionStatistic['s_pal']) ? htmlentities($closePositionStatistic['s_pal']) : '0'; ?></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>持仓编号</th>
                                <th>用户信息</th>
                                <th>股票详情</th>
                                <th>结算时间</th>
                                <th>买入数量</th>
                                <th>卖出数量</th>
                                <th>买入均价</th>
                                <th>卖出均价</th>
                                <th>总买入市值</th>
                                <th>总卖出市值</th>
                                <th>结算盈亏</th>
                            </tr>
                            <?php foreach($closePositionList['orderPositionList'] as $k=>$v): ?>
                            <tr>
                                <td><?php echo htmlentities($v['id']); ?></td>
                                <td>
                                    <a href="<?php echo url('user/index',['mobile'=>$v['mobile']]); ?>" target="_blank"  title="点击打开用户详情">
                                            <?php echo htmlentities($v['mobile']); ?><br><?php echo !empty($v['real_name']) ? htmlentities($v['real_name']) : '未实名'; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlentities($v['market']); ?><?php echo htmlentities($v['stock_code']); ?>
                                    <br>
                                    <?php echo !empty($closePositionList['stockInfo'][$v['market'].$v['stock_code']]) ? htmlentities($closePositionList['stockInfo'][$v['market'].$v['stock_code']]['stock_name']) : ''; ?>
                                    <br>
                                    <?php echo htmlentities($v['primary_account']); ?>
                                </td>
                                <td><?php echo !empty($v['s_time']) ? date('Y-m-d H : i:s',$v['s_time']):''; ?></td>
                                <td><?php echo htmlentities($v['sum_buy_volume']); ?></td>
                                <td><?php echo htmlentities($v['sum_sell_volume']); ?></td>
                                <td><?php echo htmlentities($v['position_price']); ?></td>
                                <td><?php echo htmlentities($v['s_cost_price']); ?></td>
                                <td><?php echo htmlentities($v['sum_buy_value_cost']); ?></td>
                                <td><?php echo htmlentities($v['sum_sell_value_in']); ?></td>
                                <td><?php echo htmlentities($v['s_pal']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <div class="box-footer">
                        <div class="col-sm-12 no-padding"><?php echo $closePositionList['orderPositionList']->render(); ?></div>
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