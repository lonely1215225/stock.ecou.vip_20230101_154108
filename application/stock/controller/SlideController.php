<?php

namespace app\stock\controller;

use app\common\model\Slide;
use think\App;
use think\facade\Env;

class SlideController extends BaseController
{

    // 幻灯片模型
    private $slideModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        // 实例化模型
        $this->slideModel = new Slide();
    }

    /**
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getSlideList()
    {
        // 获取文章列表
        $slideList = $this->slideModel->field('id,title,litimg,outlink,intro,create_time,update_time')
            ->order('id desc')
            ->paginate();

        return $this->message(1, '', $slideList);
    }

    /**
     * 根据id获取文章内容
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSlide()
    {
        // 获取文章id
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            $slide = $this->slideModel->where('id', $id)->find();

            return $slide ? $this->message(1, '', $slide) : $this->message(0, '没有找到幻灯片');
        } else {
            return $this->message(0, '参数错误');
        }
    }


    /**
     * 新闻编辑页面
     * - 添加、修改
     *
     * @return array|\think\response\Json
     */
    public function saveSlide()
    {
        // 文章添加编辑
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['title'] = input('post.title', 0, [FILTER_SANITIZE_STRING, 'trim']);
        $data['intro'] = input('post.intro', '');
        $data['outlink'] = input('post.outlink', 0, [FILTER_SANITIZE_STRING, 'trim']);
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        $upload_file = '/uploads/slide';
        $rootPath = Env::get('ROOT_PATH');
        $fullPath = $rootPath . 'public' . $upload_file;

        $picUrl = '';
        //判断是否上传客服微信号
        if ($_FILES['litimg']['name']) {
            $serviceFile = $this->request->file('litimg');
            $info = $serviceFile->move($fullPath);
            if (!$info) return $this->message(0, $serviceFile->getError());
            //$data['litimg'] = 'http://' . $_SERVER['HTTP_HOST'] . $upload_file . DIRECTORY_SEPARATOR . $info->getSaveName();
            $data['litimg'] = $upload_file . DIRECTORY_SEPARATOR . $info->getSaveName();
            if ($id) {
                $picUrl = $this->slideModel->where(['id' => $id])->value('litimg', 'id');
            }
        } else {
            if ($id) {
                $data['litimg'] = $this->slideModel->where(['id' => $id])->value('litimg', 'id');
            }
        }

        // 提交数据校验
        $result = $this->validate($data, 'Slide.edit');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            //删除原有的图片
            if ($picUrl) {
                $servicePath = $rootPath . 'public' . parse_url($picUrl)['path'];
                if (file_exists($servicePath)) @unlink($servicePath);
            }
            $data['id'] = $id;
            $newsInfo = $this->slideModel->isUpdate(true)->save($data);
        } else {
            $newsInfo = $this->slideModel->save($data);
        }

        return $newsInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     *  删除文章页面
     *
     * @return mixed
     * @throws \Exception
     */
    public function delete()
    {
        if (!$this->request->isPost()) return null;

        // 获取要删除的栏目Id
        $data['ids'] = input('post.id', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 提交数据校验
        $result = $this->validate($data, 'Slide.delete');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        $where[] = ['id', 'in', $data['ids']];
        $slides = $this->slideModel->where($where)->column('litimg', 'id');
        // 删除数据
        $delInfo = $this->slideModel->where($where)->delete();
        if ($delInfo) {
            $rootPath = Env::get('ROOT_PATH');
            foreach ($slides as $val) {
                $servicePath = $rootPath . 'public' . parse_url($val)['path'];
                if (file_exists($servicePath)) @unlink($servicePath);
            }
        }

        return $delInfo ? $this->message(1, '删除成功') : $this->message(0, '删除失败');
    }

    /**图片
     * 删除幻灯
     * @return \think\response\Json
     */
    public function deleteSlidePic()
    {
        $file_path = input('post.file_path', '', FILTER_SANITIZE_STRING);
		
        $path_array = parse_url($file_path);
        $path = Env::get('ROOT_PATH') . 'public' .$path_array['path'];

        if ($path) {
            if (file_exists($path)) unlink($path);
        }

        return $this->message(1, '图片删除成功！');
    }

}
