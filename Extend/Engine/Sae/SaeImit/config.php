<?php
// +----------------------------------------------------------------------
// | sae模拟器配置
// +----------------------------------------------------------------------
// | Author: luofei614<www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: config.php 903 2012-03-16 03:50:22Z luofei614@126.com $
$appConfig=  include APP_PATH.'Conf/config.php';
return array(
    'db_host'=>isset($appConfig['DB_HOST'])?$appConfig['DB_HOST']:'localhost',
    'db_user'=>isset($appConfig['DB_USER'])?$appConfig['DB_USER']:'root',
    'db_pass'=>isset($appConfig['DB_PWD'])?$appConfig['DB_PWD']:'',
    'db_name'=>isset($appConfig['DB_NAME'])?$appConfig['DB_NAME']:'sae',
    'db_charset'=>isset($appConfig['DB_CHARSET'])?$appConfig['DB_CHARSET']:'utf8',
    'storage_url'=>trim(dirname($_SERVER['SCRIPT_NAME']),'/\\').'/',
    'storage_dir'=>'./',
    'debug_file'=>APP_PATH.'Runtime/Logs/sae_debug.log'
     
);