<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<weibo.com/luofei614>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * MobileTemplate模板引擎驱动 
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Template
 * @author    luofei614 <weibo.com/luofei614>
 */
class TemplateMobile {
    /**
     * 渲染模板输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param array $var 模板变量
     * @return void
     */
    public function fetch($templateFile,$var) {
		$templateFile=substr($templateFile,strlen(THEME_PATH));
        if($tpm_theme=C('TPM_THEME')){
            $tpm_theme.='/';
        }else{
            $tpm_theme=''; 
        }
		$var['_think_template_path']=$tpm_theme.$templateFile;
		exit(json_encode($var));	
    }
}
