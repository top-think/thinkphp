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

/**
 * ThinkPHP Portal类
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Think {

    private static $_instance = array();

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function start() {
        // 设定错误和异常处理
        register_shutdown_function(array('Think','fatalError'));
        set_error_handler(array('Think','appError'));
        set_exception_handler(array('Think','appException'));
        // 注册AUTOLOAD方法
        spl_autoload_register(array('Think', 'autoload'));
        //[RUNTIME]
        Think::buildApp();         // 预编译项目
        //[/RUNTIME]
        // 运行应用
        App::run();
        return ;
    }

    //[RUNTIME]
    /**
     * 读取配置信息 编译项目
     * @access private
     * @return string
     */
    static private function buildApp() {
        
        // 读取运行模式
        if(defined('MODE_NAME')) { // 读取模式的设置
            $mode   = include MODE_PATH.strtolower(MODE_NAME).'.php';
        }else{
            $mode   =  array();
        }
        if(isset($mode['config'])) {// 加载模式配置文件
            C( is_array($mode['config'])?$mode['config']:include $mode['config'] );
        }

        // 加载项目配置文件
        if(is_file(COMMON_PATH.'Conf/config.php'))
            C(include COMMON_PATH.'Conf/config.php');

        // 加载框架底层语言包
        L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

        // 加载模式系统行为定义
        if(C('APP_TAGS_ON')) {
            if(isset($mode['extends'])) {
                C('extends',is_array($mode['extends'])?$mode['extends']:include $mode['extends']);
            }else{ // 默认加载系统行为扩展定义
                C('extends', include THINK_PATH.'Conf/tags.php');
            }
        }

        // 加载应用行为定义
        if(isset($mode['tags'])) {
            C('tags', is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
        }elseif(is_file(COMMON_PATH.'Conf/tags.php')){
            // 默认加载项目配置目录的tags文件定义
            C('tags', include COMMON_PATH.'Conf/tags.php');
        }

        $compile   = '';
        // 读取核心编译文件列表
        if(isset($mode['core'])) {
            $list  =  $mode['core'];
        }else{
            $list  =  array(
                THINK_PATH.'Common/functions.php', // 标准模式函数库
                CORE_PATH.'Core/Log.class.php',    // 日志处理类
                CORE_PATH.'Core/Dispatcher.class.php', // URL调度类
                CORE_PATH.'Core/App.class.php',   // 应用程序类
                CORE_PATH.'Core/Action.class.php', // 控制器类
                CORE_PATH.'Core/View.class.php',  // 视图类
            );
        }

        // 加载模式别名定义
        if(isset($mode['alias'])) {
            $alias = is_array($mode['alias'])?$mode['alias']:include $mode['alias'];
            alias_import($alias);
            if(!APP_DEBUG) $compile .= 'alias_import('.var_export($alias,true).');';               
        }
     
        if(APP_DEBUG) {
            // 调试模式加载系统默认的配置文件
            C(include THINK_PATH.'Conf/debug.php');
        }else{
            // 部署模式下面生成编译文件
            build_runtime_cache($compile);
        }
        return ;
    }
    //[/RUNTIME]

    /**
     * 系统自动加载ThinkPHP类库
     * 并且支持配置自动加载路径
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
        // 检查是否存在别名定义
        if(alias_import($class)) return ;
        $file       =   $class.'.class.php';
        // 自动加载行为
        if(substr($class,-8)=='Behavior') { // 加载行为
            if(require_array(array(
                CORE_PATH.'Behavior/'.$file,
                EXTEND_PATH.'Behavior/'.$file,
                COMMON_PATH.'Behavior/'.$file),true)
                || (defined('MODE_NAME') && require_cache(MODE_PATH.ucwords(MODE_NAME).'/Behavior/'.$file))) {
                return ;
            }
        }        
        // 自动加载的类库层
        foreach(explode(',',C('APP_AUTOLOAD_LAYER')) as $layer){
            if(substr($class,-strlen($layer))==$layer){
                if(require_array(array(
                    MODULE_PATH.$layer.'/'.$file, // 当前模块目录
                    COMMON_PATH.$layer.'/'.$file, // 公共类库目录
                    ),true)) {
                    return ;
                }
            }            
        }
        // 自动加载的驱动类库
        foreach(explode(',',C('APP_AUTOLOAD_DRIVER')) as $driver){
            if(substr($class, 0, strlen($driver))==$driver){
                if(require_array(array(
                    APP_PATH.'Extend/Driver/'.$driver.'/'.$file,
                    EXTEND_PATH.'Driver/'.$driver.'/'.$file,
                    CORE_PATH.'Driver/'.$driver.'/'.$file),true)){
                    return ;
                }
            }            
        }        

        // 根据自动加载路径设置进行尝试搜索
        foreach (explode(',',C('APP_AUTOLOAD_PATH')) as $path){
            if(import($path.'.'.$class))
                // 如果加载类成功则返回
                return ;
        }
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
            if(class_exists($class)){
                $o = new $class();
                if(!empty($method) && method_exists($o,$method))
                    self::$_instance[$identify] = call_user_func_array(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
        $error = array();
        $error['message']   = $e->getMessage();
        $trace  =   $e->getTrace();
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']      = $e->getFile();
            $error['line']      = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        Log::record($error['message'],Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_PARSE:
          case E_CORE_ERROR:
          case E_COMPILE_ERROR:
          case E_USER_ERROR:
            ob_end_clean();
            // 页面压缩输出支持
            if(C('OUTPUT_ENCODE')){
                $zlib = ini_get('zlib.output_compression');
                if(empty($zlib)) ob_start('ob_gzhandler');
            }
            $errorStr = "$errstr ".$errfile." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write("[$errno] ".$errorStr,Log::ERR);
            self::halt($errorStr);
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
            trace($errorStr,'','NOTIC');
            break;
      }
    }
    
    // 致命错误捕获
    static public function fatalError() {
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:
              case E_PARSE:
              case E_CORE_ERROR:
              case E_COMPILE_ERROR:
              case E_USER_ERROR:  
                ob_end_clean();
                self::halt($e);
                break;
            }
        }
    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error) {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace          = debug_backtrace();
                $e['message']   = $error;
                $e['file']      = $trace[0]['file'];
                $e['line']      = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace']     = ob_get_clean();
            } else {
                $e              = $error;
            }
            if(IS_CLI){
                exit($e['message'].PHP_EOL.'FILE: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']);
            }
        } else {
            //否则定向到错误页面
            $error_page         = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                if (C('SHOW_ERROR_MSG'))
                    $e['message'] = is_array($error) ? $error['message'] : $error;
                else
                    $e['message'] = C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        include C('TMPL_EXCEPTION_FILE');
        exit;
    }
}