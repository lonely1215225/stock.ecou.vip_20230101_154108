<?php
namespace app\index\controller;

use app\common\model\Article;
use app\common\model\Notice;
use util\NewsRedis;

class NewsController extends BaseController
{
    /**
     * 获取文章列表数据
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 分类
        $catId = input('cat_id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($catId <= 0) return $this->message(0, '请求无效');

        // 获取文章列表数据
        $newsList = Article::where('cat_id', $catId)->field('id,title,update_time')->order('create_time desc,id desc')->paginate();

        return $this->message(1, '', $newsList);
    }

    /**
     * 获取单个文章内容
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
        $article = Article::where('id', $id)->field('id,cat_id,title,content,update_time')->find();

        if ($article) {
            // 图片最大宽度控制
            $style              = '<style>img{max-width:100% !important;}</style>';
            $article['content'] = $style . $article['content'];

            return $this->message(1, '', $article);
        } else {
            return $this->message(0, '没有找到文章');
        }
    }

    /**
     * 首页新闻
     *
     * @return \think\response\Json
     */
    public function getNews()
    {
        try {
            $content = json_decode(NewsRedis::getUpChina(), true);
        } catch (\Exception $e) {
            $content = [];
        }

        return $this->message(1, '', ['content' => $content]);
    }
    /*获取新闻详情ID*/
    public function upChina()
    {
        $recData = input();
        $content = [];
        $content = NewsRedis::getNews($recData['Art_Code']);
        //print_r($content);return;
        try {
            if(!isset($content['Art_info']) || !$content['Art_info']){
                $content = NewsRedis::getNewsPath($recData);
            }
            if(!isset($content['Art_info']) || !$content['Art_info']){
                $arrey  = json_decode(NewsRedis::getBriefInfo($recData['Art_Code']), true);
                $postid = $arrey['re'][0]['post_id'];
                if($postid)$content = NewsRedis::getNewsInfo($recData['Art_Code'],$postid, true);
            }
        } catch (\Exception $e) {
            $content = [];
        }
        return $this->message(1, '', $content);
    }
    /**
     * 公司公告
     */
    public function notice() {
        // 获取文章列表数据
        $newsList = Notice::where('state', true)
            ->field('*')
            ->order('create_time desc,id desc')
            ->paginate()
            ->each( function($item, $key){
                $item->short = get_filter_str($item->content);
            });

        return $this->message(1, '', $newsList);
    }
}
