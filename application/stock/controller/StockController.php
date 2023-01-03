<?php
namespace app\stock\controller;

use app\common\model\Stock;
use think\App;
use think\Db;
use util\BasicData;
use util\QuotationRedis;
use util\RedisUtil;
use util\SearchRedis;

class StockController extends BaseController
{

    protected $stockModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->stockModel = new Stock();
    }

    /**
     * 获取股票信息列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function stockList()
    {
        $map = array();
        // 获取查询提交数据
        $data['stock_code']   = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name']   = input('stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']       = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_black']     = input('is_black', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_selective'] = input('is_selective', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_special']   = input('is_special', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_kechuang']  = input('is_kechuang', '',[FILTER_SANITIZE_STRING, 'trim']);
        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['stock_code', 'LIKE', "%" . $data['stock_code'] . "%"];
        }
        if ($data['stock_name']) {
            $map[] = ['stock_name', 'like', "%{$data['stock_name']}%"];
        }
        if ($data['market']) {
            $map[] = ['market', '=', $data['market']];
        }
        if ($data['is_black']) {
            $map[] = ['is_black', '=', $data['is_black']];
        }
        if ($data['is_selective']) {
            $map[] = ['is_selective', '=', $data['is_selective']];
        }
        if ($data['is_special']) {
            $map[] = ['is_special', '=', $data['is_special']];
        }
        if ($data['is_kechuang']) {
            $map[] = ['is_kechuang', '=', $data['is_kechuang']];
        }

        // 获取股票信息列表
        $stockList = $this->stockModel
            ->where($map)
            ->field('*')
            ->paginate(15, false, ['query' => request()->param()]);

        return $stockList ? $this->message(1, '', $stockList) : $this->message(0, '股票信息为空');
    }

    /**
     * 获取股票风险等级列表
     *
     * @return \think\response\Json
     */
    public function getStockRiskList()
    {
        return $this->message(1, '', BasicData::STOCK_RISK_LIST);
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
     * 获取证券标记列表
     *
     * @return \think\response\Json
     */
    public function getMarketSignList()
    {
        return $this->message(1, '', BasicData::MARKET_SIGN_LIST);
    }

    /**
     * 获取单个股票信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStockInfoById()
    {
        // 获取股票信息id
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        if ($id) {
            $stockInfo = $this->stockModel
                ->field('id,stock_code,stock_name,is_margin,is_suspended,risk_level,market,initial,is_selective,is_special,is_kechuang')
                ->where('id', $id)->find();

            return $stockInfo ? $this->message(1, '', $stockInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 添加修改股票信息
     *
     * @return null|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveStock()
    {
        if (!$this->request->isPost()) return null;

        // 获取表单提交数据
        $data['stock_code'] = input('post.stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name'] = input('post.stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        // 提交数据校验
        $result = $this->validate($data, 'Stock.saveStock');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        $data['is_margin']    = input('post.is_margin', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['risk_level']   = input('post.risk_level', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']       = input('post.market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['initial']      = input('post.initial', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_selective'] = input('post.is_selective', '', FILTER_SANITIZE_STRING);
        $data['is_special']   = input('post.is_special', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_kechuang']  = input('post.is_kechuang', '', [FILTER_SANITIZE_STRING, 'trim']);
        $id                   = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        //print_r($data);exit;
        // 根据id是否为空判断是添加还是编辑
        if ($id) {
            $data['id'] = $id;
            $saveInfo   = $this->stockModel->isUpdate(true)->save($data);
        } else {
            // 验证股票是否存在
            $isExit = $this->stockModel->where('stock_code', $data['stock_code'])->where('market', $data['market'])->count();
            if ($isExit) {
                return $this->message(0, '该股票已经存在，请确认');
            }
            $saveInfo = $this->stockModel->save($data);
        }

        // 更新缓存数据
        RedisUtil::cacheStockData($data['stock_code'], $data['market']);
        // 更新所有股票的基础数据
        RedisUtil::cacheAllStockCode();

        if ($data['is_selective']) {
            // 加入活跃股票订阅列表
            QuotationRedis::addActiveSubscribe($data['market'], $data['stock_code']);
        }

        return $saveInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 根据股票代码返回股票信息
     *
     * @param $code
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStockByCode($code)
    {
        if ($code) {
            $stockInfo = $this->stockModel
                ->field('id,stock_code,stock_name,is_margin,is_suspended,risk_level,market,is_kechuang')
                ->where('stock_code', $code)->where('is_black', false)->find();

            return $stockInfo ? $this->message(1, '', $stockInfo) : $this->message(1, '没有找到信息');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 获取黑名单列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getBlacklist()
    {
        $map[] = ['is_black', '=', true];
        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name'] = input('stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']     = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['stock_code', 'LIKE', "%" . $data['stock_code'] . "%"];
        }
        if ($data['stock_name']) {
            $map[] = ['stock_name', '=', $data['stock_name']];
        }
        if ($data['market']) {
            $map[] = ['market', '=', $data['market']];
        }
        // 获取股票信息列表
        $stockList = $this->stockModel
            ->field('id,stock_code,stock_name,market_time,is_margin,is_suspended,risk_level,market,is_black,board_lot')
            ->where($map)
            ->paginate(15, false, ['query' => request()->param()]);

        return $stockList ? $this->message(1, '', $stockList) : $this->message(0, '股票信息为空');
    }

    /**
     * 黑名单编辑
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editBlack()
    {
        $data['id'] = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Stock.toggleStatus');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 更新状态
        $changeInfo = $this->stockModel->isUpdate(true)->save([
                'id'       => $data['id'],
                'is_black' => Db::raw('NOT is_black'),
            ]
        );

        // 更新缓存数据
        $stockCode = input('stock_code', 0, FILTER_SANITIZE_NUMBER_INT);
        $market    = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        RedisUtil::cacheStockData($stockCode, $market);

        return $changeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 添加股票黑名单
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addBlack()
    {
        $data['id'] = input('id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Stock.toggleStatus');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 更新状态
        $changeInfo = $this->stockModel->isUpdate(true)->save([
                'id'       => $data['id'],
                'is_black' => true,
            ]
        );

        // 更新缓存数据
        $stockCode = input('stock_code', 0, FILTER_SANITIZE_NUMBER_INT);
        $market    = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        RedisUtil::cacheStockData($stockCode, $market);

        return $changeInfo ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 获取个股总数
     *
     * @return \think\response\Json
     */
    public function stockTotal()
    {
        $map = array();
        // 获取查询提交数据
        $data['stock_code']   = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name']   = input('stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']       = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_selective'] = input('is_selective', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_special']   = input('is_special', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 根据传递的参数生产where条件
        if ($data['stock_code']) {
            $map[] = ['stock_code', 'LIKE', "%{$data['stock_code']}%"];
        }
        if ($data['stock_name']) {
            $map[] = ['stock_name', 'like', "%{$data['stock_name']}%"];
        }
        if ($data['market']) {
            $map[] = ['market', '=', $data['market']];
        }
        if ($data['is_selective']) {
            $map[] = ['is_selective', '=', $data['is_selective']];
        }
        if ($data['is_special']) {
            $map[] = ['is_special', '=', $data['is_special']];
        }
        // 获取个股总数
        $stockTotal = $this->stockModel->where($map)->count();

        return $this->message(1, '', $stockTotal);
    }

    /**
     * 获取今日优选的股票代码信息
     *
     * @param $code
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStockBySelective()
    {
        // 获取股票信息列表
        $stockList = $this->stockModel
            ->where('is_selective=true')
            ->field('id,stock_code,stock_name,is_margin,is_suspended,risk_level,market')
            ->limit(0, 8)
            ->order('create_time desc')
            ->select();

        return $stockList ? $this->message(1, '', $stockList) : $this->message(1, '没有找到信息');

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
        $codeTmp    = SearchRedis::search($stock_code);
        $black_list = Stock::where('is_black=true')->field('stock_code,market')->select();
        $black_data = array();
        foreach ($black_list as $item) {
            array_push($black_data, $item['market'] . '|' . $item['stock_code']);
        }
        $codeTmp  = $codeTmp ?: [];
        $codeInfo = [];
        if ($codeTmp) {
            $codeTmp = array_diff($codeTmp, $black_data);
            foreach ($codeTmp as $k => $v) {
                $arr          = explode("|", $v);
                $codeInfo[$k] = RedisUtil::getStockData($arr[1], $arr[0]);
            }

        }

        return $codeInfo ? $this->message(1, '', $codeInfo) : $this->message(0, '');
    }

    public function updataDBstock(){
        $is_updata = input('post.is_updata', '', FILTER_SANITIZE_STRING);
        $cacheData = RedisUtil::getCacheupData();
        //print_r($cacheData);exit;
        foreach ($cacheData as $k => $item) {
            $item = explode(",",$item);
            // 获取表单提交数据
            $data['stock_code']   = $item[2];
            $data['stock_name']   = $item[3];
            // 提交数据校验
            $result = $this->validate($data, 'Stock.saveStock');
            if ($result !== true) continue;
            $data['is_margin']    = 0;
            $data['risk_level']   = 0;
            $data['market']       = $item[0];
            $data['initial']      = $item[4];
            $data['is_selective'] = 'false';
            $data['is_special']   = 'false';
            $data['is_kechuang']  = 'false';
            $isExit = $this->stockModel->where('stock_code', $data['stock_code'])->where('market', $data['market'])->find();
            if($isExit) {
                if(!$is_updata) continue;
                $data['id'] = $isExit['id'];
                $data['update_time']  = time();
                $saveInfo = $this->stockModel->isUpdate(true)->save($data);
            }else{
                $data['create_time']  = time();
                if(isset($data['id'])) unset($data['id']);
                $saveInfo = $this->stockModel->create($data);
            }
            //continue;
            return $this->message(301, '第 '.($k+1).' 条 '.$data['stock_code'].' '.$data['stock_name'].' 写入成功！');
        }
        
        return $this->message(1, '全部更新完毕');
    }
    
    public function delStock()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 删除数据
        $delInfo = $this->stockModel->where('id', $id)->delete();

        return $delInfo ? $this->message(1, '删除成功') : $this->message(0, '删除失败');
    }
}
