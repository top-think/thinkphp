<?php
//SAE环境的IO文件
if(!function_exists('saeAutoLoader')){
	//如果是普通环境，加载普通核心文件
	define('IS_SAE',false);
	require THINK_PATH.'ThinkPHP.php';
	exit();
}
define('IS_SAE',true);
$global_mc=@memcache_init();
if(!$global_mc){
	header('Content-Type:text/html;charset=utf-8');
	exit('您未开通Memcache服务，请在SAE管理平台初始化Memcache服务');
}
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
function getSaeKvInstance(){
	static $kv;
	if(!is_object($kv)){
		$kv=new SaeKV();
		if(!$kv->init()) halt('您没有初始化KVDB，请在SAE管理平台初始化KVDB服务');
	}
	return $kv;
}
//F缓存设置
function F_set($name,$value){
	$kv=getSaeKvInstance();
	return $kv->set($name,$value);
}
//F缓存获取方法
function F_get($name){
	$kv=getSaeKvInstance();
	return $kv->get($name);
}
//F缓存的删除方法
function F_delete($name){
	$kv=getSaeKvInstance();
    if(false!==strpos($name,'*')){//实现批量删除
		$keys=$kv->pkrget(rtrim($name,'*'),100);
		if(is_array($keys)){
			foreach($keys as $key=>$value){
				$kv->delete($key);
			}
		}
		return true;
	}
	return $kv->delete($name);
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
//文件上传,路径中第一个文件夹名称会作为storage的domain。
function file_upload($src_file,$dest_file){
  if(!IS_SAE){//兼容普通环境
	 $pdir=dirname($dest_file);
  	 if(!is_dir($pdir)) @mkdir($pdir,0777);
	 return copy($src_file,$dest_file);
  }
  $s=new SaeStorage();
  $arr=explode('/',ltrim($dest_file,'./'));
  $domain=array_shift($arr);
  $save_path=implode('/',$arr);
  return $s->upload($domain,$save_path,$src_file);
}
//删除文件
function file_delete($filename){
    if (IS_SAE) {
        $arr = explode('/', ltrim($filename, './'));
        $domain = array_shift($arr);
        $filePath = implode('/', $arr);
        $s = new SaeStorage();
		return $s->delete($domain, $filePath);
    } else {
        return unlink($filename);
    }
}
//一般在IO专用配置中使用
function file_url_root($domain=''){
	if(!IS_SAE) return '';
	$s=new SaeStorage();
	return rtrim($s->getUrl($domain,''),'/');
}
//静态缓存,使用KVDB实现
function html_set($filename,$content){
	$kv=getSaeKvInstance();
	return $kv->set($filename,$content);
}
function html_get($filename){
   $kv=getSaeKvInstance();
   return $kv->get($filename);
}
//日志批量保存, 记录到SAE日志中心
function log_save($logs,$request_info){
	log_write('#############'.$request_info);
	foreach($logs as $log){
		log_write($log);
	}
}
//写入单条日志
function log_write($log){
	static $is_debug=null;
	if(is_null($is_debug)){
		preg_replace('@(\w+)\=([^;]*)@e', '$appSettings[\'\\1\']="\\2";', $_SERVER['HTTP_APPCOOKIE']);
		$is_debug = in_array($_SERVER['HTTP_APPVERSION'], explode(',', $appSettings['debug'])) ? true : false;
	}
	if($is_debug)
		sae_set_display_errors(false);//记录日志不将日志打印出来
	sae_debug($log);
	if($is_debug)
		sae_set_display_errors(true);
}
