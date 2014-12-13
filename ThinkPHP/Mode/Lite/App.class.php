<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

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
        }elseif(is_file(RUNTIME_PATH.'~app.php')  && (!is_file(CONFIG_PATH.'config.php') || filemtime(RUNTIME_PATH.'~app.php')>filemtime(CONFIG_PATH.'config.php'))) {
            // 直接读取编译后的项目文件
            C(include RUNTIME_PATH.'~app.php');
        }else{
            // 预编译项目
            App::build();
        }
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
    static private function build()
    {
        // 加载惯例配置文件
        C(include THINK_PATH.'/Common/convention.php');
        // 加载项目配置文件
        if(is_file(CONFIG_PATH.'config.php')) {
            C(include CONFIG_PATH.'config.php');
        }
        $common   = '';
        $debug  =  C('APP_DEBUG');  //  是否调试模式
        // 加载项目公共文件
        if(is_file(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            if(!$debug) { // 编译文件
                $common   .= compile(COMMON_PATH.'common.php');
            }
        }
        // 加载项目编译文件列表
        if(is_file(CONFIG_PATH.'app.php')) {
            $list   =  include CONFIG_PATH.'app.php';
            foreach ($list as $key=>$file){
                // 加载并编译文件
                require $file;
                if(!$debug) {
                    $common   .= compile($file);
                }
            }
        }
        // 如果是调试模式加载调试模式配置文件
        if($debug) {
            // 加载系统默认的开发模式配置文件
            C(include THINK_PATH.'/Common/debug.php');
            if(is_file(CONFIG_PATH.'debug.php')) {
                // 允许项目增加开发模式配置定义
                C(include CONFIG_PATH.'debug.php');
            }
        }else{
            // 部署模式下面生成编译文件
            // 下次直接加载项目编译文件
            if(defined('RUNTIME_ALLINONE')) {
                // 获取用户自定义变量
                $defs = get_defined_constants(TRUE);
                $content  = array_define($defs['user']);
                $content .= substr(file_get_contents(RUNTIME_PATH.'~runtime.php'),5);
                $content .= $common."\nreturn ".var_export(C(),true).';';
                file_put_contents(RUNTIME_PATH.'~allinone.php',strip_whitespace('<?php '.$content));
            }else{
                $content  = "<?php ".$common."\nreturn ".var_export(C(),true).";\n?>";
                file_put_contents(RUNTIME_PATH.'~app.php',strip_whitespace($content));
            }
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
    static private function getModule()
    {
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
    static private function getAction()
    {
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