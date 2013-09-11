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
namespace Think\Template\Driver;
defined('THINK_PATH') or exit();
/**
 * TemplateLite模板引擎驱动 
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Template
 * @author    liu21st <liu21st@gmail.com>
 */
class Lite {
    /**
     * 渲染模板输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param array $var 模板变量
     * @return void
     */
    public function fetch($templateFile,$var) {
        vendor("TemplateLite.class#template");
        $templateFile   =   substr($templateFile,strlen(THEME_PATH));
        $tpl            =   new Template_Lite();
        $tpl->template_dir  = THEME_PATH;
        $tpl->compile_dir   = CACHE_PATH ;
        $tpl->cache_dir     = TEMP_PATH ;        
        if(C('TMPL_ENGINE_CONFIG')) {
            $config     =  C('TMPL_ENGINE_CONFIG');
            foreach ($config as $key=>$val){
                $tpl->{$key}   =  $val;
            }
        }
        $tpl->assign($var);
        $tpl->display($templateFile);
    }
}