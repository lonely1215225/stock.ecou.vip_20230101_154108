<?php

namespace util;

use app\common\model\Stock;
use app\common\model\User;
use app\common\model\UserAccount;
use util\TradingRedis;
use util\TradingUtil;
class RedisUtil
{

    // redis实例
    private static $redis = null;

    /**
     * 返回redis的静态实例
     *
     * @return \Redis
     */
    public static function redis()
    {
        if (is_null(self::$redis)) {
            self::$redis = new \Redis();
            self::$redis->connect(REDIS_SERVER_IP, REDIS_SERVER_PORT);
        }

        return self::$redis;
    }

    /**
     * 生成午夜时间戳
     * -- 用于在午夜过期的KEY
     * -- 午夜向后浮动 10 ~ 120 秒，防止大量KEY同时过期
     *
     * @param bool $delay
     *
     * @return int
     */
    public static function midnight($delay = true)
    {
        $delayTime = $delay ? intval(mt_rand(10, 120)) : 0;
        $time = intval(strtotime('tomorrow'));

        return $time + $delayTime;
    }

    /**
     * 缓存stock表中所有的股票代码到一个集合中
     * - all_stock_code_set
     */
    public static function cacheAllStockCode()
    {
        // 获取所有股票代码
        $stockModel = new Stock();
        $allStockCode = $stockModel->order('stock_code', 'ASC')->column('market||stock_code');
        $allStockCode = $allStockCode ?: ['000000'];

        // 先清除之前的缓存，再写入新的缓存
        $key = 'all_stock_code_set';
        if (self::redis()->exists($key)) {
            self::redis()->del($key);
        }
        self::redis()->sAddArray($key, $allStockCode);
    }

    /**
     * 股票是否存在
     * - stock表
     * - 股票代码在all_stock_code_set中是否存在
     *
     * @param string $stockCode 6位证券代码
     * @param string $market 证券市场代码['SH', 'SZ']
     *
     * @return bool
     */
    public static function isStockExist($stockCode, $market)
    {
        $key = 'all_stock_code_set';
        $code = $market . $stockCode;

        // 如果所有股票缓存列表不存在，则先更新缓存
        if (!self::redis()->exists($key)) {
            self::cacheAllStockCode();
        }

        return self::redis()->sIsMember($key, $code);
    }

    /**
     * 缓存股票列表
     *
     * @param string $market 证券市场代码
     */
    public static function cacheStockList($market = '')
    {
        $market = strtoupper($market);
        $market = in_array($market, array_keys(BasicData::MARKET_LIST)) ? $market : '';
        if (empty($market)) {
            $key = 'stock_list';
            $allStockCode = Stock::order('stock_code', 'ASC')
                ->where('is_special', false)
                ->where('is_black', false)
                ->column("market||','||REPLACE(REPLACE(REPLACE(CAST(market AS varchar), 'SH', '1'), 'SZ', '0'), 'BJ', '2')||','||stock_code||','||stock_name||','||initial");
        } else {
            $key = 'stock_list_' . strtolower($market);
            $allStockCode = Stock::order('stock_code', 'ASC')
                ->where('is_special', false)
                ->where('is_black', false)
                ->where('market', $market)
                ->column("market||','||REPLACE(REPLACE(REPLACE(CAST(market AS varchar), 'SH', '1'), 'SZ', '0'), 'BJ', '2')||','||stock_code||','||stock_name||','||initial");
        }

        // 删除之前的列表
        self::redis()->del($key);

        foreach ($allStockCode as $stock) {
            self::redis()->rpush($key, $stock);
        }
        self::redis()->expireAt($key, self::midnight());
    }

    /**
     * 获取股票列表
     *
     * @param string $market 证券市场代码
     *
     * @return array
     */
    public static function getStockList($market = '')
    {
        $market = strtoupper($market);
        $market = in_array($market, array_keys(BasicData::MARKET_LIST)) ? $market : '';
        
        $key = empty($market) ? 'stock_list' : 'stock_list_' . strtolower($market);

        // 如果所有股票缓存列表不存在，则先更新缓存
        if (!self::redis()->exists($key)) {
            self::cacheStockList($market);
        }
        $list = self::redis()->lRange($key, 0, -1);

        return $list ?: [];
    }

    /**
     * 获取需要订阅行情的股票列表
     * - 默认全部股票
     *
     * @return array
     */
    public static function getSubscribeList()
    {
        $key = 'all_stock_code_set';
        $all = self::redis()->sMembers($key);
        $list = array_map(function ($item) {
            $market = substr($item, 0, 2);
            $securityType = BasicData::marketToSecurityType($market);

            return substr($item, 2) . '_' . $securityType;
        }, $all);

        return $list;
    }

    // 缓存所有股票的基础数据
    public static function cacheAllStockData()
    {
        // 分片获取所有获取股票的数据并缓存
        $stockModel = new Stock();
        $count = $stockModel->count();
        $size = 500;
        $page = 1;
        $offset = ($page - 1) * $size;

        // 没有数据
        if (!$count) return null;

        // 有数据
        while ($offset < $count) {
            $data = $stockModel->order('id', 'ASC')
                ->limit($offset, $size)
                ->column('id,stock_code,stock_name,is_margin,market,risk_level,board_lot,decimal_place,is_suspended,is_special,is_black,is_kechuang');
            foreach ($data as $item) {
                $cacheData['stock_id'] = $item['id'];
                $cacheData['stock_code'] = $item['stock_code'];
                $cacheData['stock_name'] = $item['stock_name'];
                $cacheData['is_margin'] = intval($item['is_margin']);
                $cacheData['market'] = $item['market'];
                $cacheData['security_type'] = BasicData::marketToSecurityType($item['market']);
                $cacheData['risk_level'] = $item['risk_level'];
                $cacheData['board_lot'] = $item['board_lot'];
                $cacheData['decimal_place'] = $item['decimal_place'];
                $cacheData['is_suspended'] = intval($item['is_suspended']);
                $cacheData['is_special'] = intval($item['is_special']);
                $cacheData['is_black'] = intval($item['is_black']);
                $cacheData['is_buy_able'] = intval(!($item['is_suspended'] || $item['is_special'] || $item['is_black']));
                $cacheData['is_kechuang'] = intval($item['is_kechuang']);

                // 缓存数据
                $key = "stock_data_{$item['market']}{$item['stock_code']}";
                self::redis()->del($key);
                self::redis()->hMSet($key, $cacheData);
                // 设置缓存时间
                self::redis()->expire($key, SEARCH_CACHE_EXPIRE_TIME);
            }

            $page++;
            $offset = ($page - 1) * $size;
        }
    }

    /**
     * 缓存单个证券的基础数据
     *
     * @param string $stockCode 证券代码
     * @param string $market 证券市场代码
     *
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function cacheStockData($stockCode, $market)
    {
        // 获取股票证券的数据
        $stockModel = new Stock();
        $data = $stockModel->where('stock_code', $stockCode)
            ->where('market', $market)
            ->field('id,stock_code,stock_name,is_margin,market,risk_level,board_lot,decimal_place,is_suspended,is_special,is_black,is_kechuang')
            ->find();

        // 数据不存在
        if (!$data) return false;

        $cacheData['stock_id'] = $data['id'];
        $cacheData['stock_code'] = $data['stock_code'];
        $cacheData['stock_name'] = $data['stock_name'];
        $cacheData['is_margin'] = intval($data['is_margin']);
        $cacheData['market'] = $data['market'];
        $cacheData['security_type'] = BasicData::marketToSecurityType($data['market']);
        $cacheData['risk_level'] = $data['risk_level'];
        $cacheData['board_lot'] = $data['board_lot'];
        $cacheData['decimal_place'] = $data['decimal_place'];
        $cacheData['is_suspended'] = intval($data['is_suspended']);
        $cacheData['is_special'] = intval($data['is_special']);
        $cacheData['is_black'] = intval($data['is_black']);
        $cacheData['is_buy_able'] = intval(!($data['is_suspended'] || $data['is_black']));
        $cacheData['is_kechuang'] = intval($data['is_kechuang']);

        // 数据存在，缓存数据
        $key = "stock_data_{$market}{$stockCode}";
        self::redis()->del($key);
        //缓存数据
        $ret = self::redis()->hMSet($key, $cacheData);
        // 设置缓存时间
        self::redis()->expire($key, SEARCH_CACHE_EXPIRE_TIME);
        return $ret ? true : false;
    }

    /**
     *
     * 获取一个证券的基础数据
     *
     * @param $stockCode
     * @param $market
     *
     * @return array
     */
    public static function getStockData($stockCode, $market)
    {
        try {
            // 证券市场是否合法
            if (!in_array($market, array_keys(BasicData::MARKET_LIST))) return [];
            
            // 股票在系统中是否存在
            if (!self::isStockExist($stockCode, $market)) return [];
            
            // 检查缓存
            $key = "stock_data_{$market}{$stockCode}";
            if (!self::redis()->exists($key)) {
                self::cacheStockData($stockCode, $market);
            }
            
            // 获取缓存中的数据
            $data = self::redis()->hMGet($key, [
                'stock_id',
                'stock_code',
                'stock_name',
                'is_margin',
                'market',
                'security_type',
                'risk_level',
                'board_lot',
                'decimal_place',
                'is_suspended',
                'is_special',
                'is_black',
                'is_buy_able',
                'is_kechuang'
            ]);
            
        } catch (\Exception $e) {

        } catch (\Throwable $e) {

        }


        return $data ?? [];
    }

    /**
     * 缓存token及对应的用户数据
     *
     * @param string $token
     * @param array $data 用户数据
     */
    public static function cacheToken($token, $data)
    {
        // 切换redis数据库
        self::redis()->select(1);

        // 缓存数据
        $key = $token;
        self::redis()->hMSet($key, $data);

        // 默认一个小时过期
        self::redis()->expire($key, TOKEN_EXPIRE_TIME);

        // 切换数据库
        self::redis()->select(0);
    }

    /**
     * 根据token获取用户数据
     * - 返回空数组时表示未登录
     *
     * @param $token
     *
     * @return array
     */
    public static function getToken($token)
    {
        // 切换redis数据库
        self::redis()->select(1);

        if (self::redis()->exists($token)) {
            $user = self::redis()->hMGet($token, ['user_id', 'username', 'mobile', 'platform_id', 'agent_id', 'broker_id', 'is_bound_bank_card']);

            // 用户每次操作时刷新超时时间
            self::redis()->expire($token, TOKEN_EXPIRE_TIME);

            // 切换数据库
            self::redis()->select(0);

            return $user;
        } else {
            // 切换数据库
            self::redis()->select(0);

            return [];
        }
    }

    /**
     * 删除token
     * - 此操作会使用户退出登录
     *
     * @param $token
     */
    public static function deleteToken($token)
    {
        // 切换redis数据库
        self::redis()->select(1);

        self::redis()->del($token);

        // 切换数据库
        self::redis()->select(0);
    }

    /**
     * 缓存管理员token及对应的数据
     *
     * @param string $token
     * @param array $data 用户数据
     */
    public static function cacheAdminToken($token, $data)
    {
        // 切换redis数据库
        self::redis()->select(2);

        // 缓存数据
        $key = $token;
        self::redis()->hMSet($key, $data);

        // 默认一个小时过期
        self::redis()->expire($key, TOKEN_EXPIRE_TIME);

        // 切换数据库
        self::redis()->select(0);
    }

    /**
     * 根据token获取用户数据
     * - 返回空数组时表示未登录
     *
     * @param $token
     *
     * @return array
     */
    public static function getAdminToken($token)
    {
        // 切换redis数据库
        self::redis()->select(2);

        if (self::redis()->exists($token)) {
            $user = self::redis()->hMGet($token, ['admin_id', 'admin_name', 'mobile', 'role']);

            // 用户每次操作时刷新超时时间
            self::redis()->expire($token, TOKEN_EXPIRE_TIME);

            // 切换数据库
            self::redis()->select(0);

            return $user;
        } else {
            // 切换数据库
            self::redis()->select(0);

            return [];
        }
    }

    /**
     * 删除token
     * - 此操作会使管理员退出登录
     *
     * @param $token
     */
    public static function deleteAdminToken($token)
    {
        // 切换redis数据库
        self::redis()->select(2);

        self::redis()->delete($token);

        // 切换数据库
        self::redis()->select(0);
    }

    /**
     * 缓存行情数据
     *
     * @param array $data 需要缓存的行情数据
     */
    public static function cacheQuotation($data)
    {
        if (is_array($data) && count($data)) {
            $market = BasicData::securityTypeToMarket($data['SecurityType']);
            $stockCode = $data['SecurityCode'];

            // 时间戳
            $data['last_date_time'] = date('Y-m-d H:i:s');
            $data['last_time'] = time();

            $key = 'stock_hq_' . $market . $stockCode;
            self::redis()->hMSet($key, $data);
        }
    }
    /*查询股票实时行情*/
    public static function getQuotationData($stockCode, $market, $alive=true)
    {
        self::redis()->rpush('RequestList', $market.$stockCode);
        self::redis()->expireAt('RequestList', self::midnight());
        $key = 'stock_hq_' . $market . $stockCode;
        $data = self::redis()->hGetAll($key);
        if(!empty($data)&&is_array($data)){
            $nowHIS = intval(date('His'))-intval(date('His',$data[80]));
            $item = $nowHIS <= 5 && $nowHIS >= 0 ? $data : self::checkAndGetStock($key,$market,$stockCode,$data,$alive);
        }else{
            $item = self::checkAndGetStock($key,$market,$stockCode,$data,$alive);
        }
        $json = self::JsonRegroup($item) ?? [];
        return $json ?? [];
    }
    public static function checkAndGetStock($key,$market,$stockCode,$data,$alive)
    {
        try {
            if($alive == true) return $data;
            $item = getApiStock(strtolower($market).$stockCode);
            if($item) self::redis()->hMSet($key, $item);
        } catch (\Exception $e) {
            $item = $data;
        }
        return $item ?? [] ;
    }
    /**
     * 获取股票的行情信息
     *
     * @param $stockCode
     * @param $market
     *
     * @return array
     */
    /*public static function getQuotationData($stockCode, $market)
    {
        try {
            $key = 'stock_hq_' . $market . $stockCode;
            $data = self::redis()->hGetAll($key);
            $item = is_array($data) ? $data : [];
            
    	    $json = self::JsonRegroup($item) ?? [];
        } catch (\Exception $e) {
            
        } catch (\Throwable $e) {
            
        }
        return $json ?? [];
    }*/
    
    public static function JsonRegroup($item)
    {
        $json['market']   = isset($item[0])?strtoupper($item[0]):'';
        $json['code']     = isset($item[2])?$item[2]:'';
        $json['name']     = isset($item[1])?$item[1]:'';
        $json['Price']    = isset($item[3])?$item[3]:'';
        $json['Close']    = isset($item[4])?$item[4]:'';
        $json['Open']     = isset($item[5])?$item[5]:'';
        $json['High']     = isset($item[33])?$item[33]:'';
        $json['Low']      = isset($item[34])?$item[34]:'';
        $json['Highest']  = isset($item[41])?$item[41]:''; 
		$json['Lowest']   = isset($item[42])?$item[42]:''; 
        $json['Volume']   = isset($item[36])?$item[36]:'';
        $json['Turnover'] = isset($item[37])?$item[37]:'';
        $json['Pe_ratio'] = isset($item[39])?$item[39]:'';
        $json['Pb_ratio'] = isset($item[46])?$item[46]:'';
        $json['Harden']   = isset($item[47])?$item[47]:'';
        $json['Drop']     = isset($item[48])?$item[48]:'';
        $json['Trate']    = isset($item[38])?$item[38]:'';
        $json['TMC']      = isset($item[45])?$item[45]:'';
        $json['Bp1']      = isset($item[9])?$item[9]:'';
        $json['Bv1']      = isset($item[10])?$item[10]:'';
        $json['Bp2']      = isset($item[11])?$item[11]:'';
        $json['Bv2']      = isset($item[12])?$item[12]:'';
        $json['Bp3']      = isset($item[13])?$item[13]:'';
        $json['Bv3']      = isset($item[14])?$item[14]:'';
        $json['Bp4']      = isset($item[15])?$item[15]:'';
        $json['Bv4']      = isset($item[16])?$item[16]:'';
        $json['Bp5']      = isset($item[17])?$item[17]:'';
        $json['Bv5']      = isset($item[18])?$item[18]:'';
        $json['Sp1']      = isset($item[19])?$item[19]:'';
        $json['Sv1']      = isset($item[20])?$item[20]:'';
        $json['Sp2']      = isset($item[21])?$item[21]:'';
        $json['Sv2']      = isset($item[22])?$item[22]:'';
        $json['Sp3']      = isset($item[23])?$item[23]:'';
        $json['Sv3']      = isset($item[24])?$item[24]:'';
        $json['Sp4']      = isset($item[25])?$item[25]:'';
        $json['Sv4']      = isset($item[26])?$item[26]:'';
        $json['Sp5']      = isset($item[27])?$item[27]:'';
        $json['Sv5']      = isset($item[28])?$item[28]:'';
        $json['Range']    = isset($item[3])&&isset($item[4])?round(($item[3]-$item[4]),2):'';
		$json['Rate']     = isset($item[4])&&$item[4]>0?round((($item[3]-$item[4])/$item[4]*100),2):'';
        $json['last_time']= isset($item[80])?$item[80] : '';
        $json['last_date_time'] = isset($item[80]) ? self::market_time($item[80]) : date('Y-m-d H:i:s');
        
        return $json;
    }
    public static function market_time($time)
    {
        if(!$time) return date('Y-m-d H:i:s');
        $nowHI = intval(date('Hi'));
        if(930  > $nowHI || $nowHI > 1500){
            return date('Y-m-d 15:00:00',$time);
        }
        if(1130 < $nowHI && $nowHI < 1300){
            return date('Y-m-d 11:30:00',$time);
        }else{
            return date('Y-m-d H:i:s',$time);
        }
    }
    /**
     * 缓存用戶是否禁买，禁卖
     *
     * @param array $data 需要缓存的行情数据
     */
    public static function getUserData($userID)
    {
        try {
            // 证券市场是否合法
            if (!$userID) return [];

            $key = 'user_set_' . $userID;
            // 用户信息在系统中是否存在
            if (!self::redis()->exists($key)) {
                self::cacheUserData($userID);
            }

            // 获取缓存中的数据
            $data = self::redis()->hMGet($key, [
                'is_deny_login',
                'is_deny_cash',
                'is_deny_buy',
                'is_deny_sell'
            ]);
        } catch (\Exception $e) {

        } catch (\Throwable $e) {

        }

        return $data ?? [];

    }

    /**
     * 缓存用戶是否禁买，禁卖，禁止登陆，禁止提现
     *
     * @param array $data 需要缓存的行情数据
     */
    public static function cacheUserData($userID)
    {
        if ($userID) {
            $key = 'user_set_' . $userID;
            if (self::redis()->exists($key)) {
                self::redis()->del($key);
            }

            $userInfo = User::where('id', $userID)->column('CAST(is_deny_login AS int),CAST(is_deny_cash AS int),CAST(is_deny_buy AS int),CAST(is_deny_sell AS int)', 'id');

            self::redis()->hMSet($key, $userInfo);
        }
    }

    /**
     * @return null
     */
    public static function cacheUserCashCoupon($userID)
    {
        if($userID) {
            $key = 'user_account_'.$userID;
            $userAccountInfo = UserAccount::where('user_id', $userID)->column('cash_coupon,cash_coupon_time', 'id');

            self::redis()->hMSet($key, $userAccountInfo);
        }
    }

    /**
     *
     */
    public static function getCacheUserCashCoupon($userID){
        if($userID) {
            $key = 'user_account_'.$userID;
            if(!self::redis()->exists($key)){
                self::cacheUserCashCoupon($userID);
            }

            // 获取缓存中的数据
            $data = self::redis()->hMGet($key, [
                'cash_coupon',
                'cash_coupon_time',
            ]);

            self::redis()->hMSet($key, $data);
        }
    }
    /*
    缓存最新获取的所有股票
    */
    public static function cacheupData($data)
    {
        // 数据存在，缓存数据
        $key = "stock_list";
        $stocks = $data['items'];
        // 删除之前的列表
        self::redis()->del($key);
    	foreach ($stocks as $item) {
            //print_r($item);exit;
            $stock = $item ? $item['market'].",".$item['type'].",".$item['code'].",".$item['name'].",".$item['pinyin'] : " ";
            //print_r($stock);exit;
            self::redis()->rpush($key, $stock);
        }
        $ret =  self::redis()->expireAt($key, self::midnight());
        
        return $ret ? true : false;
    }
    /*
    获取缓存的最新获取的所有股票
    */
    public static function getCacheupData()
    {
        $key = "stock_list";
        $list = self::redis()->lRange($key, 0, -1);
        return $list ?: [];
    }
    
    public static function cacheClientIp($data)
    {
        self::redis()->set('admin_ip',$data);
    }
    public static function getClientHost()
    {
        if(self::redis()->exists('admin_ip')){
            return self::redis()->get('admin_ip');
        }else{
            return '';
        }
    }
}
