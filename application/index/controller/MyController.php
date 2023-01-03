<?php

namespace app\index\controller;

use app\common\model\AdminIncome;
use app\common\model\AdminUser;
use app\common\model\User;
use app\common\model\UserAccount;
use app\common\model\Yuebao;
use app\index\logic\UserLogic;
use util\SystemRedis;
use Endroid\QrCode\QrCode;

class MyController extends BaseController
{

    // 上级经纪人的推广二维码图片地址
    public function promotion()
    {
        // 获取对应经纪人的推广码
        $code = AdminUser::where('id', $this->brokerID)->value('code');

        // 二维码图片路径
        $img = $this->request->domain() . '/uploads/qr_code/' . $code . '.png';

        // 推广链接
        $url = $this->request->scheme() . '://' . $this->request->rootDomain() . '/#/register?code=' . $code;

        return $this->message(1, '', [
            'img' => $img,
            'url' => $url,
            'code' => $code,
        ]);
    }

    // 用户的邀请二维码
    public function invite()
    {
        // 获取对应经纪人的推广码
        $code = User::where('id', $this->userId)->value('code');
        if(!$code) {
            // 生成推广码，及二维码图片
            UserLogic::setInviteCode($this->userId, $this->request);
            $code = User::where('id', $this->userId)->value('code');
        }

        // 推广链接
        //$url = $this->request->scheme() . "://" . $this->request->rootDomain() . '/#/pages/public/register?code=' . $code;

        // 检查二维码图片文件是否存在
        /*$file = UPLOAD_DIR . '/qr_code/app_url.png';
        if (!is_file($file)) {
            $qrCode  = new QrCode($url);
            $qrCode->writeFile($file);
        }*/

        $data = SystemRedis::getConfig();
        $url  = $data['h5_url'] ?? '';
        if(!$url){
            $port = $_SERVER['SERVER_PORT'] == '80' ? '' : ':'.$_SERVER['SERVER_PORT'];
            $url  = $this->request->scheme() . "://" . $_SERVER['HTTP_HOST'] . $port . '/down';
        }
        // 检查二维码图片文件是否存在
        $file = UPLOAD_DIR . '/qr_code/app_url.png';
        if (!is_file($file)) {
            $qrCode  = new QrCode($url);
            $qrCode->writeFile($file);
        }
        // 二维码图片路径
        $img  = $this->request->domain() . '/uploads/qr_code/app_url.png';
        $json = [
            'img'    => $img, 
            'url'    => $url, 
            'code'   => $code, 
            'banner' => $data['banner_url']
        ];
        return $this->message(1, '', $json);
    }

    /**
     * 返佣明细
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function commission()
    {
        // 推广数量（一级）
        $inviteCount = User::where('pid', $this->userId)->count();

        // 总佣金
        $totalCommission = UserAccount::where('user_id', $this->userId)->value('total_commission', 0);

        // 返佣列表
        $list = AdminIncome::where('up_user_id', $this->userId)
            ->where('up_user_money', '>', 0)
            ->field('user_id,up_user_money,income_time')
            ->order('income_time', 'desc')
            ->paginate();

        // 来源用户实名信息
        $realName = [];
        $fromUserID = array_column($list->getCollection()->toArray(), 'user_id');
        if (count($fromUserID)) {
            $fromUserID = array_unique($fromUserID);
            $realName = User::where('id', 'IN', $fromUserID)->column('real_name,username', 'id');
        }

        foreach ($list as $key => $item) {
            $real_name = $realName[$item['user_id']]['real_name'];
            if ($real_name) {
                $list[$key]['real_name'] = '*' . mb_substr($real_name, 1, null, 'UTF-8');
            } else {
                $user_name = $realName[$item['user_id']]['username'];
                $list[$key]['real_name'] = substr_replace($user_name, '****', 3, 4);
            }

            unset($list[$key]['user_id']);
        }

        return $this->message(1, '', [
            'invite_count' => $inviteCount,
            'total_commission' => $totalCommission,
            'data' => $list,
        ]);
    }

    /**
     * 我的推广会员列表
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function generalize_user_list()
    {
        // 推广数量（一级）
        $inviteCount = User::where('pid', $this->userId)->count();
        // 总佣金
        $totalCommission = UserAccount::where('user_id', $this->userId)->value('total_commission', 0);

        $userList = User::where('pid', $this->userId)
            ->field('id,username,create_time,platform_id')
            ->order('create_time', 'desc')
            ->paginate();

        // 来源用户实名信息
        $realName = [];
        $fromUserID = array_column($userList->getCollection()->toArray(), 'id');
        if (count($fromUserID)) {
            $fromUserID = array_unique($fromUserID);
            $realName = User::where('id', 'IN', $fromUserID)->column('real_name,username', 'id');
        }

        foreach ($userList as $key => $item) {
            $real_name = $realName[$item['id']]['real_name'];
            if ($real_name) {
                $userList[$key]['real_name'] = '*' . mb_substr($real_name, 1, null, 'UTF-8');
            } else {
                $user_name = $realName[$item['id']]['username'];
                $userList[$key]['real_name'] = substr_replace($user_name, '****', 3, 4);
            }

            unset($userList[$key]['user_id']);
        }

        return $this->message(1, '', [
            'invite_count' => $inviteCount,
            'total_commission' => $totalCommission,
            'data' => $userList,
        ]);
    }

    /**
     * 余额宝万份收益
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function yuebao()
    {
        $data = Yuebao::alias('a')
            ->join(['__USER_ACCOUNT__' => 'b'], 'a.user_id=b.user_id')
            ->where('a.user_id', $this->userId)
            ->field('a.income,a.income_time,a.base_income,b.total_yuebao')
            ->order('a.income_time desc,a.id desc')
            ->find();

        $code = 0;
        $income_type = '昨日收益';
        if (!$data) {
            $yuebao = systemRedis::getYuebao();
            $income       = '0.00';
            $base_income  = $yuebao['yuebao_fee'];
            $total_yuebao = '0.00';
        } else {
            $code = 1;
            $data = $data->getData();
            $income_time = date('Y-m-d', strtotime($data['income_time']));
            if ($income_time == date('Y-m-d')) {
                $income_type = '今日收益';
            }
            $base_income  = $data['base_income'];
            $income       = $data['income'];
            $total_yuebao = $data['total_yuebao'];
        }

        return $this->message($code, '', [
            'income'        => $income, //昨日或今日收益
            'base_income'   => $base_income,//万份收益
            'total_yuebao'  => $total_yuebao,//累计收益
            'income_type'   => $income_type //收益类型，昨日收益或今日收益
        ]);

    }
}
