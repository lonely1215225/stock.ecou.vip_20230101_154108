<?php
namespace app\stock\controller;

use app\common\model\PayCompany;
use util\BasicData;
use think\Db;

class PayCompanyController extends BaseController
{

    /**
     * 获取支付公司列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        // 获取支付公司列表
        $payCompanyList = PayCompany::column('name,pay_type,pay_channel', 'id');

        return $payCompanyList ? $this->message(1, '', $payCompanyList) : $this->message(1, '');
    }

    /**
     * 支付方式
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function paymentWay()
    {
        // 获取支付公司列表（去除类型为online）
        $list = PayCompany::where('pay_channel', 'neq', PAYMENT_WAY_ONLINE)
            ->field('id,name,pay_type,is_open,min,max,pay_channel,to_name,to_org_name,to_branch,to_account,to_qrcode,sort')
            ->paginate();

        return $this->message(1, '', $list);
    }

    /**
     * 支付通道列表
     *
     * @return \think\response\Json
     */
    public function payment_way_list()
    {
        return $this->message(1, '', BasicData::PAYMENT_WAY_LIST);
    }

    /**
     * 编辑支付方式
     *
     * @return PayCompanyController|\think\response\Json
     */
    public function editPaymentWay()
    {
        $data['to_name']     = input('to_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['to_org_name'] = input('to_org_name', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['to_branch']   = input('to_branch', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['to_account']  = input('to_account', '', [FILTER_SANITIZE_STRING, 'trim']);
        $id                  = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');
        try {
            $file = $this->request->file('file');
            if ($file) {
                $info              = $file->rule('unique')
                    ->validate(['size' => 2097152, 'ext' => 'jpg,jpeg,png,gif'])
                    ->move(UPLOAD_DIR . '/pay/');
                $data['to_qrcode'] = '/uploads/pay/' . str_replace("\\", '/', $info->getSaveName());
            }
        } catch (\Exception $e) {
        }

        PayCompany::update($data, [
            ['id', '=', $id],
        ]);
        $rRows = PayCompany::getNumRows();

        return $rRows ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 获取单个支付公司信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        // id
        $id = input('id', '', FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            $item = PayCompany::where('id', $id)
                ->field('id,name,pay_type,is_open,min,max,pay_channel,to_name,to_org_name,to_branch,to_account,to_qrcode')
                ->find();

            return $item ? $this->message(1, '', $item) : $this->message(0, '没有找到文章');
        } else {
            return $this->message(0, '参数错误');
        }
    }

    /**
     * 是否开启编辑
     *
     * @return \think\response\Json
     */
    public function isOpen()
    {
        $id = input('id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数错误');

        // 更新状态
        $upRet = PayCompany::update([
            'is_open' => Db::raw('NOt is_open'),
        ], [
            ['id', '=', $id],
        ]);

        return $upRet ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

}
