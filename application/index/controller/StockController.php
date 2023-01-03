<?php
namespace app\index\controller;

use app\common\model\Stock;
use app\common\model\Favorite;
use util\RedisUtil;
use util\QuotationRedis;
use util\SearchRedis;

class StockController extends BaseController
{

    /**
     * 全部股票列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        $market = input('market', '', FILTER_SANITIZE_STRING);
        $list   = RedisUtil::getStockList($market);
        $list   = array_chunk($list,800,true);
        //print_r(count($list));print_r($list);
        $result = [];
        foreach ($list as $i => $item) {
            $num = '';
            foreach ($item as $k => $v) {
                $data = explode(",", $v);
                $num .= strtolower($data[0]) . $data[2] . ",";
            }
            //print_r(rtrim($num,","));exit;
            //return $this->message(1, '', $json);
        }
        return $this->message(1, '', $result);
    }
    /**
     * 指数列表
     */
    public function stock_index()
    {
        $list[] = [
            'SH,1,000001,上证指数,SZZS',
            'SZ,0,399001,深证指数,SZZS',
            'SZ,0,399006,创业板,CYB',
        ];

        return $this->message(1, '', $list);
    }

    /**
     * 返回股票的数据
     *
     * @return \think\response\Json
     */
    public function read()
    {
        // 用户数据
        $iData['market']     = input('market', '', [FILTER_SANITIZE_STRING, 'strtoupper']);
        $iData['stock_code'] = input('symbol', '', FILTER_SANITIZE_NUMBER_INT);

        // 数据验证
        $result = $this->validate($iData, 'Stock.read');
        if ($result !== true) return $this->message(0, $result);

        // 获取基础数据
        $data['basic'] = RedisUtil::getStockData($iData['stock_code'], $iData['market']);

        // 获取行情数据
        $data['quotation'] = RedisUtil::getQuotationData($iData['stock_code'], $iData['market']);
        
        // 加入活跃股票订阅列表
        QuotationRedis::addActiveSubscribe($iData['market'], $iData['stock_code']);

        return $data ? $this->message(1, '', $data) : $this->message(0, '没有数据');
    }

    /**
     * 设置活跃股票
     */
    public function active()
    {
        $market    = input('market', '', [FILTER_SANITIZE_STRING, 'strtoupper']);
        $stockCode = input('stock_code', '', FILTER_SANITIZE_NUMBER_INT);

        if (RedisUtil::isStockExist($stockCode, $market)) {
            // 加入活跃股票订阅列表
            QuotationRedis::addActiveSubscribe($market, $stockCode);
        }
    }
    /**
     * 获取活跃股票
     */
    public function getactive()
    {
        $list = QuotationRedis::getActiveSubscribeList();
        return $this->message(1, '', $list);
    }
    /*
     * 查询活跃列表
     */
    public function getactives()
    {
        $list = QuotationRedis::getActiveStockList();
        return $this->message(1, '操作成功', $list);
    }
    /**
     * 搜索股票
     * -- 股票代码搜索
     * -- 首字母搜索
     * -- 名称搜索
     *
     * @return \think\response\Json
     */
    public function search()
    {
        // 根据用户输入搜索
        $keyword = input('keyword', '', FILTER_SANITIZE_STRING);

        // 数据验证
        $result = $this->validate(['keyword' => $keyword], 'Stock.search');
        if ($result !== true) return $this->message(0, $result);
        
        // 搜索列表
        $searchList = SearchRedis::search($keyword);
        
        if($this->userId)$favolist = Favorite::where('user_id', $this->userId)->column('stock_code');
        //print_r($favolist);exit;
        // 取出基础数据
        $list = [];
        if (count($searchList)) {
            foreach ($searchList as $item) {
                list($market, $stockCode) = explode('|', $item);
                $data = RedisUtil::getStockData($stockCode, $market);
                $item = [];
                if ($data) {
                    $item['stock_code']    = $data['stock_code'];
                    $item['stock_name']    = $data['stock_name'];
                    $item['market']        = $data['market'];
                    $item['security_type'] = $data['security_type'];
                    $item['is_buy_able']   = $data['is_buy_able'];
                    $item['is_kechuang']   = $data['is_kechuang'];
                    $item['favorite']      = isset($favolist)&&!empty($favolist)?in_array($data['stock_code'],$favolist):false;
                    $list[]                = $item;
                }
            }
        }

        return $this->message(1, '', $list);
    }

    /**
     * 禁售列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function black()
    {
        $list = Stock::where('is_black', true)->field([
            "market||stock_code||'    '||stock_name" => 'title',
        ])->select()->toArray();

        return $this->message(1, '', $list);
    }

    /**
     * 科创板列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function kechuang()
    {
        $list = Stock::where('is_kechuang', true)->field('stock_code,stock_name,market')->select();

        // 股票数据
        $ret = [];
        foreach ($list as $item) {
            // 基础数据
            $data = RedisUtil::getStockData($item['stock_code'], $item['market']);
            if (count($data) == 0) continue;

            $item['stock_code']    = $data['stock_code'];
            $item['stock_name']    = $data['stock_name'];
            $item['market']        = $data['market'];
            $item['security_type'] = $data['security_type'];
            $item['is_buy_able']   = $data['is_buy_able'];

            // 行情数据
            $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);

            $ret[] = [
                'basic'     => $item,
                'quotation' => $quotation,
            ];
        }

        return $this->message(1, '', $ret);
    }
    
    /*获取个股数据*/
	public function market()
	{
        $iData['stock_code'] = input('symbol', '', FILTER_SANITIZE_NUMBER_INT);
        $iData['market']     = input('market', '', [FILTER_SANITIZE_STRING, 'strtoupper']);
        // 数据验证
        $result = $this->validate($iData, 'Stock.market');
        if ($result !== true) return $this->message(0, $result);
        // 获取用户自选列表
        if(!$this->userId) return $this->message(0, '请重新登陆');
        $rest = Favorite::where('user_id', $this->userId)->where('stock_code', $iData['stock_code'])->find();
        // 行情数据
        $data = RedisUtil::getQuotationData($iData['stock_code'], $iData['market'],false);
        // 加入活跃股票订阅列表
        QuotationRedis::addActiveSubscribe($iData['market'], $iData['stock_code']);
        $data['favorite'] = empty($rest) ? false : true;
        return json(['data' => $data, 'status' => 1, 'message' => '操作成功']);
	}
	
}
