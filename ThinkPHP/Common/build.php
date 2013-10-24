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
 * ThinkPHP 目录创建和初始化
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */
defined('THINK_PATH') or exit();

// 检查项目目录结构 如果不存在则自动创建
if(!is_dir(COMMON_PATH)) {
    // 创建项目目录结构
    build_app_dir();
}elseif(!is_dir(LOG_PATH)){
    // 检查缓存目录
    check_runtime();
}

// 检查缓存目录(Runtime) 如果不存在则自动创建
function check_runtime() {
    if(!is_dir(RUNTIME_PATH)) {
        mkdir(RUNTIME_PATH);
    }elseif(!is_writeable(RUNTIME_PATH)) {
        header('Content-Type:text/html; charset=utf-8');
        exit('目录 [ '.RUNTIME_PATH.' ] 不可写！');
    }
    mkdir(CACHE_PATH);  // 模板缓存目录
    if(!is_dir(LOG_PATH))   mkdir(LOG_PATH);    // 日志目录
    if(!is_dir(TEMP_PATH))  mkdir(TEMP_PATH);   // 数据缓存目录
    if(!is_dir(DATA_PATH))  mkdir(DATA_PATH);   // 数据文件目录
    return true;
}

// 创建项目目录结构
function build_app_dir() {
    // 没有创建项目目录的话自动创建
    if(!is_dir(APP_PATH)) mkdir(APP_PATH,0755,true);
    if(is_writeable(APP_PATH)) {
        $dirs  = array(
            COMMON_PATH,
            COMMON_PATH.'Common/',
            COMMON_PATH.'Conf/',
            COMMON_PATH.'Lang/',
            APP_PATH.C('DEFAULT_MODULE').'/',
            APP_PATH.C('DEFAULT_MODULE').'/Common/',
            APP_PATH.C('DEFAULT_MODULE').'/Controller/',
            APP_PATH.C('DEFAULT_MODULE').'/Model/',
            APP_PATH.C('DEFAULT_MODULE').'/Conf/',
            APP_PATH.C('DEFAULT_MODULE').'/View/',
            RUNTIME_PATH,
            CACHE_PATH,
            LOG_PATH,
            TEMP_PATH,
            DATA_PATH,
            );
        foreach ($dirs as $dir){
            if(!is_dir($dir))  mkdir($dir,0755,true);
        }
        // 写入目录安全文件
        build_dir_secure($dirs);
        // 写入初始配置文件
        if(!is_file(COMMON_PATH.'Conf/config.php'))
            file_put_contents(COMMON_PATH.'Conf/config.php',"<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);");
        // 写入测试Action
        build_first_action();
    }else{
        header('Content-Type:text/html; charset=utf-8');
        exit('项目目录不可写，目录无法自动生成！<BR>请使用项目生成器或者手动生成项目目录~');
    }
}

// 创建测试Action
function build_first_action() {
    $file   =   APP_PATH.C('DEFAULT_MODULE').'/Controller/IndexController'.EXT;
    if(!is_file($file)){
        $content = file_get_contents(THINK_PATH.'Tpl/default_index.tpl');
        file_put_contents($file,$content);
    }
}

// 生成目录安全文件
function build_dir_secure($dirs=array()) {
    // 目录安全写入
    if(defined('BUILD_DIR_SECURE') && BUILD_DIR_SECURE) {
        defined('DIR_SECURE_FILENAME')  or define('DIR_SECURE_FILENAME',    'index.html');
        defined('DIR_SECURE_CONTENT')   or define('DIR_SECURE_CONTENT',     ' ');
        // 自动写入目录安全文件
        $content = DIR_SECURE_CONTENT;
        $files = explode(',', DIR_SECURE_FILENAME);
        foreach ($files as $filename){
            foreach ($dirs as $dir)
                file_put_contents($dir.$filename,$content);
        }
    }
}