<?php
/**
* +---------------------------------------------------
* | 分布式文件操作系统 FileSystem
* +---------------------------------------------------
* @author luofei614<luofei614@gmail.com>
*/
class ThinkFS{
	static private $current_include_file=null;
	static private $_contents=array();
	static private $_mtimes=array();
	//创建文件
	static public function set($filename,$content){
		//写入文件时，会将文件的创建时间放在内容的最前面
	  return runtime_set($filename,time().$content);
	}
	//包含文件
	static public function include_file($_filename,$_vars=null){
		self::$current_include_file=$_filename;
		if(!is_null($_vars))
			extract($_vars,EXTR_OVERWRITE);
		$_content=isset(self::$_contents[$_filename])?self::$_contents[$_filename]:self::get_value($_filename,'content');
		//eval时要用@屏蔽报错才能自己接管报错,接管函数self::error。
		if(@eval(' ?>'.$_content)===false)
			self::error();
		self::$current_include_file=null;
		unset(self::$_contents[$_filename]);
		return true;
	}
	//判断文件是否存在
	static public function file_exists($filename){
		return self::get_value($filename)?true:false;
	}
	//获得文件的修改时间
	static public function filemtime($filename){
		if(!isset($_mtimes[$filename]))
			return self::get_value($filename,'mtime');
		return $_mtimes[$filename];
	}
	//删除文件
	static public function unlink($filename){
		unset(self::$_contents[$filename],self::$_mtimes[$filename]);
		return runtime_delete($filename);
	}

	static private function get_value($filename,$type='mtime'){
		$content=runtime_get($filename);
		if(!$content) return false;
		$ret=array(
			'mtime'=>substr($content,0,10),
			'content'=>substr($content,10)
		);
		self::$_contents[$filename]=$ret['content'];
		self::$_mtimes[$filename]=$ret['mtime'];
		return $ret[$type];
	}
	//接管报错函数，解决eval执行代码时报错不能明确具体文件名的问题。
	static function error() {
		$error = error_get_last();
		if (!is_null($error) && strpos($error['file'], 'eval()') !== false) {
			if(!class_exists('Think')){
				if(C('OUTPUT_ENCODE')){
					$zlib = ini_get('zlib.output_compression');
					if(empty($zlib)) ob_start('ob_gzhandler');
				}
				exit("<br /><b>error</b>:  {$error['message']} in <b>" . self::$current_include_file . "</b> on line <b>{$error['line']}</b><br />");
			}else{
				Think::appError($error['type'], $error['message'], self::$current_include_file, $error['line']);
		  }
		}
	}
}
register_shutdown_function(array('ThinkFS','error'));
