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
// $Id: App.class.php 2701 2012-02-02 12:27:51Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 精简模式应用程序类
 +------------------------------------------------------------------------------
 */
class App
{//类定义开始

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function run() {
        // 设定错误和异常处理
        set_error_handler(array('App',"appError"));
        set_exception_handler(array('App',"appException"));
        //[RUNTIME]
        App::build();    // 预编译项目
        //[/RUNTIME]

        // 应用调度过滤器
        // 如果没有加载任何URL调度器
        // 默认只支持 QUERY_STRING 方式
        Dispatcher::dispatch();

        // 取得模块和操作名称
        // 可以在Dispatcher中定义获取规则
        if(!defined('MODULE_NAME')) define('MODULE_NAME',   App::getModule());       // Module名称
        if(!defined('ACTION_NAME')) define('ACTION_NAME',   App::getAction());        // Action操作

        // 记录应用初始化时间
        if(C('SHOW_RUN_TIME'))  $GLOBALS['_initTime'] = microtime(TRUE);
        // 执行操作
        R(MODULE_NAME,ACTION_NAME);
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }
    //[RUNTIME]
    /**
     +----------------------------------------------------------
     * 读取配置信息 编译项目
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function build() {
        // 加载惯例配置文件
        C(include THINK_PATH.'/Common/convention.php');
        // 加载项目配置文件
        if(is_file(CONFIG_PATH.'config.php')) {
            C(include CONFIG_PATH.'config.php');
        }
        $common   = '';
        // 加载项目公共文件
        if(is_file(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            if(!C('APP_DEBUG')) $common   .= compile(COMMON_PATH.'common.php');
        }
        if(C('APP_DEBUG')) {
            // 调试模式可以加载调试配置文件
            C(include THINK_PATH.'/Common/debug.php');
            if(is_file(CONFIG_PATH.'debug.php')) {
                // 允许项目增加调试模式配置定义
                C(include CONFIG_PATH.'debug.php');
            }
        }else{
            // 部署模式下面生成编译文件
            build_runtime_cache($common);
        }
        return ;
    }
    //[/RUNTIME]
    /**
     +----------------------------------------------------------
     * 获得实际的模块名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getModule() {
        $var  =  C('VAR_MODULE');
        $module = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_MODULE'));
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            define('P_MODULE_NAME',strtolower($module));
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $module = ucfirst(parse_name(strtolower($module),1));
        }
        unset($_POST[$var],$_GET[$var]);
        return $module;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的操作名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getAction() {
        $var  =  C('VAR_ACTION');
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        return $action;
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    static public function appException($e) {
        halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
              $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
              if(C('LOG_RECORD')){
                 Log::write($errorStr,Log::ERR);
              }
              halt($errorStr);
              break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            Log::record($errorStr,Log::NOTICE);
             break;
      }
    }

};//类定义结束
?>