<?php /*a:2:{s:65:"/www/wwwroot/stock.ecou.vip/application/dash/view/slide/edit.html";i:1553454952;s:66:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/layout.html";i:1665562605;}*/ ?>
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
    
<link rel="stylesheet" href="/static/dash/dropify/dist/css/demo.css">
<link rel="stylesheet" href="/static/dash/dropify/dist/css/dropify.min.css">
<style>
    body{line-height: 21px;font-weight:400}
    h1::after{height: 0px}
    .col-xs-1, .col-xs-2, .col-xs-3, .col-xs-4, .col-xs-5, .col-xs-6, .col-xs-7, .col-xs-8, .col-xs-9, .col-xs-10, .col-xs-11, .col-xs-12, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12{
        padding-bottom:0px;
    }
</style>
<div class="content-wrapper">
    <section class="content-header">
        <h1 style="text-align: left">
            <?php echo !empty($id) ? '编辑幻灯片' : '添加幻灯片'; ?>
            <small>Home</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo url('index/index'); ?>"><i class="fa fa-home"></i> 首页</a></li>
            <li><a href="#">内容管理</a></li>
            <li class="active">添加幻灯片</li>
        </ol>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-info">
                    <form id="addForm" action="" method="post" class="form-horizontal">
                        <div class="box-body">
                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">幻灯片标题</label>
                                <div class="col-sm-8">
                                    <input class="form-control" name="title" id="title" value="<?php echo !empty($editInfo['title']) ? htmlentities($editInfo['title']) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">幻灯片上传</label>
                                <div class="col-sm-8" id="wechat_service">
                                    <label for="litimg"></label>
                                    <input type="file" name="litimg" id="litimg" class="dropify-event" data-default-file="<?php echo !empty($editInfo['litimg']) ? htmlentities($editInfo['litimg']) : ''; ?>" value="<?php echo !empty($editInfo['litimg']) ? htmlentities($editInfo['litimg']) : ''; ?>"/>
                                </div>
                            </div>

                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">外部链接</label>
                                <div class="col-sm-8">
                                    <input class="form-control" name="outlink" id="outlink" value="<?php echo !empty($editInfo['outlink']) ? htmlentities($editInfo['outlink']) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-group col-sm-8">
                                <label class="col-sm-2 control-label">幻灯片内容</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control"  id="intro" name="intro"><?php echo !empty($editInfo['intro']) ? htmlentities($editInfo['intro']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="col-sm-8">
                                <div class="col-sm-offset-2">
                                    <input type="hidden" name="id" value="<?php echo !empty($editInfo['id']) ? htmlentities($editInfo['id']) : 0; ?>"/>
                                    <button type="submit" class="btn btn-primary">提交</button>
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

<!-- 页面自定义JS内容部分 -->

<script src="/static/dash/dropify/dist/js/dropify.min.js"></script>
<script src="/static/dash/lib/laydate/laydate.js"></script>
<script>
    $(document).ready(function () {
        // Used events
        var drEvent = $('#litimg').dropify();

        drEvent.on('dropify.beforeClear', function (event, element) {
            if (confirm("您确定要幻灯图片 \"" + element.file.name + "\" 删除吗?")) {
                var service = "<?php echo !empty($editInfo['litimg']) ? htmlentities($editInfo['litimg']) : ''; ?>";
                if (service) {
                    deleteQrcode(service);
					return false;
                }
            }
        });

        drEvent.on('dropify.afterClear', function (event, element) {
            alert('File deleted');
        });

        drEvent.on('dropify.errors', function (event, element) {
            console.log('Has Errors');
        });

        var drDestroy = $('#input-file-to-destroy').dropify();
        drDestroy = drDestroy.data('dropify')
        $('#toggleDropify').on('click', function (e) {
            e.preventDefault();
            if (drDestroy.isDropified()) {
                drDestroy.destroy();
            } else {
                drDestroy.init();
            }
        })

    });

    function  deleteQrcode(file_path) {
        $.ajax({
            url: "<?php echo url('stock/slide/deleteSlidePic'); ?>",
            type: 'POST',
            data: {file_path: file_path},
            dataType: 'json',
            success: function (data) {
                if (data.code == 1) {
                    layer.msg(data.msg, {icon: 1, time: 3500, shade: .3, shadeClose: true});
                    window.location.reload();
                } else {
                    layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                }
            },
            error: function (err) {
            }
        });
    }
</script>
<script type="text/javascript">
    $(function () {
        $("#addForm").submit(function(){
            var title   = $.trim($("input[name='title']").val());
            var id      ="<?php echo !empty($id) ? htmlentities($id) : 0; ?>";
            if(title == "") {
                layer.msg("请填写幻灯片标题", {icon:2, time:1500, shade:.3, shadeClose:true});
                return false;
            }else {
                $.ajax({
                    url: "<?php echo url('stock/slide/saveSlide'); ?>",
                    type: 'POST',
                    cache: false,
                    data: new FormData($("#addForm")[0]),
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        if (data.code == 1) {
                            layer.msg(data.msg, {icon: 1, time: 1500, shade: .3, shadeClose: true});
                            window.location.href = "<?php echo url('index'); ?>";
                            return false;
                        } else {
                            layer.msg(data.msg, {icon: 2, time: 1500, shade: .3, shadeClose: true});
                        }
                    },
                    error: function (err) {
                    }
                });

                return false;
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