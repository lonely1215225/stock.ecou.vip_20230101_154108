<?php /*a:1:{s:70:"/www/wwwroot/stock.ecou.vip/application/agent/view/passport/index.html";i:1539575650;}*/ ?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台登陆</title>
    <link rel="stylesheet" href="/static/dash/AdminLTE/components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="/static/dash/AdminLTE/components/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/static/dash/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="demo form-bg" style="padding: 60px 0 80px;">
        <div class="container">
            <div class="row">
                <div class="col-md-offset-3 col-md-6">
                    <form id="loginForm" class="form-horizontal">
                        <span class="heading">代理商后台登录</span>
                        <div class="form-group">
                            <input type="text" class="form-control" id="username" name="username" placeholder="用户名">
                            <i class="fa fa-user"></i>
                        </div>
                        <div class="form-group help">
                            <input type="password" class="form-control" id="password" name="password" placeholder="密&emsp;码">
                            <i class="fa fa-lock"></i>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-default">登录</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/static/dash/lib/jquery-3.3.1.min.js"></script>
<script src="/static/dash/lib/layer/layer.js"></script>
<script>
$(function () {
    $("#loginForm").submit(function () {
        var username = $.trim($("#username").val());
        var password = $("#password").val();

        if(username == "") {
            layer.msg("请输入用户名");
        } else if(password == "") {
            layer.msg("请输入密码");
        } else {
            $.post("<?php echo url('stock/auth/agentSignIn'); ?>", {username:username, password:password}, function (data) {
                if(data.code == 1) {
                    window.location.href = "<?php echo url('index/index'); ?>";
                } else {
                    layer.msg(data.msg);
                }
            });
        }

        return false;
    });
});
</script>
</body>
</html>