<?php
namespace app\index\controller;

use app\common\model\Notice;

class NoticeController extends BaseController
{

    /**
     * 公告列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = Notice::where('state', true)
            ->field('content')
            ->paginate();

        return $this->message(1, '', $list);
    }

    /**
     * 获取单个公告內容
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 获取文章内容
        $article = Notice::where('id', $id)->field('id,title,content,update_time')->find();

        if ($article) {
            // 图片最大宽度控制
            $style              = '<style>img{max-width:100% !important;}</style>';
            $article['content'] = $style . $article['content'];

            return $this->message(1, '', $article);
        } else {
            return $this->message(0, '没有找到公告');
        }
    }

    /**
     * 获取所有发布的公告內容
     * 用于跑马灯
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read_list()
    {
        // 获取文章内容
        $article = Notice::where('state', true)->field('id,title,content')->paginate();

        if ($article) {

            return $this->message(1, '', $article);
        } else {
            return $this->message(0, '');
        }
    }
}
