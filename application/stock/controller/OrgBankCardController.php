<?php
namespace app\stock\controller;

use app\common\model\OrgBankCard;
use think\App;

class OrgBankCardController extends BaseController
{

    protected $orgBankCardModel;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->orgBankCardModel = new OrgBankCard();
    }

    /**
     * 绑定银行卡
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function bindBankCard()
    {
        // 用户参数
        $data['admin_id']            = $this->adminId;
        $data['real_name']           = input('post.real_name', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['id_card_number']      = input('post.id_card_number', '', 'filter_id_card_number');
        $data['mobile']              = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['bank_id']             = input('post.bank_id', '', FILTER_SANITIZE_NUMBER_INT);
        $data['province']            = input('post.province', '', FILTER_SANITIZE_NUMBER_INT);
        $data['city']                = input('post.city', '', FILTER_SANITIZE_NUMBER_INT);
        $data['branch']              = input('post.branch', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['bank_number']         = input('post.bank_number', '', FILTER_SANITIZE_NUMBER_INT);
        $data['confirm_bank_number'] = input('post.confirm_bank_number', '', FILTER_SANITIZE_NUMBER_INT);
        // 数据验证
        $result = $this->validate($data, 'OrgBankCard');
        if ($result !== true) {
            return $this->message(0, $result);
        }
        // 保存银行卡
        unset($data['confirm_bank_number']);
        $r1 = $this->orgBankCardModel->where('admin_id', $this->adminId)->delete();
        // 新增
        $r2 = $this->orgBankCardModel->save($data);

        return $r2 ? $this->message(1, '绑定银行卡成功') : $this->message(0, '绑定银行卡失败');
    }

}
