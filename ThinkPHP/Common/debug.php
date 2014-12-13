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
// $Id: debug.php 2701 2012-02-02 12:27:51Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 默认的调试模式配置文件
 *  如果项目有定义自己的调试模式配置文件，本文件无效
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id: debug.php 2701 2012-02-02 12:27:51Z liu21st $
 +------------------------------------------------------------------------------
 */
if (!defined('THINK_PATH')) exit();
return  array(
    'LOG_RECORD'=>true,  // 进行日志记录
    'LOG_RECORD_LEVEL'       =>   array('EMERG','ALERT','CRIT','ERR','WARN','NOTIC','INFO','DEBUG','SQL'),  // 允许记录的日志级别
    'DB_FIELDS_CACHE'=> false, // 字段缓存信息
    'APP_FILE_CASE'  =>   true, // 是否检查文件的大小写 对Windows平台有效
    'TMPL_STRIP_SPACE'      => false,       // 是否去除模板文件里面的html空格与换行
    'SHOW_ERROR_MSG'        => true,    // 显示错误信息
);
?>