<?php
namespace app\common\model;

use think\Model;

class UserWalletLog extends Model
{

    // 自动时间戳
    public $autoWriteTimestamp = true;

    // 关闭更新时间
    protected $updateTime = false;

}
