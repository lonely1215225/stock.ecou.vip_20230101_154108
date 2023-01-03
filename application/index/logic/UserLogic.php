<?php
namespace app\index\logic;

use app\common\model\User;
use Endroid\QrCode\QrCode;

class UserLogic
{

    /**
     * 生成用户邀请码
     *
     * @return bool|string
     */
    public static function createInviteCode()
    {
        $str = mt_rand(100, 1000000000) . mt_rand(100, 1000000000);

        return substr($str, 0, 6);
    }

    /**
     * 生成用户的推广码图片并保存
     *
     * @param                $code
     * @param \think\Request $request
     */
    public static function createQrImg($code, $request)
    {
        try {
            // 保存路径
            $savePath = UPLOAD_DIR . '/qr_code/';
            if (!file_exists($savePath)) mkdir($savePath, 0755, true);

            // 图片路径
            $file = $savePath . $code . '.png';

            // 如果图片不存在则生成图片
            if (!is_file($file)) {
                $content = $request->scheme() . '://' . $request->rootDomain() . '/#/pages/public/register?code=' . $code;
                $qrCode  = new QrCode($content);

                $qrCode->writeFile($file);
            }
        } catch (\Exception $e) {
        }
    }


    /**
     * 设置用户邀请码
     * -- 随机生成推广码
     * -- 同时生成二维码图片
     *
     * @param $userID
     * @param $request
     * @return bool
     */
    public static function setInviteCode($userID, $request)
    {
        // 设置邀请码
        do {
            $code = self::createInviteCode();
            $ret  = User::update([
                'code' => $code,
            ], [
                ['id', '=', $userID],
            ]);
        } while (!$ret);

        // 生成推广码图片
        self::createQrImg($code, $request);

        return $ret ? true : false;
    }

}
