<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 导入别名定义
alias_import(array(
    'Dispatcher'         =>   THINK_PATH.'/Mode/Lite/Dispatcher.class.php',
    'Model'         =>   THINK_PATH.'/Mode/Lite/Model.class.php',
    'Db'                  =>    THINK_PATH.'/Mode/Lite/Db.class.php',
    'Debug'              =>    THINK_PATH.'/Lib/Think/Util/Debug.class.php',
    'Session'             =>   THINK_PATH.'/Lib/Think/Util/Session.class.php',
    'ThinkTemplateLite'   =>    THINK_PATH.'/Mode/Lite/ThinkTemplateLite.class.php',
    'ThinkTemplateCompiler'   =>    THINK_PATH.'/Mode/Lite/ThinkTemplateCompiler.class.php',
    )
);
?>