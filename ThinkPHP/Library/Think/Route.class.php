<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

/**
 * ThinkPHP路由解析类
 */
class Route
{

    /**
     * 路由检测
     * @param  array $paths path_info数组
     * @return boolean
     */
    public static function check($paths = array())
    {
        $rules = self::ruleCache();
        if (!empty($paths)) {
            $regx = implode('/', $paths);
        } else {
            $depr = C('URL_PATHINFO_DEPR');
            $regx = preg_replace('/\.' . __EXT__ . '$/i', '', trim($_SERVER['PATH_INFO'], $depr));
            if (!$regx) {
                return false;
            }
            // 分隔符替换 确保路由定义使用统一的分隔符
            if ('/' != $depr) {
                $regx = str_replace($depr, '/', $regx);
            }
        }
        // 静态路由检查
        if (isset($rules[0][$regx])) {
            $route = $rules[0][$regx];
            $_SERVER['PATH_INFO'] = $route[0];
            $args = array_pop($route);
            if (!empty($route[1])) {
                $args = array_merge($args, $route[1]);
            }
            $_GET = array_merge($args, $_GET);
            return true;
        }
        // 动态路由检查
        if (!empty($rules[1])) {
            foreach ($rules[1] as $rule => $route) {
                $args = array_pop($route);
                if (isset($route[2])) {
                    // 路由参数检查
                    if (!self::checkOption($route[2], __EXT__)) {
                        continue;
                    }
                }
                $matches = self::checkUrlMatch($rule, $args, $regx);
                if ($matches !== null) {
                    if ($route[0] instanceof \Closure) {
                        // 执行闭包
                        $result = self::invoke($route[0], $matches);
                        // 如果返回布尔值 则继续执行
                        return is_bool($result) ? $result : exit;
                    } else {
                        // 存在动态变量
                        if (strpos($route[0], ':')) {
                            $matches = array_values($matches);
                            $route[0] = preg_replace_callback('/:(\d+)/', function ($match) use (&$matches) {
                                return $matches[$match[1] - 1];
                            }, $route[0]);
                        }
                        // 路由参数关联$matches
                        if ('/' == substr($rule, 0, 1)) {
                            $rule_params = array();
                            foreach($route[1] as $param_key => $param)
                            {
                                list($param_name,$param_value) = explode('=', $param,2);
                                if(!is_null($param_value))
                                {
                                    if(preg_match('/^:(\d*)$/',$param_value, $match_index))
                                    {
                                        $match_index = $match_index[1]-1;
                                        $param_value = $matches[$match_index];
                                    }
                                    $rule_params[$param_name] = $param_value;
                                    unset($route[1][$param_key]);
                                }
                            }
                            $route[1] = $rule_params;
                        }
                        // 重定向
                        if ('/' == substr($route[0], 0, 1)) {
                            header("Location: $route[0]", true, $route[1]);
                            exit;
                        } else {
                            $depr = C('URL_PATHINFO_DEPR');
                            if ('/' != $depr) {
                                $route[0] = str_replace('/', $depr, $route[0]);
                            }
                            $_SERVER['PATH_INFO'] = $route[0];
                            if (!empty($route[1])) {
                                $_GET = array_merge($route[1], $_GET);
                            }
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * 路由反向解析
     * @param  string $path 控制器/方法
     * @param  array $vars url参数
     * @param  string $depr 分隔符
     * @param  string|true $suffix url后缀
     * @return string|false
     */
    public static function reverse($path, &$vars, $depr, $suffix = true)
    {
        static $_rules;
        if (is_null($_rules)) {
            if ($rules = self::ruleCache()) {
                foreach ($rules as $i => $rules2) {
                    foreach ($rules2 as $rule => $route) {
                        if (is_array($route) && is_string($route[0]) && '/' != substr($route[0], 0, 1)) {
                            $_rules[$i][$route[0]][$rule] = $route;
                        }
                    }
                }
            }
        }
        // 静态路由
        if (isset($_rules[0][$path])) {
            foreach ($_rules[0][$path] as $rule => $route) {
                $args = array_pop($route);
                if (count($vars) == count($args) && !empty($vars) && !array_diff($vars, $args)) {
                    return str_replace('/', $depr, $rule);
                }
            }
        }
        if (isset($_rules[1][$path])) {
            foreach ($_rules[1][$path] as $rule => $route) {
                $args = array_pop($route);
                $array = array();
                if (isset($route[2])) {
                    // 路由参数检查
                    if (!self::checkOption($route[2], $suffix)) {
                        continue;
                    }
                }
                if ('/' != substr($rule, 0, 1)) {
                    // 规则路由
                    foreach ($args as $key => $val) {
                        $flag = false;
                        if ($val[0] == 0) {
                            // 静态变量值
                            $array[$key] = $key;
                            continue;
                        }
                        if (isset($vars[$key])) {
                            // 是否有过滤条件
                            if (!empty($val[2])) {
                                if ($val[2] == 'int') {
                                    // 是否为数字
                                    if (!is_numeric($vars[$key]) || !preg_match('/^\d*$/',$vars[$key])) {
                                        break;
                                    }
                                } else {
                                    // 排除的名称
                                    if (in_array($vars[$key], $val[2])) {
                                        break;
                                    }
                                }
                            }
                            $flag = true;
                            $array[$key] = $vars[$key];
                        } elseif ($val[0] == 1) {
                            // 如果是必选项
                            break;
                        }
                    }
                    // 匹配成功
                    if (!empty($flag)) {
                        foreach (array_keys($array) as $key) {
                            $array[$key] = urlencode($array[$key]);
                            unset($vars[$key]);
                        }
                        return implode($depr, $array);
                    }
                } else {
                    // 正则路由
                    $keys = !empty($args) ? array_keys($args) : array_keys($vars);
                    $temp_vars = $vars;
                    $str = preg_replace_callback('/\(.*?\)/', function ($match) use (&$temp_vars, &$keys) {
                        $k = array_shift($keys);
                        $re_var = '';
                        if(isset($temp_vars[$k]))
                        {
                            $re_var = $temp_vars[$k];
                            unset($temp_vars[$k]);
                        }
                        return urlencode($re_var);
                    }, $rule);
                    $str = substr($str, 1, -1);
                    $str = rtrim(ltrim($str, '^'), '$');
                    $str = str_replace('\\', '', $str);
                    if (preg_match($rule, $str, $matches)) {
                        // 匹配成功
                        $vars = $temp_vars;
                        return str_replace('/', $depr, $str);
                    }
                }
            }
        }
        return false;
    }

    // 规则路由定义方法：
    // '路由规则'=>'[控制器/操作]?额外参数1=值1&额外参数2=值2...'
    // '路由规则'=>array('[控制器/操作]','额外参数1=值1&额外参数2=值2...')
    // '路由规则'=>'外部地址'
    // '路由规则'=>array('外部地址','重定向代码')
    // 路由规则中 :开头 表示动态变量
    // 外部地址中可以用动态变量 采用 :1 :2 的方式
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), 重定向
    // 正则路由定义方法：
    // '路由正则'=>'[控制器/操作]?参数1=值1&参数2=值2...'
    // '路由正则'=>array('[控制器/操作]?参数1=值1&参数2=值2...','额外参数1=值1&额外参数2=值2...')
    // '路由正则'=>'外部地址'
    // '路由正则'=>array('外部地址','重定向代码')
    // 参数值和外部地址中可以用动态变量 采用 :1 :2 的方式
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), 重定向
    /**
     * 读取规则缓存
     * @param  boolean $update 是否更新
     * @return array
     */
    public static function ruleCache($update = false)
    {
        $result = array();
        $module = defined('MODULE_NAME') ? '_' . MODULE_NAME : '';
        if (APP_DEBUG || $update || !$result = S('url_route_rules' . $module)) {
            // 静态路由
            $result[0] = C('URL_MAP_RULES');
            if (!empty($result[0])) {
                foreach ($result[0] as $rule => $route) {
                    if (!is_array($route)) {
                        $route = array($route);
                    }
                    if (strpos($route[0], '?')) {
                        // 分离参数
                        list($route[0], $args) = explode('?', $route[0], 2);
                        parse_str($args, $args);
                    } else {
                        $args = array();
                    }
                    if (!empty($route[1]) && is_string($route[1])) {
                        // 额外参数
                        parse_str($route[1], $route[1]);
                    }
                    $route[] = $args;
                    $result[0][$rule] = $route;
                }
            }
            // 动态路由
            $result[1] = C('URL_ROUTE_RULES');
            if (!empty($result[1])) {
                foreach ($result[1] as $rule => $route) {
                    if (!is_array($route)) {
                        $route = array($route);
                    } elseif (is_numeric($rule)) {
                        // 支持 array('rule','adddress',...) 定义路由
                        $rule = array_shift($route);
                    }
                    if (!empty($route)) {
                        $args = array();
                        if (is_string($route[0])) {
                            if (0 === strpos($route[0], '/') || 0 === strpos($route[0], 'http')) {
                                // 重定向
                                if (!isset($route[1])) {
                                    $route[1] = 301;
                                }
                            } else {
                                if (!empty($route[1]) && is_string($route[1])) {
                                    // 额外参数
                                    parse_str($route[1], $route[1]);
                                }
                                if (strpos($route[0], '?')) {
                                    // 分离参数
                                    list($route[0], $params) = explode('?', $route[0], 2);
                                    if (!empty($params)) {
                                        foreach (explode('&', $params) as $key => $val) {
                                            if (0 === strpos($val, ':')) {
                                                // 动态参数
                                                $val = substr($val, 1);
                                                $args[$key] = strpos($val, '|') ? explode('|', $val, 2) : array($val);
                                            } else {
                                                $route[1][$key] = $val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ('/' != substr($rule, 0, 1)) {
                            // 规则路由
                            foreach (explode('/', rtrim($rule, '$')) as $item) {
                                $filter = $fun = '';
                                $type = 0;
                                if (0 === strpos($item, '[:')) {
                                    // 可选变量
                                    $type = 2;
                                    $item = substr($item, 1, -1);
                                }
                                if (0 === strpos($item, ':')) {
                                    // 动态变量获取
                                    $type = $type ?: 1;
                                    if ($pos = strpos($item, '|')) {
                                        // 支持函数过滤
                                        $fun = substr($item, $pos + 1);
                                        $item = substr($item, 1, $pos - 1);
                                    }
                                    if ($pos = strpos($item, '^')) {
                                        // 排除项
                                        $filter = explode('-', substr($item, $pos + 1));
                                        $item = substr($item, 1, $pos - 1);
                                    } elseif (strpos($item, '\\')) {
                                        // \d表示限制为数字
                                        if ('d' == substr($item, -1)) {
                                            $filter = 'int';
                                        }
                                        $item = substr($item, 1, -2);
                                    } else {
                                        $item = substr($item, 1);
                                    }
                                }
                                $args[$item] = array($type, $fun, $filter);
                            }
                        }
                        $route[] = $args;
                        $result[1][$rule] = $route;
                    } else {
                        unset($result[1][$rule]);
                    }
                }
            }
            S('url_route_rules' . $module, $result);
        }
        return $result;
    }

    /**
     * 路由参数检测
     * @param  array $options 路由参数
     * @param  string|true $suffix URL后缀
     * @return boolean
     */
    private static function checkOption($options, $suffix = true)
    {
        // URL后缀检测
        if (isset($options['ext'])) {
            if ($suffix) {
                $suffix = $suffix === true ? C('URL_HTML_SUFFIX') : $suffix;
                if ($pos = strpos($suffix, '|')) {
                    $suffix = substr($suffix, 0, $pos);
                }
            }
            if ($suffix != $options['ext']) {
                return false;
            }
        }
        if (isset($options['method']) && REQUEST_METHOD != strtoupper($options['method'])) {
            // 请求类型检测
            return false;
        }
        // 自定义检测
        if (!empty($options['callback']) && is_callable($options['callback'])) {
            if (false === call_user_func($options['callback'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检测URL和路由规则是否匹配
     * @param  string $rule 路由规则
     * @param  array $args 路由动态变量
     * @param  string $regx URL地址
     * @return array|false
     */
    private static function checkUrlMatch(&$rule, &$args, &$regx)
    {
        $params = array();
        if ('/' == substr($rule, 0, 1)) {
            // 正则路由
            if (preg_match($rule, $regx, $matches)) {
                if ($args) { // 存在动态变量
                    foreach ($args as $key => $val) {
                        $params[$key] = isset($val[1]) ? $val[1]($matches[$val[0]]) : $matches[$val[0]];
                    }
                    $regx = substr_replace($regx, '', 0, strlen($matches[0]));
                }
                array_shift($matches);
                return $matches;
            } else {
                return false;
            }
        } else {
            $paths = explode('/', $regx);
            // $结尾则要求完整匹配
            if ('$' == substr($rule, -1) && count($args) != count($paths)) {
                return false;
            }
            foreach ($args as $key => $val) {
                $var = array_shift($paths) ?: '';
                if ($val[0] == 0) {
                    // 静态变量
                    if (0 !== strcasecmp($key, $var)) {
                        return false;
                    }
                } else {
                    if (isset($val[2])) {
                        // 设置了过滤条件
                        if ($val[2] == 'int') {
                            // 如果值不为整数
                            if (!preg_match('/^\d*$/',$var)) {
                                return false;
                            }
                        } else {
                            // 如果值在排除的名单里
                            if (in_array($var, $val[2])) {
                                return false;
                            }
                        }
                    }
                    if (!empty($var)) {
                        $params[$key] = !empty($val[1]) ? $val[1]($var) : $var;
                    } elseif ($val[0] == 1) {
                        // 不是可选的
                        return false;
                    }
                }
            }
            $matches = $params;
            $regx = implode('/', $paths);
        }
        // 解析剩余的URL参数
        if ($regx) {
            preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$params) {
                $params[strtolower($match[1])] = strip_tags($match[2]);
            }, $regx);
        }
        $_GET = array_merge($params, $_GET);

        // 成功匹配后返回URL中的动态变量数组
        return $matches;
    }

    /**
     * 执行闭包方法 支持参数调用
     * @param  function $closure 闭包函数
     * @param  array $var 传给闭包的参数
     * @return boolean
     */
    private static function invoke($closure, $var = array())
    {
        $reflect = new \ReflectionFunction($closure);
        $params = $reflect->getParameters();
        $args = array();
        foreach ($params as $i => $param) {
            $name = $param->getName();
            if (isset($var[$name])) {
                $args[] = $var[$name];
            } elseif (isset($var[$i])) {
                $args[] = $var[$i];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }
        return $reflect->invokeArgs($args);
    }

}