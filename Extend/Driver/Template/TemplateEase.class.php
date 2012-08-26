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
 * EaseTemplate模板引擎驱动类
 */
class TemplateEase {
    /**
     * 渲染模板输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param array $var 模板变量
     * @return void
     */
    public function fetch($templateFile,$var) {
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