<?php
namespace app\stock\validate;

use think\Validate;

class Article extends Validate
{

    protected $rule = [
        'title'   => 'require',
        'content' => 'require',
        'cat_id'  => 'require',
        'ids'     => ['require', 'regex' => '/^\d+(,\d+)*$/'],
        'id'      => 'require|number',
    ];

    protected $message = [
        'title.require'   => '标题不能为空',
        'content.require' => '内容不能为空',
        'cat_id.require'  => '文章栏目不能为空',
        'ids.require'     => '编号不能为空',
        'ids.regex'       => '编号不符合规则',
        'id.require'      => '编号不能为空',
        'id.number'       => '编号只能为数字',
    ];

    protected $scene = [
        'news_edit' => ['title', 'content', 'cat_id'],
        'cat_add'   => ['title'],
        'news_del'  => ['ids'],
        'cat_del'   => ['id'],
    ];

}
