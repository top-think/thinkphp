<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch() {
        
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

        // 开启子域名部署
        if(C('APP_SUB_DOMAIN_DEPLOY')) {
            $rules      = C('APP_SUB_DOMAIN_RULES');
            if(isset($rules[$_SERVER['HTTP_HOST']])) { // 完整域名或者IP配置
                define('APP_DOMAIN',$_SERVER['HTTP_HOST']); // 当前完整域名
                $rule = $rules[APP_DOMAIN];
            }else{
                if(strpos(C('APP_DOMAIN_SUFFIX'),'.')){ // com.cn net.cn 
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -3);
                }else{
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -2);                    
                }
                if(!empty($domain)) {
                    $subDomain = implode('.', $domain);
                    define('SUB_DOMAIN',$subDomain); // 当前完整子域名
                    $domain2   = array_pop($domain); // 二级域名
                    if($domain) { // 存在三级域名
                        $domain3 = array_pop($domain);
                    }
                    if(isset($rules[$subDomain])) { // 子域名
                        $rule = $rules[$subDomain];
                    }elseif(isset($rules['*.' . $domain2]) && !empty($domain3)){ // 泛三级域名
                        $rule = $rules['*.' . $domain2];
                        $panDomain = $domain3;
                    }elseif(isset($rules['*']) && !empty($domain2) && 'www' != $domain2 ){ // 泛二级域名
                        $rule      = $rules['*'];
                        $panDomain = $domain2;
                    }
                }                
            }

            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('模块名[/控制器名]','var1=a&var2=b');
                if(is_array($rule)){
                    list($rule,$vars) = $rule;
                }
                $array      =   explode('/',$rule);
                // 模块绑定
                $_GET[$varModule]     =   array_shift($array);
                define('BIND_MODULE',$_GET[$varModule]);
                $domainModule         =   true;       
                // 控制器绑定         
                if(!empty($array)) {
                    $controller  =   array_shift($array);
                    if($controller){
                        $_GET[$varController]   =   $controller;
                        $domainController       =   true;
                    }
                    
                }
                if(isset($vars)) { // 传入参数
                    parse_str($vars,$parms);
                    if(isset($panDomain)){
                        $pos = array_search('*', $parms);
                        if(false !== $pos) {
                            // 泛域名作为参数
                            $parms[$pos] = $panDomain;
                        }                         
                    }                   
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }elseif(isset($_GET[$varModule])){
            // 绑定模块
            define('BIND_MODULE',$_GET[$varModule]);
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
        if(empty($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
        }
        $depr = C('URL_PATHINFO_DEPR');
        define('MODULE_PATHINFO_DEPR',  $depr);
        define('__INFO__',              trim($_SERVER['PATH_INFO'],'/'));
        // URL后缀
        define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION)));

        if (__INFO__ && C('MULTI_MODULE') && !isset($_GET[$varModule])){ // 获取模块
            $paths      =   explode($depr,__INFO__,2);
            $allowList  =   C('MODULE_ALLOW_LIST');
            $module     =   preg_replace('/\.' . __EXT__ . '$/i', '',$paths[0]);
            if( empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))){
                $_GET[$varModule]       =   $module;
                $_SERVER['PATH_INFO']   =   isset($paths[1])?$paths[1]:'';     
            }else{
                $_SERVER['PATH_INFO'] = __INFO__;
            }
        }else{
            $_SERVER['PATH_INFO'] = __INFO__;
        }

        // 获取模块名称
        define('MODULE_NAME', self::getModule($varModule));
        // 检测模块是否存在
        if( MODULE_NAME && (!in_array_case(MODULE_NAME,C('MODULE_DENY_LIST')) || $domainModule ) && is_dir(APP_PATH.MODULE_NAME)){
            // 定义当前模块路径
            define('MODULE_PATH', APP_PATH.MODULE_NAME.'/');
            // 加载模块配置文件
            if(is_file(MODULE_PATH.'Conf/config.php'))
                C(include MODULE_PATH.'Conf/config.php');
            // 加载模块别名定义
            if(is_file(MODULE_PATH.'Conf/alias.php'))
                Think::addMap(include MODULE_PATH.'Conf/alias.php');
            // 加载模块函数文件
            if(is_file(MODULE_PATH.'Common/function.php'))
                include MODULE_PATH.'Common/function.php';
        }else{
            E(L('_MODULE_NOT_EXIST_').':'.MODULE_NAME);
        }

        if('' != $_SERVER['PATH_INFO'] ){
            // 检查禁止访问的URL后缀
            if(C('URL_DENY_SUFFIX') && preg_match('/\.('.trim(C('URL_DENY_SUFFIX'),'.').')$/i', $_SERVER['PATH_INFO'])){
                send_http_status(404);
                exit;
            }

            if(C('URL_HTML_SUFFIX')) {
                $_SERVER['PATH_INFO'] = preg_replace('/\.('.trim(C('URL_HTML_SUFFIX'),'.').')$/i', '', $_SERVER['PATH_INFO']);
            }else{
                $_SERVER['PATH_INFO'] = preg_replace('/\.'.__EXT__.'$/i','',$_SERVER['PATH_INFO']);
            }

            $depr = C('URL_PATHINFO_DEPR');
            $paths = explode($depr,trim($_SERVER['PATH_INFO'],$depr));

            if(!isset($_GET[$varController])) {// 获取控制器
                $_GET[$varController]   =   array_shift($paths);
            }
            // 获取操作
            $_GET[$varAction]  =   array_shift($paths);
            // 解析剩余的URL参数
            $var  =  array();
            if(C('URL_PARAMS_BIND') && 1 == C('URL_PARAMS_BIND_TYPE')){
                // URL参数按顺序绑定变量
                $var    =   $paths;
            }else{
                preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){$var[$match[1]]=strip_tags($match[2]);}, implode('/',$paths));                
            }
            $_GET   =  array_merge($var,$_GET);
        }
        define('CONTROLLER_NAME',   self::getController($varController));
        define('ACTION_NAME',       self::getAction($varAction));
        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     * 获得实际的控制器名称
     * @access private
     * @return string
     */
    static private function getController($var) {
        $controller = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_CONTROLLER'));
        unset($_GET[$var]);
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            // 智能识别方式 user_type 识别到 UserTypeController 控制器
            $controller = ucfirst(parse_name($controller,1));
        }
        return strip_tags($controller);
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
        return strip_tags(C('URL_CASE_INSENSITIVE')?strtolower($action):$action);
    }

    /**
     * 获得实际的模块名称
     * @access private
     * @return string
     */
    static private function getModule($var) {
        $module   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        if($maps = C('URL_MODULE_MAP')) {
            if(isset($maps[strtolower($module)])) {
                // 记录当前别名
                define('MODULE_ALIAS',strtolower($module));
                // 获取实际的模块名
                return   ucfirst($maps[MODULE_ALIAS]);
            }elseif(array_search(strtolower($module),$maps)){
                // 禁止访问原始模块
                return   '';
            }
        }
        return strip_tags(C('URL_CASE_INSENSITIVE') ?ucfirst(strtolower($module)):$module);
    }

}
