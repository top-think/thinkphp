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
 * 系统行为扩展 REST路由检测
 */
class CheckRestRouteBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
        'URL_ROUTER_ON'         => false,   // 是否开启URL路由
        'URL_ROUTE_RULES'       => array(), // 默认路由规则，注：分组配置无法替代
        );

    /**
     * 路由检测
     * @access public
     * @return void
     */
    public function run(&$return) {
        $regx = trim($_SERVER['PATH_INFO'],'/');
        // 是否开启路由使用
        if(empty($regx) || !C('URL_ROUTER_ON')) $return =  false;
        // 路由定义文件优先于config中的配置定义
        $routes = C('URL_ROUTE_RULES');
        if(is_array(C('routes')))  $routes = C('routes');
        // 路由处理
        if(!empty($routes)) {
            $depr = C('URL_PATHINFO_DEPR');
            foreach ($routes as $key=>$route){
                // 定义格式： array('路由规则或者正则','路由地址','路由参数','提交类型','资源类型')
                if(isset($route[3]) && strtolower($_SERVER['REQUEST_METHOD']) != strtolower($route[3])) {
                    continue; // 如果设置了提交类型则过滤
                }
                if(isset($route[4]) && !in_array(__EXT__,explode(',',$route[4]),true)) {
                    continue; // 如果设置了扩展名则过滤
                }
                if(0===strpos($route[0],'/') && preg_match($route[0],$regx,$matches)) { // 正则路由
                    return self::parseRegex($matches,$route,$regx);
                }else{ // 规则路由
                    $len1=   substr_count($regx,'/');
                    $len2 =  substr_count($route[0],'/');
                    if($len1>=$len2) {
                        if('$' == substr($route[0],-1,1)) {// 完整匹配
                            if($len1 != $len2) {
                                continue;
                            }else{
                                $route[0] =  substr($route[0],0,-1);
                            }
                        }
                        $match  =  self::checkUrlMatch($regx,$route[0]);
                        if($match)  return $return = self::parseRule($route,$regx);
                    }
                }
            }
        }
        $return  =  false;
    }

    // 检测URL和规则路由是否匹配
    static private function checkUrlMatch($regx,$rule) {
        $m1 = explode('/',$regx);
        $m2 = explode('/',$rule);
        $match = true; // 是否匹配
        foreach ($m2 as $key=>$val){
            if(':' == substr($val,0,1)) {// 动态变量
                if(strpos($val,'\\')) {
                    $type = substr($val,-1);
                    if('d'==$type && !is_numeric($m1[$key])) {
                        $match = false;
                        break;
                    }
                }elseif(strpos($val,'^')){
                    $array   =  explode('|',substr(strstr($val,'^'),1));
                    if(in_array($m1[$key],$array)) {
                        $match = false;
                        break;
                    }
                }
            }elseif(0 !== strcasecmp($val,$m1[$key])){
                $match = false;
                break;
            }
        }
        return $match;
    }

    static private function parseUrl($url) {
        $var  =  array();
        if(false !== strpos($url,'?')) { // [分组/模块/操作?]参数1=值1&参数2=值2...
            $info   =  parse_url($url);
            $path = explode('/',$info['path']);
            parse_str($info['query'],$var);
        }elseif(strpos($url,'/')){ // [分组/模块/操作]
            $path = explode('/',$url);
        }else{ // 参数1=值1&参数2=值2...
            parse_str($url,$var);
        }
        if(isset($path)) {
            $var[C('VAR_ACTION')] = array_pop($path);
            if(!empty($path)) {
                $var[C('VAR_MODULE')] = array_pop($path);
            }
            if(!empty($path)) {
                $var[C('VAR_GROUP')]  = array_pop($path);
            }
        }
        return $var;
    }

    // 解析规则路由
    // array('路由规则','[分组/模块/操作]','额外参数1=值1&额外参数2=值2...','请求类型','资源类型')
    // array('路由规则','外部地址','重定向代码','请求类型','资源类型')
    // 路由规则中 :开头 表示动态变量
    // 外部地址中可以用动态变量 采用 :1 :2 的方式
    // array('news/:month/:day/:id','News/read?cate=1','status=1','post','html,xml'), 
    // array('new/:id','/new.php?id=:1',301,'get','xml'), 重定向
    static private function parseRule($route,$regx) {
        // 获取路由地址规则
        $url   =  $route[1];
        // 获取URL地址中的参数
        $paths = explode('/',$regx);
        // 解析路由规则
        $matches  =  array();
        $rule =  explode('/',$route[0]);
        foreach ($rule as $item){
            if(0===strpos($item,':')) { // 动态变量获取
                if($pos = strpos($item,'^') ) {
                    $var  =  substr($item,1,$pos-1);
                }elseif(strpos($item,'\\')){
                    $var  =  substr($item,1,-2);
                }else{
                    $var  =  substr($item,1);
                }
                $matches[$var] = array_shift($paths);
            }else{ // 过滤URL中的静态变量
                array_shift($paths);
            }
        }
        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            if(strpos($url,':')) { // 传递动态参数
                $values  =  array_values($matches);
                $url  =  preg_replace('/:(\d)/e','$values[\\1-1]',$url);
            }
            header("Location: $url", true,isset($route[2])?$route[2]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  self::parseUrl($url);
            // 解析路由地址里面的动态参数
            $values  =  array_values($matches);
            foreach ($var as $key=>$val){
                if(0===strpos($val,':')) {
                    $var[$key] =  $values[substr($val,1)-1];
                }
            }
            $var   =   array_merge($matches,$var);
            // 解析剩余的URL参数
            if($paths) {
                preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]="\\2";', implode('/',$paths));
            }
            // 解析路由自动传人参数
            if(isset($route[2])) {
                parse_str($route[2],$params);
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }

    // 解析正则路由
    // array('路由正则','[分组/模块/操作]?参数1=值1&参数2=值2...','额外参数','请求类型','资源类型')
    // array('路由正则','外部地址','重定向代码','请求类型','资源类型')
    // 参数值和外部地址中可以用动态变量 采用 :1 :2 的方式
    // array('/new\/(\d+)\/(\d+)/','News/read?id=:1&page=:2&cate=1','status=1','post','html,xml'),
    // array('/new\/(\d+)/','/new.php?id=:1&page=:2&status=1','301','get','html,xml'), 重定向
    static private function parseRegex($matches,$route,$regx) {
        // 获取路由地址规则
        $url   =  preg_replace('/:(\d)/e','$matches[\\1]',$route[1]);
        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            header("Location: $url", true,isset($route[1])?$route[2]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  self::parseUrl($url);
            // 解析剩余的URL参数
            $regx =  substr_replace($regx,'',0,strlen($matches[0]));
            if($regx) {
                preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]="\\2";', $regx);
            }
            // 解析路由自动传人参数
            if(isset($route[2])) {
                parse_str($route[2],$params);
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }
}