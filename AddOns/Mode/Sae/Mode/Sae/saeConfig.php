<?php
//sae下的固定配置,以下配置将会覆盖项目配置。
return array(
        'DB_TYPE'=> 'mysql',     // 数据库类型
	'DB_HOST'=> SAE_MYSQL_HOST_M.",".SAE_MYSQL_HOST_S, // 服务器地址
	'DB_NAME'=> SAE_MYSQL_DB,        // 数据库名
	'DB_USER'=> SAE_MYSQL_USER,    // 用户名
	'DB_PWD'=> SAE_MYSQL_PASS,         // 密码
	'DB_PORT'=> SAE_MYSQL_PORT,        // 端口
	'DB_RW_SEPARATE'=>true,
        'DB_DEPLOY_TYPE'=> 1, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'DATA_CACHE_TYPE'=> 'Memcache',//S缓存类型为Memcache
        'HTML_FILE_SUFFIX'=>'.html',//默认静态文件后缀为html，在storage只有html文件能被直接浏览。
        'SAE_THINK_DOMAIN'=>'think',//ThinkPHP系统所需storage的domain名称。用于存储日志和静态缓存等。
        //sae下，默认不记录日志，如果要开启日志，请注释下面两行。开启日志功能后需要建立系统所需的storage。
        'LOG_EXCEPTION_RECORD'=>false,
        'LOG_RECORD'=>false
        );
