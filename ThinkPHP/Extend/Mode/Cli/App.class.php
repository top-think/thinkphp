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

/**
 * ThinkPHP 命令模式应用程序类
 */
class App {

    /**
     * 执行应用程序
     * @access public
     * @return void
     */
    static public function run() {

        if(C('URL_MODEL')==1) {// PATHINFO 模式URL下面 采用 index.php module/action/id/4
            $depr = C('URL_PATHINFO_DEPR');
            $path   = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:'';
            if(!empty($path)) {
                $params = explode($depr,trim($path,$depr));
            }
            // 取得模块和操作名称
            define('MODULE_NAME',   !empty($params)?array_shift($params):C('DEFAULT_MODULE'));
            define('ACTION_NAME',  !empty($params)?array_shift($params):C('DEFAULT_ACTION'));
            if(count($params)>1) {
                // 解析剩余参数 并采用GET方式获取
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',$params));
            }
        }else{// 默认URL模式 采用 index.php module action id 4
            // 取得模块和操作名称
            define('MODULE_NAME',   isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:C('DEFAULT_MODULE'));
            define('ACTION_NAME',    isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:C('DEFAULT_ACTION'));
            if($_SERVER['argc']>3) {
                // 解析剩余参数 并采用GET方式获取
                preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',array_slice($_SERVER['argv'],3)));
            }
        }

        // 执行操作
        $module  =  A(MODULE_NAME);
        if(!$module) {
            // 是否定义Empty模块
            $module = A("Empty");
            if(!$module){
                // 模块不存在 抛出异常
                throw_exception(L('_MODULE_NOT_EXIST_').MODULE_NAME);
            }
        }
        call_user_func(array(&$module,ACTION_NAME));
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }

};