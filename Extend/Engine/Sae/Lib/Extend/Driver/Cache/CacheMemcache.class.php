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

defined('THINK_PATH') or exit();
/**
 * Memcache缓存驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheMemcache extends Cache {

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    function __construct($options=array()) {
        if ( !extension_loaded('memcache') ) {
            throw_exception(L('_NOT_SUPPERT_').':memcache');
        }
        if(empty($options)) {
            $options = array (
                'host'        =>  C('MEMCACHE_HOST') ? C('MEMCACHE_HOST') : '127.0.0.1',
                'port'        =>  C('MEMCACHE_PORT') ? C('MEMCACHE_PORT') : 11211,
                'timeout'     =>  C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
                'persistent'  =>  false,
            );
        }
        $this->options      =   $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');        
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;        
        $func               =   $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler      =   new Memcache;
        $this->connected    =   $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }

    /**
     * 是否连接
     * @access private
     * @return boolen
     */
    private function isConnected() {
        return $this->connected;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        N('cache_read',1);
        return $this->handler->get($this->options['prefix'].$name);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolen
     */
    public function set($name, $value, $expire = null) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $name   =   $this->options['prefix'].$name;
        if($this->handler->set($name, $value, 0, $expire)) {
            if($this->options['length']>0) {
                // 记录缓存队列
                $this->queue($name);
            }
            return true;
        }
        return false;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolen
     */
    public function rm($name, $ttl = false) {
        $name   =   $this->options['prefix'].$name;
        return $ttl === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $ttl);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolen
     */
    public function clear() {
        return $this->handler->flush();
    }
}