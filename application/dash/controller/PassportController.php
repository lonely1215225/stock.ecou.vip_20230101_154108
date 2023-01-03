<?php
namespace app\dash\controller;

class PassportController extends BaseController
{

    /**
     * 显示登陆页面
     *
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 修改密码页面
     *
     * @return mixed
     */
    public function modify_password()
    {
        return $this->fetch();
    }

}
