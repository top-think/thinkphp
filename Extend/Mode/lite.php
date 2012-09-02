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
// $Id: lite.php 2702 2012-02-02 12:35:01Z liu21st $

// Lite模式定义文件
return array(
    'core'         =>   array(
        THINK_PATH.'Common/functions.php',   // 系统函数库
        CORE_PATH.'Core/Log.class.php',// 日志处理
        MODE_PATH.'Lite/App.class.php', // 应用程序类
        MODE_PATH.'Lite/Action.class.php',// 控制器类
        MODE_PATH.'Lite/Dispatcher.class.php',
    ),

    // 项目别名定义文件 [支持数组直接定义或者文件名定义]
    'alias'         =>    array(
        'Model'         =>   MODE_PATH.'Lite/Model.class.php',
        'Db'                  =>    MODE_PATH.'Lite/Db.class.php',
        'ThinkTemplate' => CORE_PATH.'Template/ThinkTemplate.class.php',
        'TagLib'        => CORE_PATH.'Template/TagLib.class.php',
        'Cache'         => CORE_PATH.'Core/Cache.class.php',
        'Debug'         => CORE_PATH.'Util/Debug.class.php',
        'Session'       => CORE_PATH.'Util/Session.class.php',
        'TagLibCx'      => CORE_PATH.'Driver/TagLib/TagLibCx.class.php',
    ), 

    // 系统行为定义文件 [必须 支持数组直接定义或者文件名定义 ]
    'extends'    =>    MODE_PATH.'Lite/tags.php',

);