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
// $Id$

/**
 +------------------------------------------------------------------------------
 * EaseTemplate模板引擎解析类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
class TemplateEase
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
        $templateFile = substr($templateFile,strlen(TMPL_PATH),-5);
        $CacheDir = substr(CACHE_PATH,0,-1);
        $TemplateDir = substr(TMPL_PATH,0,-1);
        vendor('EaseTemplate.template#ease');
        if(C('TMPL_ENGINE_CONFIG')) {
            $config  =  C('TMPL_ENGINE_CONFIG');
        }else{
            $config  =                    array(
            'CacheDir'=>$CacheDir,
            'TemplateDir'=>$TemplateDir,
            'TplType'=>'html'
             );
        }
        $tpl = new EaseTemplate($config);
        $tpl->set_var($var);
        $tpl->set_file($templateFile);
        $tpl->p();
    }
}
?>