<?php 
class SwitchMobileTplBehavior extends Behavior {
        //智能切换模板引擎
        public function run(&$params){
	      if(isset($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT']){ 
		    	C('TMPL_ENGINE_TYPE','Mobile');
                define('IS_CLIENT',true);
          }else{
                define('IS_CLIENT',false);
                 if('./client/'==TMPL_PATH){
                    $find=APP_TMPL_PATH;
                    $replace=__ROOT__.'/client/'; 
                    $parse_string=C('TMPL_PARSE_STRING');
                    if(is_null($parse_string)) $parse_string=array();
                    //自动增加一个模板替换变量，用于修复SAE平台下模板中使用../Public 会解析错误的问题。
                    C('TMPL_PARSE_STRING',array_merge($parse_string,array($find=>$replace)));
                    //判断如果是云窗调试器访问跳转访问首页到client目录
                    if(APP_DEBUG && ''==__INFO__ && preg_match('/android|iphone/i',$_SERVER['HTTP_USER_AGENT'])){ 
                        redirect(__ROOT__.'/client');
                        exit();
                    }
                }
         }
        }
}
