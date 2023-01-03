<?php /*a:2:{s:71:"/www/wwwroot/stock.ecou.vip/application/dash/view/broker/edit_code.html";i:1543900136;s:70:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/pop_layout.html";i:1568698458;}*/ ?>
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
    
<form id="ruleForm" action="" class="form-horizontal">
    <div class="box box-info">
        <div class="box-body">
            <div class="form-group col-sm-8">
                <label class="col-sm-2 control-label">新推广码</label>
                <div class="col-sm-4">
                    <input type="text" name="code" id="code" value="" class="form-control">
                </div>
            </div>
        </div>
    </div>
</form>

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
var callbackdata = function () {
    var code = $("#code").val();
    if(code == ''){
        layer.msg("推广码不能为空", {icon: 2, time: 1500, shade: .3, shadeClose: true});
    }else if(code.length != 4){
        layer.msg("推广码只能为4位", {icon: 2, time: 1500, shade: .3, shadeClose: true});
    } else{
        var data = {
            newcode:code
        };
        return data;
    }
}
</script>


</body>
</html>