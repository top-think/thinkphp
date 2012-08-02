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
// $Id: Think.class.php 2974 2012-06-11 03:46:31Z luofei614@gmail.com $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP Portal类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Think.class.php 2974 2012-06-11 03:46:31Z luofei614@gmail.com $
 +------------------------------------------------------------------------------
 */
class Think {

    private static $_instance = array();

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function start() {
        // 设定错误和异常处理
        // [saebuilder] 去掉错误接管
        // set_error_handler(array('Think','appError'));
        // set_exception_handler(array('Think','appException'));
        // 注册AUTOLOAD方法
        spl_autoload_register(array('Think', 'autoload'));
        //[RUNTIME]
        Think::buildApp();         // 预编译项目
        //[/RUNTIME]
        //编译模版
        self::buildTemplateCache();
        return ;
    }

    // ==================================================================
    //
    // 编译模版
    //
    // ------------------------------------------------------------------
    
    static public function buildTemplateCache(){
        //读取所有模版
        $list=self::getTplFileList(TMPL_PATH);
        foreach($list as $file){
            echo 'parse tpl:'.$file.PHP_EOL;
            ob_start();
                $parmas=array('var'=>array(),'file'=>$file);
                tag('view_parse',$parmas);
            ob_clean();
        }
        //实例化view类
        //执行view类的fetch方法
    }
 static protected  function getTplFileList($dir){
    $ret=array();
    $list=glob($dir.'*');
    foreach($list as $file){
        if(is_dir($file)){
            $ret=  array_merge($ret,  self::getTplFileList($file.'/'));
        }else{
        $ret[]=  $file;
        }
    }
    return $ret;
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
    static private function buildApp() {
        // 加载底层惯例配置文件
        C(include THINK_PATH.'Conf/convention.php');

        // 读取运行模式
        if(defined('MODE_NAME')) { // 模式的设置并入核心模式
            $mode   = include MODE_PATH.strtolower(MODE_NAME).'.php';
        }else{
            $mode   =  array();
        }

        // 加载模式配置文件
        if(isset($mode['config'])) {
            C( is_array($mode['config'])?$mode['config']:include $mode['config'] );
        }

        // 加载项目配置文件
        if(is_file(CONF_PATH.'config.php'))
            C(include CONF_PATH.'config.php');
        //[sae]惯例配置
        C(include SAE_PATH.'Conf/convention_sae.php');
        //[sae]专有配置
        if (is_file(CONF_PATH . 'config_sae.php'))
            C(include CONF_PATH . 'config_sae.php');
        // 加载框架底层语言包
        L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

        // 加载模式系统行为定义
        if(C('APP_TAGS_ON')) {
            if(isset($mode['extends'])) {
                C('extends',is_array($mode['extends'])?$mode['extends']:include $mode['extends']);
            }else{ //[sae] 默认加载系统行为扩展定义
                C('extends', include SAE_PATH.'Conf/tags.php');
            }
        }

        // 加载应用行为定义
        if(isset($mode['tags'])) {
            C('tags', is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
        }elseif(is_file(CONF_PATH.'tags.php')){
            // 默认加载项目配置目录的tags文件定义
            C('tags', include CONF_PATH.'tags.php');
        }

        $compile   = '';
        // 读取核心编译文件列表
        if(isset($mode['core'])) {
            $list   =  $mode['core'];
        }else{
            $list  =  array(
                SAE_PATH.'Common/functions.php', //[sae] 标准模式函数库
                SAE_PATH.'Common/sae_functions.php',//[sae]新增sae专用函数
                SAE_PATH.'Lib/Core/Log.class.php',    // 日志处理类
                CORE_PATH.'Core/Dispatcher.class.php', // URL调度类
                CORE_PATH.'Core/App.class.php',   // 应用程序类
                SAE_PATH.'Lib/Core/Action.class.php', //[sae] 控制器类
                CORE_PATH.'Core/View.class.php',  // 视图类
            );
        }
        // 项目追加核心编译列表文件
        if(is_file(CONF_PATH.'core.php')) {
            $list  =  array_merge($list,include CONF_PATH.'core.php');
        }
        foreach ($list as $file){
            if(is_file($file))  {
                require_cache($file);
                $compile .= compile($file);
            }
        }

        // 加载项目公共文件
        if(is_file(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            // 编译文件
            $compile   .= compile(COMMON_PATH.'common.php');
        }

        // 加载模式别名定义
        if(isset($mode['alias'])) {
            $alias = is_array($mode['alias'])?$mode['alias']:include $mode['alias'];
            alias_import($alias);
            $compile .= 'alias_import('.var_export($alias,true).');';
        }
        // 加载项目别名定义
        if(is_file(CONF_PATH.'alias.php')){ 
            $alias = include CONF_PATH.'alias.php';
            alias_import($alias);
            $compile .= 'alias_import('.var_export($alias,true).');';
        }
        build_runtime_cache($compile);
        return ;
    }
    //[/RUNTIME]

    /**
     +----------------------------------------------------------
     * 系统自动加载ThinkPHP类库
     * 并且支持配置自动加载路径
     +----------------------------------------------------------
     * @param string $class 对象类名
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public static function autoload($class) {
        // 检查是否存在别名定义
        if(alias_import($class)) return ;

        if(substr($class,-8)=='Behavior') { // 加载行为
            if(require_cache(CORE_PATH.'Behavior/'.$class.'.class.php') 
                || require_cache(EXTEND_PATH.'Behavior/'.$class.'.class.php') 
                || require_cache(LIB_PATH.'Behavior/'.$class.'.class.php')
                || (defined('MODE_NAME') && require_cache(MODE_PATH.ucwords(MODE_NAME).'/Behavior/'.$class.'.class.php'))) {
                return ;
            }
        }elseif(substr($class,-5)=='Model'){ // 加载模型
            if((defined('GROUP_NAME') && require_cache(LIB_PATH.'Model/'.GROUP_NAME.'/'.$class.'.class.php'))
                || require_cache(LIB_PATH.'Model/'.$class.'.class.php')
                || require_cache(EXTEND_PATH.'Model/'.$class.'.class.php') ) {
                return ;
            }
        }elseif(substr($class,-6)=='Action'){ // 加载控制器
            if((defined('GROUP_NAME') && require_cache(LIB_PATH.'Action/'.GROUP_NAME.'/'.$class.'.class.php'))
                || require_cache(LIB_PATH.'Action/'.$class.'.class.php')
                || require_cache(EXTEND_PATH.'Action/'.$class.'.class.php') ) {
                return ;
            }
        }

        // 根据自动加载路径设置进行尝试搜索
        $paths  =   explode(',',C('APP_AUTOLOAD_PATH'));
        foreach ($paths as $path){
            if(import($path.'.'.$class))
                // 如果加载类成功则返回
                return ;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得对象实例 支持调用类的静态方法
     +----------------------------------------------------------
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     +----------------------------------------------------------
     * @return object
     +----------------------------------------------------------
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
               // halt(L('_CLASS_NOT_EXIST_').':'.$class);
                echo 'class not find:'.$class.PHP_EOL;//[saebuilder] 为了显示精确的保存
        }
        return self::$_instance[$identify];
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
            if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
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

    /**
     +----------------------------------------------------------
     * 自动变量设置
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param $name 属性名称
     * @param $value  属性值
     +----------------------------------------------------------
     */
    public function __set($name ,$value) {
        if(property_exists($this,$name))
            $this->$name = $value;
    }

    /**
     +----------------------------------------------------------
     * 自动变量获取
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param $name 属性名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __get($name) {
        return isset($this->$name)?$this->$name:null;
    }
}