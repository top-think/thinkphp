<?php

/**
 * 单元测试引导文件，实现THINKPHP引导、配置加载，PHPUNIT加载，测试用例自动加载
 * @author shuhai
 */

define("APP_NO_EXEC", true);
include dirname('./') . '/index.php';

//要求thinkphp核心扩展升级到最新才能支持phpunit
if (!defined('THINK_FORK_VERSION') || version_compare(THINK_FORK_VERSION, '3.2.4', '<')) {
    echo "ThinkPHP version is not support phpunit, please upgrade to 3.2.4+.\n";
    echo "use `cd ".THINK_PATH." && git pull origin master` if you using composer.\n";
    echo "see https://github.com/vus520/thinkphp/\n";
    exit;
}

//自动加载测试类
spl_autoload_register(function ($class) {

    if (strpos($class, "tests\\cases") == 0) {
        $class = $class . '.php';
        $class = str_replace("\\", "/", $class);
        include $class;
    }

});