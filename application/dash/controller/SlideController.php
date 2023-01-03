<?php
namespace app\dash\controller;

use app\stock\controller\SlideController as Slide;
use think\App;

class SlideController extends BaseController
{

    private $slide;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->slide = new Slide();
    }

    /**
     * 幻灯片列表页面
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取幻灯片列表
        $infoResult = $this->slide->getSlideList()->getData();
        $this->assign('slideList', $infoResult['data']);

        return $this->fetch();
    }

    /**
     * 幻灯片编辑页面
     * - 添加、修改
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        // 获取文章内容
        $editInfo = $this->slide->getSlide()->getData();
        if ($editInfo['data']) {
            $this->assign('id', $editInfo['data']['id']);
            $this->assign('editInfo', $editInfo['data']);
        }

        return $this->fetch();
    }


}
