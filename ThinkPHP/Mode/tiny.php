<?php

/**
 * ThinkPHP Tiny模式定义
 * 
 * 主要精简
 * 1，去掉路由
 * 2，去掉URL调度
 * 3，去掉行为、Hook
 * 4，去掉视图
 * 5，去掉控制器的反射
 * 6，去掉Session，构建无状态Api
 * 7，去掉参数绑定、参数过滤、前置后置
 */
return array(
    // 配置文件
    'config' => array(
        THINK_PATH . 'Conf/convention.php', // 系统惯例配置
        CONF_PATH . 'config' . CONF_EXT, // 应用公共配置
    ),

    // 别名定义
    'alias'  => array(
        'Think\Exception'         => CORE_PATH . 'Exception' . EXT,
        'Think\Model'             => CORE_PATH . 'Model' . EXT,
        'Think\Db'                => CORE_PATH . 'Db' . EXT,
        'Think\Cache'             => CORE_PATH . 'Cache' . EXT,
        'Think\Cache\Driver\File' => CORE_PATH . 'Cache/Driver/File' . EXT,
        'Think\Storage'           => CORE_PATH . 'Storage' . EXT,
    ),

    // 函数和类文件
    'core'   => array(
        THINK_PATH  . 'Common/functions.php',
        COMMON_PATH . 'Common/function.php',
        MODE_PATH   . 'Tiny/App' . EXT,
    ),
);
