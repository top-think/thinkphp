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
 * ThinkPHP 精简模式Action控制器基类
 +------------------------------------------------------------------------------
 */
abstract class Action extends Think
{//类定义开始

    protected $tVar        =  array(); // 模板输出变量
    protected $trace       = array();  // 页面trace变量
   /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct()
    {
        //控制器初始化
        if(method_exists($this,'_initialize')) {
            $this->_initialize();
        }
    }
    public function trace($title,$value='') {
        if(is_array($title))
            $this->trace   =  array_merge($this->trace,$title);
        else
            $this->trace[$title] = $value;
    }
    /**
     +----------------------------------------------------------
     * 魔术方法 有不存在的操作的时候执行
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $method 方法名
     * @param array $parms 参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($method,$parms) {
        if(strtolower($method) == strtolower(ACTION_NAME)) {
            // 如果定义了_empty操作 则调用
            if(method_exists($this,'_empty')) {
                $this->_empty($method,$parms);
            }else {
                // 检查是否存在默认模版 如果有直接输出模版
                if(file_exists_case(TMPL_PATH.MODULE_NAME.'/'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX')))
                    $this->display();
                else
                    // 抛出异常
                    throw_exception(L('_ERROR_ACTION_').ACTION_NAME);
            }
        }elseif(in_array(strtolower($method),array('ispost','isget','ishead','isdelete','isput'))){
            return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method,2));
        }else{
            throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
        }
    }

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    protected function assign($name,$value=''){
        if(is_array($name)) {
            $this->tVar   =  array_merge($this->tVar,$name);
        }elseif(is_object($name)){
            foreach($name as $key =>$val)
                $this->tVar[$key] = $val;
        }else {
            $this->tVar[$name] = $value;
        }
    }
    public function get($name){
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }
    /**
     +----------------------------------------------------------
     * 是否AJAX请求
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @return bool
     +----------------------------------------------------------
     */
    protected function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
            // 判断Ajax方式提交
            return true;
        return false;
    }

    /**
     +----------------------------------------------------------
     * 操作错误跳转的快捷方法
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $message 错误信息
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function error($message,$ajax=false)
    {
        $this->_dispatch_jump($message,0,$ajax);
    }

    /**
     +----------------------------------------------------------
     * 操作成功跳转的快捷方法
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $message 提示信息
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function success($message,$ajax=false)
    {
        $this->_dispatch_jump($message,1,$ajax);
    }

    /**
     +----------------------------------------------------------
     * Ajax方式返回数据到客户端
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $data 要返回的数据
     * @param String $info 提示信息
     * @param boolean $status 返回状态
     * @param String $status ajax返回类型 JSON XML
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function ajaxReturn($data,$info='',$status=1,$type='')
    {
        // 保证AJAX返回后也能保存日志
        if(C('LOG_RECORD')) Log::save();
        $result  =  array();
        $result['status']  =  $status;
        $result['info'] =  $info;
        $result['data'] = $data;
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        if(strtoupper($type)=='JSON') {
            // 返回JSON数据格式到客户端 包含状态信息
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        }elseif(strtoupper($type)=='XML'){
            // 返回xml格式数据
            header("Content-Type:text/xml; charset=utf-8");
            exit(xml_encode($result));
        }elseif(strtoupper($type)=='EVAL'){
            // 返回可执行的js脚本
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }else{
            // TODO 增加其它格式
        }
    }

    /**
     +----------------------------------------------------------
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
        if(C('LOG_RECORD')) Log::save();
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }

    /**
     +----------------------------------------------------------
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     +----------------------------------------------------------
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function _dispatch_jump($message,$status=1,$ajax=false)
    {
        // 判断是否为AJAX返回
        if($ajax || $this->isAjax()) $this->ajaxReturn('',$message,$status);
        // 提示标题
        $this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if($this->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
        $this->assign('status',$status);   // 状态
        $this->assign('message',$message);// 提示信息
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON',false);
        if($status) { //发送成功信息
            // 成功操作后默认停留1秒
            if(!$this->get('waitSecond'))    $this->assign('waitSecond',"1");
            // 默认操作成功自动返回操作前页面
            if(!$this->get('jumpUrl')) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        }else{
            //发生错误时候默认停留3秒
            if(!$this->get('waitSecond'))    $this->assign('waitSecond',"3");
            // 默认发生错误的话自动返回上页
            if(!$this->get('jumpUrl')) $this->assign('jumpUrl',"javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
        }
        if(C('LOG_RECORD')) Log::save();
        // 中止执行  避免出错后继续执行
        exit ;
    }

    /**
     +----------------------------------------------------------
     * 模板显示
     * 只支持PHP模板
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function display($templateFile='',$charset='',$contentType='text/html')
    {
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        // 网页字符编码
        header("Content-Type:".$contentType."; charset=".$charset);
        header("Cache-control: private");  //支持页面回跳
        //页面缓存
        ob_start();
        ob_implicit_flush(0);
        // 自动定位模板文件
        $templateFile   = $this->parseTemplateFile($templateFile);
        if('php'==strtolower(C('TMPL_ENGINE_TYPE'))) {
            // 模板阵列变量分解成为独立变量
            extract($this->tVar, EXTR_OVERWRITE);
            // 直接载入PHP模板
            include $templateFile;
        }else{
            // 模板文件需要重新编译 支持第三方模板引擎
            // 调用模板引擎解析和输出
            $tpl   =  new ThinkTemplateLite;
            $tpl->fetch($templateFile,$this->tVar);
        }
        // 获取并清空缓存
        $content = ob_get_clean();
        // 模板内容替换
        $content = $this->templateContentReplace($content);
        if(C('SHOW_RUN_TIME')){
            $runtime = '<div  id="think_run_time" class="think_run_time">'.$this->showTime().'</div>';
             if(strpos($content,'{__RUNTIME__}'))
                 $content   =  str_replace('{__RUNTIME__}',$runtime,$content);
             else
                 $content   .=  $runtime;
        }
        echo $content;
        if(C('SHOW_PAGE_TRACE'))   $this->showTrace();
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
        // Lite模式没有模板主题的概念
        if(''==$templateFile) {
            // 如果模板文件名为空 按照默认规则定位
            $templateFile = TMPL_PATH.MODULE_NAME.'/'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
        }elseif(strpos($templateFile,':')){
            // 引入其它模块的操作模板
            $templateFile   =   TMPL_PATH.str_replace(':','/',$templateFile).C('TMPL_TEMPLATE_SUFFIX');
        }elseif(!is_file($templateFile))    {
            // 引入当前模块的其它操作模板
            $templateFile =  TMPL_PATH.MODULE_NAME.'/'.$templateFile.C('TMPL_TEMPLATE_SUFFIX');
        }
        if(!file_exists_case($templateFile))
            throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
        return $templateFile;
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
            '../Public'   =>  __ROOT__.'/'.APP_NAME.'/'.TMPL_DIR.'/Public',// 项目公共目录
            '__PUBLIC__'  =>  __ROOT__.'/Public',// 站点公共目录
            '__ROOT__'    => __ROOT__,       // 当前网站地址
            '__APP__'     => PHP_FILE,        // 当前项目地址
            '__URL__'     => PHP_FILE.'/'.MODULE_NAME,        // 当前模块地址
            '__ACTION__'  => PHP_FILE.'/'.MODULE_NAME.'/'.ACTION_NAME,     // 当前操作地址
            '__SELF__'    => $_SERVER['PHP_SELF'],       // 当前页面地址
        );
        // 允许用户自定义模板的字符串替换
        if(is_array(C('TMPL_PARSE_STRING')) )
            $replace =  array_merge($replace,C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace),array_values($replace),$content);
        return $content;
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
        $this->trace('请求方法',    $_SERVER['REQUEST_METHOD']);
        $this->trace('通信协议',    $_SERVER['SERVER_PROTOCOL']);
        $this->trace('请求时间',    date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']));
        $this->trace('用户代理',    $_SERVER['HTTP_USER_AGENT']);
        $log    =   Log::$log;
        $this->trace('日志记录',count($log)?count($log).'条日志<br/>'.implode('<br/>',$log):'无日志记录');
        $files =  get_included_files();
        $this->trace('加载文件',    count($files).str_replace("\n",'<br/>',substr(substr(print_r($files,true),7),0,-2)));
        $_trace =   array_merge($_trace,$this->trace);
        // 调用Trace页面模板
        include C('TMPL_TRACE_FILE');
    }
}//类定义结束
?>