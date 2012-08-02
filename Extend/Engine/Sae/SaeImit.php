<?php

/**
 * +---------------------------------------------------
 * | SAE本地开发环境， 模拟sae服务
 * +---------------------------------------------------
 * @author luofei614<www.3g4k.com>
 */
@(ini_set('post_max_size', '10M')); // sae下最大上传文件为10M
@(ini_set('upload_max_filesize', '10M'));
$sae_config = include(SAE_PATH.'SaeImit/config.php');//读取配置文件
include_once SAE_PATH.'SaeImit/defines.php';
include_once SAE_PATH.'SaeImit/sae_functions.php';
include_once SAE_PATH.'SaeImit/imit_functions.php';
include_once SAE_PATH.'SaeImit/Lang.php';
spl_autoload_register('sae_auto_load');
function sae_auto_load($class){
    $files=array(
    'SaeObject'=>SAE_PATH.'SaeImit/SaeObject.class.php',
    'SaeCounter'=> SAE_PATH.'SaeImit/SaeCounter.class.php',
    'SaeRank'=>SAE_PATH.'SaeImit/SaeRank.class.php',
    'SaeTaskQueue'=>SAE_PATH.'SaeImit/SaeTaskQueue.class.php',
    'SaeStorage'=>SAE_PATH.'SaeImit/SaeStorage.class.php',
    'SaeKVClient'=>SAE_PATH.'SaeImit/SaeKVClient.class.php',
    'Memcache'=>SAE_PATH.'SaeImit/Memcache.class.php',
    'CacheFile'=>THINK_PATH.'Lib/Driver/Cache/CacheFile.class.php',
    'SaeMail'=>SAE_PATH.'SaeImit/SaeMail.class.php',
    'SaeMysql'=>SAE_PATH.'SaeImit/SaeMysql.class.php',
    'ImitSqlite'=>SAE_PATH.'SaeImit/ImitSqlite.class.php',
     'SaeFetchurl'=>SAE_PATH.'SaeImit/SaeFetchurl.class.php',
     'SaeImage'=>SAE_PATH.'SaeImit/SaeImage.class.php'
    );
    if(isset($files[$class]))
        require $files[$class];
}
?>
