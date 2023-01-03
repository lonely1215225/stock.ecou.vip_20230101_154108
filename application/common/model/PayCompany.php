<?php
namespace app\common\model;

use think\Model;

class PayCompany extends Model
{

    // 开启自动时间戳
    public $autoWriteTimestamp = true;

    // 关闭创建时间
    protected $createTime = false;

}
