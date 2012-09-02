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
 * Shmop缓存驱动 
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheShmop extends Cache {

    /**
     * 架构函数
     * @access public
     */
    public function __construct($options='') {
        if ( !extension_loaded('shmop') ) {
            throw_exception(L('_NOT_SUPPERT_').':shmop');
        }
        if(!empty($options)){
            $options = array(
                'size'      => C('SHARE_MEM_SIZE'),
                'tmp'       => TEMP_PATH,
                'project'   => 's',
                'length'    =>  0,
                );
        }
        $this->options = $options;
        $this->handler = $this->_ftok($this->options['project']);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name = false) {
        N('cache_read',1);
        $id = shmop_open($this->handler, 'c', 0600, 0);
        if ($id !== false) {
            $ret = unserialize(shmop_read($id, 0, shmop_size($id)));
            shmop_close($id);

            if ($name === false) {
                return $ret;
            }
            if(isset($ret[$name])) {
                $content   =  $ret[$name];
                if(C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                    //启用数据压缩
                    $content   =   gzuncompress($content);
                }
                return $content;
            }else {
                return null;
            }
        }else {
            return false;
        }
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
        $lh = $this->_lock();
        $val = $this->get();
        if (!is_array($val)) $val = array();
        if( C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //数据压缩
            $value   =   gzcompress($value,3);
        }
        $val[$name] = $value;
        $val = serialize($val);
        if($this->_write($val, $lh)) {
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
        $lh = $this->_lock();
        $val = $this->get();
        if (!is_array($val)) $val = array();
        unset($val[$name]);
        $val = serialize($val);
        return $this->_write($val, $lh);
    }

    /**
     * 生成IPC key
     * @access private
     * @param string $project 项目标识名
     * @return integer
     */
    private function _ftok($project) {
        if (function_exists('ftok'))   return ftok(__FILE__, $project);
        if(strtoupper(PHP_OS) == 'WINNT'){
            $s = stat(__FILE__);
            return sprintf("%u", (($s['ino'] & 0xffff) | (($s['dev'] & 0xff) << 16) |
            (($project & 0xff) << 24)));
        }else {
            $filename = __FILE__ . (string) $project;
            for($key = array(); sizeof($key) < strlen($filename); $key[] = ord(substr($filename, sizeof($key), 1)));
            return dechex(array_sum($key));
        }
    }

    /**
     * 写入操作
     * @access private
     * @param string $name 缓存变量名
     * @return integer|boolen
     */
    private function _write(&$val, &$lh) {
        $id  = shmop_open($this->handler, 'c', 0600, $this->options['size']);
        if ($id) {
           $ret = shmop_write($id, $val, 0) == strlen($val);
           shmop_close($id);
           $this->_unlock($lh);
           return $ret;
        }
        $this->_unlock($lh);
        return false;
    }

    /**
     * 共享锁定
     * @access private
     * @param string $name 缓存变量名
     * @return boolen
     */
    private function _lock() {
        if (function_exists('sem_get')) {
            $fp = sem_get($this->handler, 1, 0600, 1);
            sem_acquire ($fp);
        } else {
            $fp = fopen($this->options['tmp'].$this->prefix.md5($this->handler), 'w');
            flock($fp, LOCK_EX);
        }
        return $fp;
    }

    /**
     * 解除共享锁定
     * @access private
     * @param string $name 缓存变量名
     * @return boolen
     */
    private function _unlock(&$fp) {
        if (function_exists('sem_release')) {
            sem_release($fp);
        } else {
            fclose($fp);
        }
    }
}