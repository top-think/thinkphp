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

// REST模式定义文件
return array(

    'core'          =>   array(
        THINK_PATH.'Common/functions.php', // 标准模式函数库
        CORE_PATH.'Core/Log.class.php',    // 日志处理类
        CORE_PATH.'Core/Dispatcher.class.php', // URL调度类
        CORE_PATH.'Core/App.class.php',   // 应用程序类
        CORE_PATH.'Core/View.class.php',  // 视图类
        MODE_PATH.'Rest/Action.class.php',// 控制器类
    ),

    // 系统行为定义文件 [必须 支持数组直接定义或者文件名定义 ]
    'extends'       =>    MODE_PATH.'Rest/tags.php',

    // 模式配置文件  [支持数组直接定义或者文件名定义]（如有相同则覆盖项目配置文件中的配置）
    'config'        =>   MODE_PATH.'Rest/config.php',
);