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
 * ThinkPHP内置的Dispatcher类 用于精简模式
 * 完成URL解析、路由和调度
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Dispatcher.class.php 2702 2012-02-02 12:35:01Z liu21st $
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch() {
        $urlMode  =  C('URL_MODEL');
        if($urlMode == URL_COMPAT || !empty($_GET[C('VAR_PATHINFO')])){
            // 兼容模式判断
            define('PHP_FILE',_PHP_FILE_.'?'.C('VAR_PATHINFO').'=');
            $_SERVER['PATH_INFO']   = $_GET[C('VAR_PATHINFO')];
            unset($_GET[C('VAR_PATHINFO')]);
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

        // 分析PATHINFO信息
        tag('path_info');
        // 分析PATHINFO信息
        $depr = C('URL_PATHINFO_DEPR');
        if(!empty($_SERVER['PATH_INFO'])) {
            if(C('URL_HTML_SUFFIX') && !empty($_SERVER['PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = preg_replace('/\.'.trim(C('URL_HTML_SUFFIX'),'.').'$/', '', $_SERVER['PATH_INFO']);
            }
            if(!self::routerCheck()){   // 检测路由规则 如果没有则按默认规则调度URL
                $paths = explode($depr,trim($_SERVER['PATH_INFO'],'/'));
                $var  =  array();
                if (C('APP_GROUP_LIST') && !isset($_GET[C('VAR_GROUP')])){
                    $var[C('VAR_GROUP')] = in_array(strtolower($paths[0]),explode(',',strtolower(C('APP_GROUP_LIST'))))? array_shift($paths) : '';
                }
                if(!isset($_GET[C('VAR_MODULE')])) {// 还没有定义模块名称
                    $var[C('VAR_MODULE')]  =   array_shift($paths);
                }
                $var[C('VAR_ACTION')]  =   array_shift($paths);
                // 解析剩余的URL参数
                $res = preg_replace('@(\w+)'.$depr.'([^'.$depr.'\/]+)@e', '$var[\'\\1\']="\\2";', implode($depr,$paths));
                $_GET   =  array_merge($var,$_GET);
            }
        }

        // 获取分组 模块和操作名称
        if (C('APP_GROUP_LIST')) {
            define('GROUP_NAME', self::getGroup(C('VAR_GROUP')));
        }
        define('MODULE_NAME',self::getModule(C('VAR_MODULE')));
        define('ACTION_NAME',self::getAction(C('VAR_ACTION')));
        // URL常量
        define('__SELF__',$_SERVER['REQUEST_URI']);
        // 当前项目地址
        define('__APP__',PHP_FILE);
        // 当前模块和分组地址
        $module = defined('P_MODULE_NAME')?P_MODULE_NAME:MODULE_NAME;
        if(defined('GROUP_NAME')) {
            $group   = C('URL_CASE_INSENSITIVE') ?strtolower(GROUP_NAME):GROUP_NAME;
            define('__GROUP__', GROUP_NAME == C('DEFAULT_GROUP') ?__APP__ : __APP__.'/'.$group);
            define('__URL__', __GROUP__.$depr.$module);
        }else{
            define('__URL__',__APP__.'/'.$module);
        }
        // 当前操作地址
        define('__ACTION__',__URL__.$depr.ACTION_NAME);
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
    static private function getModule($var) {
        $module = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            define('P_MODULE_NAME',strtolower($module));
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $module = ucfirst(parse_name(P_MODULE_NAME,1));
        }
        return $module;
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
        define('P_ACTION_NAME',$action);
        return C('URL_CASE_INSENSITIVE')?strtolower($action):$action;
    }

    /**
     * 获得实际的分组名称
     * @access private
     * @return string
     */
    static private function getGroup($var) {
        $group   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_GROUP'));
        unset($_GET[$var]);
        return ucfirst(strtolower($group));
    }

}