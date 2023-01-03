<?php
namespace app\stock\controller;

use app\common\model\StockSuspension;
use think\App;
use util\RedisUtil;

class SuspensionController extends BaseController
{

    protected $stockSuspensionModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->stockSuspensionModel = new StockSuspension();
    }

    /**
     * 获取停牌复牌信息列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSuspensionList()
    {
        $whereArr = [];

        // 获取查询提交数据
        $data['stock_code']    = input('stock_code', '', FILTER_SANITIZE_NUMBER_INT);
        $data['is_suspension'] = input('is_suspension', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $whereArr['stock_code'] = $data['stock_code'];
        }
        if ($data['is_suspension'] == 1) {
            $infoList = $this->stockSuspensionModel
                ->field('id, stock_id, stock_code, suspension_date, resumption_date,market')
                ->order('suspension_date DESC')
                ->where($whereArr)
                ->where('suspension_date', '<=', date("Y-m-d H:i:s"))
                ->where('resumption_date', '>', date("Y-m-d H:i:s"))
                ->paginate(15, false, ['query' => request()->param()]);
        } elseif ($data['is_suspension'] == 2) {
            $infoList = $this->stockSuspensionModel
                ->field('id, stock_id, stock_code, suspension_date, resumption_date,market')
                ->order('suspension_date DESC')
                ->where($whereArr)
                ->where('resumption_date', '<=', date("Y-m-d H:i:s"))
                ->paginate(15, false, ['query' => request()->param()]);
        } else {
            $infoList = $this->stockSuspensionModel
                ->field('id, stock_id, stock_code, suspension_date, resumption_date,market')
                ->order('suspension_date DESC')
                ->where($whereArr)
                ->paginate(15, false, ['query' => request()->param()]);
        }
        $stockName = [];
        if ($infoList) {
            foreach ($infoList->getCollection()->toArray() as $k => $v) {
                $stockName[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $infoList ? $this->message(1, '', ['infoList' => $infoList, 'stockName' => $stockName]) : $this->message(0, '信息列表为空');
    }

    /**
     * 获取单个停牌复牌信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSuspensionById()
    {
        // 获取停牌复牌信息id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $susInfo = $this->stockSuspensionModel
                ->field('id, stock_id, stock_code, suspension_date, resumption_date,market')
                ->where('id', $id)->find();

            return $susInfo ? $this->message(1, '', $susInfo) : $this->message(0, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 添加编辑停牌复牌信息
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveSuspension()
    {
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['stock_code'] = input('post.stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 提交数据校验
        $result = $this->validate($data, 'StockSuspension.saveStockSus');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        $data['market']          = input('post.market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['suspension_date'] = input('post.suspension_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['resumption_date'] = input('post.resumption_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $id                      = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 调取股票接口返回股票信息
        $stockModel = new StockController();
        $stockInfo  = $stockModel->getStockByCode($data['stock_code'])->getData();
        if (!$stockInfo['data']) {
            return $this->message(0, '该股票信息不存在,请重新输入股票代码');
        } else {
            $data['stock_id'] = $stockInfo['data']['id'];
        }

        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            $saveInfo   = $this->stockSuspensionModel->isUpdate(true)->save($data);
        } else {
            $saveInfo = $this->stockSuspensionModel->save($data);
        }

        return $saveInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 获取停牌复牌总数
     *
     * @return \think\response\Json
     */
    public function suspensionTotal()
    {
        // 获取停牌复牌总数
        $suspensionTotal = $this->stockSuspensionModel->count();

        return $this->message(1, '', $suspensionTotal);
    }

}
