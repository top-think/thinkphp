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
 * ThinkPHP 命令模式应用程序类
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
        App::build(); // 预编译项目
        //[/RUNTIME]

        if(C('URL_MODEL')==1) {// PATHINFO 模式URL下面 采用 index.php module/action/id/4
            $depr = C('URL_PATHINFO_DEPR');
            $path   = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:'';
            if(!empty($path)) {
                $params = explode($depr,trim($path,$depr));
            }
            // 取得模块和操作名称
            define('MODULE_NAME',   !empty($params)?array_shift($params):C('DEFAULT_MODULE'));
            define('ACTION_NAME',  !empty($params)?array_shift($params):C('DEFAULT_ACTION'));
            if(count($params)>1) {
                // 解析剩余参数 并采用GET方式获取
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',$params));
            }
        }else{// 默认URL模式 采用 index.php module action id 4
            // 取得模块和操作名称
            define('MODULE_NAME',   isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:C('DEFAULT_MODULE'));
            define('ACTION_NAME',    isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:C('DEFAULT_ACTION'));
            if($_SERVER['argc']>3) {
                // 解析剩余参数 并采用GET方式获取
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',array_slice($_SERVER['argv'],3)));
            }
        }

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
    static public function appException($e) {
        exit($e->__toString());
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
              exit($errorStr);
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