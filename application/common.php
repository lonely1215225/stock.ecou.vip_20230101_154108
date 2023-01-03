<?php
// +----------------------------------------------------------------------
// | 应用公共文件
// +----------------------------------------------------------------------

// 引入常量定义
include_once __DIR__ . DIRECTORY_SEPARATOR . 'define.php';
use util\SystemRedis;
use app\common\model\UserAccount;
use Yurun\Util\Chinese;
use Yurun\Util\Chinese\Pinyin;
use util\RedisUtil;
/**
 * 过滤用户输入的身份证号
 *
 * @param $value
 *
 * @return null|string|string[]
 */
function filter_id_card_number($value)
{
    return preg_replace('/[^0-9X]/', '', strtoupper($value));
}

/**
 * 过滤用户输入的浮点数
 *
 * @param $value
 *
 * @return float|double|false
 */
function filter_float($value)
{
    return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}
/*
 * 过滤掉只保留字符串
 */
function get_filter_str($str)
{
    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $str, $matches);
    $str = join('', $matches[0]);
    return $str; //输出 中文字符
}
/**
 * 过滤用户输入的日期
 *
 * @param $value
 *
 * @return false|string
 */
function filter_date($value)
{
    try {
        $date = new \DateTime($value);

        return date('Y-m-d', $date->getTimestamp());
    } catch (\Exception $e) {
        return '';
    }
}


/**
 * 生成6位验证码
 *
 * @return string
 */
function create_captcha()
{
    $str = mt_rand(1, 1000000000) . mt_rand(100, 1000000000);

    return substr($str, 0, 6);
}

/**
 * 将Base64格式的图片，保存为文件
 *
 * @param $base64Img
 * @param $dir
 *
 * @return array
 */
function saveBase64Img($base64Img, $dir)
{
    // 截取data:image/png;base64, 这个逗号后的字符
    $base64Arr = explode(',', $base64Img);
    // MIME类型及扩展名
    $mime = str_replace(['data:', ';base64'], '', $base64Arr[0]);
    if ($mime == 'image/png') {
        $ext = '.png';
    } elseif ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $ext = '.jpg';
    } elseif ($mime == 'image/gif') {
        $ext = '.gif';
    } else {
        return [
            'code' => 0,
            'msg'  => '只能上传jpg,png,gif格式的图片',
        ];
    }

    // 保存的文件夹
    $saveDir = UPLOAD_DIR . "/{$dir}/";
    file_exists($saveDir) || mkdir($saveDir, 0755, true);

    // 新的文件名
    $filename = date('YmdHis') . mt_rand(100000, 999999) . $ext;

    // 对截取后的字符使用base64_decode进行解码image/jpeg;
    $imgData = base64_decode($base64Arr[1]);
    file_put_contents($saveDir . $filename, $imgData); //写入文件并保存

    return [
        'code' => 1,
        'path' => "/uploads/{$dir}/" . $filename,
    ];
}

/**
 * 判断是否可买并返回数据
 *
 * @return array|bool
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException\
 *
 */
function isBuyCashCoupon($userId)
{
    // 获取策略金余额
    $account = UserAccount::where('user_id', $userId)->field('cash_coupon,cash_coupon_time,cash_coupon_frozen,cash_coupon_uptime')->find();
    $upTime = $account['cash_coupon_uptime'];
    $buyCapital = $account['cash_coupon'] - $account['cash_coupon_frozen'];

    $cashCoupon = SystemRedis::getCashCoupon();
    $cashCouponTime = intval($cashCoupon['expiry_time']);
    $cashCouponUnit = $cashCoupon['expiry_unit'];
    $useUnit = $cashCouponUnit == 1  ?  '个月' : '个';
    $expiryDateMsg = $cashCouponTime.$useUnit.'工作日有效';
    if($account['cash_coupon'] <= 0 && $account['cash_coupon_time'] <= 0) return ['buy' => false, 'buyCapital' => 0, 'forcedSell' => false,'expiryDate' => $expiryDateMsg];

    //如果时间不存在
    if(empty($upTime)) return ['buy' => true, 'buyCapital' => $buyCapital, 'forcedSell' => false, 'expiryDate' => $expiryDateMsg];
    $upDate = date('Ymd', $upTime);
    $nowDate = date('Ymd', time());

    if ($cashCouponUnit == 1) {
        $expiryDate = date("Y-m-d", strtotime("+{$cashCouponTime} month", $upTime));
        $expiryTime = strtotime($expiryDate);
    } else {
        $expiryDate = get_days($upTime, $cashCouponTime - 1);
        $expiryTime = strtotime($expiryDate. ' '. $cashCoupon['close_position_time']);
    }

    if(time() >= $expiryTime) {
        $result = ['buy' => false, 'buyCapital' => 0, 'forcedSell' => true, 'expiryDate' => $expiryDateMsg];
    } else {
        if ($upDate == $nowDate) {
            $result = ['buy' => true, 'buyCapital' => $buyCapital, 'forcedSell' => false, 'expiryDate' => $expiryDateMsg];
        } else {
            $result = ['buy' => false, 'buyCapital' => 0, 'forcedSell' => false, 'expiryDate' => $expiryDateMsg];
        }
    }

    return $result;
}

//方法二：
function get_days($time = "", $intvalDays = 1)
{
    $now = empty($time) ? time() : $time;
    $days = array();
    $i = 1;
    while(count($days) < $intvalDays)
    {
        $timer = $now + 3600 * 24 * $i;
        $num= date("N", $timer) -2; //周一开始
        if($num>=-1 and $num<=3)
        {
            $days[]=date("Y-m-d",$now + 3600 * 24 * $i);
        }

        $i++;
    }

    return array_pop($days);
}

function getApiStock($code,$model='stock'){
    /**********************第三方接口********************/
    //$recData['data'] = ThirdMarket($code,$model);
    /**********************腾讯证券网********************/
    $recData['data'] = TencentMarkets($code);
    switch ($model) {
		case 'stock' ://单个股票
		    $stockData = isset($recData['data']) ? $recData['data'][0] : [];
		    break;
		case 'stocks' ://多个股票
		    $stockData = isset($recData['data']) ? $recData['data'] : [];
		    break;
		default :
		    $stockData = isset($recData['data']) ? $recData['data'][0] : [];
		    break;
    }
    return $stockData;
}
/*第三方数据接口*/
function ThirdMarket($code,$model){
    $url     = "http://".LOCAL_STOCK_HOST.":".LOCAL_STOCK_PORT."/api/market/getstocks";
    $data    = ['codes'=> $code];
	$result  = curls($url,$data);
    $recData = json_decode($result, true);
    return $recData;
}
//腾讯数据获取
function TencentMarkets($code){
	$url = 'https://qt.gtimg.cn/q=' . $code;
	$output = curl($url);
	$str  = mb_convert_encoding($output, 'utf-8', 'gbk');
	$str  = str_replace(array("\r\n", "\r", "\n"), "", $str);
	$t2   = explode('~";', $str);
	$json = [];
	foreach ($t2 as $key => $val) {
        $array = explode("~", $val);
        if(isset($array[2])&&isset($array[3])&&isset($array[4])){
            $json[] = jsonQuotation(explode("~", $val));
        }else{
            continue;
        }
    }
	return $json;
}
//腾讯数据解析
function jsonQuotation($item) {
    $item[0]  = isset($item[0])?substr(str_replace('v_','', $item[0]), 0, 2):'';//交易所代号
    $item[80] = isset($item[30]) ? strtotime($item[30]) : time();
    return $item;
}
function apiStockList(){
	$data = getStockList();
    //$data = json_decode($data, true);
    //dump($data);exit;
    $json['count'] = $data['data']['count'];
    foreach ($data['data']['items'] as $item) {
        $cacheData['code']   = trim($item[1]);
        $cacheData['name']   = $item[2];
        $cacheData['type']   = 0;
        $cacheData['market'] = substr($item[0],7,2);
        $cacheData['pinyin'] = strtoupper(getPinYin($item[2]));
        $json['items'][]     = $cacheData;
    }
    //dump($json['items']);exit;
    return $json;
}
function getStockList(){
    $flag = '';
    $url  = 'http://api.waditu.com';
    $post_str = '{
        "api_name":"stock_basic",
        "token":"c30c5266b67ce2e0b93e12ddb18926150b777331e22568b07cabcca3",
        "params":{"list_status":"L","exchange":"'.$flag.'"},
        "fields":""
    }';
    $stocks = cache('StockList:all');
    if(!$stocks){
        $res =  curls($url,$post_str);
        //print_r($res);exit;
        $response = json_decode($res,true);
        if(isset($response['data'])&&!empty($response['data'])){
            $stocks = $response['data']['items']?:'';
            cache('StockList:all',$stocks, 60*60);
        }
    }
    $json['data'] = [
        'count' => count($stocks),
        'items' => $stocks?:'',
    ];
    return $json;
}
function eastStockList() 
{
    $host = "http://43.push2.eastmoney.com";
    $url  = $host."/api/qt/clist/get?pn=1&pz=5000&po=1&np=1&fltt=2&invt=2&fid=f3&fs=m:0+t:6,m:0+t:80,m:1+t:2,m:1+t:23,m:0+t:81+s:2048&fields=f12,f13,f14";
    $ch   = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	curl_close($ch);
    $response = json_decode($output,true);
    if(!$response['data']['diff']) return false;
    //print_r($response['data']['total']);exit;
    if(isset($response['data']['diff'])&&!empty($response['data']['diff'])){
        $json['count'] = $response['data']['total'];
        foreach ($response['data']['diff'] as $item) {
            $cacheData['code']   = trim($item['f12']);
            $cacheData['type']   = toType($item);
            $cacheData['name']   = $item['f14'];
            $cacheData['market'] = toMarket($item);
            $cacheData['pinyin'] = strtoupper(getPinYin($item['f14']));
            $json['items'][]       = $cacheData;
        }
    }
    return $json;
}
function toMarket($item){
    if($item['f12'] == '000001'){
        return 'SZ';
    }
    if(in_array(substr($item['f12'], 0, 1),array('6', '9'))){
	    return 'SH';
	}
	if(in_array(substr($item['f12'], 0, 1),array('0', '2', '3'))){
	    return 'SZ';
	}
	if(in_array(substr($item['f12'], 0, 1),array('4', '8'))){
	    return 'BJ';
	}
}
function toType($item){
	if($item['f13']=='0'&&in_array(substr($item['f12'], 0, 1),array('4', '8'))){
	    return '2';
	}else{
	    return $item['f13'];
	}
}
function curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
function curls($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Access-Token:' . LOCAL_TRADING_TOKEN,
        'Client-Host:'  . RedisUtil::getClientHost(),
    ));
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}
function getPinYin($zh){
    // 特殊字符替换
    $search  = [
        ' ', '　', 'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ', 'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ', 'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ', 'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ', 'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ', 'Ｚ',
        'ａ', 'ｂ', 'ｃ', 'ｄ', 'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ', 'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ', 'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ', 'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ', 'ｙ', 'ｚ',
    ];
    $replace = [
        '', '', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];
    $ret     = Chinese::toPinyin($zh, Pinyin::CONVERT_MODE_PINYIN_FIRST);
    $initial = implode('', $ret['pinyinFirst'][0]);
    $initial = strtoupper(str_replace($search, $replace, $initial));
    preg_match_all('/[a-zA-Z]+/',$initial,$result);
    return join('',$result[0]);
}

function get_between($input, $start, $end)
{
    $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
    return $substr;
}