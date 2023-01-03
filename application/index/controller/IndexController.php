<?php

namespace app\index\controller;

use app\common\model\Slide;
use app\common\model\Stock;
use util\RedisUtil;
use util\SystemRedis;
use think\Controller;

class IndexController extends Controller
{

    public function index()
    {
        //$url = $this->request->scheme() . '://' . $this->request->rootDomain();
        $this->redirect('dash/index/index');
    }
    //获取系统基础设置
    public function config(){
        $data = SystemRedis::getConfig();
        if(!$data)return json(['code' => 0,'msg' => '没有数据','data' => '']);
        $json['is_regist'] = $data['is_regist'] ?? '0';
        $json['is_invita'] = $data['is_invita'] ?? '0';
        $json['is_smsreg'] = $data['is_smsreg'] ?? '0';
        $json['is_share']  = $data['is_share']  ?? '0';
        $json['kefu_link'] = $data['kefu_link'] ?? '';
        $json['kefuphone'] = $data['kefuphone'] ?? '';
        return json(['code' => 1,'msg' => '获取成功','data' => $json]);
    }
    //获取幻灯片列表
    public function slideshow()
    {
        $this->slideModel = new Slide();
        $list = $this->slideModel->field('id,title,litimg,outlink')->order('create_time desc,id desc')->select()->toArray();
        $json = [];
        foreach ($list as $item) {
            //$data['id']      = $item['id'];
            $data['litimg'] = "http://". $_SERVER['HTTP_HOST'].$item['litimg'];
            $data['outlink'] = $item['outlink'];
            //$data['title']   = $item['title'];
            $json[] = $data;
        }
        return json([
            'code' => 1,
            'msg' => '',
            'data' => $json,
        ]);
    }

    //获取股票今日优选信息列表
    public function selective()
    {
        $this->stockModel = new Stock();
        $stockList = $this->stockModel
            ->where('is_selective=true')
            ->field('id,stock_code,stock_name,is_margin,is_suspended,risk_level,market')
            ->limit(0, 8)
            ->order('create_time desc')
            ->select()
            ->toArray();
			//echo $this->stockModel->getLastSql();
        $showList = [];
        if ($stockList) {
            foreach ($stockList as $item) {
                // 基础数据
                $data = RedisUtil::getStockData($item['stock_code'], $item['market']);
                if (count($data) == 0) continue;

                $item['stock_code'] = $data['stock_code'];
                $item['stock_name'] = $data['stock_name'];
                $item['market'] = $data['market'];
                $item['security_type'] = $data['security_type'];
                $item['is_buy_able'] = $data['is_buy_able'];

                // 行情数据
                $quotation = RedisUtil::getQuotationData($item['stock_code'], $item['market']);

                $showList[] = [
                    'basic' => $item,
                    'quotation' => $quotation,
                ];
            }
        }
        return json([
            'code' => 1,
            'msg' => '',
            'data' => $showList,
        ]);

    }

    //获取二维码
    public function get_qrcode()
    {
        $qrcode = systemRedis::getQrcode();
        if (!$qrcode)return json(['code' => 0,'msg' => '没有数据','data' => '']);
        $data = [
            'wechat_service'    => $qrcode['wechat_official_account'] ?? "",  //微信公众号
            'wechat_kefu'       => $qrcode['wechat_customer_service'] ?? "",  //客服微信号
            'wechat_android'    => $qrcode['wechat_android'] ?? "",
            'wechat_ios'        => $qrcode['wechat_ios'] ?? "",
            'pc_download_url'   => $qrcode['pc_download_url'] ?? ""
        ];
        return json(['code' => 1,'msg' => '','data' => $data]);
    }
    //新浪股票热门行业排行
	public function sinahy(){
		//echo 'okok';exit;
		$output = curl('https://vip.stock.finance.sina.com.cn/q/view/newSinaHy.php');
		if(!$output)return json(['status' => 0, 'message' => '操作失败']);
		//print_r($output);exit;
		$findme = '{"';
		$pos = strpos($output, $findme) + 2;
		$output = substr($output, $pos, -2);
		$t2 = explode('","', mb_convert_encoding($output, 'utf-8', 'gbk'));
		$res = array();
		foreach ($t2 as $k => $v) {
			$res[$k] = explode(',', substr(explode(':', $v)[1], 1));
		}
		if (!$res) {
            return json(['data' => $res, 'status' => 0, 'message' => '操作失败']);
        }
        return json(['data' => $res, 'status' => 1, 'message' => '操作成功']);
	}
	//新浪股票热门行业分类排行
	public function sinaNodeclass($page,$num,$node){
        $url    = 'http://vip.stock.finance.sina.com.cn/quotes_service/api/json_v2.php/Market_Center.getHQNodeData?page='.$page.'&num='.$num.'&sort=symbol&asc=1&node='.$node;
        //print_r($url);exit;
        $json = curl($url);
        $data = json_decode($json, true);
        if (!$data) {
            return json(['data' => $data, 'status' => 0, 'message' => '操作失败']);
        }
        return json(['data' => $data, 'status' => 1, 'message' => '操作成功']);
    }
}
