<?php
namespace Think;

/**
 * ThinkPHP极简模式
 * @author shuhai
 *
 */
class App
{
    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    public static function init()
    {
        // 定义当前请求的系统常量
        define('NOW_TIME', $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
        define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
        define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
        define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
        define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);

        if (C('REQUEST_VARS_FILTER')) {
            // 全局安全过滤
            array_walk_recursive($_GET, 'think_filter');
            array_walk_recursive($_POST, 'think_filter');
            array_walk_recursive($_REQUEST, 'think_filter');
        }

        // 日志目录转换为绝对路径
        C('LOG_PATH', realpath(LOG_PATH) . '/');
        // TMPL_EXCEPTION_FILE 改为绝对地址
        C('TMPL_EXCEPTION_FILE', realpath(C('TMPL_EXCEPTION_FILE')));
        return;
    }

    /**
     * 执行应用程序
     * @access public
     * @return void
     */
    public static function exec()
    {
        // 获取控制器和操作名
        define('MODULE_NAME',     defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE'));
        define('CONTROLLER_NAME', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : C('DEFAULT_CONTROLLER'));
        define('ACTION_NAME',     defined('BIND_ACTION') ? BIND_ACTION : C('DEFAULT_ACTION'));
        
        $module = sprintf("%s/%s", MODULE_NAME, CONTROLLER_NAME);
        $module = A($module);
        $action = ACTION_NAME;

        //检查模块/控制器是否存在
        if (!$module) {
            // 是否定义Empty控制器
            $module = A('Empty');
            if (!$module) {
                E(L('_CONTROLLER_NOT_EXIST_') . ':' . CONTROLLER_NAME);
            }
        }

        try {
            if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
                // 非法操作
                throw new \ReflectionException();
            }

            //执行当前操作
            $method = new \ReflectionMethod($module, $action);
            if ($method->isPublic() && !$method->isStatic()) {
                $method->invoke($module);
            } else {
                // 操作方法不是Public 抛出异常
                throw new \ReflectionException();
            }
        } catch (\ReflectionException $e) {
            // 方法调用发生异常后 引导到__call方法处理
            $method = new \ReflectionMethod($module, '__call');
            $method->invokeArgs($module, array($action, ''));
        }
        return;
    }

    /**
     * 运行应用实例 入口文件使用的快捷方法
     * @access public
     * @return void
     */
    public static function run()
    {
        load_ext_file(COMMON_PATH);

        App::init();
        App::exec();
    }

}
