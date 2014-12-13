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
// $Id: App.class.php 2207 2011-11-30 13:17:26Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP AMF模式应用程序类
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
    static public function run()
    {
        // 设定错误和异常处理
        set_error_handler(array('App',"appError"));
        set_exception_handler(array('App',"appException"));
        //[RUNTIME]
        // 检查项目是否编译过
        // 在部署模式下会自动在第一次执行的时候编译项目
        if(defined('RUNTIME_MODEL')){
            // 运行模式无需载入项目编译缓存
        }elseif(is_file(RUNTIME_PATH.'~'.APP_CACHE_NAME.'.php')  && (!is_file(CONFIG_PATH.'config.php') || filemtime(RUNTIME_PATH.'~'.APP_CACHE_NAME.'.php')>filemtime(CONFIG_PATH.'config.php'))) {
            // 直接读取编译后的项目文件
            C(include RUNTIME_PATH.'~'.APP_CACHE_NAME.'.php');
        }else{
            // 预编译项目
            App::build();
        }
        //[/RUNTIME]

    	//导入类库
    	Vendor('Zend.Amf.Server');
    	//实例化AMF
    	$server = new Zend_Amf_Server();
        $actions =  explode(',',C('APP_AMF_ACTIONS'));
        foreach ($actions as $action)
       	    $server -> setClass($action.'Action');
    	echo $server -> handle();

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
    static private function build()
    {
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
            if(!C('APP_DEBUG'))  $common   .= compile(COMMON_PATH.'common.php');
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
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    static public function appException($e)
    {
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
    static public function appError($errno, $errstr, $errfile, $errline)
    {
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