<?php /*a:2:{s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/order/order.html";i:1551324128;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
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
.buy{color: #f66;}
.buy a{color: #f66;border-bottom:1px dotted #f66;}
.sell{color: #0384ec;}
.sell a{color: #0384ec;border-bottom:1px dotted #0384ec;}
 .tj{
     background-color: #ecf0f5;
     font-weight:bold;
 }
</style>
<link rel="stylesheet" href="/static/dash/lib/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css">
<script src="/static/dash/lib/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js"></script>
<div class="content-wrapper">
    <section class="content-header">
        <h1>委托列表</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">订单管理</a></li>
            <li class="active">委托列表</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <form id="searchInfo" action="<?php echo url('order'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">股票代码</label>
                                    <div class="search-body">
                                        <input class="form-control" id="stockCode" name="stock_code" type="text"  value="<?php echo !empty($stock_code) ? htmlentities($stock_code) : ''; ?>">
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">手机号</label>
                                    <div class="search-body">
                                        <input class="form-control" type="text" name="mobile" id="mobile"  value="<?php echo !empty($mobile) ? htmlentities($mobile) : ''; ?>">
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">资金账号</label>
                                    <div class="search-body">
                                        <input type="text" name="primary_account" id="primaryAccount" value="<?php echo !empty($primary_account) ? htmlentities($primary_account) : ''; ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">经济人</label>
                                    <div class="search-body">
                                        <select class="form-control" id="brokerId" name="broker_id" onchange="showBroker(this.value);">
                                            <option value="">请选择</option>
                                            <?php foreach($brokerList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo isset($broker_id) && $k==$broker_id?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">开始委托时间</label>
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
                                    <label class="text-right">结束委托时间</label>
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
                                    <label class="text-right">方向</label>
                                    <div class="search-body">
                                        <select class="form-control" name="direction" id="direction">
                                            <option value="">请选择</option>
                                            <?php foreach($tradeDirectionList as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($k); ?>" <?php echo isset($direction) && $k==$direction?'selected':''; ?>><?php echo htmlentities($v); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="search-box">
                                    <label class="text-right">持仓编号</label>
                                    <div class="search-body">
                                        <input class="form-control" name="order_position_id" id="orderPositionId" type="text"  value="<?php echo !empty($id) ? htmlentities($id) : ''; ?>">
                                    </div>
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
                            </div>
                            <div class="col-sm-12 no-padding">
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
                    <div id="div1" class="table-scroll-x-top" style="width: 100%" onscroll="scrollWindow(this,'scroll_tab')">
                        <div class="table-scroll-x-top2" style="width:130%"></div>
                    </div>
                    <div class="box-body no-padding table-scroll-x" id="scroll_tab"  onscroll="scrollWindow(this,'div1')">
                        <table class="table table-bordered table-middle table-center">
                            <thead>
                            <tr class="tj">
                                <td>总计</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><?php echo !empty($orderStatistic['totalvolume']) ? htmlentities($orderStatistic['totalvolume']) : '0'; ?></td>
                                <td><?php echo !empty($orderStatistic['totalsuccess']) ? htmlentities($orderStatistic['totalsuccess']) : '0'; ?></td>
                                <td colspan="4"></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>委托编号</th>
                                <th>持仓编号</th>
                                <th>用户信息</th>
                                <th>更新时间</th>
                                <th>股票详情</th>
                                <th>方向</th>
                                <th>委托时间</th>
                                <th>委托价格</th>
                                <th>委托数量</th>
                                <th>成交数量</th>
                                <th>合同编号</th>
                                <th>状态</th>
                                <th>月管理费</th>
                                <th>月管理到期时间</th>
                            </tr>
                            <?php foreach($orderList['orderlist'] as $k=>$v): ?>
                            <tr <?php echo $v['direction']=='buy' ? "class='buy'" : "class='sell'"; ?>>
                                <td><?php echo htmlentities($v['id']); ?></td>
                                <td><?php echo htmlentities($v['order_position_id']); ?></td>
                                <td>
                                    <a href="<?php echo url('user/index',['mobile'=>$v['mobile']]); ?>" target="_blank" style="cursor: pointer" title="点击打开用户详情">
                                        <font <?php echo $v['direction']=='buy' ? "class='buy'" : "class='sell'"; ?>>
                                            <?php echo htmlentities($v['mobile']); ?>
                                            <br>
                                            <?php echo !empty($v['real_name']) ? htmlentities($v['real_name']) : '未实名'; ?>
                                        </font>
                                    </a>
                                </td>
                                <td><?php echo htmlentities($v['update_time']); ?></td>
                                <td>
                                    <?php echo htmlentities($v['market']); ?><?php echo htmlentities($v['stock_code']); ?>
                                    <br>
                                    <?php echo !empty($orderList['stockInfo'][$v['market'].$v['stock_code']]) ? htmlentities($orderList['stockInfo'][$v['market'].$v['stock_code']]['stock_name']) : ''; ?>
                                    <br>
                                    <?php echo htmlentities($v['primary_account']); ?>
                                </td>
                                <td><?php echo !empty($v['direction']) ? htmlentities($tradeDirectionList[$v['direction']]) : ''; ?></td>
                                <td><?php echo htmlentities($v['create_time']); ?></td>
                                <td><?php echo htmlentities($v['price']); ?></td>
                                <td><?php echo htmlentities($v['volume']); ?></td>
                                <td><?php echo htmlentities($v['volume_success']); ?></td>
                                <td><?php echo htmlentities($v['order_sn']); ?></td>
                                <td><?php echo $v['cancel_state']=='success' ? htmlentities($cancelStateList[$v['cancel_state']]) : ($v['state']?$orderStateList[$v['state']]:''); ?></td>
                                <td><?php echo !empty($v['is_monthly']) ? '是' : '否'; ?></td>
                                <td><?php echo htmlentities($v['monthly_expire_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <div class="box-footer">
                        <div class="col-sm-12 no-padding"><?php echo $orderList['orderlist']->render(); ?></div>
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

<script>
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
</script>
</body>
</html>