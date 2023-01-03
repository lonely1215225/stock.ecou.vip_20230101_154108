<?php /*a:2:{s:76:"/www/wwwroot/stock.ecou.vip/application/dash/view/user/offline_transfer.html";i:1568698792;s:70:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/pop_layout.html";i:1568698458;}*/ ?>
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
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div>
    <!--  内容主体 -->
    
<section class="content">
    <table class="table table-middle">
        <tbody>
        <tr>
            <td width="60">转入方式</td>
            <td width="350">
                <?php echo htmlentities($payCompanyList[$oneRecharge['pay_company_id']]['name']); ?>(<?php echo htmlentities($payCompanyList[$oneRecharge['pay_company_id']]['pay_type']); ?>)
            </td>
        </tr>
        <tr>
            <td width="60">转入账户</td>
            <td width="350">
                <?php echo $oneRecharge['offline_to_account']; ?>
            </td>
        </tr>
        <tr>
            <td width="50">付款方名称</td>
            <td width="350">
                <?php echo htmlentities($oneRecharge['offline_name']); ?>
            </td>
        </tr>
        <tr>
            <td width="50">充值金额</td>
            <td width="350">
                <?php echo htmlentities($oneRecharge['money']); ?>
            </td>
        </tr>
        <tr>
            <td width="60">转账凭证</td>
            <td width="350" id="layer-photos-demo">
                <img layer-src="<?php echo htmlentities($oneRecharge['offline_img']); ?>" src="<?php echo htmlentities($oneRecharge['offline_img']); ?>" height="70">
            </td>
        </tr>
        <tr>
            <td width="60">实际到账金额</td>
            <td width="350">
                <input type="text" value="<?php echo htmlentities($oneRecharge['money']); ?>" name="money" id="money" class="form-control">
            </td>
        </tr>
        </tbody>
    </table>
</section>


</div>

<!-- Bootstrap 3.3.7 -->
<script src="/static/dash/AdminLTE/components/bootstrap/js/bootstrap.min.js"></script>
<!-- Slimscroll -->
<!--<script src="/static/dash/AdminLTE/components/jquery-slimscroll/jquery.slimscroll.min.js"></script>-->
<script src="https://cdn.bootcss.com/jQuery-slimScroll/1.3.8/jquery.slimscroll.js"></script>
<!-- FastClick -->
<script src="/static/dash/AdminLTE/components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="/static/dash/AdminLTE/dist/js/adminlte.min.js"></script>


<!-- 页面自定义JS内容部分 -->

<script type="text/javascript">
$(function () {
    document.getElementById("money").focus();
});
var callbackdata = function () {
    var money         = $("#money").val();
    if(money == ""){
        layer.msg("请填写实际到账金额", {icon:2, time:1500, shade:.3, shadeClose:true});
    }else{
        var data = {
            money:money
        };
        return data;
    }
};

layer.photos({
    photos: '#layer-photos-demo'
    ,anim: 5
});

</script>


</body>
</html>