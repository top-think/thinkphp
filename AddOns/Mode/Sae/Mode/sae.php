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
// $Id: sae.php 2701 2012-02-02 12:27:51Z liu21st $

// 系统默认的核心列表文件
return array(
    THINK_PATH.'/Mode/Sae/functions.php',   // 系统函数库
    THINK_PATH.'/Lib/Think/Core/Think.class.php',
    THINK_PATH.'/Lib/Think/Exception/ThinkException.class.php',  // 异常处理类
    THINK_PATH.'/Lib/Think/Core/Dispatcher.class.php', // URL调度和路由类
    THINK_PATH.'/Mode/Sae/App.class.php',   // 应用程序类
    THINK_PATH.'/Lib/Think/Core/Action.class.php', // 控制器类
    THINK_PATH.'/Mode/Sae/Log.class.php',    // 日志处理类
    //THINK_PATH.'/Lib/Think/Core/Model.class.php', // 模型类
    THINK_PATH.'/Mode/Sae/View.class.php',  // 视图类
    THINK_PATH.'/Mode/Sae/alias.php',  // 加载别名
);
?>