<?php
namespace app\agent\controller;

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

}
