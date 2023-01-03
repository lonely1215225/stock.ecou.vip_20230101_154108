<?php /*a:2:{s:72:"/www/wwwroot/stock.ecou.vip/application/agent/view/my/bind_bankcard.html";i:1541837142;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
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
            绑定银行卡
            <small>Home</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">我的</a></li>
            <li><a href="#">绑定银行卡</a></li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <form id="editForm" action="" method="post" class="form-horizontal">
                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">持卡人</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="real_name" id="realName" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">身份证号</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="id_card_number" id="idCardNumber" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">手机号码</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="mobile" id="mobile" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">开户银行</label>
                                <div class="col-sm-8">
                                    <label>
                                        <select name="bank_id" id="bankId" class="form-control">
                                          <option value="">请选择</option>
                                          <?php foreach($banksList as $bk=>$bv): ?>
                                            <option value="<?php echo htmlentities($bv['id']); ?>"><?php echo htmlentities($bv['bank_name']); ?></option>
                                          <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">开户所在省市</label>
                                <div class="col-sm-8">
                                    <label>
                                        <select name="province" id="province" class="form-control" onchange="showCity()">
                                            <option value="">请选择</option>
                                            <?php foreach($cityInfo as $k=>$v): ?>
                                            <option value="<?php echo htmlentities($v['id']); ?>" data-key="<?php echo htmlentities($k); ?>"><?php echo htmlentities($v['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label id="showCity">

                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">开户行支行</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="branch" id="branch" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">银行卡号</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="bank_number" id="bankNumber" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">确认银行卡号</label>
                                <div class="col-sm-8">
                                    <label>
                                        <input type="text" class="form-control" name="confirm_bank_number" id="confirmBankNumber" value="">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="col-sm-8">
                                <div class="col-sm-offset-2">
                                    <button type="submit" class="btn btn-primary">提交认证</button>
                                </div>
                            </div>
                        </div>
                    </form>
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
function showCity() {
    var k= $("#province").find("option:selected").attr("data-key");
    var cityAll=<?php echo json_encode($cityInfo); ?>;
    var cityArr=cityAll[k];
    var str = "<select id=\"city\" name=\"city\" class=\"form-control\">";
    $.each(cityArr.cities, function (i, el) {
        str +="<option value='"+el.id+"' selected>"+el.name+"</option>";
    });
    str +="</select>";
    $("#showCity").html(str);
}
$(function () {
    $("#editForm").submit(function () {
        var realName          = $("#realName").val();
        var idCardNumber      = $("#idCardNumber").val();
        var mobile            = $("#mobile").val();
        var bankId          = $("#bankId").val();
        var province          = $("#province").val();
        var city              = $("#city").val();
        var branch            = $("#branch").val();
        var bankNumber        = $("#bankNumber").val();
        var confirmBankNumber = $("#confirmBankNumber").val();
        if (realName == "") {
            layer.msg("请填持卡人姓名", {icon: 2, time: 1500, shade: .3, shadeClose: true});
        } else {
            $.post("<?php echo url('stock/orgBankCard/bindBankCard'); ?>", {
                real_name: realName,
                id_card_number: idCardNumber,
                mobile: mobile,
                bank_id: bankId,
                province: province,
                city:city,
                branch: branch,
                bank_number: bankNumber,
                confirm_bank_number:confirmBankNumber,
            }, function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true}, function () {
                        window.location.href = "<?php echo url('bind_bankcard'); ?>";
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