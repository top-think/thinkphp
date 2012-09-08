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

defined('THINK_PATH') or exit();
/**
 * 系统行为扩展：运行时间信息显示
 * @category   Think
 * @package  Think
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class ShowRuntimeBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'SHOW_RUN_TIME'		=> false,   // 运行时间显示
        'SHOW_ADV_TIME'		=> false,   // 显示详细的运行时间
        'SHOW_DB_TIMES'		=> false,   // 显示数据库查询和写入次数
        'SHOW_CACHE_TIMES'	=> false,   // 显示缓存操作次数
        'SHOW_USE_MEM'		=> false,   // 显示内存开销
        'SHOW_LOAD_FILE'    => false,   // 显示加载文件数
        'SHOW_FUN_TIMES'    => false ,  // 显示函数调用次数
    );

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        if(C('SHOW_RUN_TIME')){
            if(false !== strpos($content,'{__NORUNTIME__}')) {
                $content   =  str_replace('{__NORUNTIME__}','',$content);
            }else{
                $runtime = $this->showTime();
                 if(strpos($content,'{__RUNTIME__}'))
                     $content   =  str_replace('{__RUNTIME__}',$runtime,$content);
                 else
                     $content   .=  $runtime;
            }
        }else{
            $content   =  str_replace(array('{__NORUNTIME__}','{__RUNTIME__}'),'',$content);
        }
    }

    /**
     * 显示运行时间、数据库操作、缓存次数、内存使用信息
     * @access private
     * @return string
     */
    private function showTime() {
        // 显示运行时间
        G('beginTime',$GLOBALS['_beginTime']);
        G('viewEndTime');
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
            $showTime .= ' | UseMem:'. number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024).' kb';
        }
        if(C('SHOW_LOAD_FILE')) {
            $showTime .= ' | LoadFile:'.count(get_included_files());
        }
        if(C('SHOW_FUN_TIMES')) {
            $fun  =  get_defined_functions();
            $showTime .= ' | CallFun:'.count($fun['user']).','.count($fun['internal']);
        }
        return $showTime;
    }
}