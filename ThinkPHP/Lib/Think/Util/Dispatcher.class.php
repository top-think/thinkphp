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
// $Id: Dispatcher.class.php 2841 2012-03-23 05:58:37Z liu21st@gmail.com $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Dispatcher.class.php 2841 2012-03-23 05:58:37Z liu21st@gmail.com $
 +------------------------------------------------------------------------------
 */
class Dispatcher extends Think
{//类定义开始

    /**
     +----------------------------------------------------------
     * URL映射到控制器
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function dispatch() {
        $urlMode  =  C('URL_MODEL');
        if($urlMode == URL_REWRITE ) {
            //当前项目地址
            $url    =   dirname(_PHP_FILE_);
            if($url == '/' || $url == '\\')
                $url    =   '';
            define('PHP_FILE',$url);
        }elseif($urlMode == URL_COMPAT){
            define('PHP_FILE',_PHP_FILE_.'?'.C('VAR_PATHINFO').'=');
        }else {
            //当前项目地址
            define('PHP_FILE',_PHP_FILE_);
        }

        // 开启子域名部署
        if(C('APP_SUB_DOMAIN_DEPLOY')) {
            $rules = C('APP_SUB_DOMAIN_RULES');
            $subDomain    = strtolower(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
            define('SUB_DOMAIN',$subDomain); // 二级域名定义
            if($subDomain && array_key_exists($subDomain,$rules)) {
                $rule =  $rules[$subDomain];
            }elseif(isset($rules['*'])){ // 泛域名支持
                if('www' != $subDomain && !in_array($subDomain,C('APP_SUB_DOMAIN_DENY'))) {
                    $rule =  $rules['*'];
                }
            }
            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('分组名/[模块名]','var1=a&var2=b');
                $array   =  explode('/',$rule[0]);
                $module = array_pop($array);
                if(!empty($module)) {
                    $_GET[C('VAR_MODULE')] = $module;
                    $domainModule   =  true;
                }
                if(!empty($array)) {
                    $_GET[C('VAR_GROUP')]  = array_pop($array);
                    $domainGroup =  true;
                }
                if(isset($rule[1])) { // 传入参数
                    parse_str($rule[1],$parms);
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }
        $depr = C('URL_PATHINFO_DEPR');
        // 分析PATHINFO信息
        self::getPathInfo();
        if(!self::routerCheck()){   // 检测路由规则 如果没有则按默认规则调度URL
            $paths = explode($depr,trim($_SERVER['PATH_INFO'],'/'));
            $var  =  array();
            if (C('APP_GROUP_LIST') && !isset($_GET[C('VAR_GROUP')])){
                $var[C('VAR_GROUP')] = in_array(strtolower($paths[0]),explode(',',strtolower(C('APP_GROUP_LIST'))))? array_shift($paths) : '';
                if(C('APP_GROUP_DENY') && in_array(strtolower($var[C('VAR_GROUP')]),explode(',',strtolower(C('APP_GROUP_DENY'))))) {
                    // 禁止直接访问分组
                    exit;
                }
            }
            if(!isset($_GET[C('VAR_MODULE')])) {// 还没有定义模块名称
                $var[C('VAR_MODULE')]  =   array_shift($paths);
            }
            $var[C('VAR_ACTION')]  =   array_shift($paths);
            // 解析剩余的URL参数
            $res = preg_replace('@(\w+)'.$depr.'([^'.$depr.'\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode($depr,$paths));
            $_GET   =  array_merge($var,$_GET);
        }

        // 获取分组 模块和操作名称
        if (C('APP_GROUP_LIST')) {
            define('GROUP_NAME', self::getGroup(C('VAR_GROUP')));
            // 加载分组配置文件
            if(is_file(CONFIG_PATH.GROUP_NAME.'/config.php'))
                C(include CONFIG_PATH.GROUP_NAME.'/config.php');
            // 加载分组函数文件
            if(is_file(COMMON_PATH.GROUP_NAME.'/function.php'))
                include COMMON_PATH.GROUP_NAME.'/function.php';
        }
        define('MODULE_NAME',self::getModule(C('VAR_MODULE')));
        define('ACTION_NAME',self::getAction(C('VAR_ACTION')));
        // URL常量
        define('__SELF__',strip_tags($_SERVER['REQUEST_URI']));
        define('__INFO__',$_SERVER['PATH_INFO']);
        // 当前项目地址
        define('__APP__',strip_tags(PHP_FILE));
        // 当前模块和分组地址
        $module = defined('P_MODULE_NAME')?P_MODULE_NAME:MODULE_NAME;
        if(defined('GROUP_NAME')) {
            $group   = C('URL_CASE_INSENSITIVE') ?strtolower(GROUP_NAME):GROUP_NAME;
            define('__GROUP__',(!empty($domainGroup) || GROUP_NAME == C('DEFAULT_GROUP') )?__APP__ : __APP__.'/'.$group);
            define('__URL__',!empty($domainModule)?__GROUP__.$depr : __GROUP__.$depr.$module);
        }else{
            define('__URL__',!empty($domainModule)?__APP__.'/' : __APP__.'/'.$module);
        }
        // 当前操作地址
        define('__ACTION__',__URL__.$depr.ACTION_NAME);
        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
    +----------------------------------------------------------
    * 获得服务器的PATH_INFO信息
    +----------------------------------------------------------
    * @access public
    +----------------------------------------------------------
    * @return void
    +----------------------------------------------------------
    */
    public static function getPathInfo() {
        if(!empty($_GET[C('VAR_PATHINFO')])) {
            // 兼容PATHINFO 参数
            $path = $_GET[C('VAR_PATHINFO')];
            unset($_GET[C('VAR_PATHINFO')]);
        }elseif(!empty($_SERVER['PATH_INFO'])){
            $pathInfo = $_SERVER['PATH_INFO'];
            if(0 === strpos($pathInfo,$_SERVER['SCRIPT_NAME']))
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            else
                $path = $pathInfo;
        }elseif(!empty($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
            if(0 === strpos($pathInfo, $_SERVER['SCRIPT_NAME']))
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            else
                $path = $pathInfo;
        }elseif (!empty($_SERVER['REDIRECT_PATH_INFO'])){
            $path = $_SERVER['REDIRECT_PATH_INFO'];
        }elseif(!empty($_SERVER["REDIRECT_URL"])){
            $path = $_SERVER["REDIRECT_URL"];
            if(empty($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] == $_SERVER["REDIRECT_QUERY_STRING"]) {
                $parsedUrl = parse_url($_SERVER["REQUEST_URI"]);
                if(!empty($parsedUrl['query'])) {
                    $_SERVER['QUERY_STRING'] = $parsedUrl['query'];
                    parse_str($parsedUrl['query'], $GET);
                    $_GET = array_merge($_GET, $GET);
                    reset($_GET);
                }else {
                    unset($_SERVER['QUERY_STRING']);
                }
                reset($_SERVER);
            }
        }
        
        if(C('URL_HTML_SUFFIX') && !empty($path)) {
            $path = preg_replace('/\.'.trim(C('URL_HTML_SUFFIX'),'.').'$/', '', $path);
        }
        $_SERVER['PATH_INFO'] = empty($path) ? '/' : $path;
    }

    /**
     +----------------------------------------------------------
     * 路由检测
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function routerCheck() {
        $regx = trim($_SERVER['PATH_INFO'],'/');
        if(empty($regx)) return true;
        // 是否开启路由使用
        if(!C('URL_ROUTER_ON')) return false;
        // 路由定义文件优先于config中的配置定义
        $routes = C('URL_ROUTE_RULES');
        if(is_array(C('routes')))
            $routes = C('routes');
        // 路由处理
        if(!empty($routes)) {
            $depr = C('URL_PATHINFO_DEPR');
            foreach ($routes as $key=>$route){
                if(0 === stripos($regx.$depr,$route[0].$depr)) {
                    // 简单路由定义：array('路由定义','分组/模块/操作名', '路由对应变量','额外参数'),
                    $var  =  self::parseUrl($route[1]);
                    //  获取当前路由参数对应的变量
                    $paths = explode($depr,trim(str_ireplace($route[0].$depr,$depr,$regx),$depr));
                    $vars    =   explode(',',$route[2]);
                    for($i=0;$i<count($vars);$i++)
                        $var[$vars[$i]]     =   array_shift($paths);
                    // 解析剩余的URL参数
                    $res = preg_replace('@(\w+)\/([^,\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode('/',$paths));
                    $_GET   =  array_merge($var,$_GET);
                    if(isset($route[3])) {
                        parse_str($route[3],$params);
                        $_GET   =   array_merge($_GET,$params);
                    }
                    return true;
                }elseif(1 < substr_count($route[0],'/') && preg_match($route[0],$regx,$matches)) {
                    // 路由定义规则：array('正则定义','分组/模块/操作名', '路由对应变量','额外参数'),
                    $var  =  self::parseUrl($route[1]);
                    //  获取当前路由参数对应的变量
                    $vars    =   explode(',',$route[2]);
                    for($i=0;$i<count($vars);$i++)
                        $var[$vars[$i]]     =   $matches[$i+1];
                    // 解析剩余的URL参数
                    $res = preg_replace('@(\w+)\/([^,\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', str_replace($matches[0],'',$regx));
                    $_GET   =  array_merge($var,$_GET);
                    if(isset($route[3])) {
                        parse_str($route[3],$params);
                        $_GET   =   array_merge($_GET,$params);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    static private function parseUrl($route) {
        $array   =  explode('/',$route);
        $var  =  array();
        $var[C('VAR_ACTION')] = array_pop($array);
        $var[C('VAR_MODULE')] = array_pop($array);
        if(!empty($array)) $var[C('VAR_GROUP')]  = array_pop($array);
        return $var;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的模块名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getModule($var) {
        $module = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_MODULE'));
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            define('P_MODULE_NAME',strtolower($module));
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $module = ucfirst(parse_name(P_MODULE_NAME,1));
        }
        unset($_GET[$var]);
        return strip_tags($module);
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
    static private function getAction($var) {
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        define('P_ACTION_NAME',$action);
        return strip_tags(C('URL_CASE_INSENSITIVE')?strtolower($action):$action);
    }

    /**
     +----------------------------------------------------------
     * 获得实际的分组名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getGroup($var) {
        $group   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_GROUP'));
        unset($_GET[$var]);
        return strip_tags(ucfirst(strtolower($group)));
    }

}//类定义结束
?>