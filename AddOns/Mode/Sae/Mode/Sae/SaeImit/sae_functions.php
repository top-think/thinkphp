<?php
//SAE系统函数
function sae_set_display_errors($show=true){
//在本地设置不显示错误， 只不显示sae_debug的文字信息， 但是会显示系统错误，方便我们开发。
	global $sae_config;
	$sae_config['display_error']=$show;
}
function sae_debug($log){
    global $sae_config;
    error_log(date('[c]').$log.PHP_EOL,3,$sae_config['debug_file']);
	if($sae_config['display_error']!==false){
		echo $log.PHP_EOL;
	}
}

function memcache_init(){
	static $handler;
	if(is_object($handler)) return $handler;
    $handler=new Memcache;
    $handler->connect('127.0.0.1',11211);
    return $handler;
}
