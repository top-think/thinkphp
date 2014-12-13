<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * Apachenote缓存类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class CacheApachenote extends Cache
{//类定义开始


    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($options='')
    {
        if(empty($options)){
            $options = array(
                'host' => '127.0.0.1',
                'port' => 1042,
                'timeout' => 10
        );
        }
        $this->handler = null;
        $this->open();
        $this->options = $options;
        $this->type = strtoupper(substr(__CLASS__,6));

    }

    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function isConnected()
    {
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
     public function get($name)
     {
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
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function set($name, $value)
    {
        N('cache_write',1);
		$this->open();
        $value = serialize($value);
        $s = 'S' . pack('NN', strlen($name), strlen($value)) . $name . $value;

        fwrite($this->handler, $s);
        $ret = fgets($this->handler);
        $this->close();
        $this->setTime[$name] = time();
        return $ret === "OK\n";
    }

    /**
     +----------------------------------------------------------
     * 删除缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
     public function rm($name)
     {
         $this->open();
         $s = 'D' . pack('N', strlen($name)) . $name;
         fwrite($this->handler, $s);
         $ret = fgets($this->handler);
         $this->close();

         return $ret === "OK\n";
     }

    /**
     +----------------------------------------------------------
     * 关闭缓存
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     */
     private function close()
     {
         fclose($this->handler);
         $this->handler = false;
     }

    /**
     +----------------------------------------------------------
     * 打开缓存
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     */
     private function open()
     {
         if (!is_resource($this->handler)) {
             $this->handler = fsockopen($this->options['host'], $this->options['port'], $_, $_, $this->options['timeout']);
             $this->connected = is_resource($this->handler);
         }
     }

}//类定义结束
?>