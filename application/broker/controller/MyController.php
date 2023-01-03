<?php
namespace app\broker\controller;

use app\stock\controller\BanksController;
use app\stock\controller\CityController;
use app\stock\controller\OrgAccountController;
use app\stock\controller\OrgAccountLogController;
use app\stock\controller\OrgBankCardController;
use app\stock\controller\BrokerController;
use think\App;

class MyController extends BaseController
{

    protected $orgAccountLogApi;
    protected $orgBankApi;
    protected $orgAccountApi;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orgAccountLogApi = new OrgAccountLogController();
        $this->orgBankApi       = new OrgBankCardController();
        $this->orgAccountApi    = new OrgAccountController();
    }

    /**
     * 修改密码页面
     *
     * @return mixed
     */
    public function modify_password()
    {
        return $this->fetch();
    }

    /**
     * 推广码
     *
     * @return mixed
     */
    public function promotion_code()
    {
        $adminUserApi = new BrokerController();
        // 获取推广码
        $promotionCode = $adminUserApi->promotionCode()->getData();
        $code          = $promotionCode['data']['code'];
        $img           = $promotionCode['data']['img'];
        $url           = $promotionCode['data']['url'];

        $this->assign('code', $code);
        $this->assign('img', $img);
        $this->assign('url', $url);

        return $this->fetch();
    }

    /**
     * 绑定银行卡
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bind_bankcard()
    {
        // 获取省市数据
        $cityApi  = new CityController();
        $cityInfo = $cityApi->tree()->getData();
        $this->assign('cityInfo', $cityInfo['data'] ?: []);
        // 获取银行列表
        $banksApi  = new BanksController();
        $banksList = $banksApi->getBanks()->getData();
        $this->assign('banksList', $banksList['data'] ?: []);

        return $this->fetch();
    }

    /**
     * 登录用户提现申请
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function do_withdraw()
    {
        // 获取账户信息
        $accountInfo = $this->orgAccountApi->orgAccount()->getData();
        $this->assign('accountInfo', $accountInfo['code'] == 1 ? $accountInfo['data'] : []);

        return $this->fetch();
    }

    /**
     * 资金明细
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function account_log()
    {
        $orgAccountLogApi = new OrgAccountLogController();
        $logInfo          = $orgAccountLogApi->accountLogByself()->getData();
        $this->assign('logInfo', $logInfo['code'] == 1 ? $logInfo['data'] : []);
        // 获取用户账户变动类型常量
        $changeType = $orgAccountLogApi->getChangeType()->getData();
        $this->assign('changeType', $changeType['data']);
        // 获取经济人总变动金额
        $totalChangeMoney = $orgAccountLogApi->changeMoneyBySelf()->getData();
        $this->assign('totalChangeMoney', $totalChangeMoney['data']);

        return $this->fetch();
    }

    /**
     * 用户提现
     *
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function withdraw()
    {
        // 获取账户信息
        $accountInfo = $this->orgAccountApi->orgAccount()->getData();
        $this->assign('accountInfo', $accountInfo['code'] == 1 ? $accountInfo['data'] : []);

        return $this->fetch();
    }

}
