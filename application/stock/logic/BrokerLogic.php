<?php
namespace app\stock\logic;

use app\common\model\AdminUser;
use Endroid\QrCode\QrCode;

class BrokerLogic
{

    /**
     * 生成经纪人的推广吗图片并保存
     *
     * @param string         $code
     * @param \think\request $request
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
                $content = $request->scheme() . '://www.' . $request->rootDomain() . '/#/register?code=' . $code;
                $qrCode  = new QrCode($content);

                $qrCode->writeFile($file);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 生成经纪人推广码
     * 格式为4为字母加数字组合，以字母开头
     *
     * @return string
     */
    public static function createPromotionCode()
    {
        // 首字母
        $charsHead = "abcdefghij";
        $str       = $charsHead[mt_rand(0, 9)];
        // 后面3位
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str   .= $chars[mt_rand(0, 35)];
        $str   .= $chars[mt_rand(0, 35)];
        $str   .= $chars[mt_rand(0, 35)];

        return $str;
    }

    /**
     * 设置经纪人推广码
     * -- 随机生成推广码
     * -- 同时生成二维码图片
     *
     * @param int            $brokerID
     * @param \think\Request $request
     *
     * @return bool
     */
    public static function setPromotionCode($brokerID, $request)
    {
        // 设置推广码
        do {
            $code = self::createPromotionCode();
            $ret  = AdminUser::update([
                'code' => $code,
            ], [
                ['id', '=', $brokerID],
            ]);
        } while (!$ret);

        // 生成推广码图片
        self::createQrImg($code, $request);

        return $ret ? true : false;
    }

}
