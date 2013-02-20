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
 * ??»¶ç±»å?ç¼??ç±? * @category   Think
 * @package  Think
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheFile extends Cache {

    /**
     * ?¶æ??½æ?
     * @access public
     */
    public function __construct($options=array()) {
        if(!empty($options)) {
            $this->options =  $options;
        }
        $this->options['temp']      =   !empty($options['temp'])?   $options['temp']    :   C('DATA_CACHE_PATH');
        $this->options['prefix']    =   isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');
        $this->options['expire']    =   isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['length']    =   isset($options['length'])?  $options['length']  :   0;
        if(substr($this->options['temp'], -1) != '/')    $this->options['temp'] .= '/';
        $this->init();
    }

    /**
     * ????????     * @access private
     * @return boolen
     */
    private function init() {
        $stat = stat($this->options['temp']);
        $dir_perms = $stat? $stat['mode'] & 0007777 : 0007777; // Get the permission bits.
        $file_perms = $dir_perms & 0000666; // Remove execute bits for files.

        // ??»ºé¡¹ç?ç¼?????
        if (!is_dir($this->options['temp'])) {
            if (!  mkdir($this->options['temp']))
                return false;
             chmod($this->options['temp'], $dir_perms);
        }
    }

    /**
     * ??????????¨æ?ä»¶å?
     * @access private
     * @param string $name ç¼???????     * @return string
     */
    private function filename($name) {
        $name	=	md5($name);
        if(C('DATA_CACHE_SUBDIR')) {
            // ä½¿ç?å­??å½?            $dir   ='';
            for($i=0;$i<C('DATA_PATH_LEVEL');$i++) {
                $dir	.=	$name{$i}.'/';
            }
            if(!is_dir($this->options['temp'].$dir)) {
                mkdir($this->options['temp'].$dir,0755,true);
            }
            $filename	=	$dir.$this->options['prefix'].$name.'.php';
        }else{
            $filename	=	$this->options['prefix'].$name.'.php';
        }
        return $this->options['temp'].$filename;
    }

    /**
     * è¯»å?ç¼??
     * @access public
     * @param string $name ç¼???????     * @return mixed
     */
    public function get($name) {
        $filename   =   $this->filename($name);
        if (!is_file($filename)) {
           return false;
        }
        N('cache_read',1);
        $content    =   file_get_contents($filename);
        if( false !== $content) {
            $expire  =  (int)substr($content,8, 12);
            if($expire != 0 && time() > filemtime($filename) + $expire) {
                //ç¼??è¿?????ç¼????»¶
                unlink($filename);
                return false;
            }
            if(C('DATA_CACHE_CHECK')) {//å¼???°æ??¡é?
                $check  =  substr($content,20, 32);
                $content   =  substr($content,52, -3);
                if($check != md5($content)) {//?¡é????
                    return false;
                }
            }else {
            	$content   =  substr($content,20, -3);
            }
            if(C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                //????°æ???¼©
                $content   =   gzuncompress($content);
            }
            $content    =   unserialize($content);
            return $content;
        }
        else {
            return false;
        }
    }

    /**
     * ???ç¼??
     * @access public
     * @param string $name ç¼???????     * @param mixed $value  å­???°æ?
     * @param int $expire  ????¶é? 0ä¸ºæ°¸ä¹?     * @return boolen
     */
    public function set($name,$value,$expire=null) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire =  $this->options['expire'];
        }
        $filename   =   $this->filename($name);
        $data   =   serialize($value);
        if( C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //?°æ???¼©
            $data   =   gzcompress($data,3);
        }
        if(C('DATA_CACHE_CHECK')) {//å¼???°æ??¡é?
            $check  =  md5($data);
        }else {
            $check  =  '';
        }
        $data    = "<?php\n//".sprintf('%012d',$expire).$check.$data."\n?>";
        $result  =   file_put_contents($filename,$data);
        if($result) {
            if($this->options['length']>0) {
                // è®°å?ç¼?????
                $this->queue($name);
            }
            clearstatcache();
            return true;
        }else {
            return false;
        }
    }

    /**
     * ???ç¼??
     * @access public
     * @param string $name ç¼???????     * @return boolen
     */
    public function rm($name) {
        return unlink($this->filename($name));
    }

    /**
     * æ¸??ç¼??
     * @access public
     * @param string $name ç¼???????     * @return boolen
     */
    public function clear() {
        $path   =  $this->options['temp'];
        if ( $dir = opendir( $path ) ) {
            while ( $file = readdir( $dir ) ) {
                $check = is_dir( $file );
                if ( !$check )
                    unlink( $path . $file );
            }
            closedir( $dir );
            return true;
        }
    }
}
