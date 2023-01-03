<?php
namespace app\stock\controller;

use app\common\model\StockXrxd;
use think\App;
use util\BasicData;
use util\RedisUtil;
use util\SearchRedis;

class XrxdController extends BaseController
{

    protected $stockXrxdModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->stockXrxdModel = new StockXrxd();
    }

    /**
     * 获取除权除息列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getXrxdList()
    {
        $map = [];
        // 获取查询提交数据
        $data['stock_code']  = input('stock_code', '', FILTER_SANITIZE_NUMBER_INT);
        $data['is_finished'] = input('is_finished', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['stock_code', '=', $data['stock_code']];
        }
        if ($data['is_finished']) {
            $map[] = ['is_finished', '=', $data['is_finished']];
        }
        if ($data['start_date']) {
            $map[] = ['execute_date', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $map[] = ['execute_date', '<=', $data['end_date']];
        }
        // 获取股票信息列表
        $infoList  = $this->stockXrxdModel
            ->field('id,stock_id,stock_code,execute_date,base_volume,give_volume,transfer_volume,dividend,remark,is_finished,market')
            ->order('execute_date DESC,id desc')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);
        $stockName = [];
        if ($infoList) {
            $xrxdList = $infoList->getCollection()->toArray();
            foreach ($xrxdList as &$v) {
                $account_volume = $v['give_volume'] + $v['transfer_volume'];
                $v['give_volume'] = $v['give_volume'] == 0 ? '--' : $v['give_volume'];
                $v['transfer_volume'] = $v['transfer_volume'] == 0 ? '--' : $v['transfer_volume'];
                $v['account_volume'] = $account_volume == 0 ? '--' : $account_volume;
                $v['dividend'] = $v['dividend'] == 0 ? '--' : $v['dividend'];
                $stockName[$v['market'] . $v['stock_code']] = RedisUtil::getStockData($v['stock_code'], $v['market']);
            }
        }

        return $infoList ? $this->message(1, '', ['infoList' => $infoList,'xrxdList' => $xrxdList, 'stockName' => $stockName]) : $this->message(0, '信息列表为空');
    }

    /**
     * 获取单个除权除息信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getXrxdById()
    {
        // 获取股票信息id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $xrxdkInfo = $this->stockXrxdModel
                ->field('id,stock_id,stock_code,execute_date,base_volume,transfer_volume,give_volume,dividend,remark,market')
                ->where('id', $id)->find();

            return $xrxdkInfo ? $this->message(1, '', $xrxdkInfo) : $this->message(0, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 添加编辑除权除息信息
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveXrxd()
    {
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['stock_code'] = input('post.stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 提交数据校验
        $result = $this->validate($data, 'StockXrxd.saveStockXrxd');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        $data['base_volume']     = input('post.base_volume', 0, 'floatval');
        $data['give_volume']     = input('post.give_volume', 0, 'floatval');
        $data['transfer_volume'] = input('post.transfer_volume', 0, 'floatval');
        $data['dividend']        = input('post.dividend', 0, function ($value) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        });

        // 基础股票数量不能都为空
        if (empty($data['base_volume'])) {
            return $this->message(0, '基础股票数量不能为空或零');
        }

        //送股股数、转股股数不能都为空
        if (empty($data['give_volume']) && empty($data['transfer_volume']) && empty($data['dividend'])) {
            return $this->message(0, '送股股数、转股股数,股利金，至少有一项不能为空');
        }

        $data['execute_date'] = input('post.execute_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']       = input('post.market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['remark']       = input('post.remark', '');
        $id                   = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

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
            $saveInfo   = $this->stockXrxdModel->isUpdate(true)->save($data);
        } else {
            $saveInfo = $this->stockXrxdModel->save($data);
        }

        return $saveInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 获取证券市场列表
     *
     * @return \think\response\Json
     */
    public function getMarketList()
    {
        return $this->message(1, '', BasicData::MARKET_LIST);
    }

    /**
     * 从缓存中读取股票数据
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchCode()
    {
        $stock_code = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 缓存中读取数据
        $codeTmp  = SearchRedis::search($stock_code);
        $codeTmp  = $codeTmp ?: [];
        $codeInfo = [];
        if ($codeTmp) {
            foreach ($codeTmp as $k => $v) {
                $arr          = explode("|", $v);
                $codeInfo[$k] = RedisUtil::getStockData($arr[1], $arr[0]);
            }
        }

        return $codeInfo ? $this->message(1, '', $codeInfo) : $this->message(0, '');
    }

    /**
     * 获取除权除息列表总数
     *
     * @return \think\response\Json
     */
    public function xrxdTotal()
    {
        // 获取除权除息总数
        $xrxdTotal = $this->stockXrxdModel->count();

        return $this->message(1, '', $xrxdTotal);
    }

}
