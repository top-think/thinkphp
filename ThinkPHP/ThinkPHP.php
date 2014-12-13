<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ThinkPHP.php 1829 2010-10-18 08:15:58Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP公共文件
 +------------------------------------------------------------------------------
 */
// 记录和统计时间（微秒）
function G($start,$end='',$dec=3) {
    static $_info = array();
    if(!empty($end)) { // 统计时间
        if(!isset($_info[$end])) {
            $_info[$end]   =  microtime(TRUE);
        }
        return number_format(($_info[$end]-$_info[$start]),$dec);
    }else{ // 记录时间
        $_info[$start]  =  microtime(TRUE);
    }
}

//记录开始运行时间
G('beginTime');
if(!defined('APP_PATH')) define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
if(!defined('RUNTIME_PATH')) define('RUNTIME_PATH',APP_PATH.'/Runtime/');
if(!defined('APP_CACHE_NAME')) define('APP_CACHE_NAME','app');// 指定缓存名称
if(defined('RUNTIME_ALLINONE') && is_file(RUNTIME_PATH.'~allinone.php')) {
    // ALLINONE 模式直接载入allinone缓存
    $result   =  require RUNTIME_PATH.'~allinone.php';
    C($result);
    // 自动设置为运行模式
    define('RUNTIME_MODEL',true);
}else{
    if(version_compare(PHP_VERSION,'5.0.0','<'))  die('require PHP > 5.0 !');
    // ThinkPHP系统目录定义
    if(!defined('THINK_PATH')) define('THINK_PATH', dirname(__FILE__));
    if(!defined('APP_NAME')) define('APP_NAME', basename(dirname($_SERVER['SCRIPT_FILENAME'])));
    $runtime = defined('THINK_MODE')?'~'.strtolower(THINK_MODE).'_runtime.php':'~runtime.php';
    if(is_file(RUNTIME_PATH.$runtime)) {
        // 加载框架核心编译缓存
        require RUNTIME_PATH.$runtime;
    }else{
        // 加载编译函数文件
        require THINK_PATH."/Common/runtime.php";
        // 生成核心编译~runtime缓存
        build_runtime();
    }
}
// 记录加载文件时间
G('loadTime');
?>