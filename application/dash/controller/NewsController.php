<?php
namespace app\dash\controller;

use app\stock\controller\ArticleController;
use think\App;


class NewsController extends BaseController
{

    private $articleApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->articleApi = new ArticleController();
    }

    /**
     * 新闻列表页面
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取新闻列表
        $infoResult = $this->articleApi->getNewsList()->getData();
        $this->assign('newsList', $infoResult['data']);

        return $this->fetch();
    }

    /**
     * 新闻编辑页面
     * - 添加、修改
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function news_edit()
    {
        // 获取文章内容
        $editInfo = $this->articleApi->getNews()->getData();
        if ($editInfo['data']) {
            $this->assign('id', $editInfo['data']['id']);
            $this->assign('editInfo', $editInfo['data']);
        }

        // 获取文章栏目
        $catInfo = $this->articleApi->getCategoryList()->getData();
        $this->assign('catInfo', $catInfo['code'] == 1 ? $catInfo['data'] : []);

        return $this->fetch();
    }

    /**
     * 分类列表页面
     *
     * @return mixed
     * @throws \think\Exception\DbException
     */
    public function cat_list()
    {
        // 接受api返回的信息
        $infoResult = $this->articleApi->getCategoryList()->getData();
        $this->assign('catList', $infoResult['data']);

        return $this->fetch();
    }

    /**
     * 分类添加页面
     *
     * @return mixed
     */
    public function cat_add()
    {
        return $this->fetch();
    }

    /**
     * 分类修改页面
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cat_edit()
    {
        // 根据ID获取文章栏目内容
        $catInfo = $this->articleApi->getCategory()->getData();

        if ($catInfo['data']) {
            $this->assign('catInfo', $catInfo['data']);
            $this->assign('id', $catInfo['data']['id']);
        } else {
            $this->error('参数错误');
        }

        return $this->fetch();
    }

    /**
     * 获取联系方式内容
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function contact()
    {
        // 获取联系方式内容
        $contactInfo = $this->articleApi->contact()->getData();
        $this->assign('contactInfo', $contactInfo['code'] == 1 ? $contactInfo['data'] : []);

        return $this->fetch();
    }

    public function contact_edit()
    {
        // 获取联系方式内容
        $contactInfo = $this->articleApi->contact()->getData();
        $this->assign('contactInfo', $contactInfo['code'] == 1 ? $contactInfo['data'] : []);
        
        return $this->fetch();
    }

}
