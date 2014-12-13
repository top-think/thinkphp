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
// $Id: TemplateLite.class.php 2207 2011-11-30 13:17:26Z liu21st $

/**
 +------------------------------------------------------------------------------
 * TemplateLite模板引擎解析类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id: TemplateLite.class.php 2207 2011-11-30 13:17:26Z liu21st $
 +------------------------------------------------------------------------------
 */
class TemplateLite
{
    /**
     +----------------------------------------------------------
     * 渲染模板输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名
     * @param array $var 模板变量
     * @param string $charset 模板输出字符集
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function fetch($templateFile,$var,$charset) {
        $templateFile=substr($templateFile,strlen(TMPL_PATH));
        vendor("TemplateLite.class#template");
        $tpl = new Template_Lite();
        if(C('TMPL_ENGINE_CONFIG')) {
            $config  =  C('TMPL_ENGINE_CONFIG');
            foreach ($config as $key=>$val){
                $tpl->{$key}   =  $val;
            }
        }else{
            $tpl->template_dir = TMPL_PATH;
            $tpl->compile_dir = CACHE_PATH ;
            $tpl->cache_dir = TEMP_PATH ;
        }
        $tpl->assign($var);
        $tpl->display($templateFile);
    }
}
?>