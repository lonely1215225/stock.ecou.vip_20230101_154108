<?php /*a:2:{s:74:"/www/wwwroot/stock.ecou.vip/application/agent/view/broker/edit_broker.html";i:1542262726;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
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
    
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?php echo !empty($brokerInfo) ? '编辑' : '添加'; ?>经纪人
            <small></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">经纪人管理</a></li>
            <li class="active">编辑经纪人</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <form id="agentForm" action="" class="form-horizontal">
                    <div class="box box-info">
                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">用户名</label>
                                <div class="input-group col-sm-4">
                                    <input type="text" name="username" id="username" value="<?php echo !empty($brokerInfo['username']) ? htmlentities($brokerInfo['username']) : ''; ?>"
                                           class="form-control" <?php echo isset($brokerInfo) ?'disabled':''; ?>>
                                </div>
                            </div>
                        </div>

                        <?php if(!isset($brokerInfo)): ?>
                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">密码</label>
                                <div class="input-group col-sm-4">
                                    <input type="text" name="password" id="password" value="" class="form-control">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">代理/经济人名称</label>
                                <div class="input-group col-sm-4">
                                    <input type="text" name="org_name" id="orgName" value="<?php echo !empty($brokerInfo['org_name']) ? htmlentities($brokerInfo['org_name']) : ''; ?>"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">手机号</label>
                                <div class="input-group col-sm-4">
                                    <input type="text" name="mobile" id="mobile" value="<?php echo !empty($brokerInfo['mobile']) ? htmlentities($brokerInfo['mobile']) : ''; ?>"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">分成比例</label>
                                <div class="input-group col-sm-4">
                                    <input name="commission_rate" id="commissionRate" value="<?php echo !empty($brokerInfo['commission_rate']) ? htmlentities($brokerInfo['commission_rate']) : ''; ?>" class="form-control" placeholder="最高分成比例为<?php echo htmlentities($selfInfo['commission_rate']); ?>%">
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">是否禁止登陆</label>
                                <div class="input-group col-sm-4">
                                    <select id="isDenyLogin" name="is_deny_login" class="form-control">
                                        <option value="0" <?php echo isset($brokerInfo) &&
                                                0==$brokerInfo['is_deny_login']?'selected':''; ?>>否
                                        </option>
                                        <option value="1" <?php echo isset($brokerInfo) &&
                                                1==$brokerInfo['is_deny_login']?'selected':''; ?>>是
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">是否拒绝提现</label>
                                <div class="input-group col-sm-4">
                                    <select id="isDenyCash" name="is_deny_cash" class="form-control">
                                        <option value="0" <?php echo isset($brokerInfo) &&
                                                0==$brokerInfo['is_deny_cash']?'selected':''; ?>>否
                                        </option>
                                        <option value="1" <?php echo isset($brokerInfo) &&
                                                1==$brokerInfo['is_deny_cash']?'selected':''; ?>>是
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">备注</label>
                                <div class="input-group col-sm-4">
                                    <textarea class="form-control" name="remark" id="remark" rows="3"><?php echo !empty($brokerInfo['remark']) ? htmlentities($brokerInfo['remark']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="col-sm-8">
                                <div class="col-sm-offset-2">
                                    <button type="submit" class="btn btn-primary">保存</button>
                                </div>
                            </div>
                        </div>

                    </div>
                    <input type="hidden" id="selfCommissionRate" name="self_commission_rate" value="<?php echo htmlentities($selfInfo['commission_rate']); ?>">
                </form>
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
    $("#agentForm").submit(function () {
        var id = "<?php echo !empty($brokerInfo['id']) ? htmlentities($brokerInfo['id']) : 0; ?>";
        var username = $("#username").val();
        var password = id == 0 ? $("#password").val() : '';
        var orgName = $("#orgName").val();
        var mobile = $("#mobile").val();
        var commissionRateold = $("#commissionRate").val();
        var dot = commissionRateold.indexOf(".");
        var dotCnt=0;
        if(dot !== -1){
            dotCnt = commissionRateold.substring(dot+1,commissionRateold.length);
        }
        var commissionRate = parseFloat(commissionRateold);
        var isDenyLogin = $("#isDenyLogin").val();
        var isDenyCash = $("#isDenyCash").val();
        var remark = $("#remark").val();
        var selfCommissionRate=$("#selfCommissionRate").val();
        if (username == '') {
            layer.msg("请填写用户名称", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if (id == 0 && password == '') {
            layer.msg("请填写用户密码", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if (orgName == '') {
            layer.msg("请填写代理/经纪人名称", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if (mobile == '') {
            layer.msg("请填手机号", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if (dotCnt.length > 2){
            layer.msg("最多保留两位小数", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        }else if (commissionRate === '') {
            layer.msg("请填写分成比例", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else if(commissionRate > selfCommissionRate){
            layer.msg("最高分成比例为"+selfCommissionRate+"%", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        }
        else if (isNaN(commissionRate) || commissionRate<0 || commissionRate>100) {
            layer.msg("请填写正确的分成比例", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/broker/saveBroker'); ?>", {
                id: id,
                username: username,
                password: password,
                org_name: orgName,
                mobile: mobile,
                commission_rate: commissionRate,
                is_deny_login: isDenyLogin,
                is_deny_cash: isDenyCash,
                remark: remark
            }, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.href = "<?php echo url('index'); ?>";
                    });
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            });
        }

        return false;
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
</script>
</body>
</html>