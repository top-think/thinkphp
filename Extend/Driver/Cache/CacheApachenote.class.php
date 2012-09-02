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
 * Apachenote缓存驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheApachenote extends Cache {

    /**
     * 架构函数
     * @access public
     */
    public function __construct($options='') {
        if(empty($options)){
            $options = array(
                'host'      =>  '127.0.0.1',
                'port'      =>  1042,
                'timeout'   =>  10,
                'length'    =>  0
            );
        }
        $this->handler = null;
        $this->open();
        $this->options = $options;
    }

    /**
     * 是否连接
     * @access public
     * @return boolen
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
     public function get($name) {
         $this->open();
         $s = 'F' . pack('N', strlen($name)) . $name;
         fwrite($this->handler, $s);

         for ($data = ''; !feof($this->handler);) {
             $data .= fread($this->handler, 4096);
         }
        N('cache_read',1);
         $this->close();
         return $data === '' ? '' : unserialize($data);
     }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @return boolen
     */
    public function set($name, $value) {
        N('cache_write',1);
        $this->open();
        $value = serialize($value);
        $s = 'S' . pack('NN', strlen($name), strlen($value)) . $name . $value;

        fwrite($this->handler, $s);
        $ret = fgets($this->handler);
        $this->close();
        if($ret === "OK\n") {
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
     public function rm($name) {
         $this->open();
         $s = 'D' . pack('N', strlen($name)) . $name;
         fwrite($this->handler, $s);
         $ret = fgets($this->handler);
         $this->close();
         return $ret === "OK\n";
     }

    /**
     * 关闭缓存
     * @access private
     */
     private function close() {
         fclose($this->handler);
         $this->handler = false;
     }

    /**
     * 打开缓存
     * @access private
     */
     private function open() {
         if (!is_resource($this->handler)) {
             $this->handler = fsockopen($this->options['host'], $this->options['port'], $_, $_, $this->options['timeout']);
             $this->connected = is_resource($this->handler);
         }
     }

}