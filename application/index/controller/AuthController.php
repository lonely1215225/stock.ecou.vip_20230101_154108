<?php

namespace app\index\controller;

use app\common\model\UserAccount;
use app\common\model\AdminUser;
use app\common\model\User;
use app\index\logic\UserLogic;
use sms\SmsUtil;
use util\CaptchaRedis;
use util\RedisUtil;
use util\SystemRedis;
use think\Db;
use think\facade\Session;


class AuthController extends BaseController
{

    /**
     * 登陆接口
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sign_in()
    {
        // 用户参数
        $data['mobile']   = input('post.mobile', '', ['trim', FILTER_SANITIZE_NUMBER_INT]);
        $data['password'] = input('post.password', '');

        // 验证数据
        $result = $this->validate($data, 'User.SignIn');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 查询用户
        $user = User::where('mobile', $data['mobile'])
            ->field('id,username,mobile,password,is_deny_login,platform_id,agent_id,broker_id,is_bound_bank_card')
            ->find();
        if ($user && password_verify($data['password'], $user['password'])) {
            // 是否被禁止登录
            if ($user['is_deny_login']) return $this->message(0, '禁止登陆');

            // 缓存token及用户数据
            $token = md5($user['id'] . $user['username'] . time());
            $data = [
                'user_id'     => $user['id'],
                'username'    => $user['username'],
                'mobile'      => $user['mobile'],
                'platform_id' => $user['platform_id'],
                'agent_id'    => $user['agent_id'],
                'broker_id'   => $user['broker_id'],
                'is_bound_bank_card' => $user['is_bound_bank_card'],
            ];
            RedisUtil::cacheToken($token, $data);
            
            return $this->message(1, '登陆成功', ['token' => $token]);
        }

        return $this->message(0, '手机号或密码错误');
    }

    /**
     * 退出登录
     *
     * @return \think\response\Json
     */
    public function sign_out()
    {
        if ($this->token != '') {
            RedisUtil::deleteToken($this->token);
        }

        return $this->message(1, '退出登录成功');
    }

    /**
     * 注册接口
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register()
    {
        $config = SystemRedis::getConfig();
        //print_r($config['is_regist']);exit;
        if($config['is_regist']=='0') return $this->message(0, '注册功能已关闭');
        // 用户参数
        $data['mobile']   = $mobile = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['password'] = input('post.password', '');
        $data['confirm']  = input('post.confirm', '');
        $data['captcha']  = $captcha = input('post.captcha', '', FILTER_SANITIZE_NUMBER_INT);
        $data['code'] = input('post.code', '', ['trim', FILTER_SANITIZE_STRING]);

        if (empty($data['mobile'])) return $this->message(0, '请输入您的手机号！');

        $userExist = User::where('mobile|username', '=', $data['mobile'])->column('mobile', 'id');
        if ($userExist) return $this->message(0, '手机号或用户名已经被注册');

        if (!in_array(strlen($data['mobile']), [8, 9, 11])) return $this->message(0, '您输入的手机号码有误!');
        
        // 推荐码长度不符合规范，不予放行
        if($config['is_invita']=='1'){//邀请注册开关
            if (!in_array(strlen($data['code']), [4, 6])) return $this->message(0, '推荐码错误');
        }

        // 验证数据
        $result = $this->validate($data, 'User.Register');
        if ($result !== true) return $this->message(0, $result);

        // 验证 - 手机验证码
        if($config['is_smsreg']=='1'){//短信注册开关
            if (!CaptchaRedis::isCaptchaExist($data['mobile'], $data['captcha'])) return $this->message(0, '手机验证码不正确');
        }
        if (strlen($data['code']) == 4) {
            // 获取推荐码对应的经纪人ID和代理商ID
            $broker = AdminUser::where('code', $data['code'])->field('id,pid')->find();
            if (!$broker) return $this->message(0, '推荐码错误');
            $data['agent_id']  = $broker['pid'];
            $data['broker_id'] = $broker['id'];
        } elseif (strlen($data['code']) == 6) {
            // 6位推广码为用户邀请
            $upUser = User::where('code', $data['code'])->field('id,agent_id,broker_id')->find();
            if (!$upUser) return $this->message(0, '推荐码错误');
            $data['agent_id']  = $upUser['agent_id'];
            $data['broker_id'] = $upUser['broker_id'];
            $data['pid']       = $upUser['id'];
        } else {
            $data['agent_id']  = 99;//99
            $data['broker_id'] = 100;//100
        }

        // 获取代理商对应的平台ID，当前仅有一个平台，ID为1
        $platformID = 1;

        // 用户归属
        $data['platform_id'] = $platformID;

        // 入库操作
        Db::startTrans();
        try {
            // 其他数据
            unset($data['confirm'], $data['code'], $data['captcha']);
            $data['username'] = $data['mobile'];
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['reg_ip']   = $this->request->ip();

            // 写入用户表
            $user = User::create($data);

            // 用户ID
            $userID = $user['id'];
            $accountData['user_id'] = $user['id'];

            // 写入用户账户表
            $userAccount = UserAccount::create($accountData);

            if ($user && $userAccount) {
                Db::commit();
                $ret = true;

                RedisUtil::getCacheUserCashCoupon($userID);
                // 生成推广码，及二维码图片
                UserLogic::setInviteCode($userID, $this->request);
            } else {
                Db::rollback();
                $ret = false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $ret = false;
        }

        // 删除验证码
        try {
            CaptchaRedis::deleteCaptcha($mobile, $captcha);
            Session::delete('sms_captcha');
        } catch (\Exception $e) {
            // TODO 写入日志
        }

        return $ret ? $this->message(1, '注册成功') : $this->message(0, '注册失败');
    }

    /**
     * 重置密码
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reset_password()
    {
        // 用户参数
        $data['mobile']   = $mobile = input('post.mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['password'] = input('post.password', '');
        $data['confirm']  = input('post.confirm', '');
        $data['captcha']  = $captcha = input('post.captcha', '', FILTER_SANITIZE_NUMBER_INT);

        // 验证数据
        $result = $this->validate($data, 'User.ResetPassword');
        if ($result !== true) return $this->message(0, $result);

        // 验证 - 手机验证码
        if (!CaptchaRedis::isCaptchaExist($data['mobile'], $data['captcha'])) return $this->message(0, '手机验证码不正确');

        unset($data['confirm'], $data['captcha']);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        // 更新用户表
        $userInfo = User::where('username', $data['mobile'])->field('password')->find();
        $userInfo['password'] = $data['password'];
        $uRet = $userInfo->save();

        // 删除验证码
        try {
            CaptchaRedis::deleteCaptcha($mobile, $captcha);
            Session::delete('sms_captcha');
        } catch (\Exception $e) {
            // TODO 写入日志
        }

        return $uRet ? $this->message(1, '重置密码成功') : $this->message(0, '操作失败');
    }

    /**
     * 发送短信验证码
     * -- 发送注册验证码
     * -- 发送忘记密码验证码
     * -- 发送提现验证码
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send_captcha_sms()
    {
        if (Session::has('sms_captcha')) {
            $resetFlag = Session::get('sms_captcha');
            if ((time() - $resetFlag['time']) < 60) {
                return $this->message(0, '操作太频繁，请一分钟之后再试');
            }
        }

        // 验证码类型
        $type = input('type', '', FILTER_SANITIZE_STRING);
        $type = in_array($type, [SmsUtil::CAPTCHA_REGISTER, SmsUtil::CAPTCHA_RESET_PASSWORD, SmsUtil::CAPTCHA_WITHDRAW]) ? $type : '';
        if ($type == '') return $this->message(0, '非法操作');
        // 手机号
        if (SmsUtil::CAPTCHA_WITHDRAW == $type) {
            $mobile = $this->mobile;
        } else {
            $mobile = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);

            //检测手机号是否正确
            if (!in_array(strlen($mobile), [8, 9, 11])) return $this->message(0, '您输入的手机号码有误!');

            //检查是否注册
            $user = User::where('mobile', $mobile)->field('1')->limit(1)->find();
            if (SmsUtil::CAPTCHA_REGISTER == $type) {
                if ($user) {
                    return $this->message(0, '您填写的手机号已经被注册！');
                }
            }

            //找回密密码
            if (SmsUtil::CAPTCHA_RESET_PASSWORD == $type) {
                if (!$user) {
                    return $this->message(0, '您填写的手机号码不存在！');
                }
            }
        }

        // 验证
        $result = $this->validate(['mobile' => $mobile], 'User.SendSms');
        if ($result !== true) return $this->message(0, $result);

        $ret = SmsUtil::sendCaptcha($mobile, $type);

        return $ret ? $this->message(1, '验证码发送成功') : $this->message(0, '验证码发送失败，请稍后再试');
    }

    /**
     * 是否已登陆
     * -- 该方法不用做任何处理，BaseController 里面 initialize 方法会自动判断返回
     * -- 调用该方法前必须检测是否已登录，检测会自动进行
     */
    public function is_login()
    {
        $ret = User::update([
            'login_ip'    => $this->request->ip(),
            'update_time' => time(),
        ], [
            ['id', '=', $this->userId],
        ]);
        return $ret ? $this->message(1, '已登录') : $this->message(0, '未登录');
    }

}
