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

// 命令行模式定义文件
return array(
    'core'          =>   array(
        MODE_PATH.'Cli/functions.php',   // 命令行系统函数库
        MODE_PATH.'Cli/Log.class.php',
        MODE_PATH.'Cli/App.class.php',
        MODE_PATH.'Cli/Action.class.php',
    ),

    // 项目别名定义文件 [支持数组直接定义或者文件名定义]
    'alias'         =>   array(
        'Model'     =>   MODE_PATH.'Cli/Model.class.php',
        'Db'        =>   MODE_PATH.'Cli/Db.class.php',
        'Cache'     =>   CORE_PATH.'Core/Cache.class.php',
        'Debug'     =>   CORE_PATH.'Util/Debug.class.php',
    ), 

    // 系统行为定义文件 [必须 支持数组直接定义或者文件名定义 ]
    'extends'       =>    array(), 

    // 项目应用行为定义文件 [支持数组直接定义或者文件名定义]
    'tags'          =>   array(), 

);