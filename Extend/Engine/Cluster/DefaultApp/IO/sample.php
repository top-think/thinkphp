<?php
//分布式环境IO操作实现函数，本文件只是示例代码，所有IO操作采用Memcache实现，但实际情况除了可以用Memcache存储还可以用redis，mongodb等。请根据自己环境的实际情况对以下函数进行实现。
if(!extension_loaded('memcache')){
	header('Content-Type:text/html;charset=utf-8');
	exit('您的环境不支持Memcache，请先安装Memcache扩展或者更改IO/sample.php文件，更改其他方式实现IO操作');
}
$global_mc=memcache_connect('localhost',11211);

/**
 * 编译缓存文件创建方法
 * 在写入核心缓存或者模版缓存时会调用这个函数，函数传递了要存的文件路径($filename)和文件内容($content)，你需要在这个函数中定义缓存内容存放在哪里
 * 
 * @param string $filename 文件名
 * @param string $content 文件内容
 * @return boolean
 */
function runtime_set($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}

/**
 * 获得编译缓存文件内容
 * 
 * @param string $filename  文件名
 * @return string  返回编译缓存内容
 */
function runtime_get($filename){
	global $global_mc;
	return $global_mc->get($filename);
}

/**
 * 删除缓存文件 
 * 
 * @param string $filename 
 * @return boolean
 */
function runtime_delete($filename){
	global $global_mc;
	return $global_mc->delete($filename);
}

/**
 * 设置F缓存
 * 调用F函数进行设置缓存时会触发，建议存储在可持久化的地方。
 * 
 * @param string $name 
 * @param string $value 
 * @return boolean
 */
function F_set($name,$value){
	global $global_mc;
	return $global_mc->set($name,$value,MEMCACHE_COMPRESSED,0);
}

/**
 * 获得F缓存
 * 
 * @param string $name 
 * @return string 返回F缓存内容
 */
function F_get($name){
	global $global_mc;
	return $global_mc->get($name);
}

/**
 * F缓存删除方法
 * 
 * @param string $name 
 * @return boolean
 */
function F_delete($name){
	global $global_mc;
	return $global_mc->delete($name);
}

/**
 * S缓存存储方法， S缓存建议存储在具有过期机制的缓存中（如memcache）
 * 只有当配置项DATA_CACHE_TYPE的值为file时，用S函数设置值才会触发此函数，DATA_CACHE_TYPE 系统默认为file，如果你设置了这个配置项不为file，那么S缓存的实现为你指定的缓存类型
 * 
 * @param string $name  
 * @param string $value 
 * @param integer $expire 过期时间，单位秒
 * @return boolean
 */
function S_set($name,$value,$expire){
	global $global_mc;
	return $global_mc->set($name,$value,MEMCACHE_COMPRESSED,$expire);
}
/**
 * 获得S缓存
 * 
 * @param string $name 
 * @return string 返回缓存内容
 */
function S_get($name){
	global $global_mc;
	return $global_mc->get($name);
}
/**
 * 删除S缓存 
 * 
 * @param string $name 
 * @return boolean
 */
function S_delete($name){
	global $global_mc;
	return $global_mc->delete($name);
}
/**
 * S缓存清空方法
 * 一般情况下是不会触发这个方法的，如果你的缓存没有flush方法也可以不实现这个函数 
 * 
 * @return boolean
 */
function S_clear(){
	global $global_mc;
	return $global_mc->flush();
}

/**
 * 文件上传
 * 系统先将要上传的文件保存为当前服务器的临时文件，我们需要将临时文件保存到一些分布式的存储系统上
 * 
 * @param string $src_file 临时文件地址
 * @param string $dest_file 保存地址
 * @return void
 */
function file_upload($src_file,$dest_file){
	$pdir=dirname($dest_file);
	if(!is_dir($pdir)) @mkdir($pdir,0777);
	return copy($src_file,$dest_file);
}

/**
 * 删除上传文件 
 * 
 * @param string $filename 
 * @return void
 */
function file_delete($filename){
	return unlink($filename);
}

/**
 * 获得文件显示地址的根路径
 * 
 * @param string $domain 
 * @access public
 * @return string
 */
function file_domain($domain=''){
	return '';
}

/**
 * 静态文件创建方法 
 * 设置配置项HTML_CACHE_ON为true后开启静态缓存机制会触发这个函数
 * 
 * @param string $filename 要创建的静态缓存文件名称 
 * @param string $content 静态缓存文件内容
 * @return boolean
 */
function html_set($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}

/**
 * 获得静态缓存内容
 * 
 * @param mixed $filename 
 * @return string 返回静态缓存内容
 */
function html_get($filename){
   global $global_mc;
   return $global_mc->get($filename);
}

/**
 * 请求日志保存方法 
 * 
 * @param array $logs 单次请求的所有日志  
 * @param string $request_info 此次请求信息，包括请求时间，客户端ip等信息。 
 * @return void
 */
function log_save($logs,$request_info){
}

/**
 * 单条日志写入方法 
 * 
 * @param string $log 日志内容
 * @return void
 */
function log_write($log){
}
