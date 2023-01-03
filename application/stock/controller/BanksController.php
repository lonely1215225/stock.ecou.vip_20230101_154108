<?php
namespace app\stock\controller;

use app\common\model\Banks;

class BanksController extends BaseController
{

    /**
     * 获取银行列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBanks()
    {
        // 实例化模型
        $banksModel = new Banks();
        $banksList  = $banksModel->select()->toArray();

        return $banksList ? $this->message(1, '', $banksList) : $this->message(0, '');
    }

}
