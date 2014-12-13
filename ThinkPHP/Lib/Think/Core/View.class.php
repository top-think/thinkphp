<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
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
 * ThinkPHP 视图输出
 * 支持缓存和页面压缩
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
class View extends Think
{
    protected $tVar        =  array(); // 模板输出变量
    protected $trace       = array();  // 页面trace变量
    protected $templateFile  = '';      // 模板文件名

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    public function assign($name,$value=''){
        if(is_array($name)) {
            $this->tVar   =  array_merge($this->tVar,$name);
        }elseif(is_object($name)){
            foreach($name as $key =>$val)
                $this->tVar[$key] = $val;
        }else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     +----------------------------------------------------------
     * Trace变量赋值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    public function trace($title,$value='') {
        if(is_array($title))
            $this->trace   =  array_merge($this->trace,$title);
        else
            $this->trace[$title] = $value;
    }

    /**
     +----------------------------------------------------------
     * 取得模板变量的值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name){
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    /* 取得所有模板变量 */
    public function getAllVar(){
        return $this->tVar;
    }

    // 调试页面所有的模板变量
    public function traceVar(){
        foreach ($this->tVar as $name=>$val){
            dump($val,1,'['.$name.']<br/>');
        }
    }

    /**
     +----------------------------------------------------------
     * 加载模板和页面输出 可以返回输出内容
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名 留空为自动获取
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function display($templateFile='',$charset='',$contentType='')
    {
        $this->fetch($templateFile,$charset,$contentType,true);
    }

    /**
     +----------------------------------------------------------
     * 输出布局模板
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $display 是否直接显示
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    protected function layout($content,$charset='',$contentType='')
    {
        if(false !== strpos($content,'<!-- layout')) {
            // 查找布局包含的页面
            $find = preg_match_all('/<!-- layout::(.+?)::(.+?) -->/is',$content,$matches);
            if($find) {
                for ($i=0; $i< $find; $i++) {
                    // 读取相关的页面模板替换布局单元
                    if(0===strpos($matches[1][$i],'$'))
                        // 动态布局
                        $matches[1][$i]  =  $this->get(substr($matches[1][$i],1));
                    if(0 != $matches[2][$i] ) {
                        // 设置了布局缓存
                        // 检查布局缓存是否有效
                        $guid =  md5($matches[1][$i]);
                        $cache  =  S($guid);
                        if($cache) {
                            $layoutContent = $cache;
                        }else{
                            $layoutContent = $this->fetch($matches[1][$i],$charset,$contentType);
                            S($guid,$layoutContent,$matches[2][$i]);
                        }
                    }else{
                        $layoutContent = $this->fetch($matches[1][$i],$charset,$contentType);
                    }
                    $content    =   str_replace($matches[0][$i],$layoutContent,$content);
                }
            }
        }
        return $content;
    }

    /**
     +----------------------------------------------------------
     * 加载模板和页面输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名 留空为自动获取
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $display 是否直接显示
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function fetch($templateFile='',$charset='',$contentType='',$display=false)
    {
        G('_viewStartTime');
        // 使用null参数作为模版名直接返回不做任何输出
        if(null===$templateFile) return;
        // 网页字符编码
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        if(empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        header("Content-Type:".$contentType."; charset=".$charset);
        header("Cache-control: private");  //支持页面回跳
        header("X-Powered-By:ThinkPHP".THINK_VERSION);
        //页面缓存
        ob_start();
        ob_implicit_flush(0);
        // 自动定位模板文件
        if(!file_exists_case($templateFile))
            $templateFile   = $this->parseTemplateFile($templateFile);
        $engine  = strtolower(C('TMPL_ENGINE_TYPE'));
        if('php'==$engine) {
            // 模板阵列变量分解成为独立变量
            extract($this->tVar, EXTR_OVERWRITE);
            // 直接载入PHP模板
            include $templateFile;
        }elseif('think'==$engine && $this->checkCache($templateFile)) {
            // 如果是Think模板引擎并且缓存有效 分解变量并载入模板缓存
            extract($this->tVar, EXTR_OVERWRITE);
            //载入模版缓存文件
            include C('CACHE_PATH').md5($templateFile).C('TMPL_CACHFILE_SUFFIX');
        }else{
            // 模板文件需要重新编译 支持第三方模板引擎
            // 调用模板引擎解析和输出
            $className   = 'Template'.ucwords($engine);
            require_cache(THINK_PATH.'/Lib/Think/Util/Template/'.$className.'.class.php');
            $tpl   =  new $className;
            $tpl->fetch($templateFile,$this->tVar,$charset);
        }
        $this->templateFile   =  $templateFile;
        // 获取并清空缓存
        $content = ob_get_clean();
        // 模板内容替换
        $content = $this->templateContentReplace($content);
        // 布局模板解析
        $content = $this->layout($content,$charset,$contentType);
        // 输出模板文件
        return $this->output($content,$display);
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
    protected function checkCache($tmplTemplateFile)
    {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        $tmplCacheFile = C('CACHE_PATH').md5($tmplTemplateFile).C('TMPL_CACHFILE_SUFFIX');
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != -1 && time() > filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        //缓存有效
        return true;
    }

    /**
     +----------------------------------------------------------
     *  创建静态页面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function buildHtml($htmlfile,$htmlpath='',$templateFile='',$charset='',$contentType='') {
        $content = $this->fetch($templateFile,$charset,$contentType);
        $htmlpath   = !empty($htmlpath)?$htmlpath:HTML_PATH;
        $htmlfile =  $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
        if(!is_dir(dirname($htmlfile)))
            // 如果静态目录不存在 则创建
            mk_dir(dirname($htmlfile));
        if(false === file_put_contents($htmlfile,$content))
            throw_exception(L('_CACHE_WRITE_ERROR_').':'.$htmlfile);
        return $content;
    }

    /**
     +----------------------------------------------------------
     * 输出模板
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $content 模板内容
     * @param boolean $display 是否直接显示
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    protected function output($content,$display) {
        if(C('HTML_CACHE_ON'))  HtmlCache::writeHTMLCache($content);
        if($display) {
            if(false !== strpos($content,'{__RUNTIME__}'))
            {
                $runtime = C('SHOW_RUN_TIME')? '<div  id="think_run_time" class="think_run_time">'.$this->showTime().'</div>' : '';
                $content = str_replace('{__RUNTIME__}', $runtime, $content);
            }
            echo $content;
            if(C('SHOW_PAGE_TRACE'))   $this->showTrace();
            return null;
        }else {
            return $content;
        }
    }

    /**
     +----------------------------------------------------------
     * 模板内容替换
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $content 模板内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function templateContentReplace($content) {
        // 系统默认的特殊变量替换
        $replace =  array(
            '../Public'     => APP_PUBLIC_PATH,// 项目公共目录
            '__PUBLIC__'    => WEB_PUBLIC_PATH,// 站点公共目录
            '__TMPL__'      => APP_TMPL_PATH,  // 项目模板目录
            '__ROOT__'      => __ROOT__,       // 当前网站地址
            '__APP__'       => __APP__,        // 当前项目地址
            '__UPLOAD__'    => __ROOT__.'/Uploads',
            '__ACTION__'    => __ACTION__,     // 当前操作地址
            '__SELF__'      => __SELF__,       // 当前页面地址
            '__URL__'       => __URL__,
            '__INFO__'      => __INFO__,
        );
        if(defined('GROUP_NAME'))
        {
            $replace['__GROUP__'] = __GROUP__;// 当前项目地址
        }
        if(C('TOKEN_ON')) {
            if(strpos($content,'{__TOKEN__}')) {
                // 指定表单令牌隐藏域位置
                $replace['{__TOKEN__}'] =  $this->buildFormToken();
            }elseif(strpos($content,'{__NOTOKEN__}')){
                // 标记为不需要令牌验证
                $replace['{__NOTOKEN__}'] =  '';
            }elseif(preg_match('/<\/form(\s*)>/is',$content,$match)) {
                // 智能生成表单令牌隐藏域
                $replace[$match[0]] = $this->buildFormToken().$match[0];
            }
        }
        // 允许用户自定义模板的字符串替换
        if(is_array(C('TMPL_PARSE_STRING')) )
            $replace =  array_merge($replace,C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace),array_values($replace),$content);
        return $content;
    }

    /**
     +----------------------------------------------------------
     * 创建表单令牌隐藏域
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function buildFormToken() {
        // 开启表单验证自动生成表单令牌
        $tokenName   = C('TOKEN_NAME');
        $tokenType = C('TOKEN_TYPE');
        $tokenValue = $tokenType(microtime(TRUE));
        $token   =  '<input type="hidden" name="'.$tokenName.'" value="'.$tokenValue.'" />';
        $_SESSION[$tokenName]  =  $tokenValue;
        return $token;
    }

    /**
     +----------------------------------------------------------
     * 自动定位模板文件
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param string $templateFile 文件名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    private function parseTemplateFile($templateFile) {
        if(''==$templateFile) {
            // 如果模板文件名为空 按照默认规则定位
            $templateFile = C('TMPL_FILE_NAME');
        }elseif(false === strpos($templateFile,'.')){
            $templateFile  = str_replace(array('@',':'),'/',$templateFile);
            $count   =  substr_count($templateFile,'/');
            $path   = dirname(C('TMPL_FILE_NAME'));
            for($i=0;$i<$count;$i++)
                $path   = dirname($path);
            $templateFile =  $path.'/'.$templateFile.C('TMPL_TEMPLATE_SUFFIX');
        }
        if(!file_exists_case($templateFile))
            throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
        return $templateFile;
    }

    /**
     +----------------------------------------------------------
     * 显示运行时间、数据库操作、缓存次数、内存使用信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function showTime() {
        // 显示运行时间
        G('viewStartTime');
        $showTime   =   'Process: '.G('beginTime','viewEndTime').'s ';
        if(C('SHOW_ADV_TIME')) {
            // 显示详细运行时间
            $showTime .= '( Load:'.G('beginTime','loadTime').'s Init:'.G('loadTime','initTime').'s Exec:'.G('initTime','viewStartTime').'s Template:'.G('viewStartTime','viewEndTime').'s )';
        }
        if(C('SHOW_DB_TIMES') && class_exists('Db',false) ) {
            // 显示数据库操作次数
            $showTime .= ' | DB :'.N('db_query').' queries '.N('db_write').' writes ';
        }
        if(C('SHOW_CACHE_TIMES') && class_exists('Cache',false)) {
            // 显示缓存读写次数
            $showTime .= ' | Cache :'.N('cache_read').' gets '.N('cache_write').' writes ';
        }
        if(MEMORY_LIMIT_ON && C('SHOW_USE_MEM')) {
            // 显示内存开销
            $startMem    =  array_sum(explode(' ', $GLOBALS['_startUseMems']));
            $endMem     =  array_sum(explode(' ', memory_get_usage()));
            $showTime .= ' | UseMem:'. number_format(($endMem - $startMem)/1024).' kb';
        }
        return $showTime;
    }

    /**
     +----------------------------------------------------------
     * 显示页面Trace信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     */
    private function showTrace(){
        // 显示页面Trace信息 读取Trace定义文件
        // 定义格式 return array('当前页面'=>$_SERVER['PHP_SELF'],'通信协议'=>$_SERVER['SERVER_PROTOCOL'],...);
        $traceFile  =   CONFIG_PATH.'trace.php';
        $_trace =   is_file($traceFile)? include $traceFile : array();
         // 系统默认显示信息
        $this->trace('当前页面',    $_SERVER['REQUEST_URI']);
        $this->trace('模板缓存',    C('CACHE_PATH').md5($this->templateFile).C('TMPL_CACHFILE_SUFFIX'));
        $this->trace('请求方法',    $_SERVER['REQUEST_METHOD']);
        $this->trace('通信协议',    $_SERVER['SERVER_PROTOCOL']);
        $this->trace('请求时间',    date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']));
        $this->trace('用户代理',    $_SERVER['HTTP_USER_AGENT']);
        $this->trace('会话ID'   ,   session_id());
        $log    =   Log::$log;
        $this->trace('日志记录',count($log)?count($log).'条日志<br/>'.implode('<br/>',$log):'无日志记录');
        $files =  get_included_files();
        $this->trace('加载文件',    count($files).str_replace("\n",'<br/>',substr(substr(print_r($files,true),7),0,-2)));
        $_trace =   array_merge($_trace,$this->trace);
        // 调用Trace页面模板
        include C('TMPL_TRACE_FILE');
    }

}//
?>