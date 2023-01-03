<?php
namespace app\common\model;

use think\Model;

class System extends Model
{

    // 自动时间戳
    public $autoWriteTimestamp = true;

    // 关闭创建时间
    protected $createTime = false;

}
