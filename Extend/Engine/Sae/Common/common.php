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
// $Id: common.php 2984 2012-06-11 14:10:44Z luofei614@gmail.com $

/**
  +------------------------------------------------------------------------------
 * Think 基础函数库
  +------------------------------------------------------------------------------
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id: common.php 2984 2012-06-11 14:10:44Z luofei614@gmail.com $
  +------------------------------------------------------------------------------
 */

// 记录和统计时间（微秒）
function G($start,$end='',$dec=4) {
    static $_info = array();
    if(is_float($end)) { // 记录时间
        $_info[$start]  =  $end;
    }elseif(!empty($end)){ // 统计时间
        if(!isset($_info[$end])) $_info[$end]   =  microtime(TRUE);
        return number_format(($_info[$end]-$_info[$start]),$dec);
    }else{ // 记录时间
        $_info[$start]  =  microtime(TRUE);
    }
}

// 设置和获取统计数据
function N($key, $step=0) {
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int) $step;
}

/**
  +----------------------------------------------------------
 * 字符串命名风格转换
 * type
 * =0 将Java风格转换为C的风格
 * =1 将C风格转换为Java的风格
  +----------------------------------------------------------
 * @access protected
  +----------------------------------------------------------
 * @param string $name 字符串
 * @param integer $type 转换类型
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */
function parse_name($name, $type=0) {
    if ($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

// 优化的require_once
//[sae]， 在sae下可以导入sae专用文件
function require_cache($filename) {
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        //sae专属文件的文件名为 name_sae.class.php 或 name_sae.php
        $sae_filename = strpos($filename, 'class.php') ? str_replace('.class.php', '_sae.class.php', $filename) : str_replace('.php', '_sae.php', $filename);
        $sae_files=C('SAE_SPECIALIZED_FILES');//[sae]读取系统专属文件列表
        if (is_file($sae_filename)) {
            require $sae_filename;
            $_importFiles[$filename] = true;
        }elseif(isset($sae_files[basename($filename)])){
            require $sae_files[basename($filename)];
            $_importFiles[$filename] = true;
        }elseif (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

// 区分大小写的文件存在判断
function file_exists_case($filename) {
    if (is_file($filename)) {
        if (IS_WIN && C('APP_FILE_CASE')) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
  +----------------------------------------------------------
 * 导入所需的类库 同java的Import
 * 本函数有缓存功能
  +----------------------------------------------------------
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
  +----------------------------------------------------------
 * @return boolen
  +----------------------------------------------------------
 */
function import($class, $baseUrl = '', $ext='.class.php') {
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if ('' === $baseUrl && false === strpos($class, '/')) {
        // 检查别名导入
        return alias_import($class);
    }
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || APP_NAME == $class_strut[0]) {
            //加载当前项目应用类库
            $baseUrl = dirname(LIB_PATH);
            $class = substr_replace($class, basename(LIB_PATH).'/', 0, strlen($class_strut[0]) + 1);
        }elseif ('think' == strtolower($class_strut[0])){ // think 官方基类库
            $baseUrl = CORE_PATH;
            $class = substr($class,6);
        }elseif (in_array(strtolower($class_strut[0]), array('org', 'com'))) {
            // org 第三方公共类库 com 企业公共类库
            $baseUrl = LIBRARY_PATH;
        }else { // 加载其他项目应用类库
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
            $baseUrl = APP_PATH . '../' . $class_strut[0] . '/'.basename(LIB_PATH).'/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class),false)) {
        // 如果类不存在 则导入类库文件
        return require_cache($classfile);
    }
}

/**
  +----------------------------------------------------------
 * 基于命名空间方式导入函数库
 * load('@.Util.Array')
  +----------------------------------------------------------
 * @param string $name 函数库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
  +----------------------------------------------------------
 * @return void
  +----------------------------------------------------------
 */
function load($name, $baseUrl='', $ext='.php') {
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {
            //加载当前项目函数库
            $baseUrl = COMMON_PATH;
            $name = substr($name, 2);
        } else {
            //加载ThinkPHP 系统函数库
            $baseUrl = EXTEND_PATH . 'Function/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    require_cache($baseUrl . $name . $ext);
}

// 快速导入第三方框架类库
// 所有第三方框架的类库文件统一放到 系统的Vendor目录下面
// 并且默认都是以.php后缀导入
function vendor($class, $baseUrl = '', $ext='.php') {
    if (empty($baseUrl))
        $baseUrl = VENDOR_PATH;
    return import($class, $baseUrl, $ext);
}

// 快速定义和导入别名
function alias_import($alias, $classfile='') {
    static $_alias = array();
    if (is_string($alias)) {
        if(isset($_alias[$alias])) {
            return require_cache($_alias[$alias]);
        }elseif ('' !== $classfile) {
            // 定义别名导入
            $_alias[$alias] = $classfile;
            return;
        }
    }elseif (is_array($alias)) {
        $_alias   =  array_merge($_alias,$alias);
        return;
    }
    return false;
}

/**
  +----------------------------------------------------------
 * D函数用于实例化Model 格式 项目://分组/模块
 +----------------------------------------------------------
 * @param string name Model资源地址
  +----------------------------------------------------------
 * @return Model
  +----------------------------------------------------------
 */
function D($name='') {
    if(empty($name)) return new Model;
    static $_model = array();
    if(strpos($name,'://')) {// 指定项目
        $name   =  str_replace('://','/Model/',$name);
    }else{
        $name   =  C('DEFAULT_APP').'/Model/'.$name;
    }
    if(isset($_model[$name])) return $_model[$name];
    import($name.'Model');
    $class   =   basename($name.'Model');
    if(class_exists($class)) {
        $model = new $class();
    }else {
        $model  = new Model(basename($name));
    }
    $_model[$name]  =  $model;
    return $model;
}

/**
  +----------------------------------------------------------
 * M函数用于实例化一个没有模型文件的Model
  +----------------------------------------------------------
 * @param string name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
  +----------------------------------------------------------
 * @return Model
  +----------------------------------------------------------
 */
function M($name='', $tablePrefix='',$connection='') {
    static $_model = array();
    if(strpos($name,':')) {
        list($class,$name)    =  explode(':',$name);
    }else{
        $class   =   'Model';
    }
    if (!isset($_model[$name . '_' . $class]))
        $_model[$name . '_' . $class] = new $class($name,$tablePrefix,$connection);
    return $_model[$name . '_' . $class];
}

/**
  +----------------------------------------------------------
 * A函数用于实例化Action 格式：[项目://][分组/]模块
  +----------------------------------------------------------
 * @param string name Action资源地址
  +----------------------------------------------------------
 * @return Action
  +----------------------------------------------------------
 */
function A($name) {
    static $_action = array();
    if(isset($_action[$name]))
        return $_action[$name];
    if(strpos($name,'://')) {// 指定项目
        $name   =  str_replace('://','/Action/',$name);
    }else{
        $name   =  '@/Action/'.$name;
    }
    import($name.'Action');
    $class   =   basename($name.'Action');
    if(class_exists($class,false)) {
        $action = new $class();
        $_action[$name]  =  $action;
        return $action;
    }else {
        return false;
    }
}

// 远程调用模块的操作方法
// URL 参数格式 [项目://][分组/]模块/操作 
function R($url,$vars=array()) {
    $info =  pathinfo($url);
    $action  =  $info['basename'];
    $module =  $info['dirname'];
    $class = A($module);
    if($class)
        return call_user_func_array(array(&$class,$action),$vars);
    else
        return false;
}

// 获取和设置语言定义(不区分大小写)
function L($name=null, $value=null) {
    static $_lang = array();
    // 空参数返回所有定义
    if (empty($name))
        return $_lang;
    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value))
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        $_lang[$name] = $value; // 语言定义
        return;
    }
    // 批量定义
    if (is_array($name))
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    return;
}

// 获取配置值
function C($name=null, $value=null) {
    static $_config = array();
    // 无参数时获取所有
    if (empty($name))
        return $_config;
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : null;
            $_config[$name] = is_array($value)?array_change_key_case($value):$value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name)){
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null; // 避免非法参数
}

// 处理标签扩展
function tag($tag, &$params=NULL) {
    // 系统标签扩展
    $extends = C('extends.' . $tag);
    // 应用标签扩展
    $tags = C('tags.' . $tag);
    if (!empty($tags)) {
        if(empty($tags['_overlay']) && !empty($extends)) { // 合并扩展
            $tags = array_unique(array_merge($extends,$tags));
        }elseif(isset($tags['_overlay'])){ // 通过设置 '_overlay'=>1 覆盖系统标签
            unset($tags['_overlay']);
        }
    }elseif(!empty($extends)) {
        $tags = $extends;
    }
    if($tags) {
        if(APP_DEBUG) {
            G($tag.'Start');
            Log::record('Tag[ '.$tag.' ] --START--',Log::INFO);
        }
        // 执行扩展
        foreach ($tags as $key=>$name) {
            if(!is_int($key)) { // 指定行为类的完整路径 用于模式扩展
                $name   = $key;
            }
            B($name, $params);
        }
        if(APP_DEBUG) { // 记录行为的执行日志
            Log::record('Tag[ '.$tag.' ] --END-- [ RunTime:'.G($tag.'Start',$tag.'End',6).'s ]',Log::INFO);
        }
    }else{ // 未执行任何行为 返回false
        return false;
    }
}

// 动态添加行为扩展到某个标签
function add_tag_behavior($tag,$behavior,$path='') {
    $array   =  C('tags.'.$tag);
    if(!$array) {
        $array   =  array();
    }
    if($path) {
        $array[$behavior] = $path;
    }else{
        $array[] =  $behavior;
    }
    C('tags.'.$tag,$array);
}

// 过滤器方法
function filter($name, &$content) {
    $class = $name . 'Filter';
    require_cache(LIB_PATH . 'Filter/' . $class . '.class.php');
    $filter = new $class();
    $content = $filter->run($content);
}

// 执行行为
function B($name, &$params=NULL) {
    $class = $name.'Behavior';
    G('behaviorStart');
    $behavior = new $class();
    $behavior->run($params);
    if(APP_DEBUG) { // 记录行为的执行日志
        G('behaviorEnd');
        Log::record('Run '.$name.' Behavior [ RunTime:'.G('behaviorStart','behaviorEnd',6).'s ]',Log::INFO);
    }
}

// 渲染输出Widget
function W($name, $data=array(), $return=false) {
    $class = $name . 'Widget';
    require_cache(LIB_PATH . 'Widget/' . $class . '.class.php');
    if (!class_exists($class))
        throw_exception(L('_CLASS_NOT_EXIST_') . ':' . $class);
    $widget = Think::instance($class);
    $content = $widget->render($data);
    if ($return)
        return $content;
    else
        echo $content;
}

// 去除代码中的空白和注释
function strip_whitespace($content) {
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for($k = $i+1; $k < $j; $k++) {
                        if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}


//[RUNTIME]
//[sae] 在sae下能编译sae专用文件
function compile($filename) {
    $sae_filename = strpos($filename, 'class.php') ? str_replace('.class.php', '_sae.class.php', $filename) : str_replace('.php', '_sae.php', $filename);
    $content = is_file($sae_filename) ? file_get_contents($sae_filename) : file_get_contents($filename);
    // 替换预编译指令
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    $content = substr(trim($content), 5);
    if ('?>' == substr($content, -2))
        $content = substr($content, 0, -2);
    return $content;
}

// 根据数组生成常量定义
function array_define($array,$check=true) {
    $content = "\n";
    foreach ($array as $key => $val) {
        $key = strtoupper($key);
        if($check)   $content .= 'defined(\'' . $key . '\') or ';
        if (is_int($val) || is_float($val)) {
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_bool($val)) {
            $val = ($val) ? 'true' : 'false';
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_string($val)) {
            $content .= "define('" . $key . "','" . addslashes($val) . "');";
        }
        $content    .= "\n";
    }
    return $content;
}
//[/RUNTIME]

//获得storage的domain地址,在config_sae.php中可以使用
function sae_storage_root($domain){
    if(defined('SAE_CACHE_BUILDER'))
        return '~sae_storage_root("'.$domain.'")';
    $s=Think::instance('SaeStorage');
    return rtrim($s->getUrl('Public',''),'/');
}