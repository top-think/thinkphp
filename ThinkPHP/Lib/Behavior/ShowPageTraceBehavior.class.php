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
// $Id: ShowPageTraceBehavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 页面Trace显示输出
 +------------------------------------------------------------------------------
 */
class ShowPageTraceBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'SHOW_PAGE_TRACE'        => false,   // 显示页面Trace信息
    );

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        if(C('SHOW_PAGE_TRACE')) {
            echo $this->showTrace();
        }
    }

    /**
     +----------------------------------------------------------
     * 显示页面Trace信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     */
    private function showTrace() {
         // 系统默认显示信息
        $log  =   Log::$log;
        $files =  get_included_files();
        $trace   =  array(
            '请求时间'=>  date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
            '当前页面'=>  __SELF__,
            '请求协议'=>  $_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'],
            '运行信息'=>  $this->showTime(),
            '会话ID'    =>  session_id(),
            '日志记录'=>  count($log)?count($log).'条日志<br/>'.implode('<br/>',$log):'无日志记录',
            '加载文件'=>  count($files).str_replace("\n",'<br/>',substr(substr(print_r($files,true),7),0,-2)),
            );

        // 读取项目定义的Trace文件
        $traceFile  =   CONF_PATH.'trace.php';
        if(is_file($traceFile)) {
            // 定义格式 return array('当前页面'=>$_SERVER['PHP_SELF'],'通信协议'=>$_SERVER['SERVER_PROTOCOL'],...);
            $trace   =  array_merge(include $traceFile,$trace);
        }
        // 设置trace信息
        trace($trace);
        // 调用Trace页面模板
        ob_start();
        include C('TMPL_TRACE_FILE')?C('TMPL_TRACE_FILE'):THINK_PATH.'Tpl/page_trace.tpl';
        return ob_get_clean();
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
        G('beginTime',$GLOBALS['_beginTime']);
        G('viewEndTime');
        $showTime   =   'Process: '.G('beginTime','viewEndTime').'s ';
        // 显示详细运行时间
        $showTime .= '( Load:'.G('beginTime','loadTime').'s Init:'.G('loadTime','initTime').'s Exec:'.G('initTime','viewStartTime').'s Template:'.G('viewStartTime','viewEndTime').'s )';
        // 显示数据库操作次数
        if(class_exists('Db',false) ) {
            $showTime .= ' | DB :'.N('db_query').' queries '.N('db_write').' writes ';
        }
        // 显示缓存读写次数
        if( class_exists('Cache',false)) {
            $showTime .= ' | Cache :'.N('cache_read').' gets '.N('cache_write').' writes ';
        }
        // 显示内存开销
        if(MEMORY_LIMIT_ON ) {
            $showTime .= ' | UseMem:'. number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024).' kb';
        }
        // 显示文件加载数
        $showTime .= ' | LoadFile:'.count(get_included_files());
        // 显示函数调用次数 自定义函数,内置函数
        $fun  =  get_defined_functions();
        $showTime .= ' | CallFun:'.count($fun['user']).','.count($fun['internal']);
        return $showTime;
    }
}