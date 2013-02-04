<?php
if(function_exists('saeAutoLoader')){
	define('IS_CLOUD',true);
	define('IS_BAE',false);
	require dirname(__FILE__).'/sae.php';
	define('IO_TRUE_NAME','sae');
}elseif(isset($_SERVER['HTTP_BAE_ENV_APPID'])){
	define('IS_CLOUD',true);
	define('IS_SAE',false);
	require dirname(__FILE__).'/bae.php';
	define('IO_TRUE_NAME','bae');
}else{	
	define('IS_SAE',false);
	define('IS_BAE',false);
	define('IS_CLOUD',false);
	$runtime = defined('MODE_NAME')?'~'.strtolower(MODE_NAME).'_runtime.php':'~runtime.php';
	defined('RUNTIME_FILE') or define('RUNTIME_FILE',RUNTIME_PATH.$runtime);
	//加载 common_local 文件
	if(is_file(APP_PATH.'Common/common_local.php')){
		require APP_PATH.'Common/common_local.php';
	}
	//本地上传文件的IO操作
	function file_upload($src_file,$dest_file){
		$pdir=dirname($dest_file);
		if(!is_dir($pdir)) @mkdir($pdir,0777);
		return copy($src_file,$dest_file);
	}
	function file_delete($filename){
		return unlink($filename);
	}
	function file_get($filename){
		return file_get_contents($filename);
	}
	if(!APP_DEBUG && is_file(RUNTIME_FILE)) {
	    // 部署模式直接载入运行缓存
	    require RUNTIME_FILE;
	}else{
	    // 加载运行时文件
	    require THINK_PATH.'Common/runtime.php';
	}
	exit();
}
