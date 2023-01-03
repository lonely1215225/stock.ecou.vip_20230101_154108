<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
    // 模板引擎类型 支持 php think 支持扩展
    'type'               => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'          => 1,
    // 模板路径
    'view_path'          => '',
    // 模板后缀
    'view_suffix'        => 'html',
    // 模板文件名分隔符
    'view_depr'          => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'          => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'            => '}',
    // 标签库标签开始标记
    'taglib_begin'       => '{',
    // 标签库标签结束标记
    'taglib_end'         => '}',
    // 模板替换设置
    'tpl_replace_string' => [
        // 公共文件目录
        '__PUBLIC__'    => think\facade\Request::root(),
        // 静态文件目录
        '__STATIC__'    => think\facade\Request::root() . '/static',
        // 前台静态文件目录
        '__HOME__'      => think\facade\Request::root() . '/static/home',
        // 后台静态文件目录
        '__DASH__'      => think\facade\Request::root() . '/static/dash',
        //tinymce富文本编辑器
        '__TINYMCE__'   => think\facade\Request::root() . '/static/tinymce',
        // AdminLTE目录
        '__ADMIN_LTE__' => think\facade\Request::root() . '/static/dash/AdminLTE',
    ],
];
