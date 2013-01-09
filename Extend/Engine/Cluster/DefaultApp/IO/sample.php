<?php
//分布式环境IO操作实现函数，本文件只是示例代码，请根据自己环境的实际情况对以下函数进行修改。
if(!extension_loaded('memcache')){
	header('Content-Type:text/html;charset=utf-8');
	exit('您的环境不支持Memcache，请先安装Memcache扩展或者更高IO/sample.php文件，更改其他方式实现IO操作');
}
$global_mc=memcache_connect('localhost',11211);
//编译缓存文件创建方法
function runtime_set($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}
//编译缓存文件设置方法
function runtime_get($filename){
	global $global_mc;
	return $global_mc->get($filename);
}
//编译缓存文件删除方法
function runtime_delete($filename){
	global $global_mc;
	return $global_mc->delete($filename);
}
//F缓存设置，强烈建议修改为可持久性的存储方式，如redis
function F_set($name,$value){
	global $global_mc;
	return $global_mc->set($name,$value,MEMCACHE_COMPRESSED,0);
}
//F缓存获取方法，强烈建议修改为可持久性的存储方式，如redis
function F_get($name){
	global $global_mc;
	return $global_mc->get($name);
}
//F缓存的删除方法，强烈建议修改为可持久性的存储方式，如redis
function F_delete($name){
	global $global_mc;
	return $global_mc->delete($name);
}
//S缓存的设置方法， 注：只有当DATA_CACHE_TYPE配置为File时下面的函数才会被触发，如果DATA_CACHE_TYPE如果不为File则触发你指定类型的缓存驱动。
function S_set($name,$value,$expire){
	global $global_mc;
	return $global_mc->set($name,$value,MEMCACHE_COMPRESSED,$expire);
}
function S_get($name){
	global $global_mc;
	return $global_mc->get($name);
}
function S_delete($name){
	global $global_mc;
	return $global_mc->delete($name);
}
function S_clear(){
	global $global_mc;
	return $global_mc->flush();
}
//文件上传,这只是示例代码，暂时以单机写入的方式举例，请根据自己的实际环境修改代码
function file_upload($src_file,$dest_file){
	$pdir=dirname($dest_file);
	if(!is_dir($pdir)) @mkdir($pdir,0777);
	return copy($src_file,$dest_file);
}
//删除上传的文件
function file_delete($filename){
	return delete($filename);
}
//获得文件的根地址
function file_url_root(){
	return '';
}
//静态缓存,强烈建议修改为可持久性的存储方式
function html_set($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}
function html_get($filename){
   global $global_mc;
   return $global_mc->get($filename);
}
//日志批量保存
function log_save($logs,$request_info){
}
//写入单条日志
function log_write($log){
}
