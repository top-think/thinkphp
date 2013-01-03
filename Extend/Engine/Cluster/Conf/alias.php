<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
// 系统别名定义文件
// TODU ,加载其他Cluster的文件
return array(
    'Model'         => CORE_PATH.'Core/Model.class.php',
    'Db'            => CORE_PATH.'Core/Db.class.php',
    'Log'          	=> CORE_PATH.'Core/Log.class.php',
    'ThinkTemplate' => CLUSTER_PATH.'Lib/Template/ThinkTemplate.class.php',
    'TagLib'        => CORE_PATH.'Template/TagLib.class.php',
	'Cache'         => CORE_PATH.'Core/Cache.class.php',
	//[cluster] 修改文件缓存实现类
	'CacheFile'		=> CLUSTER_PATH.'Lib/Driver/Cache/CacheFile.class.php',
    'Widget'        => CORE_PATH.'Core/Widget.class.php',
    'TagLibCx'      => CORE_PATH.'Driver/TagLib/TagLibCx.class.php',
);
