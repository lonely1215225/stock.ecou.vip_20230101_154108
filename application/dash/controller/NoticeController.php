<?php
namespace app\dash\controller;

use app\stock\controller\NoticeController AS NoticeApi;
use think\App;

class NoticeController extends BaseController
{

    protected $noticeApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->noticeApi = new NoticeApi();
    }

    /**
     * 公告列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $noticeList = $this->noticeApi->index()->getData();
        $this->assign('noticeList', $noticeList['code'] == 1 ? $noticeList['data'] : '');

        return $this->fetch();
    }

    /**
     * 公告编辑页面
     * - 添加、修改
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notice_edit()
    {
        // 获取文章内容
        $editInfo = $this->noticeApi->getNotice()->getData();
        if ($editInfo['data']) {
            $this->assign('id', $editInfo['data']['id']);
            $this->assign('editInfo', $editInfo['data']);
        }

        return $this->fetch();
    }

}
