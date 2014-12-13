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
// $Id: ParseTemplateBehavior.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 模板解析
 +------------------------------------------------------------------------------
 */

class ParseTemplateBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
        // 布局设置
        'TMPL_ENGINE_TYPE'		=> 'Think',     // 默认模板引擎 以下设置仅对使用Think模板引擎有效
        'TMPL_CACHFILE_SUFFIX'  => '.php',      // 默认模板缓存后缀
        'TMPL_DENY_FUNC_LIST'	=> 'echo,exit',	// 模板引擎禁用函数
        'TMPL_DENY_PHP'  =>false, // 默认模板引擎是否禁用PHP原生代码
        'TMPL_L_DELIM'          => '{',			// 模板引擎普通标签开始标记
        'TMPL_R_DELIM'          => '}',			// 模板引擎普通标签结束标记
        'TMPL_VAR_IDENTIFY'     => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象
        'TMPL_STRIP_SPACE'      => true,       // 是否去除模板文件里面的html空格与换行
        'TMPL_CACHE_ON'			=> true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'TMPL_CACHE_TIME'		=>	 0,         // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
        'TMPL_LAYOUT_ITEM'    =>   '{__CONTENT__}', // 布局模板的内容替换标识
        'LAYOUT_ON'           => false, // 是否启用布局
        'LAYOUT_NAME'       => 'layout', // 当前布局名称 默认为layout

        // Think模板引擎标签库相关设定
        'TAGLIB_BEGIN'          => '<',  // 标签库标签开始标记
        'TAGLIB_END'            => '>',  // 标签库标签结束标记
        'TAGLIB_LOAD'           => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
        'TAGLIB_BUILD_IN'       => 'cx', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'TAGLIB_PRE_LOAD'       => '',   // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
        );

    // 行为扩展的执行入口必须是run
    public function run(&$_data){
        $engine  = strtolower(C('TMPL_ENGINE_TYPE'));
        if('think'==$engine){ //[sae] 采用Think模板引擎
            if($this->checkCache($_data['file'])) { // 缓存有效
                SaeMC::include_file(md5($_data['file']).C('TMPL_CACHFILE_SUFFIX'),$_data['var']);
            }else{
                $tpl = Think::instance('ThinkTemplate');
                // 编译并加载模板文件
                $tpl->fetch($_data['file'],$_data['var']);
            }
        }else{
            // 调用第三方模板引擎解析和输出
            $class   = 'Template'.ucwords($engine);
            if(is_file(CORE_PATH.'Driver/Template/'.$class.'.class.php')) {
                // 内置驱动
                $path = CORE_PATH;
            }else{ // 扩展驱动
                $path = EXTEND_PATH;
            }
            if(require_cache($path.'Driver/Template/'.$class.'.class.php')) {
                $tpl   =  new $class;
                $tpl->fetch($_data['file'],$_data['var']);
            }else {  // 类没有定义
                throw_exception(L('_NOT_SUPPERT_').': ' . $class);
            }
        }
        //[sae] 添加trace信息。
        trace(array(
            '[SAE]核心缓存'=>$_SERVER['HTTP_APPVERSION'].'/'.RUNTIME_FILE,
            '[SAE]模板缓存'=>$_SERVER['HTTP_APPVERSION'].'/'.md5($_data['file']).C('TMPL_CACHFILE_SUFFIX')
        ));
    }

    /**
     +----------------------------------------------------------
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplTemplateFile  模板文件名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    //[sae] 检查模版
    protected function checkCache($tmplTemplateFile) {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        //[sae] 不加模版目录，简化模版名称
        $tmplCacheFile = md5($tmplTemplateFile).C('TMPL_CACHFILE_SUFFIX');
        if(!SaeMC::file_exists($tmplCacheFile)){
            return false;
        //}elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
        }elseif (filemtime($tmplTemplateFile) > SaeMC::filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != 0 && time() > SaeMC::filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        // 开启布局模板
        if(C('LAYOUT_ON')) {
            $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').C('TMPL_TEMPLATE_SUFFIX');
            if(filemtime($layoutFile) > SaeMC::filemtime($tmplCacheFile)) {
                return false;
            }
        }
        // 缓存有效
        return true;
    }
}