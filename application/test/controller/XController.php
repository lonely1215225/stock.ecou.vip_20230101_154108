<?php
namespace app\test\controller;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use app\common\model\Condition;
use app\common\model\OrderTraded;

use app\common\model\Stock;
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
use app\common\model\Yuebao as YuebaoModel;
use app\index\logic\AccountLog;

class XController extends Controller
{

    /**
     * @throws \Exception
     */
    public function index()
    {
        $x = SearchRedis::search('000727');
        dump($x);
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

}
