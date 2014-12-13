<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 简洁模式核心定义文件列表
return array(
    THINK_PATH.'/Common/functions.php',   // 系统函数库
    THINK_PATH.'/Lib/Think/Core/Think.class.php',
    THINK_PATH.'/Lib/Think/Exception/ThinkException.class.php',// 异常处理
    THINK_PATH.'/Lib/Think/Core/Log.class.php',// 日志处理
    THINK_PATH.'/Mode/Amf/App.class.php', // 应用程序类
    THINK_PATH.'/Mode/Amf/Action.class.php',// 控制器类
    THINK_PATH.'/Mode/Amf/alias.php', // 加载别名

);
?>