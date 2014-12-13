<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://4wei.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 尘缘 <130775@qq.com>
// +----------------------------------------------------------------------
// $Id: CacheRedis.class.php 2690 2012-02-01 06:31:51Z liu21st $

/**
 +------------------------------------------------------------------------------
 * Memcache缓存类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    vus520 <130775@qq.com>
 * @version   0.0.1
 +------------------------------------------------------------------------------
 */
class CacheRedis extends Cache
{//类定义开始

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($options='') {
        if ( !extension_loaded('redis') ) {
            throw_exception(L('_NOT_SUPPERT_').':redis');
        }
        if(empty($options)) {
            $options = array (
                'host'  => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
                'port'  => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
                'timeout' => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
                'persistent' => false,
                'expire'   => C('DATA_CACHE_TIME'),
                'length'   => 0,
            );
        }
        $this->options =  $options;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new Redis;
        $this->connected = $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    private function isConnected() {
        return $this->connected;
    }

    /**
     +----------------------------------------------------------
     * 读取缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name) {
        N('cache_read',1);
        return $this->handler->get($name);
    }

    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function set($name, $value, $expire = null) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        if(is_int($expire)) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 删除缓存
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function rm($name) {
        return $this->handler->delete($name);
    }

    /**
     +----------------------------------------------------------
     * 清除缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function clear() {
        return $this->handler->flushDB();
    }
}//类定义结束
?>