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
 * ThinkPHP Widget类 抽象类
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author liu21st <liu21st@gmail.com>
 */
abstract class Widget {

    // 使用的模板引擎 每个Widget可以单独配置不受系统影响
    protected $template =  '';

    /**
     * 渲染输出 render方法是Widget唯一的接口
     * 使用字符串返回 不能有任何输出
     * @access public
     * @param mixed $data  要渲染的数据
     * @return string
     */
    abstract public function render($data);

    /**
     * 渲染模板输出 供render方法内部调用
     * @access public
     * @param string $templateFile  模板文件
     * @param mixed $var  模板变量
     * @return string
     */
    protected function renderFile($templateFile='',$var='') {
        ob_start();
        ob_implicit_flush(0);
        if(!file_exists_case($templateFile)){
            // 自动定位模板文件
            $name   = substr(get_class($this),0,-6);
            $filename   =  empty($templateFile)?$name:$templateFile;
            $templateFile = LIB_PATH.'Widget/'.$name.'/'.$filename.C('TMPL_TEMPLATE_SUFFIX');
            if(!file_exists_case($templateFile))
                throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
        }
        $template   =  strtolower($this->template?$this->template:(C('TMPL_ENGINE_TYPE')?C('TMPL_ENGINE_TYPE'):'php'));
        if('php' == $template) {
            // 使用PHP模板
            if(!empty($var)) extract($var, EXTR_OVERWRITE);
            // 直接载入PHP模板
            include $templateFile;
        }elseif('think'==$template){ // 采用Think模板引擎
            if($this->checkCache($templateFile)) { // 缓存有效
                // 分解变量并载入模板缓存
                extract($var, EXTR_OVERWRITE);
                //载入模版缓存文件
                include C('CACHE_PATH').md5($templateFile).C('TMPL_CACHFILE_SUFFIX');
            }else{
                $tpl = Think::instance('ThinkTemplate');
                // 编译并加载模板文件
                $tpl->fetch($templateFile,$var);
            }
        }else{
            $class   = 'Template'.ucwords($template);
            if(is_file(CORE_PATH.'Driver/Template/'.$class.'.class.php')) {
                // 内置驱动
                $path = CORE_PATH;
            }else{ // 扩展驱动
                $path = EXTEND_PATH;
            }
            require_cache($path.'Driver/Template/'.$class.'.class.php');
            $tpl   =  new $class;
            $tpl->fetch($templateFile,$var);
        }
        $content = ob_get_clean();
        return $content;
    }

    /**
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param string $tmplTemplateFile  模板文件名
     * @return boolen
     */
    protected function checkCache($tmplTemplateFile) {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        $tmplCacheFile = C('CACHE_PATH').md5($tmplTemplateFile).C('TMPL_CACHFILE_SUFFIX');
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != 0 && time() > filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        // 缓存有效
        return true;
    }
}