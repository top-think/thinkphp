<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <luofei614@gmail.com>
// +----------------------------------------------------------------------

// ThinkPHP 入口文件

//[cluster] 定义路径常量
defined('CLUSTER_PATH') or define('CLUSTER_PATH',ENGINE_PATH.'Cluster/');
//[cluster] 提前系统目录定义
defined('IO_NAME') or define('IO_NAME','auto');
defined('IO_PATH') or define('IO_PATH',APP_PATH.'IO/'.IO_NAME.'.php');
//[cluster] 建立默认应用
if(!file_exists(IO_PATH)) require CLUSTER_PATH.'build_first_app.php';
require IO_PATH;
//[cluster] 记录开始运行时间 移动到加载IO文件之后
$GLOBALS['_beginTime'] = microtime(TRUE);
// 记录内存初始使用
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();
//[cluster] 定义加载IO配置
defined('IO_TRUE_NAME') or define('IO_TRUE_NAME',IO_NAME);
require CLUSTER_PATH.'Lib/Core/ThinkFS.class.php';
defined('RUNTIME_PATH') or define('RUNTIME_PATH',APP_PATH.'Runtime/');
defined('APP_DEBUG') 	or define('APP_DEBUG',false); // 是否调试模式
$runtime = defined('MODE_NAME')?'~'.strtolower(MODE_NAME).'_runtime.php':'~runtime.php';
defined('RUNTIME_FILE') or define('RUNTIME_FILE',RUNTIME_PATH.$runtime);
if(!APP_DEBUG && ThinkFS::file_exists(RUNTIME_FILE)) {
    //[cluster] 部署模式直接载入运行缓存
	ThinkFS::include_file(RUNTIME_FILE);
}else{
    //[cluster] 加载运行时文件
    require CLUSTER_PATH.'Common/runtime.php';
}
