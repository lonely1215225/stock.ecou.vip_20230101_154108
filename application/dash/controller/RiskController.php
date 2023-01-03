<?php
namespace app\dash\controller;

use app\stock\controller\SuspensionController;
use app\stock\controller\StockController;
use app\stock\controller\XrxdController;
use util\SearchRedis;
use util\RedisUtil;
use think\App;

class RiskController extends BaseController
{

    protected $susApi;
    protected $xrxdApi;
    protected $stockApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->susApi   = new SuspensionController();
        $this->xrxdApi  = new XrxdController();
        $this->stockApi = new StockController();
    }

    /**
     * 股票信息列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 获取股票信息列表
        $stockInfo = $this->stockApi->stockList()->getData();
        $this->assign('stockInfo', $stockInfo['code'] == 1 ? $stockInfo['data'] : []);

        // 获取风险等级列表
        $riskList = $this->stockApi->getStockRiskList()->getData();
        $this->assign('riskList', $riskList['data']);

        // 获取证券市场列表
        $marketList = $this->stockApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        // 获取个股总数
        $stockTotal = $this->stockApi->stockTotal()->getData();
        $this->assign('stockTotal', $stockTotal['data']);

        // 获取查询提交数据
        $data['stock_code']  = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name']  = input('stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']      = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_black']    = input('is_black', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_selective'] = input('is_selective', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['is_special'] = input('is_special', '',[FILTER_SANITIZE_STRING, 'trim']);
        $data['is_kechuang'] = input('is_kechuang', '',[FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stockCode', $data['stock_code']);
        $this->assign('stockName', $data['stock_name']);
        $this->assign('market', $data['market']);
        $this->assign('is_black', $data['is_black']);
        $this->assign('is_selective', $data['is_selective']);
        $this->assign('is_special', $data['is_special']);
        $this->assign('is_kechuang', $data['is_kechuang']);

        return $this->fetch();
    }

    /**
     * 编辑添加个股
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_stock()
    {
        // 获取编辑股票的信息
        $stockInfo = $this->stockApi->getStockInfoById()->getData();
        if ($stockInfo['data']) {
            $this->assign('id', $stockInfo['data']['id']);
            $this->assign('stockInfo', $stockInfo['data']);
        }

        // 获取风险等级列表
        $riskList = $this->stockApi->getStockRiskList()->getData();
        $this->assign('riskList', $riskList['data']);

        // 获取证券市场列表
        $marketList = $this->stockApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        return $this->fetch();
    }

    /**
     * 个股黑名单列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function black_list()
    {
        // 获取股票信息列表
        $stockInfo = $this->stockApi->getBlacklist()->getData();
        $this->assign('stockInfo', $stockInfo['code'] == 1 ? $stockInfo['data'] : []);

        // 获取风险等级列表
        $riskList = $this->stockApi->getStockRiskList()->getData();
        $this->assign('riskList', $riskList['data']);

        // 获取证券市场列表
        $marketList = $this->stockApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        // 获取查询提交数据
        $data['stock_code'] = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['stock_name'] = input('stock_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']     = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $this->assign('stockCode', $data['stock_code']);
        $this->assign('stockName', $data['stock_name']);
        $this->assign('market', $data['market']);

        return $this->fetch();
    }

    /**
     * 添加个股黑名单
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_black()
    {
        // 获取查询股票数据
        $stockList = $this->stockApi->searchCode()->getData();
        $this->assign('codeInfo', $stockList['data']);

        // 获取证券市场列表
        $marketList = $this->stockApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        return $this->fetch();
    }

    /**
     * 停牌复牌信息列表
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function suspension()
    {
        // 获取停牌复牌信息列表
        $susInfo = $this->susApi->getSuspensionList()->getData();
        $this->assign('susInfo', $susInfo['code'] == 1 ? $susInfo['data'] : []);
        // 获取停牌复牌列表总数
        $suspensionTotal = $this->susApi->suspensionTotal()->getData();
        $this->assign('suspensionTotal', $suspensionTotal['data']);
        $data['stock_code']    = input('stock_code', '', FILTER_SANITIZE_NUMBER_INT);
        $data['is_suspension'] = input('is_suspension', '', FILTER_SANITIZE_NUMBER_INT);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('is_suspension', $data['is_suspension']);

        return $this->fetch();
    }

    /**
     * 添加编辑停牌复牌信息
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_suspension()
    {
        // 获取待编辑停牌复牌信息
        $susInfo = $this->susApi->getSuspensionById()->getData();
        if ($susInfo['data']) {
            $this->assign('susInfo', $susInfo['data']);
        }

        return $this->fetch();
    }

    /**
     * 除权除息信息列表
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function xrxd()
    {
        // 获取除权除息信息列表
        $xrxdInfo = $this->xrxdApi->getXrxdList()->getData();
        $this->assign('xrxdInfo', $xrxdInfo['code'] == 1 ? $xrxdInfo['data'] : []);

        // 获取除权除息列表总数
        $xrxdTotal = $this->xrxdApi->xrxdTotal()->getData();
        $this->assign('xrxdTotal', $xrxdTotal['data']);
        $data['stock_code']  = input('stock_code', '', FILTER_SANITIZE_NUMBER_INT);
        $data['is_finished'] = input('is_finished', '', FILTER_SANITIZE_NUMBER_INT);
        $data['start_date']  = input('start_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['end_date']    = input('end_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['page']        = input('page', 1, FILTER_SANITIZE_NUMBER_INT);
        $this->assign('stock_code', $data['stock_code']);
        $this->assign('is_finished', $data['is_finished']);
        $this->assign('start_date', $data['start_date']);
        $this->assign('end_date', $data['end_date']);
        $this->assign('page', $data['page']);
        return $this->fetch();
    }

    /**
     * 添加编辑除权除息信息
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_xrxd()
    {
        // 获取待编辑除权除息信息
        $xrxdInfo = $this->xrxdApi->getXrxdById()->getData();
        if ($xrxdInfo['data']) {
            $this->assign('xrxdInfo', $xrxdInfo['data']);
        }

        // 获取证券公司列表
        $marketList = $this->xrxdApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        return $this->fetch();
    }

    /**
     * 除权除息详情
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail_xrxd()
    {
        // 获取单个除权除息信息
        $xrxdInfo = $this->xrxdApi->getXrxdById()->getData();
        if ($xrxdInfo['data']) {
            $this->assign('xrxdInfo', $xrxdInfo['data']);
        }

        return $this->fetch();
    }

    /**
     * 获取查询股票数据
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_code()
    {
        // 获取查询股票数据
        $codeInfo = $this->xrxdApi->searchCode()->getData();
        $this->assign('codeInfo', $codeInfo['data']);

        // 获取证券市场列表
        $marketList = $this->xrxdApi->getMarketList()->getData();
        $this->assign('marketList', $marketList['data']);

        return $this->fetch();
    }
    
    public function updata_all_stock()
    {
        $data = apiStockList();
        //$data = eastStockList();
    	if(empty($data)) exit;
    	//print_r($data);exit;
        $redisData = RedisUtil::cacheupData($data);// 缓存获取到的数据
        // 更新所有股票的基础数据
        RedisUtil::cacheAllStockCode();
        RedisUtil::cacheAllStockData();
        $json['count'] = $data['count'];
        foreach ($data['items'] as $k => $item) {
            $infoData[] = $item;
        }
        $json['items'] = $infoData;
        //print_r($json);exit;
    	$this->assign('count', $json['count']);
        $this->assign('items', $json['items']);
        return $this->fetch();
    }

}
