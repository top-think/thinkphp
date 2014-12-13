<?php

/**
 * +---------------------------------------------------
 * | SAE本地开发环境， 模拟sae服务
 * +---------------------------------------------------
 * @author luofei614<www.3g4k.com>
 */
error_reporting(E_ALL & ~E_NOTICE);
@(ini_set('post_max_size', '10M')); // sae下最大上传文件为10M
@(ini_set('upload_max_filesize', '10M'));
if(C()==NULL){
    //如果配置为空， 立即读取配置文件
    C(include THINK_PATH.'/Common/convention.php');
    if(is_file(CONFIG_PATH.'config.php'))
            C(include CONFIG_PATH.'config.php');
}
$sae_config = include(THINK_PATH.'/Mode/Sae/SaeImit/config.php');//读取配置文件
include_once THINK_PATH.'/Mode/Sae/SaeImit/defines.php';
include_once THINK_PATH.'/Mode/Sae/SaeImit/sae_functions.php';
include_once THINK_PATH.'/Mode/Sae/SaeImit/imit_functions.php';
include_once THINK_PATH.'/Mode/Sae/SaeImit/Lang.php';
alias_import(array(
    'SaeObject'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeObject.class.php',
    'SaeCounter'=> THINK_PATH.'/Mode/Sae/SaeImit/SaeCounter.class.php',
    'SaeRank'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeRank.class.php',
    'SaeTaskQueue'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeTaskQueue.class.php',
    'SaeStorage'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeStorage.class.php',
    'SaeKVClient'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeKVClient.class.php',
    'Memcache'=>THINK_PATH.'/Mode/Sae/SaeImit/Memcache.class.php',
    'CacheFile'=>THINK_PATH.'/Lib/Think/Util/Cache/CacheFile.class.php',
    'SaeMail'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeMail.class.php',
    'SaeMysql'=>THINK_PATH.'/Mode/Sae/SaeImit/SaeMysql.class.php',
    'ImitSqlite'=>THINK_PATH.'/Mode/Sae/SaeImit/ImitSqlite.class.php',
    )
);
?>
