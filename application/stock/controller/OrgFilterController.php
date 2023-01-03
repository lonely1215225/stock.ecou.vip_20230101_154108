<?php
namespace app\stock\controller;

use app\common\model\AdminUser;

/**
 * 供各列表按代理商/经纪人搜索用
 *
 * @package app\stock\controller
 */
class OrgFilterController extends BaseController
{

    // 获取代理商列表
    public function agent()
    {
        // 获取代理商
        $agentList = AdminUser::where('role', ADMIN_ROLE_AGENT)->where('pid', $this->adminId)->order('id', 'ASC')->column('org_name', 'id');

        return $this->message(1, '', $agentList);
    }

    // 根据代理商ID获取经纪人列表
    public function broker()
    {
        $agentID = input('agent_id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 如果当前登陆的是代理商
        if ($this->role == ADMIN_ROLE_AGENT) {
            $agentID = $this->adminId;
        }

        // 获取经纪人
        $brokerList = AdminUser::where('role', ADMIN_ROLE_BROKER)->where('pid', $agentID)->order('id', 'ASC')->column('org_name', 'id');

        return $this->message(1, '', $brokerList);
    }

}
