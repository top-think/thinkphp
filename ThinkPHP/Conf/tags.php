<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 系统默认的核心行为扩展列表文件
return array(
    // 定义格式：行为名称=>行为标签
    'ReadHtmlCache'     =>  'app_begin', // 读取静态缓存
    'CheckRoute'        =>  'route_check', // 路由检测
    'LocationTemplate'  =>  'view_template', // 自动定位模板文件
    'ParseTemplate'     =>  'view_parse', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
    'ContentReplace'    =>  'view_filter', // 模板输出替换
    'TokenBuild'        =>  'view_filter',   // 表单令牌
    'WriteHtmlCache'    =>  'view_filter', // 写入静态缓存
    'ShowRuntime'       =>  'view_filter', // 运行时间显示
    'ShowPageTrace'     =>  'view_end', // 页面Trace显示
);