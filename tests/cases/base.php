<?php

namespace tests\cases;

use \PHPUnit_Framework_TestCase;

/**
 * 测试常用基础类，清空配置，建立测试数据库等
 *
 * Class base
 * @package tests\cases
 */
class base extends PHPUnit_Framework_TestCase
{
    /**
     * 清空配置项，加载指定的配置文件中的配置
     * @param $config
     */
    function loadConfig($config, $rebuild = true)
    {
        $file = CONF_PATH . $config . CONF_EXT;
        $config = include $file;
        if (!empty($config)) {
            $config = [];
        }

        if ($rebuild) {
            C([]);
            C($config);
        }
    }
}