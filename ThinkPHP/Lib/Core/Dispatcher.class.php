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
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch() {
        $urlMode        =   C('URL_MODEL');
        $varPath        =   C('VAR_PATHINFO');
        $varModule      =   C('VAR_MODULE');
        $varController  =   C('VAR_CONTROLLER');
        $varAction      =   C('VAR_ACTION');
        if(isset($_GET[$varPath])) { // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO'] = $_GET[$varPath];
            unset($_GET[$varPath]);
        }elseif(IS_CLI){ // CLI模式下 index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        }
        if($urlMode == URL_COMPAT ){
            // 兼容模式判断
            define('PHP_FILE',_PHP_FILE_.'?'.$varPath.'=');
        }elseif($urlMode == URL_REWRITE ) {
            //当前项目地址
            $url    =   dirname(_PHP_FILE_);
            if($url == '/' || $url == '\\')
                $url    =   '';
            define('PHP_FILE',$url);
        }else {
            //当前项目地址
            define('PHP_FILE',_PHP_FILE_);
        }

        // 开启子域名部署
        if(C('APP_SUB_DOMAIN_DEPLOY')) {
            $rules      = C('APP_SUB_DOMAIN_RULES');
            if(isset($rules[$_SERVER['HTTP_HOST']])) { // 完整域名或者IP配置
                $rule = $rules[$_SERVER['HTTP_HOST']];
            }else{
                $subDomain  = strtolower(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
                define('SUB_DOMAIN',$subDomain); // 二级域名定义
                if($subDomain && isset($rules[$subDomain])) {
                    $rule =  $rules[$subDomain];
                }elseif(isset($rules['*'])){ // 泛域名支持
                    if('www' != $subDomain && !in_array($subDomain,C('APP_SUB_DOMAIN_DENY'))) {
                        $rule =  $rules['*'];
                    }
                }                
            }

            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('模块名/[控制器名]','var1=a&var2=b');
                $array      =   explode('/',$rule[0]);
                $controller =   array_pop($array);
                if(!empty($controller)) {
                    $_GET[$varController]  =   $controller;
                    $domainController           =   true;
                }
                if(!empty($array)) {
                    $_GET[$varModule]   =   array_pop($array);
                    $domainModule            =   true;
                }
                if(isset($rule[1])) { // 传入参数
                    parse_str($rule[1],$parms);
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }
        // 分析PATHINFO信息
        if(!isset($_SERVER['PATH_INFO'])) {
            $types   =  explode(',',C('URL_PATHINFO_FETCH'));
            foreach ($types as $type){
                if(0===strpos($type,':')) {// 支持函数判断
                    $_SERVER['PATH_INFO'] =   call_user_func(substr($type,1));
                    break;
                }elseif(!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type],$_SERVER['SCRIPT_NAME']))?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME']))   :  $_SERVER[$type];
                    break;
                }
            }
        }
        $depr = C('URL_PATHINFO_DEPR');
        if(!empty($_SERVER['PATH_INFO'])) {
            tag('path_info');
            $part =  pathinfo($_SERVER['PATH_INFO']);
            define('__EXT__', isset($part['extension'])?strtolower($part['extension']):'');
            if(__EXT__){
                if(C('URL_DENY_SUFFIX') && preg_match('/\.('.trim(C('URL_DENY_SUFFIX'),'.').')$/i', $_SERVER['PATH_INFO'])){
                    send_http_status(404);
                    exit;
                }
                if(C('URL_HTML_SUFFIX')) {
                    $_SERVER['PATH_INFO'] = preg_replace('/\.('.trim(C('URL_HTML_SUFFIX'),'.').')$/i', '', $_SERVER['PATH_INFO']);
                }else{
                    $_SERVER['PATH_INFO'] = preg_replace('/.'.__EXT__.'$/i','',$_SERVER['PATH_INFO']);
                }
            }

            if(!self::routerCheck()){   // 检测路由规则 如果没有则按默认规则调度URL
                $paths = explode($depr,trim($_SERVER['PATH_INFO'],'/'));
                if(C('VAR_URL_PARAMS')) {
                    // 直接通过$_GET['_URL_'][1] $_GET['_URL_'][2] 获取URL参数 方便不用路由时参数获取
                    $_GET[C('VAR_URL_PARAMS')]   =  $paths;
                }
                $var  =  array();
                if (C('MULTI_MODULE') && !isset($_GET[$varModule])){ // 获取模块
                    $var[$varModule] = array_shift($paths);
                    if(C('APP_MODULE_DENY') && in_array(strtolower($var[$varModule]),explode(',',strtolower(C('APP_MODULE_DENY'))))) {
                        // 禁止直接访问模块
                        exit;
                    }
                }
                if(!isset($_GET[$varController])) {// 获取控制器
                    $var[$varController]  =   array_shift($paths);
                }
                // 获取操作
                $var[$varAction]  =   array_shift($paths);
                // 解析剩余的URL参数
                preg_replace('@(\w+)\/([^\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode('/',$paths));
                $_GET   =  array_merge($var,$_GET);
            }
            define('__INFO__',$_SERVER['PATH_INFO']);
        }else{
            define('__INFO__','');
        }

        // URL常量
        define('__SELF__',strip_tags($_SERVER['REQUEST_URI']));
        // 当前项目地址
        define('__APP__',strip_tags(PHP_FILE));

        // 获取模块名称
        define('MODULE_NAME', self::getModule($varModule));
        // 检测模块是否存在 并且公共模块不能访问
        if(is_dir(MODULES_PATH.MODULE_NAME)){
            // 模块URL地址
            define('__MODULE__',(!empty($domainModule) || !C('MULTI_MODULE'))?__APP__ : __APP__.'/'.(C('URL_CASE_INSENSITIVE') ? strtolower(MODULE_NAME) : MODULE_NAME));
            
            // 定义当前模块路径
            define('MODULE_PATH', MODULES_PATH.MODULE_NAME.'/');
            // 定义当前模块的模版缓存路径
            C('CACHE_PATH',CACHE_PATH.MODULE_NAME.'/');

            // 加载模块配置文件
            if(is_file(MODULE_PATH.'Conf/config.php'))
                C(include MODULE_PATH.'Conf/config.php');
            // 加载模块别名定义
            if(is_file(MODULE_PATH.'Conf/alias.php'))
                alias_import(include MODULE_PATH.'Conf/alias.php');
            // 加载模块tags文件定义
            if(is_file(MODULE_PATH.'Conf/tags.php'))
                C('tags', include MODULE_PATH.'Conf/tags.php');
            // 加载模块函数文件
            if(is_file(MODULE_PATH.'Common/function.php'))
                include MODULE_PATH.'Common/function.php';
        }else{
            E(L('_MODULE_NOT_EXIST_').':'.MODULE_NAME);
        }
        define('CONTROLLER_NAME',   self::getController($varController));
        define('ACTION_NAME',       self::getAction($varAction));
        
        // 当前控制器地址
        $controllerName    =   defined('CONTROLLER_ALIAS')?CONTROLLER_ALIAS:CONTROLLER_NAME;
        define('__CONTROLLER__',!empty($domainController)?__MODULE__.$depr : __MODULE__.$depr.( C('URL_CASE_INSENSITIVE') ? strtolower($controllerName) : $controllerName ) );

        // 当前操作地址
        define('__ACTION__',__CONTROLLER__.$depr.(defined('ACTION_ALIAS')?ACTION_ALIAS:ACTION_NAME));
        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     * 路由检测
     * @access public
     * @return void
     */
    static public function routerCheck() {
        $return   =  false;
        // 路由检测标签
        tag('route_check',$return);
        return $return;
    }

    /**
     * 获得实际的模块名称
     * @access private
     * @return string
     */
    static private function getController($var) {
        $module = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_CONTROLLER'));
        unset($_GET[$var]);
        if($maps = C('URL_CONTROLLER_MAP')) {
            if(isset($maps[strtolower($module)])) {
                // 记录当前别名
                define('CONTROLLER_ALIAS',strtolower($module));
                // 获取实际的模块名
                return   $maps[CONTROLLER_ALIAS];
            }elseif(array_search(strtolower($module),$maps)){
                // 禁止访问原始模块
                return   '';
            }
        }
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $module = ucfirst(parse_name($module,1));
        }
        return strip_tags($module);
    }

    /**
     * 获得实际的操作名称
     * @access private
     * @return string
     */
    static private function getAction($var) {
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        if($maps = C('URL_ACTION_MAP')) {
            if(isset($maps[strtolower(CONTROLLER_NAME)])) {
                $maps =   $maps[strtolower(CONTROLLER_NAME)];
                if(isset($maps[strtolower($action)])) {
                    // 记录当前别名
                    define('ACTION_ALIAS',strtolower($action));
                    // 获取实际的操作名
                    return   $maps[ACTION_ALIAS];
                }elseif(array_search(strtolower($action),$maps)){
                    // 禁止访问原始操作
                    return   '';
                }
            }
        }        
        return strip_tags(C('URL_CASE_INSENSITIVE')?strtolower($action):$action);
    }

    /**
     * 获得实际的分组名称
     * @access private
     * @return string
     */
    static private function getModule($var) {
        $group   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        return strip_tags(C('URL_CASE_INSENSITIVE') ?ucfirst(strtolower($group)):$group);
    }

}
