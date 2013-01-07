<?php
if(function_exists('saeAutoLoader')){
	require dirname(__FILE__).'/sae.php';
	define('IO_CONFIG','sae');
}elseif(isset($_SERVER['HTTP_BAE_ENV_APPID'])){
	require dirname(__FILE__).'/bae.php';
	define('IO_CONFIG','bae');
}else{
	require THINK_PATH.'ThinkPHP.php';
	exit();
}
