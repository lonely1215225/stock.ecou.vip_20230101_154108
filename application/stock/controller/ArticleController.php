<?php
namespace app\stock\controller;

use app\common\model\Article;
use app\common\model\ArticleCategory;
use think\App;

class ArticleController extends BaseController
{

    // 文章模型
    private $articleModel;
    // 文章分类模型
    private $articleCategoryModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        // 实例化模型
        $this->articleModel         = new Article();
        $this->articleCategoryModel = new ArticleCategory();
    }

    /**
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getNewsList()
    {
        // 获取文章列表
        $newsList = $this->articleModel->alias('a')
            ->field('a.id,a.cat_id,a.title,a.update_time,ac.id bid,ac.title cat_name')
            ->join(['__ARTICLE_CATEGORY__' => 'ac'], 'a.cat_id=ac.id')->order('a.id desc')
            ->paginate();

        return $this->message(1, '', $newsList);
    }

    /**
     * 根据id获取文章内容
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNews()
    {
        // 获取文章id
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            $article = $this->articleModel->where('id', $id)->find();

            return $article ? $this->message(1, '', $article) : $this->message(0, '没有找到文章');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 根据ID获取分类信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCategory()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $category = $this->articleCategoryModel->field('id,title,is_show')->where('id', $id)->find();

            return $category ? $this->message(1, '', $category) : $this->message(1, '没有找到分类');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取文章栏目列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCategoryList()
    {
        $catList = $this->articleCategoryModel->field('id,title,is_show,update_time')->select();
        $catList = $catList ?: [];

        return $this->message(1, '', $catList);
    }

    /**
     * 新闻编辑页面
     * - 添加、修改
     *
     * @return array|\think\response\Json
     */
    public function saveNews()
    {
        // 文章添加编辑
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['title']   = input('post.title', 0, [FILTER_SANITIZE_STRING, 'trim']);
        $data['content'] = input('post.content', '');
        $data['cat_id']  = input('post.cat_id', 0);
        $id              = input('id', '', FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Article.news_edit');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            $newsInfo   = $this->articleModel->isUpdate(true)->save($data);
        } else {
            $newsInfo = $this->articleModel->save($data);
        }

        return $newsInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 分类添加修改页面
     *
     * @return \think\response\Json
     */
    public function saveCat()
    {
        // 非POST请求处理
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['title'] = input('post.title', '', [FILTER_SANITIZE_STRING, 'trim']);
        $id            = input('id', '', FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Article.cat_add');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        if ($id) {
            $data['is_show'] = input('post.is_show', 1);
            $data['id']      = $id;

            // 更新数据
            $upInfo = $this->articleCategoryModel->isUpdate(true)->save($data);
        } else {
            // 插入数据库
            $upInfo = $this->articleCategoryModel->save($data);
        }

        return $upInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 删除文章栏目
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function catDel()
    {
        if (!$this->request->isPost()) return null;

        // 获取要删除的栏目Id
        $data['id'] = input('post.id', '', FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Article.cat_del');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 检查该文章栏目下是否有文章，有则不允许删除
        $isHasArticle = $this->articleModel->field('id')->where('cat_id', $data['id'])->find();

        if ($isHasArticle) {
            return $this->message(0, '该文章栏目下有文章，不能删除，请检查！');
        } else {
            // 删除数据
            $delInfo = $this->articleCategoryModel->where('id', $data['id'])->delete();

            return $delInfo ? $this->message(1, '删除成功') : $this->message(0, '删除失败');
        }
    }

    /**
     *  删除文章页面
     *
     * @return mixed
     * @throws \Exception
     */
    public function newsDel()
    {
        if (!$this->request->isPost()) return null;

        // 获取要删除的栏目Id
        $data['ids'] = input('post.id', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 提交数据校验
        $result = $this->validate($data, 'Article.news_del');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 删除数据
        $delInfo = $this->articleModel->where('id', 'in', $data['ids'])->delete();

        return $delInfo ? $this->message(1, '删除成功') : $this->message(0, '删除失败');
    }

    /**
     *  添加编辑文章上传图片并保存到指定目录
     *
     * @return mixed
     */
    public function uploadImg()
    {
        if (!$this->request->isPost()) return null;

        $file = $this->request->file('file');
        if ($file) {
            $info = $file->rule('date')
                ->validate(['size' => 2097152, 'ext' => 'jpg,jpeg,png,gif'])
                ->move(UPLOAD_DIR . '/article/');
            if ($info) {
                return $this->message(1, '上传成功', [
                    'src'   => 'http://s.ndd668.com/uploads/article/' . str_replace("\\", '/', $info->getSaveName()),
                    'title' => '图片',
                ]);
            }
        }

        return $this->message(0, '上传失败');
    }

    /**
     * 获取联系方式内容
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function contact()
    {
        // 获取联系方式内容
        $contactInfo = $this->articleModel->field('id,title,content,update_time')->where('id', 1)->find();
        $contactInfo = $contactInfo ?: [];

        return $contactInfo ? $this->message(1, '', $contactInfo) : $this->message(0, '');
    }

}
