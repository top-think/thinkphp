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
//[RUNTIME]
// 目录设置
define('CACHE_DIR',  'Cache');
define('HTML_DIR',    'Html');
define('CONF_DIR',    'Conf');
define('LIB_DIR',      'Lib');
define('LOG_DIR',     'Logs');
define('LANG_DIR',    'Lang');
define('TEMP_DIR',    'Temp');
define('TMPL_DIR',     'Tpl');
// 路径设置
define('TMPL_PATH',APP_PATH.'/'.TMPL_DIR.'/');
define('HTML_PATH',APP_PATH.'/'.HTML_DIR.'/'); //
define('COMMON_PATH',   APP_PATH.'/Common/'); // 项目公共目录
define('LIB_PATH',         APP_PATH.'/'.LIB_DIR.'/'); //
define('CACHE_PATH',   RUNTIME_PATH.CACHE_DIR.'/'); //
define('CONFIG_PATH',  APP_PATH.'/'.CONF_DIR.'/'); //
define('LOG_PATH',       RUNTIME_PATH.LOG_DIR.'/'); //
define('LANG_PATH',     APP_PATH.'/'.LANG_DIR.'/'); //
define('TEMP_PATH',      RUNTIME_PATH.TEMP_DIR.'/'); //
define('DATA_PATH', RUNTIME_PATH.'Data/'); //
define('VENDOR_PATH',THINK_PATH.'/Vendor/');
//[/RUNTIME]
// 为了方便导入第三方类库 设置Vendor目录到include_path
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);
?>