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
// $Id: runtime.php 957 2012-06-10 02:44:34Z luofei614@126.com $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 运行时文件 编译后不再加载
 +------------------------------------------------------------------------------
 */
if (!defined('THINK_PATH')) exit();
if (version_compare(PHP_VERSION, '5.2.0', '<')) die('require PHP > 5.2.0 !');
//  版本信息
define('THINK_VERSION', '3.0');
define('THINK_RELEASE', '20120323');

//   系统信息
if(version_compare(PHP_VERSION,'5.4.0','<') ) {
    //[sae]下不支持这个函数  
    @set_magic_quotes_runtime (0);
    define('MAGIC_QUOTES_GPC',false);//[saebuilder] 常量值固定
}
//[saebuilder] 常量固定值
define('IS_CGI',0);
define('IS_WIN',0);
define('IS_CLI',0);

// 项目名称
defined('APP_NAME') or  define('APP_NAME', basename(dirname($_SERVER['SCRIPT_FILENAME'])));
if(!IS_CLI) {
    // 当前文件名
    if(!defined('_PHP_FILE_')) {
        if(IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp  = explode('.php',$_SERVER['PHP_SELF']);
            define('_PHP_FILE_',  rtrim(str_replace($_SERVER['HTTP_HOST'],'',$_temp[0].'.php'),'/'));
        }else {
            define('_PHP_FILE_',    '/'.trim($_SERVER['SCRIPT_NAME'],'/'));//[saebuilder] 前面加上斜杠与web一致
        }
    }
    if(!defined('__ROOT__')) {
        // 网站URL根目录
        if( strtoupper(APP_NAME) == strtoupper(basename(dirname(_PHP_FILE_))) ) {
            $_root = dirname(dirname(_PHP_FILE_));
        }else {
            $_root = dirname(_PHP_FILE_);
        }
        define('__ROOT__',   (($_root=='/' || $_root=='\\')?'':$_root));
    }

    //支持的URL模式
    define('URL_COMMON',      0);   //普通模式
    define('URL_PATHINFO',    1);   //PATHINFO模式
    define('URL_REWRITE',     2);   //REWRITE模式
    define('URL_COMPAT',      3);   // 兼容模式
}

// 路径设置 可在入口文件中重新定义 所有路径常量都必须以/ 结尾
defined('CORE_PATH') or define('CORE_PATH',THINK_PATH.'Lib/'); // 系统核心类库目录
defined('EXTEND_PATH') or define('EXTEND_PATH',THINK_PATH.'Extend/'); // 系统扩展目录
defined('MODE_PATH') or define('MODE_PATH',EXTEND_PATH.'Mode/'); // 模式扩展目录
defined('ENGINE_PATH') or define('ENGINE_PATH',EXTEND_PATH.'Engine/'); // 引擎扩展目录// 系统模式目录
defined('VENDOR_PATH') or define('VENDOR_PATH',EXTEND_PATH.'Vendor/'); // 第三方类库目录
defined('LIBRARY_PATH') or define('LIBRARY_PATH',EXTEND_PATH.'Library/'); // 扩展类库目录
defined('COMMON_PATH') or define('COMMON_PATH',    APP_PATH.'Common/'); // 项目公共目录
defined('LIB_PATH') or define('LIB_PATH',    APP_PATH.'Lib/'); // 项目类库目录
defined('CONF_PATH') or define('CONF_PATH',  APP_PATH.'Conf/'); // 项目配置目录
defined('LANG_PATH') or define('LANG_PATH', APP_PATH.'Lang/'); // 项目语言包目录
defined('TMPL_PATH') or define('TMPL_PATH',APP_PATH.'Tpl/'); // 项目模板目录
defined('HTML_PATH') or define('HTML_PATH',$_SERVER['HTTP_APPVERSION'].'/html/'); //[sae] 项目静态目录,静态文件会存到KVDB
defined('LOG_PATH') or define('LOG_PATH',  RUNTIME_PATH.'Logs/'); // 项目日志目录
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH.'Temp/'); // 项目缓存目录
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH.'Data/'); // 项目数据目录
defined('CACHE_PATH') or define('CACHE_PATH',   RUNTIME_PATH.'Cache/'); // 项目模板缓存目录

// 为了方便导入第三方类库 设置Vendor目录到include_path
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);

// 加载运行时所需要的文件 并负责自动目录生成
function load_runtime_file() {
    //[sae] 加载系统基础函数库
    require SAE_PATH.'Common/common.php';
    //[sae] 读取核心编译文件列表
    $list = array(
        SAE_PATH.'Lib/Extend/Tool/SaeCacheBuilder/Think.class.php',//[saebuilder] 加载更改后的Think类
        CORE_PATH.'Core/ThinkException.class.php',  // 异常处理类
        CORE_PATH.'Core/Behavior.class.php',
    );
    // 加载模式文件列表
    foreach ($list as $key=>$file){
        if(is_file($file))  require_cache($file);
    }
    //[sae] 加载系统类库别名定义
    alias_import(include SAE_PATH.'Conf/alias.php');
    if(!is_dir(LIB_PATH)) {
        // 创建项目目录结构
        build_app_dir();
    }elseif(!is_dir(CACHE_PATH)){
        // 检查缓存目录
        check_runtime();
    }
    //[saebuilder] 去掉了删除缓存的操作
}

// 检查缓存目录(Runtime) 如果不存在则自动创建
function check_runtime() {
    if(!is_dir(RUNTIME_PATH)) {
        mkdir(RUNTIME_PATH);
    }elseif(!is_writeable(RUNTIME_PATH)) {
        exit('RUNTIME_PATH not writeable');
    }
    mkdir(CACHE_PATH);  // 模板缓存目录
    return true;
}


// 创建项目目录结构
function build_app_dir() {
    // 没有创建项目目录的话自动创建
    if(!is_dir(APP_PATH)) mkdir(APP_PATH,0777,true);
    if(is_writeable(APP_PATH)) {
        $dirs  = array(
            LIB_PATH,
            RUNTIME_PATH,
            CONF_PATH,
            COMMON_PATH,
            LANG_PATH,
            CACHE_PATH,
            TMPL_PATH,
            TMPL_PATH.C('DEFAULT_THEME').'/',
            LOG_PATH,
            TEMP_PATH,
            DATA_PATH,
            LIB_PATH.'Model/',
            LIB_PATH.'Action/',
            LIB_PATH.'Behavior/',
            LIB_PATH.'Widget/',
            );
        foreach ($dirs as $dir){
            if(!is_dir($dir))  mkdir($dir,0777,true);
        }
        // 写入目录安全文件
        build_dir_secure($dirs);
        // 写入初始配置文件
        if(!is_file(CONF_PATH.'config.php'))
            file_put_contents(CONF_PATH.'config.php',"<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);\n?>");
        // 写入测试Action
        if(!is_file(LIB_PATH.'Action/IndexAction.class.php'))
            build_first_action();
    }else{
        exit('APP_PATH  not  writeable');
    }
}

// 创建测试Action
function build_first_action() {
    $content = file_get_contents(THINK_PATH.'Tpl/default_index.tpl');
    file_put_contents(LIB_PATH.'Action/IndexAction.class.php',$content);
}

// 生成目录安全文件
function build_dir_secure($dirs='') {
    // 目录安全写入
    if(defined('BUILD_DIR_SECURE') && BUILD_DIR_SECURE) {
        defined('DIR_SECURE_FILENAME') or define('DIR_SECURE_FILENAME','index.html');
        defined('DIR_SECURE_CONTENT') or define('DIR_SECURE_CONTENT',' ');
        // 自动写入目录安全文件
        $content = DIR_SECURE_CONTENT;
        $files = explode(',', DIR_SECURE_FILENAME);
        foreach ($files as $filename){
            foreach ($dirs as $dir)
                file_put_contents($dir.$filename,$content);
        }
    }
}

// 创建编译缓存
function build_runtime_cache($append='') {
    // 生成编译文件
    $defs = get_defined_constants(TRUE);
    $content    =  '$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
    //[sae]编译SaeMC核心
    $content.=compile(SAE_PATH.'Lib/Core/SaeMC.class.php');
    if(defined('RUNTIME_DEF_FILE')) { //[sae] 编译后的常量文件外部引入
        //SaeMC::set(RUNTIME_DEF_FILE, '<?php '.array_define($defs['user']));
        //[saebuilder] 生成常量文件
        $defs['user']['APP_DEBUG']=false;//[saebuilder] APP_DEBUG固定为false
        unset($defs['user']['SAE_CACHE_BUILDER']);//[saebuilder]去掉SAE_CACHE_BUILDER常量
        file_put_contents(RUNTIME_DEF_FILE, '<?php '.array_define($defs['user']));
        echo 'build runtime_def_file:'.RUNTIME_DEF_FILE.PHP_EOL;
        $content  .=  'SaeMC::include_file(\''.RUNTIME_DEF_FILE.'\');';
    }else{
        $content  .= array_define($defs['user']);
    }
    $content    .= 'set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);';
    //[sae] 读取核心编译文件列表
    $list = array(
        SAE_PATH.'Common/common.php',
        SAE_PATH.'Lib/Core/Think.class.php',
        CORE_PATH.'Core/ThinkException.class.php',
        CORE_PATH.'Core/Behavior.class.php',
    );
    foreach ($list as $file){
        $content .= compile($file);
    }
    // 系统行为扩展文件统一编译
    if(C('APP_TAGS_ON')) {
        $content .= build_tags_cache();
    }
    //[sae] 编译SAE的alias
    $alias = include SAE_PATH.'Conf/alias.php';
    $content .= 'alias_import('.var_export($alias,true).');';
    // 编译框架默认语言包和配置参数
    //[saebuilder] 处理配置项，将SAE常量原样输出
    $content .= $append."\nL(".var_export(L(),true).");C(".preg_replace(array('/\'SAE_(.*?)\'/e','/\'~([a-zA-Z_][a-zA-Z0-9_]*)\((.*?)\)\'/'), array('parse_sae_define("\\1")','\\1(\\2)'), var_export(C(),true)).');G(\'loadTime\');Think::Start();';
    //[saebuilder] 生成缓存文件
    //SaeMC::set(RUNTIME_FILE, strip_whitespace('<?php '.$content));
    file_put_contents(RUNTIME_FILE, strip_whitespace('<?php '.$content));
    echo 'build core file:'.RUNTIME_FILE.PHP_EOL;
}
//sae常量原返回处理
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
// 执行入口
Think::Start();