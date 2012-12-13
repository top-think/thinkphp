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
 * ThinkPHP 运行时文件 编译后不再加载
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 */
defined('THINK_PATH') or exit();
if(version_compare(PHP_VERSION,'5.2.0','<'))  die('require PHP > 5.2.0 !');
// [sae_runtime] 固定常量
// 以下是SAE平台固定的常量
define('THINK_VERSION', '3.1.2');
define('MAGIC_QUOTES_GPC',false);
define('IS_CGI',0 );
define('IS_WIN',0);
define('IS_CLI',0);
define('_PHP_FILE_','/'.basename(__FILE__));
if( strtoupper(APP_NAME) == strtoupper(basename(dirname(_PHP_FILE_))) ) {
            $_root = dirname(dirname(_PHP_FILE_));
        }else {
            $_root = dirname(_PHP_FILE_);
 }
define('__ROOT__',   (($_root=='/' || $_root=='\\')?'':$_root));
define('URL_COMMON',      0);   //普通模式
define('URL_PATHINFO',    1);   //PATHINFO模式
define('URL_REWRITE',     2);   //REWRITE模式
define('URL_COMPAT',      3);   // 兼容模式
//----------------------------------------------------------
defined('CORE_PATH')    or define('CORE_PATH',      THINK_PATH.'Lib/'); // 系统核心类库目录
defined('EXTEND_PATH')  or define('EXTEND_PATH',    THINK_PATH.'Extend/'); // 系统扩展目录
defined('MODE_PATH')    or define('MODE_PATH',      EXTEND_PATH.'Mode/'); // 模式扩展目录
defined('ENGINE_PATH')  or define('ENGINE_PATH',    EXTEND_PATH.'Engine/'); // 引擎扩展目录
defined('VENDOR_PATH')  or define('VENDOR_PATH',    EXTEND_PATH.'Vendor/'); // 第三方类库目录
defined('LIBRARY_PATH') or define('LIBRARY_PATH',   EXTEND_PATH.'Library/'); // 扩展类库目录
defined('COMMON_PATH')  or define('COMMON_PATH',    APP_PATH.'Common/'); // 项目公共目录
defined('LIB_PATH')     or define('LIB_PATH',       APP_PATH.'Lib/'); // 项目类库目录
defined('CONF_PATH')    or define('CONF_PATH',      APP_PATH.'Conf/'); // 项目配置目录
defined('LANG_PATH')    or define('LANG_PATH',      APP_PATH.'Lang/'); // 项目语言包目录
defined('TMPL_PATH')    or define('TMPL_PATH',      APP_PATH.'Tpl/'); // 项目模板目录
//[sae_runtime] 静态文件不能为当前应用的版本号为目录，固定以sae_runtime为目录
defined('HTML_PATH') or define('HTML_PATH','HTTP_APPVERSION/html/'); //[sae] 项目静态目录,静态文件会存到KVDB
defined('LOG_PATH')     or define('LOG_PATH',       RUNTIME_PATH.'Logs/'); // 项目日志目录
defined('TEMP_PATH')    or define('TEMP_PATH',      RUNTIME_PATH.'Temp/'); // 项目缓存目录
defined('DATA_PATH')    or define('DATA_PATH',      RUNTIME_PATH.'Data/'); // 项目数据目录
defined('CACHE_PATH')   or define('CACHE_PATH',     RUNTIME_PATH.'Cache/'); // 项目模板缓存目录

// 为了方便导入第三方类库 设置Vendor目录到include_path
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);

// 加载运行时所需要的文件 并负责自动目录生成
function load_runtime_file() {
    //[sae] 加载系统基础函数库
    require SAE_PATH.'Common/common.php';
     require SAE_PATH.'Common/sae_common.php';
    //[sae] 读取核心编译文件列表
    $list = array(
        SAE_PATH.'Lib/Core/Think.class.php',
        CORE_PATH.'Core/ThinkException.class.php',  // 异常处理类
        CORE_PATH.'Core/Behavior.class.php',
    );
    // 加载模式文件列表
    foreach ($list as $key=>$file){
        if(is_file($file))  require_cache($file);
    }
    //[sae] 加载系统类库别名定义
    //alias_import(include SAE_PATH.'Conf/alias.php');
    //[sae]在sae下不对目录结构进行检查
}

//[sae]下，不需要生成检查runtime目录函数

// 创建编译缓存
function build_runtime_cache($append='') {
    // 生成编译文件
    $defs = get_defined_constants(TRUE);
    $content    =  '$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
    //[sae]编译SaeMC核心
    $content.=compile(SAE_PATH.'Lib/Core/SaeMC.class.php');
    $defs['user']['APP_DEBUG']=false;//[sae] 关闭调试
    if(defined('RUNTIME_DEF_FILE')) { //[sae] 编译后的常量文件外部引入
        SaeMC::set(RUNTIME_DEF_FILE, '<?php '.array_define($defs['user']));
        $content  .=  'SaeMC::include_file(\''.RUNTIME_DEF_FILE.'\');';
    }else{
        $content  .= array_define($defs['user']);
    }
    $content    .= 'set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);';
    //[sae] 读取核心编译文件列表
    $list = array(
        SAE_PATH.'Common/common.php',
        SAE_PATH.'Common/sae_common.php',
        SAE_PATH.'Lib/Core/Think.class.php',
        CORE_PATH.'Core/ThinkException.class.php',
        CORE_PATH.'Core/Behavior.class.php',
    );
    foreach ($list as $file){
        $content .= compile($file);
    }
    // 系统行为扩展文件统一编译
    $content .= build_tags_cache();
    //[sae] 编译SAE的alias
    //$alias = include SAE_PATH.'Conf/alias.php';
    //$content .= 'alias_import('.var_export($alias,true).');';
    // 编译框架默认语言包和配置参数
    // [sae_runtime] 对配置中的SAE常量进行处理。 配置项的值如果是 ~func() 的字符串 则会 编译为 执行func函数。主要是为了处理 sae_storage_root 函数在SAE_RUNTIME模式下的使用
    $content .= $append."\nL(".var_export(L(),true).");C(".preg_replace(array('/\'SAE_(.*?)\'/e','/\'~([a-zA-Z_][a-zA-Z0-9_]*)\((.*?)\)\'/'), array('parse_sae_define("\\1")','\\1(\\2)'), var_export(C(),true)).');G(\'loadTime\');Think::Start();';
    //[sae] 生成编译缓存文件
    SaeMC::set(RUNTIME_FILE, strip_whitespace('<?php '.str_replace("defined('THINK_PATH') or exit();",' ',$content)));
}

//sae常量原返回处理
//[sae_runtime]
function  parse_sae_define($define){
    //将逗号，替换为连接形式
    $define=str_replace(',', ".','.", $define);
    return 'SAE_'.$define;
}
// 编译系统行为扩展类库
function build_tags_cache() {
    $tags = C('extends');
    $content = '';
    foreach ($tags as $tag=>$item){
        foreach ($item as $key=>$name) {
            $content .= is_int($key)?compile(CORE_PATH.'Behavior/'.$name.'Behavior.class.php'):compile($name);
        }
    }
    return $content;
}

//[sae]下，不需要生成目录结构函数

// 加载运行时所需文件
load_runtime_file();
// 记录加载文件时间
G('loadTime');
//编译核心文件，BuildApp为Think::buildApp 复制过来的
buildApp();
function buildApp() {
        // 读取运行模式
        if(defined('MODE_NAME')) { // 读取模式的设置
            $mode   = include MODE_PATH.strtolower(MODE_NAME).'.php';
        }else{
            $mode   =  array();
        }

        if(isset($mode['config'])) {// 加载模式配置文件
            C( is_array($mode['config'])?$mode['config']:include $mode['config'] );
        }else{ // 加载底层惯例配置文件
            C(include THINK_PATH.'Conf/convention.php');
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
                SAE_PATH.'Lib/Core/Log.class.php',    //[sae] 日志处理类
                SAE_PATH.'Lib/Core/Sms.class.php',    //[sae] 短信预警类
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
        }else{
            //[sae] 别名文件
            $alias = include SAE_PATH.'Conf/alias.php';
        }
        alias_import($alias);
        $compile .= 'alias_import('.var_export($alias,true).');';
        
         // 加载项目别名定义
        if(is_file(CONF_PATH.'alias.php')){ 
            $alias = include CONF_PATH.'alias.php';
            alias_import($alias);
            $compile .= 'alias_import('.var_export($alias,true).');';
        }
            // 部署模式下面生成编译文件
        build_runtime_cache($compile);
        return ;
    }