<?php
namespace app\stock\controller;

use app\common\model\Notice;
use think\App;
use think\Db;
use util\NoticeRedis;

class NoticeController extends BaseController
{

    protected $noticeModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->noticeModel = new Notice();
    }

    /**
     * 获取所有公告列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取所有公告列表
        $noticeList = $this->noticeModel->field('id,state,title,content')->paginate();

        return $noticeList ? $this->message(1, '', $noticeList) : $this->message(0, '');
    }

    /**
     * 公告编辑页面
     * - 添加、修改
     *
     * @return array|\think\response\Json
     */
    public function saveNotice()
    {
        // 公告添加编辑
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['title']        = input('post.title', 0, [FILTER_SANITIZE_STRING, 'trim']);
        $data['content']      = input('post.content', '');
        $data['publish_time'] = time();
        $id                   = input('id', '', FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Notice.notice_edit');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        $data['state'] = input('post.state', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            $noticeInfo = $this->noticeModel->isUpdate(true)->save($data);
        } else {
            $noticeInfo = $this->noticeModel->save($data);
        }

        return $noticeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 根据id获取公告内容
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNotice()
    {
        // 获取公告id
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            $notice = $this->noticeModel->where('id', $id)->find();

            return $notice ? $this->message(1, '', $notice) : $this->message(0, '没有找到公告');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     *  删除公告
     *
     * @return mixed
     * @throws \Exception
     */
    public function noticeDel()
    {
        if (!$this->request->isPost()) return null;

        // 获取要删除的栏目Id
        $data['id'] = input('post.id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 删除数据
        $delInfo = $this->noticeModel->where('id', $data['id'])->delete();

        return $delInfo ? $this->message(1, '删除成功') : $this->message(0, '删除失败');
    }

    /**
     * 更改是否发布状态
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeState()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 更新状态
        $changeInfo = $this->noticeModel->isUpdate(true)->save([
                'id'    => $id,
                'state' => Db::raw('NOT state'),
            ]
        );

        // 更新缓存数据
        NoticeRedis::cacheNoticeData($id);

        return $changeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

}
