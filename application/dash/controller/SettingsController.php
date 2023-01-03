<?php
namespace app\dash\controller;

use app\stock\controller\NonTradingDateController;
use app\stock\controller\PayCompanyController;
use app\stock\controller\SystemController;
use util\SystemRedis;
use think\App;

class SettingsController extends BaseController
{

    private $systemApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->systemApi = new SystemController();
    }
    public function config()
    {
        $config = $this->systemApi->getConfig()->getData();
        $this->assign('config', $config['data']);
        return $this->fetch();
    }
    /**
     * 交易时间设置页面
     *
     * @return mixed
     */
    public function trading_time()
    {
        $marketTime = $this->systemApi->getMarketTime();
        $this->assign('marketTime', $marketTime->getData()['data']);

        return $this->fetch();
    }

    /**
     * 非交易日管理
     *
     * @return mixed
     * @throws \Exception
     */
    public function non_trading_date()
    {
        // 已选
        $nonTradingDateApi = new NonTradingDateController();
        $calendar          = $nonTradingDateApi->calendar();
        $this->assign('month', $calendar->getData()['data']);

        return $this->fetch();
    }

    // 交易费用设置
    public function trading_fee()
    {
        $tradingFee = $this->systemApi->getTradingFee();
        $this->assign('tradingFee', $tradingFee->getData()['data']);

        return $this->fetch();
    }

    // 涨跌幅禁买线
    public function buy_limit_rate()
    {
        // 获取涨跌幅禁买线数据
        $limit_rate = $this->systemApi->getBuyLimitRate()->getData();
        $this->assign('limit_rate', $limit_rate['data']);

        return $this->fetch();
    }

    // 支付方式
    public function payment_way()
    {
        // 支付方式列表
        $payCompanyApi = new PayCompanyController();
        $list          = $payCompanyApi->paymentWay()->getData();
        $this->assign('list', $list['data']);
        // 支付通道列表
        $payment_way = $payCompanyApi->payment_way_list()->getData();
        $this->assign('payment_way', $payment_way['data']);

        return $this->fetch();
    }

    // 编辑支付方式
    public function edit_payment_way()
    {
        $payCompanyApi = new PayCompanyController();
        $item          = $payCompanyApi->read()->getData();
        $this->assign('item', $item['data']);

        return $this->fetch();
    }

    /**
     * 收益宝配置
     */
    public function yuebao_set()
    {
        $yueBao = $this->systemApi->getYuebaoSet()->getData();
        $this->assign('yuebao', $yueBao['data']);

        return $this->fetch();
    }

    /**
     * 二维码设置
     * @return mixed
     */
    public function qrcode_set()
    {
        $qrcode = $this->systemApi->getQrcodeSet()->getData();
        $this->assign('qrcode', $qrcode['data']);

        return $this->fetch();
    }
    /**
     * APP设置
     * @return mixed
     */
    public function app_config()
    {
        $config = $this->systemApi->getAppConfig()->getData();
        $this->assign('appconfig', $config['data']);
        return $this->fetch();
    }

    /**
     * 代金券设置
     * @return mixed
     */
    public function cash_coupon_set()
    {
        $cashCoupon = $this->systemApi->getCashCoupon()->getData();
        $this->assign('cashCoupon', $cashCoupon['data']);
        return $this->fetch();
    }
}
