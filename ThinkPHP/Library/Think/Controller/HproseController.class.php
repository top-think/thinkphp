<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Controller;
use Think\Controller;
/**
 * ThinkPHP RPC控制器类
 */
class HproseController extends Controller {

    protected $allowMethodList  =   '';

   /**
     * 架构函数
     * @access public
     */
    public function __construct() {
        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
        //导入类库
        Vendor('Hprose.HproseHttpServer');
        //实例化phprpc
        $server     =   new \HproseHttpServer();
        if($this->allowMethodList){
            $methods    =   $this->allowMethodList;
        }else{
            $methods    =   get_class_methods($this);
        }
        $server->addMethods($methods,$this);
        if(APP_DEBUG) {
            $server->setDebugEnabled(true);
        }
        $server->start();
    }

}
