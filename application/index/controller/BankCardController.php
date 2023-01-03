<?php

namespace app\index\controller;

use app\common\model\Banks;
use app\common\model\User;
use app\common\model\UserBankCard;
use app\common\model\UserAccount;
use think\Db;
use util\SystemRedis;
use app\index\controller\CityController;

class BankCardController extends BaseController
{
    /**
     * 获取用户银行卡信息接口
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        // 获取用户的银行卡信息
        $bankCard = UserBankCard::where('user_id', $this->userId)->field('bank_number,bank_name,state')->find();

        if ($bankCard) {
            if (strlen($bankCard['bank_number']) == 19) {
                // 19位卡号
                $bankCard['bank_number'] = substr_replace($bankCard['bank_number'], '****', 11, 4);
            } else {
                // 16位卡号
                $bankCard['bank_number'] = substr_replace($bankCard['bank_number'], '****', 8, 4);
            }
        }

        return $this->message(1, '', $bankCard);
    }

    /**
     * 获取完整信息
     * -- 用于用户完善银行卡绑定
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function full()
    {
        $City = new CityController();
        // 获取用户的银行卡信息
        $bankCard = UserBankCard::where('user_id', $this->userId)
            ->field('id,bank_name,bank_number,province,branch,city,real_name,mobile,id_card_number,bank_id')->find();
        if(!$bankCard)return $this->message(0, '为获取到用户银行卡', $bankCard);
        $bankCard['cities'] = '';
        if($bankCard['province']) {
            $cityData = $City->cities($bankCard['province']);
            $getData = $cityData->getData();
            if($getData['data']) {
                $bankCard['cities'] = $getData['data'];
            }
        }

        return $this->message(1, '', $bankCard);
    }

    /**
     * 保存银行卡接口
     * - 新增绑定
     * - 完善银行卡信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save()
    {
        // 用户参数
        $data['user_id'] = $this->userId;
        $data['real_name'] = input('post.real_name', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['id_card_number'] = input('post.id_card_number', '', 'filter_id_card_number');
        $data['mobile'] = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['bank_id'] = input('post.bank_id', '', FILTER_SANITIZE_NUMBER_INT);
        //$data['bank_name'] = input('post.bank_name', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['province'] = input('post.province', '', FILTER_SANITIZE_NUMBER_INT);
        $data['city'] = input('post.city', '', FILTER_SANITIZE_NUMBER_INT);
        $data['branch'] = input('post.branch', '', ['trim', FILTER_SANITIZE_STRING]);
        $data['bank_number'] = input('post.bank_number', '', FILTER_SANITIZE_NUMBER_INT);
        $data['confirm_bank_number'] = input('post.confirm_bank_number', '', FILTER_SANITIZE_NUMBER_INT);
        $banksModel = new Banks();
        $userBank = $banksModel->where(['id' => $data['bank_id']])->column('id,bank_name', 'id');
        $data['bank_name'] = $userBank[$data['bank_id']];

        // 查询银行卡
        $bankCard = UserBankCard::where('user_id', $this->userId)
            ->field('real_name,id_card_number,mobile,bank_id,bank_name,province,city,branch,bank_number,state')
            ->find();

        if ($bankCard) {
            // 此操作为完善银行卡信息
            // 验证数据
            $result = $this->validate($data, 'UserBankCard', 'UserBankCard.Complete');
        } else {
            // 此操作为新增绑定银行卡信息
            // 数据验证
            $result = $this->validate($data, 'UserBankCard');
        }

        // 验证不合法
        if ($result !== true) return $this->message(0, $result);

        // 保存银行卡信息，并设置用户表为已绑定银行卡状态
        Db::startTrans();
        try {
            $userBankCardModel = new UserBankCard();

            // 保存银行卡
            unset($data['confirm_bank_number']);
            if ($userBankCardModel->where('user_id', $this->userId)->count()) {
                $isAdd = false;

                // 完善信息
                $bankCard['mobile'] = $data['mobile'];
                $bankCard['bank_id'] = $data['bank_id'];
                $bankCard['bank_name'] = $data['bank_name'];
                $bankCard['province'] = $data['province'];
                $bankCard['city'] = $data['city'];
                $bankCard['branch'] = $data['branch'];
                $bankCard['state'] = BANK_CARD_BIND;
                // 保存银行卡信息
                $bRet = $bankCard->save();
            } else {
                $isAdd = true;

                // 新增
                $data['state'] = BANK_CARD_BIND;
                $bRet = UserBankCard::create($data);
            }

            // 用户表：银行卡已绑定，姓名
            $uRet = User::update([
                'is_bound_bank_card' => true,
                'real_name' => $data['real_name'],
            ], [['id', '=', $this->userId]]);

            $userAccountRes = true;
            if ($isAdd) {
                $userAccountInfo = UserAccount::where('user_id', $this->userId)->column('cash_coupon,cash_coupon_time', 'id');
                if(empty($userAccountInfo['cash_coupon']) && empty($userAccountInfo['cash_coupon_time'])){
                    $cashCoupon = SystemRedis::getCashCoupon();
                    if($cashCoupon['is_open'] == 1) {
                        $accountData['cash_coupon'] = $cashCoupon['cash_coupon_money'];
                        $accountData['cash_coupon_time'] = time();
                        $userAccountRes = UserAccount::update($accountData, [['user_id', '=', $this->userId]]);
                    }
                }
            }

            if ($bRet && $uRet && $userAccountRes) {
                // 提交事务
                Db::commit();

                // 返回成功信息
                return $this->message(1, '绑定银行卡成功');
            } else {
                // 提交事务
                Db::rollback();

                // 返回成功信息
                return $this->message(0, '绑定银行卡失败1');
            }
        } catch (\Exception $e) {
            Db::rollback();

            // 返回失败信息
            return $this->message(0, '绑定银行卡失败2');
        }
    }

    /**
     * 解绑银行卡接口
     *
     * @return \think\response\Json
     */
    public function delete()
    {
        // TODO:判断有无进行中的提现申请,如果有,不可解绑

        Db::startTrans();
        try {
            // 删除银行卡信息
            $bRet = UserBankCard::where('user_id', $this->userId)->delete();

            // 用户表：未绑定银行卡，清空姓名
            $uRet = User::update([
                'is_bound_bank_card' => false,
                'real_name' => '',
            ], [
                ['id', '=', $this->userId],
            ]);

            if ($bRet && $uRet) {
                Db::commit();

                return $this->message(1, '解绑银行卡成功');
            } else {
                Db::rollback();
            }
        } catch (\Exception $e) {
            Db::rollback();
        }

        return $this->message(0, '解绑银行卡失败');
    }

    /**
     * 返回银行列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function banks()
    {
        $banksModel = new Banks();
        $list = $banksModel->field('id,bank_name')->select()->toArray();

        return $this->message(1, '', $list);
    }

}
