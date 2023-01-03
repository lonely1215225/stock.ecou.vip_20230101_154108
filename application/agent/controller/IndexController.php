<?php
namespace app\agent\controller;

class IndexController extends BaseController
{

    /**
     * 管理控制台首页
     *
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

}
