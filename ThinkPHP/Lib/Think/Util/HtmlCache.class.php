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
// $Id: HtmlCache.class.php 2701 2012-02-02 12:27:51Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 静态缓存类
 * 支持静态缓存规则定义
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id: HtmlCache.class.php 2701 2012-02-02 12:27:51Z liu21st $
 +------------------------------------------------------------------------------
 */
class HtmlCache extends Think
{
    static private $cacheTime = null; // 缓存有效期（支持函数）
    static private $requireCache = false;    // 是否需要缓存

    // 判断是否需要静态缓存
    static private function requireHtmlCache() {
        // 分析当前的静态规则
         $htmls = C('htmls'); // 读取静态规则
         if(!empty($htmls)) {
            // 静态规则文件定义格式 actionName=>array(‘静态规则’,’缓存时间’,’附加规则')
            // 'read'=>array('{id},{name}',60,'md5') 必须保证静态规则的唯一性 和 可判断性
            // 检测静态规则
            $moduleName = strtolower(MODULE_NAME);
            if(isset($htmls[$moduleName.':'.ACTION_NAME])) {
                $html   =   $htmls[$moduleName.':'.ACTION_NAME];   // 某个模块的操作的静态规则
            }elseif(isset($htmls[$moduleName.':'])){// 某个模块的静态规则
                $html   =   $htmls[$moduleName.':'];
            }elseif(isset($htmls[ACTION_NAME])){
                $html   =   $htmls[ACTION_NAME]; // 所有操作的静态规则
            }elseif(isset($htmls['*'])){
                $html   =   $htmls['*']; // 全局静态规则
            }elseif(isset($htmls['empty:index']) && !class_exists(MODULE_NAME.'Action')){
                $html   =    $htmls['empty:index']; // 空模块静态规则
            }elseif(isset($htmls[$moduleName.':_empty']) && self::isEmptyAction(MODULE_NAME,ACTION_NAME)){
                $html   =    $htmls[$moduleName.':_empty']; // 空操作静态规则
            }
            if(!empty($html)) {
                self::$requireCache = true; // 需要缓存
                // 解读静态规则
                $rule    = $html[0];
                // 以$_开头的系统变量
                $rule  = preg_replace('/{\$(_\w+)\.(\w+)\|(\w+)}/e',"\\3(\$\\1['\\2'])",$rule);
                $rule  = preg_replace('/{\$(_\w+)\.(\w+)}/e',"\$\\1['\\2']",$rule);
                // {ID|FUN} GET变量的简写
                $rule  = preg_replace('/{(\w+)\|(\w+)}/e',"\\2(\$_GET['\\1'])",$rule);
                $rule  = preg_replace('/{(\w+)}/e',"\$_GET['\\1']",$rule);
                // 特殊系统变量
                $rule  = str_ireplace(
                    array('{:app}','{:module}','{:action}','{:group}'),
                    array(APP_NAME,MODULE_NAME,ACTION_NAME,defined('GROUP_NAME')?GROUP_NAME:''),
                    $rule);
                // {|FUN} 单独使用函数
                $rule  = preg_replace('/{|(\w+)}/e',"\\1()",$rule);
                if(!empty($html[2])) $rule    =   $html[2]($rule); // 应用附加函数
                self::$cacheTime = isset($html[1])?$html[1]:C('HTML_CACHE_TIME'); // 缓存有效期
                // 当前缓存文件
                define('HTML_FILE_NAME',HTML_PATH . $rule.C('HTML_FILE_SUFFIX'));
                return true;
            }
        }
        // 无需缓存
        return false;
    }

    /**
     +----------------------------------------------------------
     * 读取静态缓存
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function readHTMLCache() {
        if(self::requireHtmlCache() && self::checkHTMLCache(HTML_FILE_NAME,self::$cacheTime)) { //静态页面有效
            if(C('HTML_READ_TYPE')==1) {
                // 重定向到静态页面
                redirect(str_replace(array(realpath($_SERVER["DOCUMENT_ROOT"]),"\\"),array('',"/"),realpath(HTML_FILE_NAME)));
            }else {
                // 读取静态页面输出
                readfile(HTML_FILE_NAME);
                exit();
            }
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 写入静态缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content 页面内容
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static public function writeHTMLCache($content) {
        if(self::$requireCache) {
            //静态文件写入
            // 如果开启HTML功能 检查并重写HTML文件
            // 没有模版的操作不生成静态文件
            if(!is_dir(dirname(HTML_FILE_NAME)))
                mk_dir(dirname(HTML_FILE_NAME));
            if( false === file_put_contents( HTML_FILE_NAME , $content ))
                throw_exception(L('_CACHE_WRITE_ERROR_').':'.HTML_FILE_NAME);
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 检查静态HTML文件是否有效
     * 如果无效需要重新更新
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $cacheFile  静态文件名
     * @param integer $cacheTime  缓存有效期
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    static public function checkHTMLCache($cacheFile='',$cacheTime='') {
        if(!is_file($cacheFile)){
            return false;
        }elseif (filemtime(C('TMPL_FILE_NAME')) > filemtime($cacheFile)) {
            // 模板文件如果更新静态文件需要更新
            return false;
        }elseif(!is_numeric($cacheTime) && function_exists($cacheTime)){
            return $cacheTime($cacheFile);
        }elseif ($cacheTime != -1 && time() > filemtime($cacheFile)+$cacheTime) {
            // 文件是否在有效期
            return false;
        }
        //静态文件有效
        return true;
    }

    //检测是否是空操作
    static private function isEmptyAction($module,$action) {
        $className =  $module.'Action';
        $class=new $className;
        return !method_exists($class,$action);
    }
}
?>