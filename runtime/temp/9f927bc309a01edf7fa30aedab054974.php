<?php /*a:2:{s:76:"/www/wwwroot/stock.ecou.vip/application/dash/view/risk/updata_all_stock.html";i:1653922924;s:70:"/www/wwwroot/stock.ecou.vip/application/dash/view/base/pop_layout.html";i:1568698458;}*/ ?>
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
    <div class="content-wrapper">
        <div class="box box-info">
            <div class="box-header with-border">
                <div class="col-sm-12 no-padding">
                    <div class="search-box">
                        <label class="text-right">获取到股票数量：</label>
                        <div class="search-body"><?php echo htmlentities($count); ?></div>
                    </div>
                    <div class="search-box">
                        <label class="text-right">更新现有数据：</label>
                        <label><input type="checkbox" name="is_updata" id="is_updata" value="0"></label>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-middle table-center">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>股票编号</th>
                        <th>股票名称</th>
                        <th>证券公司</th>
                        <th>简称</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($items as $k=>$v): ?>
                    <tr>
                        <td><?php echo htmlentities($k+1); ?></td>
                        <td><?php echo htmlentities($v['code']); ?></td>
                        <td><?php echo htmlentities($v['name']); ?></td>
                        <td><?php echo htmlentities($v['market']); ?></td>
                        <td><?php echo htmlentities($v['pinyin']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
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

<script>
    var callbackdata = function () {
        var is_updata = $("#is_updata").is(":checked") ? 1 : 0;
        return is_updata;
    }
</script>


</body>
</html>