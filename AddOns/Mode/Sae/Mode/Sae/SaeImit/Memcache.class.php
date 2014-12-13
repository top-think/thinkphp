<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: Memcache.class.php 2701 2012-02-02 12:27:51Z liu21st $
/**
*memcache模拟器。
*当本地环境不支持memcache时被调用。
*当你本地环境支持memcache时，会使用原始的memcache，此类将不起作用。
*/
class Memcache extends Think{
	private static $handler;
	public function __construct(){
		if(!is_object(self::$handler)){
			self::$handler=new CacheFile(); 
		}
	}
	public static function add($key, $var, $flag=null, $expire=-1){
		if(self::$handler->get($key)!==false) return false;
		return	self::$handler->set($key,$var,$expire);
	}
	public static function addServer($host,$port=11211,$persistent=null,$weight=null,$timeout=null,$retry_interval=null,$status=null,$failure_back=null,$timeoutms=null){
		return true;
	}
	public static function close(){
		return true;
	}
	public static function connect($host,$port=11211,$timeout=null){
		return true;
	}
	public static function decrement($key,$value=1){
		return self::$handler->decrement($key,$value);
	}
	
	public static function increment($key,$value=1){
		return self::$handler->increment($key,$value);
	}
	
	public static function delete($key,$timeout=0){
		$v=S($key);
		if($v===false) return false;
		if($timeout!==0){
			return	self::$handler->set($key,$v,$timeout);
		}else{
			return self::$handler->rm($key);
		}
	}
	public static function flush(){
		return self::$handler->clear();
	}
	public static function get($key,$flag=null){
		if(is_string($key)){
			return self::$handler->get($key);
		}else{
			//返回数组形式 array('k1'=>'v1','k2'=>'v2')
			$ret=array();
			foreach($key as $k){
				$ret[$k]=self::$handler->get($k);
			}
			return $ret;
		}
	}
	public static function getExtendedStats($type=null,$slabid=null,$limit=100){
		//pass
		return true;
	}
	public static function getServerStatus($host,$port=11211){
		return 1;
	}
	public static function getStats($type,$stabid=null,$limit=100){
		//pass
		return true;
	}
	
	public static function getVersion(){
		//todu 待完善
		return true;
	}
	
	public static function pconnect($host,$port=11211,$timeout=null){
		//pass
		return true;
	}
	public static function replace($key,$var,$flag=null,$expire=-1){
		if(self::$handler->get($key)===false) return false;
		return self::$handler->set($key,$var,$flag,$expire);
	}
	
	public static function set($key,$var,$flag=null,$expire=-1){
		return self::$handler->set($key,$var,$expire);
	}
	public static function setCompressThreshold($threshold,$min_savings=null){
		//pass
		return true;
	}
	
	public static function setServerParams($host,$port=11211,$timeout=-1,$retry_interval=false,$status=null,$retry_interval=false){
		return true;
	}
	//todu memcache_debug 函数
}

function memcache_add($m,$key, $var, $flag=null, $expire=-1){
	return Memcache::add($key,$var,$flag,$expire);
}
function memcache_add_server($host,$port=11211,$persistent=null,$weight=null,$timeout=null,$retry_interval=null,$status=null,$failure_back=null,$timeoutms=null){
	return true;
}
function memcache_close(){
	return true;
}

function memcache_decrement($m,$key,$value=1){
	return Memcache::decrement($m,$key,$value);
}
function memcache_increment($m,$key,$value=1){
	return Memcache::increment($key,$value);
}
function memcache_delete($m,$key,$timeout=0){
	return Memcache::delete($key,$timeout);
}
function memcache_flush($m){
	return Memcache::flush();
}

function memcache_get_extended_stats($m,$type=null,$slabid=null,$limit=100){
	return true;
}

function memcache_get_server_status($m,$host,$port=11211){
	return 1;
}
function memcache_get_stats($m,$type,$stabid=null,$limit=100){
	return true;
}
function memcache_get_version($m){
	return true;
}
function memcache_pconnect($host,$port=11211,$timeout=null){
	return true;
}
function memcache_replace($m,$key,$var,$flag=null,$expire){
	return Memcache::replace($key,$var,$flag,$expire);
}



function memcache_set_compress_threshold($m,$threshold,$min_savings=null){
	return true;
}
function memcache_set_server_params($host,$port=11211,$timeout=-1,$retry_interval=false,$status=null,$retry_interval=false){
	return true;
}

function memcache_set($m,$key,$value){
	return $mmc->set($key,$value);
}
function memcache_get($m,$key){
	return $mmc->get($key);
}