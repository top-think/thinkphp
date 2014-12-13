<?php
//sae下的固定配置,以下配置将会覆盖项目配置。
return array(
        'DB_TYPE'=> 'mysql',     // 数据库类型
	'DB_HOST'=> SAE_MYSQL_HOST_M.','.SAE_MYSQL_HOST_S, // 服务器地址
	'DB_NAME'=> SAE_MYSQL_DB,        // 数据库名
	'DB_USER'=> SAE_MYSQL_USER,    // 用户名
	'DB_PWD'=> SAE_MYSQL_PASS,         // 密码
	'DB_PORT'=> SAE_MYSQL_PORT,        // 端口
	'DB_RW_SEPARATE'=>true,
        'DB_DEPLOY_TYPE'=> 1, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'SAE_SPECIALIZED_FILES'=>array(
            //SAE系统专属文件。
            'UploadFile.class.php'=>SAE_PATH.'Lib/Extend/Library/ORG/Net/UploadFile_sae.class.php',
            'Image.class.php'=>SAE_PATH.'Lib/Extend/Library/ORG/Util/Image_sae.class.php'
         )
        );
