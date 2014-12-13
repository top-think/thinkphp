<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: cevin <cevin1991@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * HMAC 加密实现类
 +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  Crypt
 * @author    cevin <cevin1991@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Hmac
{

    /**
     +----------------------------------------------------------
     * SHA1加密
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param string $key 加密key
     * @param string $str 字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	public static function sha1($key,$str) {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize)
            $key=pack('H*', $hashfunc($key));
        $key=str_pad($key,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack(
                    'H*',$hashfunc(
                        ($key^$opad).pack(
                            'H*',$hashfunc(
                                ($key^$ipad).$str
                            )
                        )
                    )
                );
        return $hmac;
    }

    /**
     +----------------------------------------------------------
     * MD5加密
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param string $key 加密key
     * @param string $str 字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	public static function md5($key, $str) {
        $b = 64;
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*",md5($k_ipad . $str)));
    }

}
?>