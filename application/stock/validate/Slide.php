<?php
namespace app\stock\validate;

use think\Validate;

class Slide extends Validate
{

    protected $rule = [
        'title'   => 'require',
        'litimg'  => 'require',
        'ids'     => ['require', 'regex' => '/^\d+(,\d+)*$/'],
        'id'      => 'require|number',
    ];

    protected $message = [
        'title.require'   => '标题不能为空',
        'litimg.require'  => '幻灯片不能为空',
        'id.require'      => '编号不能为空',
        'id.number'       => '编号只能为数字',
    ];

    protected $scene = [
        'edit'      => ['title', 'litimg'],
        'delete'    => ['ids'],
        'del'       => ['id'],
    ];

}
