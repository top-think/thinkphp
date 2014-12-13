<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 模板引擎Lite版本
 +------------------------------------------------------------------------------
 */
class ThinkTemplateLite {
    // 属性定义
    protected $var = array();//模板变量
    protected $config =  array();// 模板配置
    // 架构方法
    public function __construct(){
        $this->config['cache_path']        =  CACHE_PATH;//C('CACHE_PATH');
        $this->config['template_suffix']   =  C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix']       =  C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache']        =  C('TMPL_CACHE_ON');
        $this->config['cache_time']        =  C('TMPL_CACHE_TIME');
        $this->config['taglib_begin']        =  $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end']          =  $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin']         =  $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end']           =  $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl']       =  C('TMPL_FILE_NAME');
        $this->config['tag_level']            =  C('TAG_NESTED_LEVEL');
    }
    // 正则替换的转义 方便定制
    private function stripPreg($str) {
        $str = str_replace(array('{','}','(',')','|','[',']'),array('\{','\}','\(','\)','\|','\[','\]'),$str);
        return $str;
    }
    // 模板配置赋值
    public function __set($name,$value='') {
        if(is_array($name)) {
            $this->config   =  array_merge($this->config,$name);
        }else{
            $this->config[$name]= $value;
        }
    }
    // 模板配置取值
    public function __get($name) {
        if(isset($this->config[$name]))
            return $this->config[$name];
        else
            return null;
    }
    // 模板变量赋值
    public function assign($name,$value)
    {
        if(is_array($name)) {
            $this->var   =  array_merge($this->var,$name);
        }else{
            $this->var[$name]= $value;
        }
    }
    // 模板变量取值
    public function get($name) {
        if(isset($this->var[$name]))
            return $this->var[$name];
        else
            return false;
    }
    // 载入模板 模板引擎入口
    public function fetch($templateFile,$templateVar='')
    {
        if(!empty($templateVar))   $this->assign($templateVar);
        //根据模版文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'].md5($templateFile).$this->config['cache_suffix'];
        if (!$this->checkCache($templateFile,$tmplCacheFile)) // 判断缓存是否有效
            $this->loadTemplate($templateFile,$tmplCacheFile);
        // 模板阵列变量分解成为独立变量
        extract($this->var, EXTR_OVERWRITE);
        //载入模版缓存文件
        include $tmplCacheFile;
    }
    // 读取并编译模板
    protected function loadTemplate($templateFile,$tmplCacheFile) {
        // 需要更新模版 读出原模板内容
        $tmplContent = file_get_contents($templateFile);
        //编译模板内容
        $tmplContent = $this->compiler($tmplContent,$templateFile);
        // 检测缓存目录
        if(!is_dir($this->config['cache_path']))
            mk_dir($this->config['cache_path']);
        //重写Cache文件
        if( false === file_put_contents($tmplCacheFile,trim($tmplContent)))
            throw_exception(L('_CACHE_WRITE_ERROR_').':'.$tmplCacheFile);
    }
    // 模板编译
    protected function compiler($tmplContent,$templateFile) {
        $compiler = new ThinkTemplateCompiler();
        return $compiler->parse($tmplContent,$templateFile);
    }
    // 检查缓存
    protected function checkCache($tmplTemplateFile,$tmplCacheFile) {
        if (!$this->config['tmpl_cache']) // 优先对配置检测
            return false;
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif ($this->config['cache_time'] != -1 && time() > filemtime($tmplCacheFile)+$this->config['cache_time']) {
            // 缓存是否在有效期
            return false;
        }
        //缓存有效
        return true;
    }
}
?>