<?php
namespace app\index\controller;

use app\common\model\Favorite;
use util\BasicData;
use util\QuotationRedis;
use util\RedisUtil;
use app\stock\controller\StockController;

class FavoriteController extends BaseController
{

    /**
     * 获取用户的自选列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        // 获取用户的自选列表
        $list = Favorite::where('user_id', $this->userId)->column('id,user_id,market,stock_code');
        if(!$list)return $this->message(0, '没有自选数据', $list);;
        // 获取证券市场列表
        $this->stockApi = new StockController();
        $marketList = $this->stockApi->getMarketSignList()->getData();
        $jdata = [];
        foreach ($list as $k => $item) {
            $data = RedisUtil::getStockData($item['stock_code'], $item['market']);
            if (count($data) == 0) continue;
            $jdata[$k]['market']        = $data['market'];
            $jdata[$k]['stock_code']    = $data['stock_code'];
            $jdata[$k]['stock_name']    = $data['stock_name'];
            $jdata[$k]['security_type'] = $data['security_type'];
            $jdata[$k]['is_buy_able']   = $data['is_buy_able'];
            $jdata[$k]['is_kechuang']   = $data['is_kechuang'];
            $data['market'] = $data['is_kechuang'] ? 'KC' : $data['market'];
            $jdata[$k]['sign_name']     = $marketList['data'][$data['market']];
            // 行情数据
            $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);
            // 当前价
            $jdata[$k]['Price'] = $quotation['Price'] ?: '0.00';
            $jdata[$k]['Rate']  = $quotation['Rate'] ?: '0.00';
            //$ret[] = $item;
        }
        return $this->message(1, '', $jdata);
    }

    /**
     * 添加自选接口
     *
     * @return \think\response\Json
     */
    public function save()
    {
        // 用户数据
        $data['user_id']    = $this->userId;
        $data['stock_code'] = input('post.symbol', '', FILTER_SANITIZE_NUMBER_INT);
        $data['market']     = input('post.market', '', [FILTER_SANITIZE_STRING, 'strtoupper']);
        // 数据验证
        $result = $this->validate($data, 'Favorite.add');
        if ($result !== true) return $this->message(0, $result);

        // 写入数据库
        $stockModel = new Favorite();
        
        $ret        = $stockModel->save($data);
        $code       = $ret ? 1 : 0;
        $msg        = $ret ? '添加自选成功' : '添加自选失败';

        // 加入活跃股票订阅列表
        QuotationRedis::addActiveSubscribe($data['market'], $data['stock_code']);

        return $this->message($code, $msg);
    }

    /**
     * 删除自选接口
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function delete()
    {
        $market    = input('post.market', '', FILTER_SANITIZE_STRING);
        $stockCode = input('post.symbol', '', FILTER_SANITIZE_NUMBER_INT);
        // 验证数据
        if (!in_array($market, array_keys(BasicData::MARKET_LIST))) return $this->message(0, '参数不合法');

        $map  = [
            ['user_id', '=', $this->userId],
            ['market', '=', $market],
            ['stock_code', '=', $stockCode],
        ];
        $ret  = Favorite::where($map)->delete();
        $code = $ret ? 1 : 0;
        $msg  = $ret ? '删除自选成功' : '删除自选失败';

        return $this->message($code, $msg);
    }

    /**
     * 获取用户自选列表
     *
     * @return \think\response\Json
     */
    public function simple()
    {
        // 获取用户自选列表
        $list = Favorite::where('user_id', $this->userId)->column('stock_code,market', 'id');

        $ret = [];
        foreach ($list as $item) {
            // 基础数据
            $data = RedisUtil::getStockData($item['stock_code'], $item['market']);
            if (count($data) == 0) continue;
            unset($item['id']);
            $item['stock_name']    = $data['stock_name'];
            $item['security_type'] = $data['security_type'];
            $item['is_kechuang'] = $data['is_kechuang'];
            // 行情数据
            $quotation   = RedisUtil::getQuotationData($item['stock_code'], $item['market']);
            $item['Bp1'] = $quotation['Bp1'];
            $item['Sp1'] = $quotation['Sp1'];
            $ret[]       = $item;
        }

        return $this->message(1, '', $ret);
    }
    /*检查是否是自选股*/
    function verdict(){
        $market    = input('post.market', '', FILTER_SANITIZE_STRING);
        $stockCode = input('post.symbol', '', FILTER_SANITIZE_NUMBER_INT);
        // 验证数据
        if (!in_array($market, array_keys(BasicData::MARKET_LIST))) return $this->message(0, '参数不合法');
        if(!$this->userId) return $this->message(0, '请重新登陆');
        $rest = Favorite::where('user_id', $this->userId)->where('stock_code', $stockCode)->where('market', $market)->find();
        //print_r($rest);exit;
        return $this->message(1, '', $rest);
    }

}