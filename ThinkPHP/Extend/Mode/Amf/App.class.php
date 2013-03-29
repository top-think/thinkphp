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

/**
 * ThinkPHP AMF模式应用程序类
 */
class App {

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function run() {

    	//导入类库
    	Vendor('Zend.Amf.Server');
    	//实例化AMF
    	$server = new Zend_Amf_Server();
        $actions =  explode(',',C('APP_AMF_ACTIONS'));
        foreach ($actions as $action)
       	    $server -> setClass($action.'Action');
    	echo $server -> handle();

        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }

};