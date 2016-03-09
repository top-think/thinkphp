<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

// 分布式文件存储类
class Storage
{

    /**
     * 操作句柄
     * @var string
     * @access protected
     */
    protected static $handler;

    /**
     * 连接分布式文件系统
     * @access public
     * @param string $type 文件类型
     * @param array $options  配置数组
     * @return void
     */
    public static function connect($type = 'File', $options = array())
    {
        $class         = 'Think\\Storage\\Driver\\' . ucwords($type);
        self::$handler = new $class($options);
    }

    public static function __callStatic($method, $args)
    {
        //调用缓存驱动的方法
        if (method_exists(self::$handler, $method)) {
            return call_user_func_array(array(self::$handler, $method), $args);
        }
    }
}
