<?php
//SAE系统常量，根据自己实际情况进行修改。
define( 'SAE_MYSQL_HOST_M', $sae_config['db_host'] );
define( 'SAE_MYSQL_HOST_S', $sae_config['db_host'] );
define( 'SAE_MYSQL_PORT', 3306 );
define( 'SAE_MYSQL_USER', $sae_config['db_user'] );
define( 'SAE_MYSQL_PASS', $sae_config['db_pass']);
define( 'SAE_MYSQL_DB', $sae_config['db_name']);
define('IMIT_CREATE_TABLE',true);//是否自动建立模拟器需要的数据表
// settings
define('SAE_FETCHURL_SERVICE_ADDRESS','http://fetchurl.sae.sina.com.cn');

// storage
define( 'SAE_STOREHOST', 'http://stor.sae.sina.com.cn/storageApi.php' );
define( 'SAE_S3HOST', 'http://s3.sae.sina.com.cn/s3Api.php' );

// saetmp constant
define( 'SAE_TMP_PATH' , '');


// define AccessKey and SecretKey
define( 'SAE_ACCESSKEY', '');
define( 'SAE_SECRETKEY', '');
//unset( $_SERVER['HTTP_ACCESSKEY'] );
//unset( $_SERVER['HTTP_SECRETKEY'] );

// gravity define
define('SAE_NorthWest', 1);
define('SAE_North', 2);
define('SAE_NorthEast',3);
define('SAE_East',6);
define('SAE_SouthEast',9);
define('SAE_South',8);
define('SAE_SouthWest',7);
define('SAE_West',4);
define('SAE_Static',10);
define('SAE_Center',5);

// font stretch
define('SAE_Undefined',0);
define('SAE_Normal',1);
define('SAE_UltraCondensed',2);
define('SAE_ExtraCondensed',3);
define('SAE_Condensed',4);
define('SAE_SemiCondensed',5);
define('SAE_SemiExpanded',6);
define('SAE_Expanded',7);
define('SAE_ExtraExpanded',8);
define('SAE_UltraExpanded',9);

// font style
define('SAE_Italic',2);
define('SAE_Oblique',3);

// font name
define('SAE_SimSun',1);
define('SAE_SimKai',2);
define('SAE_SimHei',3);
define('SAE_Arial',4);
define('SAE_MicroHei',5);

// anchor postion
define('SAE_TOP_LEFT','tl');
define('SAE_TOP_CENTER','tc');
define('SAE_TOP_RIGHT','tr');
define('SAE_CENTER_LEFT','cl');
define('SAE_CENTER_CENTER','cc');
define('SAE_CENTER_RIGHT','cr');
define('SAE_BOTTOM_LEFT','bl');
define('SAE_BOTTOM_CENTER','bc');
define('SAE_BOTTOM_RIGHT','br');

// errno define
define('SAE_Success', 0); // OK
define('SAE_ErrKey', 1); // invalid accesskey or secretkey
define('SAE_ErrForbidden', 2); // access fibidden for quota limit
define('SAE_ErrParameter', 3); // parameter not exist or invalid
define('SAE_ErrInternal', 500); // internal Error
define('SAE_ErrUnknown', 999); // unknown error

// fonts for gd
define('SAE_Font_Sun', '/usr/share/fonts/chinese/TrueType/uming.ttf');
define('SAE_Font_Kai', '/usr/share/fonts/chinese/TrueType/ukai.ttf');
define('SAE_Font_Hei', '/usr/share/fonts/chinese/TrueType/wqy-zenhei.ttc');
define('SAE_Font_MicroHei', '/usr/share/fonts/chinese/TrueType/wqy-microhei.ttc');
