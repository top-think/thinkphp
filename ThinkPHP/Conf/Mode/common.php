<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
      
/**
 * ThinkPHP 普通模式定义
 */
return array(
    // 配置文件
    'config'    =>  array(
        THINK_PATH.'Conf/convention.php',   // 系统惯例配置
        CONF_PATH.'config.php',      // 应用公共配置
    ),

    // 别名定义
    'alias'     =>  array(
        'Think\Log'               => CORE_PATH . 'Log'.EXT,
        'Think\Log\Driver\File'   => CORE_PATH . 'Log/Driver/File'.EXT,
        'Think\Exception'         => CORE_PATH . 'Exception'.EXT,
        'Think\Model'             => CORE_PATH . 'Model'.EXT,
        'Think\Db'                => CORE_PATH . 'Db'.EXT,
        'Think\Template'          => CORE_PATH . 'Template'.EXT,
        'Think\Cache'             => CORE_PATH . 'Cache'.EXT,
        'Think\Cache\Driver\File' => CORE_PATH . 'Cache/Driver/File'.EXT,
        'Think\Storage'           => CORE_PATH . 'Storage'.EXT,
    ),

    // 函数和类文件
    'core'      =>  array(
        THINK_PATH.'Common/functions.php',
        COMMON_PATH.'Common/function.php',
        CORE_PATH . 'Hook'.EXT,
        CORE_PATH . 'App'.EXT,
        CORE_PATH . 'Dispatcher'.EXT,
        //CORE_PATH . 'Log'.EXT,
        CORE_PATH . 'Route'.EXT,
        CORE_PATH . 'Controller'.EXT,
        CORE_PATH . 'View'.EXT,
        CORE_PATH . 'Behavior'.EXT,
        BEHAVIOR_PATH . 'ReadHtmlCacheBehavior'.EXT,
        BEHAVIOR_PATH . 'ShowPageTraceBehavior'.EXT,
        BEHAVIOR_PATH . 'ParseTemplateBehavior'.EXT,
        BEHAVIOR_PATH . 'ContentReplaceBehavior'.EXT,
        BEHAVIOR_PATH . 'WriteHtmlCacheBehavior'.EXT,
    ),
    // 行为扩展定义
    'tags'  =>  array(
        'app_init'      =>  array(
        ),
        'app_begin'     =>  array(
            'Behavior\ReadHtmlCache', // 读取静态缓存
        ),
        'app_end'       =>  array(
            'Behavior\ShowPageTrace', // 页面Trace显示
        ),
        'path_info'     =>  array(),
        'action_begin'  =>  array(),
        'action_end'    =>  array(),
        'view_begin'    =>  array(),
        'view_parse'    =>  array(
            'Behavior\ParseTemplate', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
        ),
        'template_filter'=> array(
            'Behavior\ContentReplace', // 模板输出替换
        ),
        'view_filter'   =>  array(
            'Behavior\WriteHtmlCache', // 写入静态缓存
        ),
        'view_end'      =>  array(),
    ),
);
