<?php /*a:2:{s:70:"/www/wwwroot/stock.ecou.vip/application/agent/view/order/position.html";i:1605761956;s:67:"/www/wwwroot/stock.ecou.vip/application/agent/view/base/layout.html";i:1551173342;}*/ ?>
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
                        <form id="searchInfo" action="<?php echo url('position'); ?>" method="get">
                            <div class="col-sm-12 no-padding">
                                <div class="search-box">
                                    <label class="text-right">股票代码</label>
                                    <div class="search-body"><input class="form-control" name="stock_code" id="stockCode" type="text"  value="<?php echo !empty($stock_code) ? htmlentities($stock_code) : ''; ?>"></div>
                                </div>
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
                    <div id="div1" class="table-scroll-x-top" onscroll="scrollWindow(this,'scroll_tab')">
                        <div class="table-scroll-x-top2" style="width: 140%"></div>
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
                                <td></td>
                                <td></td>
                                <td></td>
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
                                <td><?php echo !empty($positionStatistic['sum_strategy']) ? htmlentities($positionStatistic['sum_strategy']) : '0'; ?>
                                <td colspan="2"></td>
                            </tr>
                            </thead>
                            <tr>
                                <th>持仓编号</th>
                                <th>用户信息</th>
                                <th>股票详情</th>
                                <th><span class="text-red">持仓</span>/<span class="text-green">可卖</span>/<span class="text-blue">今仓</span></th>
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
                                <th>持仓收益</th>
                                <th>累计提走盈利</th>
                                <th>除权除息</th>
                                <th>停牌天数</th>
                                <th>管理费</th>
                                <th>保证金</th>
                                <th>策略金余额</th>
                                <th>月管理费</th>
                                <th>月管理到期时间</th>
                            </tr>
                            <?php foreach($positionList['orderPositionList'] as $k=>$v): ?>
                            <tr>
                                <td><?php echo htmlentities($v['id']); ?></td>
                                <td>
                                    <a href="<?php echo url('user/index',['mobile'=>$v['mobile']]); ?>" target="_blank" title="查看用户信息">
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
                                <td class="pal" id="pal<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>" data-flag="<?php echo htmlentities($v['id']); ?>" data-avg="<?php echo htmlentities($v['position_price']); ?>" data-num="<?php echo htmlentities($v['volume_position']); ?>" data-sum="<?php echo htmlentities($v['sum_sell_pal']); ?>"></td>
                                <td class="avg" id="avg<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>" data-flag="<?php echo htmlentities($v['id']); ?>" data-avg="<?php echo htmlentities($v['position_price']); ?>"></td>
                                <td id="sumpal<?php echo htmlentities($v['stock_code']); ?>_<?php echo htmlentities($positionList['securityType'][$v['market']]); ?>_<?php echo htmlentities($v['id']); ?>"></td>
                                <td><?php echo htmlentities($v['sum_back_profit']); ?></td>
                                <td>
                                    送转：<?php echo htmlentities($v['xrxd_volume']); ?>
                                    <br>
                                    配息：<?php echo htmlentities($v['xrxd_dividend']); ?>
                                </td>
                                <td><?php echo htmlentities($v['suspension_days']); ?></td>
                                <td><?php echo htmlentities($v['sum_management_fee']); ?></td>
                                <td>
                                    <?php echo htmlentities($v['sum_deposit']); ?>
                                </td>
                                <td><?php echo htmlentities($v['strategy']); ?></td>
                                <td><?php echo !empty($v['is_monthly']) ? '是' : '否'; ?></td>
                                <td><?php echo htmlentities($v['monthly_expire_date']); ?></td>
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
    });
    var wsUriPostion ="ws://47.114.91.240:21280";
    function createWebsocketPosition() {
        websockets = new WebSocket(wsUriPostion);
        websockets.onopen = function(evt) {
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