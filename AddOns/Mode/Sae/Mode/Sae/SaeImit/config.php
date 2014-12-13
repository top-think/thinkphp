<?php
// +----------------------------------------------------------------------
// | sae模拟器配置
// +----------------------------------------------------------------------
// | Author: luofei614<www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: config.php 2442 2011-12-18 08:29:00Z luofei614@gmail.com $
return array(
    'db_host'=>C('DB_HOST'),
    'db_user'=>C('DB_USER'),
    'db_pass'=>C('DB_PWD'),
    'db_name'=>C('DB_NAME'),
    'db_charset'=>C('DB_CHARSET'),
    'storage_url'=>__ROOT__.'/Public/',
    'storage_dir'=>'./Public/',
    'debug_file'=>RUNTIME_PATH."/Logs/sae_debug.log"
     
);