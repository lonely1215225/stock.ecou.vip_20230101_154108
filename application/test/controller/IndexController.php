<?php

namespace app\test\controller;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use app\common\model\Condition;
use app\common\model\OrderTraded;

use app\common\model\Stock;
use app\common\model\System;
use app\common\model\User;
use app\common\model\UserStrategyLog;
use app\cli\logic\OrderCancelMsg;
use app\cli\logic\OrderSell;
use app\index\logic\UserLogic;
use app\stock\logic\BrokerLogic;
use Endroid\QrCode\QrCode;
use think\Controller;
use app\common\model\Order;
use app\common\model\OrderPosition;
use app\common\model\UserAccount;
use app\common\model\UserWithdraw;
use app\index\logic\Calc;
use app\cli\logic\TradedMsg;
use sms\Sms;
use sms\SmsUtil;
use think\Db;
use think\facade\Env;
use think\facade\Session;
use think\Request;
use util\CaptchaRedis;
use util\Debug;
use util\NoticeRedis;
use util\OrderRedis;
use util\RedisUtil;
use util\ScriptRedis;
use util\SearchRedis;
use util\SystemRedis;
use util\TradingUtil;
use Yurun\Util\Chinese;
use Yurun\Util\Chinese\Pinyin;
use app\common\model\PayCompany;
use app\index\logic\Funds;
use util\QuotationRedis;
use app\common\model\Yuebao;
use app\stock\controller\SystemController;
use app\stock\controller\StockController;

class IndexController extends Controller
{

    /**
     * @throws \Exception
     */
    public function index()
    {

        $file = UPLOAD_DIR . '/uploads/qr_code/111.png';
        var_dump($file);
        //Db::execute('UNLOCK tables');
        //$data = RedisUtil::getStockData('688099', 'SH');
        //var_dump($data);
        // 用户的持仓统计
        // $list = OrderPosition::column('sum_buy_value_cost,sum_buy_volume', 'id');
        // foreach ($list as $positionID => $po) {
        //     OrderPosition::update([
        //         'b_cost_price' => bcdiv($po['sum_buy_value_cost'], $po['sum_buy_volume'], 4),
        //     ], [
        //         ['id', '=', $positionID]
        //     ]);
        // }


        // OrderRedis::cacheAllPosition();
        // OrderRedis::cachePositionUserStrategy();

        // Condition::update([
        //     'state' => CONDITION_STATE_EXPIRE,
        // ], [
        //     ['state', ['=', CONDITION_STATE_ING], ['=', CONDITION_STATE_NONE], 'or'],
        //     ['trading_date', '<', TradingUtil::currentTradingDate()],
        // ]);

        // 测试获取五档行情价格
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 5, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 4, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 3, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 2, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 1, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', 0, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', -1, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', -2, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', -3, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', -4, 'only'));
        // dump(QuotationRedis::getStallsPrice('SZ', '002477', -5, 'only'));

        //$upUser = User::where('mobile|username','=',15216414522)->column('mobile', 'id');

        // 缓存持仓数据
//        OrderRedis::cacheAllPosition();
//        OrderRedis::cachePositionUserStrategy();
//
        // 更新所有股票的基础数据
//        RedisUtil::cacheAllStockCode();
//        RedisUtil::cacheAllStockData();
    }

    public static function calcBuyCapital($strategyBalance, $frozen, $isMonthly = false)
    {
        // 获取系统交易费用设置
        $tradingFee = SystemRedis::getTradingFee();
        // 管理费比例
        $managementFee = $isMonthly ? $tradingFee['monthly_m_fee'] : $tradingFee['management_fee'];
        // 保证金比例
        $depositRate = $tradingFee['deposit_rate'];

        // 可用策略金
        $strategy = $strategyBalance - $frozen;

        // 最大可买市值
        $buyCapital = bcdiv($strategy, $depositRate + $managementFee,2);

        return $buyCapital;
    }


    public function deal($volume)
    {
        // $volume < 10000（单笔成交），$volume >= 10000（1到2笔成交），$volume >= 20000（2到4笔成交）
        $tradedCount = 1;
        $volume >= 10000 && $tradedCount = mt_rand(1, 2);
        $volume >= 20000 && $tradedCount = mt_rand(2, 4);
        for ($i = 1; $i <= $tradedCount; $i++) {
            // 需要为后续成交预留足够数量（剩余次数 * 100）
            $hand = mt_rand(1, ($volume - ($tradedCount - $i)) / 100);
            $tradedVolume = $hand * 100;

            // 如果是最后一次成交，则成交数量为全部未成交数量
            if ($i == $tradedCount) {
                $tradedVolume = $volume;
            }

            // 剩余未成交数量
            $volume = $volume - $tradedVolume;

            dump('当前循环次数：' . $i . '，当前hand:' . $hand . ',当前成交量:' . $tradedVolume . '，剩余成交量：' . $volume);
            // 执行成交

        }
    }

    // 生成推广二维码
    public function qr_img()
    {
        // 用户的
        $userCode = User::where('code', 'NOT NULL')->column('code');
        foreach ($userCode as $code) {
            UserLogic::createQrImg($code, $this->request);
        }

        // 经纪人的
        $borkerCode = AdminUser::where('code', 'NOT NULL')->column('code');
        foreach ($borkerCode as $code) {
            BrokerLogic::createQrImg($code, $this->request);
        }
    }

    // 持仓股票的当前行情
    public function position_hq()
    {
        $positionList = OrderPosition::where('is_finished', false)->distinct(true)->field('market,stock_code')->select();

        $quotationList = [];

        if (is_array($quotationList) && count($positionList)) {
            foreach ($positionList as $position) {
                $market = $position['market'];
                $stockCode = $position['stock_code'];

                $stockData = RedisUtil::getStockData($stockCode, $market);

                // 最新行情
                $quotation = RedisUtil::getQuotationData($stockCode, $market);
                $quotationList[] = [
                    'market' => $market,
                    'stock_code' => $stockCode,
                    'stock_name' => $stockData['stock_name'],
                    'Price' => $quotation['Price'],
                    'last_date_time' => $quotation['last_date_time'],
                ];
            }
        }

        $this->assign('quotationList', $quotationList);

        return $this->fetch();
    }

    // 当前订阅的股票行情
    public function hangqing()
    {
        RedisUtil::redis()->sUnionStore('subscribe_all', 'subscribe_active', 'subscribe_position');
        $subscribeList = RedisUtil::redis()->sMembers('subscribe_all');

        $quotationList = [];
        if ($subscribeList) {
            foreach ($subscribeList as $item) {
                list ($stockCode, $market) = explode('_', $item);
                $market    = \util\BasicData::securityTypeToMarket($market);
                $stockData = \util\RedisUtil::getStockData($stockCode, $market);

                // 最新行情
                $quotation       = RedisUtil::getQuotationData($stockCode, $market);
                $quotationList[] = [
                    'market'         => $market,
                    'stock_code'     => $stockCode,
                    'stock_name'     => $stockData['stock_name'] ?? '',
                    'Price'          => $quotation['Price'] ?? 0,
                    'last_date_time' => $quotation['last_date_time'] ?? '',
                ];
            }
        }

        $this->assign('quotationList', $quotationList);

        return $this->fetch();
    }

    protected function message($code, $msg = '', $data = [], $desc = '')
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');

        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'desc' => $desc,
        ]);
    }
}
