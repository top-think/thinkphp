<?php
//分布式环境IO操作实现函数，本文件只是示例代码，请根据自己环境的实际情况对以下函数进行修改。
$global_mc=memcache_connect('localhost',11211);
//编译缓存文件创建方法
function runtime_write($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}
//编译缓存文件设置方法
function runtime_read($filename){
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
//文件上传
function cluster_uploaded_file($filename,$destination){
  //请根据自己的实际情况实现文件上传
  dump($filename);
  dump($destination);
  return true;
}
//静态缓存,强烈建议修改为可持久性的存储方式
function html_write($filename,$content){
	global $global_mc;
	return $global_mc->set($filename,$content,MEMCACHE_COMPRESSED,0);
}
function html_read($filename){
   global $global_mc;
   return $global_mc->get($filename);
}
//日志批量保存
function log_save($logs,$request_info){
}
//写入单条日志
function log_write($log){
}
