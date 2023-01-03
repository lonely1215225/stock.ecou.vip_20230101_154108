<?php

namespace app\stock\controller;

use app\common\model\AdminUser;
use app\common\model\Yuebao as YuebaoModel;
use app\common\model\User;

use think\App;
use think\Db;
use think\facade\Request;
use util\BasicData;


class YuebaoContent extends BaseController
{
    public function index()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile']    = input('mobile', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['agent_id']  = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['broker_id'] = input('broker_id', 0, FILTER_SANITIZE_NUMBER_INT);
        // 根据传递的参数生产where条件
        if ($data['mobile']) {
            $map[] = ['mobile', '=', $data['mobile']];
        }
        if ($data['agent_id']) {
            $map[] = ['agent_id', '=', $data['agent_id']];
        }
        if ($data['broker_id']) {
            $map[] = ['broker_id', '=', $data['broker_id']];
        }

        // 获取用户信息列表
        $userList            = YuebaoModel::where($map)
            ->field('id,user_id,income_time,wallet_balance,income,base_income,is_received,create_time,update_time')
            ->order('id DESC')
            ->paginate(15, false, ['query' => request()->param()]);
        $dataAll['userList'] = $userList;

        return $dataAll ? $this->message(1, '', $dataAll) : $this->message(0, '收益宝信息为空');
    }
}