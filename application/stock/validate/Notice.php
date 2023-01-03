<?php
namespace app\stock\validate;

use think\Validate;

class Notice extends Validate
{

    protected $rule = [
        'title'   => 'require',
        'content' => 'require',
        'id'      => 'require|number',
    ];

    protected $message = [
        'title.require'   => '标题不能为空',
        'content.require' => '内容不能为空',
        'id.require'      => '编号不能为空',
        'id.number'       => '编号只能为数字',
    ];

    protected $scene = [
        'notice_edit' => ['title', 'content'],
    ];

}
