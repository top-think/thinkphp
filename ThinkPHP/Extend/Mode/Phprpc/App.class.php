<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: App.class.php 2504 2011-12-28 07:35:29Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP AMF模式应用程序类
 +------------------------------------------------------------------------------
 */
class App {

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function run() {

    	//导入类库
    	Vendor('phpRPC.phprpc_server');
    	//实例化phprpc
    	$server = new PHPRPC_Server();
        $actions =  explode(',',C('APP_PHPRPC_ACTIONS'));
        foreach ($actions as $action){
       	    //$server -> setClass($action.'Action'); 
			$temp = $action.'Action';
			$methods = get_class_methods($temp);
			$server->add($methods,new $temp);
		}
        if(APP_DEBUG) {
            $server->setDebugMode(true);
        }
        $server->setEnableGZIP(true);
		$server->start();
		//C('PHPRPC_COMMENT',$server->comment());
		echo $server->comment();
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }

};