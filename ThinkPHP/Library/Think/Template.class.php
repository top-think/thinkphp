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

use Think\Hook as Hook;
//use Think\Crypt\Driver\Think as Think;
use Think\Storage as Storage;
use Think\Think as Think;

/**
 * ThinkPHP内置模板引擎类
 * 支持XML标签和普通标签的模板解析
 * 编译型模板引擎 支持动态缓存
 */
class  Template
{

    // 当前模板文件
    protected $templateFile = '';
    // 模板变量
    public $tVar = array();
    public $config = array();
    private $literal = array();

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
        $this->config['cache_path']      = C('CACHE_PATH');
        $this->config['template_suffix'] = C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix']    = C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache']      = C('TMPL_CACHE_ON');
        $this->config['cache_time']      = C('TMPL_CACHE_TIME');
        $this->config['taglib_begin']    = $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end']      = $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin']      = $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end']        = $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl']    = C('TEMPLATE_NAME');
        $this->config['layout_item']     = C('TMPL_LAYOUT_ITEM');
    }

    /**
     * 标签位转换
     * @access private
     * @param  string $str 标签位
     * @return string
     */
    private function stripPreg($str)
    {
        return str_replace(
            array('{', '}', '(', ')', '|', '[', ']', '-', '+', '*', '.', '^', '?'),
            array('\{', '\}', '\(', '\)', '\|', '\[', '\]', '\-', '\+', '\*', '\.', '\^', '\?'),
            $str);
    }

    /**
     * 模板变量获取
     * @access public
     * @param  string $name 变量名
     * @return string|false
     */
    public function get($name)
    {
        if (isset($this->tVar[$name])) {
            return $this->tVar[$name];
        } else {
            return false;
        }
    }

    /**
     * 模板变量设置
     * @access public
     * @param  string $name 变量名
     * @param  string $value 变量值
     * @return void
     */
    public function set($name, $value)
    {
        $this->tVar[$name] = $value;
    }

    /**
     * 加载模板
     * @access public
     * @param  string $templateFile 模板文件
     * @param  array $templateVar 模板变量
     * @param  string $prefix 模板标识前缀
     * @return void
     */
    public function fetch($templateFile, $templateVar, $prefix = '')
    {
        $this->tVar        = $templateVar;
        $templateCacheFile = $this->loadTemplate($templateFile, $prefix);
        Storage::load($templateCacheFile, $this->tVar, null, 'tpl');
    }

    /**
     * 加载主模板并缓存
     * @access public
     * @param  string $templateFile 模板文件
     * @param  string $prefix 模板标识前缀
     * @return string
     * @throws ThinkExecption
     */
    public function loadTemplate($templateFile, $prefix = '')
    {
        if (is_file($templateFile)) {
            $this->templateFile = $templateFile;
            // 读取模板文件内容
            $tmplContent = file_get_contents($templateFile);
        } else {
            $tmplContent = $templateFile;
        }
        // 根据模版文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'] . $prefix . md5($templateFile) . $this->config['cache_suffix'];

        // 判断是否启用布局
        if (C('LAYOUT_ON')) {
            if (false !== strpos($tmplContent, '{__NOLAYOUT__}')) {
                // 可以单独定义不使用布局
                $tmplContent = str_replace('{__NOLAYOUT__}', '', $tmplContent);
            } else { // 替换布局的主体内容
                $layoutFile = THEME_PATH . C('LAYOUT_NAME') . $this->config['template_suffix'];
                // 检查布局文件
                if (!is_file($layoutFile)) {
                    E(L('_TEMPLATE_NOT_EXIST_') . ':' . $layoutFile);
                }
                $tmplContent = str_replace($this->config['layout_item'], $tmplContent, file_get_contents($layoutFile));
            }
        }
        // 编译模板内容
        $tmplContent = $this->compiler($tmplContent);
        Storage::put($tmplCacheFile, trim($tmplContent), 'tpl');
        return $tmplCacheFile;
    }

    /**
     * 编译模板文件内容
     * @access protected
     * @param mixed $tmplContent 模板内容
     * @return string
     */
    protected function compiler(&$tmplContent)
    {
        //模板解析
        $this->parse($tmplContent);
        // 添加安全代码
        $tmplContent = '<?php if (!defined(\'THINK_PATH\')) exit();?>' . $tmplContent;
        // 优化生成的php代码
        $tmplContent = preg_replace('/\?>\s*<\?php\s?/is', '', $tmplContent);
        // 模版编译过滤标签
        Hook::listen('template_filter', $tmplContent);
        return strip_whitespace($tmplContent);
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param  string $content 要解析的模板内容
     * @return viod
     */
    public function parse(&$content)
    {
        // 内容为空不解析
        if (empty($content)) {
            return;
        }
        // 解析继承
        $this->parseExtend($content);
        // 解析布局
        $this->parseLayout($content);
        // 检查include语法
        $this->parseInclude($content);
        // 检查PHP语法
        $this->parsePhp($content);

        // 替换literal标签内容
        $this->parseLiteral($content);

        // 获取需要引入的标签库列表
        // 标签库只需要定义一次，允许引入多个一次
        // 一般放在文件的最前面
        // 格式：<taglib name="html,mytag..." />
        // 当TAGLIB_LOAD配置为true时才会进行检测
        if (C('TAGLIB_LOAD')) {
            $tagLibs = $this->getIncludeTagLib($content);
            if (!empty($tagLibs)) {
                // 对导入的TagLib进行解析
                foreach ($tagLibs as $tag) {
                    $this->parseTagLib($tag, $content);
                }
            }
        }
        // 预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀
        if (C('TAGLIB_PRE_LOAD')) {
            $tagLibs = explode(',', C('TAGLIB_PRE_LOAD'));
            foreach ($tagLibs as $tag) {
                $this->parseTagLib($tag, $content);
            }
        }
        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $tagLibs = explode(',', C('TAGLIB_BUILD_IN'));
        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }
        // 解析普通模板标签 {$tagName}
        $this->parseTag($content);

        // 还原被替换的Literal标签
        $this->parseLiteral($content, true);
        return;
    }

    /**
     * 检查PHP语法
     * @access protected
     * @param  string $content 要解析的模板内容
     * @return void
     */
    protected function parsePhp(&$content)
    {
        if (ini_get('short_open_tag')) {
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
        }
        // PHP语法检查
        if (C('TMPL_DENY_PHP') && false !== strpos($content, '<?php')) {
            E(L('_NOT_ALLOW_PHP_'));
        }
        return;
    }

    /**
     * 解析模板中的布局标签
     * @access protected
     * @param  string $content 要解析的模板内容
     * @return void
     */
    protected function parseLayout(&$content)
    {
        // 读取模板中的布局标签
        if (preg_match($this->getRegex('layout'), $content, $matches)) {
            //替换Layout标签
            $content = str_replace($matches[0], '', $content);
            //解析Layout标签
            $array = $this->parseAttr($matches[0]);
            if (!C('LAYOUT_ON') || C('LAYOUT_NAME') != $array['name']) {
                // 读取布局模板
                $layoutFile = THEME_PATH . $array['name'] . $this->config['template_suffix'];
                if (is_file($layoutFile)) {
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    // 替换布局的主体内容
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
        return;
    }

    /**
     * 解析模板中的include标签
     * @access protected
     * @param  string $content 要解析的模板内容
     * @return void
     */
    protected function parseInclude(&$content)
    {
        $regex      = $this->getRegex('include');
        $self       = &$this;
        $funReplace = function ($template) use (&$funReplace, &$regex, &$content, &$self) {
            if (preg_match_all($regex, $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array = $self->parseAttr($match[0]);
                    $file  = $array['file'];
                    unset($array['file']);
                    // 分析模板文件名并读取内容
                    $parseStr = $self->parseTemplateName($file);
                    // 替换变量
                    foreach ($array as $k => $v) {
                        $parseStr = str_replace('[' . $k . ']', $v, $parseStr);
                    }
                    // 再次对包含文件进行模板分析
                    $funReplace($parseStr);
                    $content = str_replace($match[0], $parseStr, $content);
                }
                unset($matches);
            }
        };
        // 替换模板中的include标签
        $funReplace($content);
        return;
    }

    /**
     * 解析模板中的extend标签
     * @access protected
     * @param  string $content 要解析的模板内容
     * @return void
     */
    protected function parseExtend(&$content)
    {
        $regex  = $this->getRegex('extend');
        $flag   = array();
        $blocks = $extBlocks = array();
        $extend = '';
        $self   = &$this;
        $fun    = function ($template) use (&$self, &$fun, &$regex, &$flag, &$extend, &$blocks, &$extBlocks) {
            if (preg_match($regex, $template, $matches)) {
                if (!isset($flag[$matches['name']])) {
                    $flag[$matches['name']] = 1;
                    // 读取继承模板
                    $extend = $self->parseTemplateName($matches['name']);
                    // 递归检查继承
                    $fun($extend);
                    // 取得block标签内容
                    $blocks = array_merge($blocks, $self->parseBlock($template));
                    return;
                }
            } else {
                // 取得顶层模板block标签内容
                $extBlocks = $self->parseBlock($template);
                if (empty($extend)) {
                    // 无extend标签但有block标签的情况
                    $extend = $template;
                }
            }
        };

        $fun($content);
        if (!empty($extend)) {
            if ($extBlocks) {
                foreach ($extBlocks as $name => $v) {
                    $replace = isset($blocks[$name]) ? $blocks[$name]['content'] : $v['content'];
                    $extend  = str_replace($v['begin']['tag'] . $v['content'] . $v['end']['tag'], $replace, $extend);
                }
            }
            $content = $extend;
        }
        return;
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param  string $content 模板内容
     * @return void
     */
    private function parseLiteral(&$content, $restore = false)
    {
        $regex = $this->getRegex($restore ? 'restoreliteral' : 'literal');
        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                // 替换literal标签
                foreach ($matches as $i => $match) {
                    $this->literal[$i] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content           = str_replace($match[0], "<!--###literal{$i}###-->", $content);
                }
            } else {
                // 还原literal标签
                foreach ($matches as $i => $match) {
                    $content = str_replace($match[0], $this->literal[$i], $content);
                }
                // 销毁literal记录
                unset($this->literal);
            }
            unset($matches);
        }
        return;
    }

    /**
     * 获取模板中的block标签
     * @access protected
     * @param  string $content 模板内容
     * @return array
     */
    protected function parseBlock(&$content)
    {
        $regex = $this->getRegex('block');
        $array = array();
        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $right = array();
            foreach ($matches as $match) {
                if (empty($match['name'][0])) {
                    if (!empty($right)) {
                        $begin                 = array_pop($right);
                        $end                   = array('offset' => $match[0][1], 'tag' => $match[0][0]);
                        $start                 = $begin['offset'] + strlen($begin['tag']);
                        $len                   = $end['offset'] - $start;
                        $array[$begin['name']] = array(
                            'begin'   => $begin,
                            'content' => substr($content, $start, $len),
                            'end'     => $end,
                        );
                    } else {
                        continue;
                    }
                } else {
                    $right[] = array('name' => $match[2][0], 'offset' => $match[0][1], 'tag' => $match[0][0]);
                }
            }
            unset($right, $matches);
        }
        return $array;
    }

    /**
     * 搜索模板页面中包含的TagLib库
     * 并返回列表
     * @access protected
     * @param  string $content 模板内容
     * @return array|null
     */
    protected function getIncludeTagLib(&$content)
    {
        // 搜索是否有TagLib标签
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            // 替换TagLib标签
            $content = str_replace($matches[0], '', $content);
            return explode(',', $matches['name']);
        }
        return null;
    }

    /**
     * TagLib库解析
     * @access public
     * @param  string $tagLib 要解析的标签库
     * @param  string $content 要解析的模板内容
     * @param  boolean $hide 是否隐藏标签库前缀
     * @return void
     */
    public function parseTagLib($tagLib, &$content, $hide = false)
    {
        if (strpos($tagLib, '\\')) {
            // 支持指定标签库的命名空间
            $className = $tagLib;
            $tagLib    = substr($tagLib, strrpos($tagLib, '\\') + 1);
        } else {
            $className = 'Think\\Template\TagLib\\' . ucwords($tagLib);
        }
        $tagLib = strtolower($tagLib);
        \Think\Think::instance($className)->parseTag($content, $hide ? '' : $tagLib);
        return;
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param  string $tagStr 标签内容
     * @return void
     */
    public function parseTag(&$content)
    {
        $regex = $this->getRegex('tag');
        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str  = stripslashes($match[1]);
                $flag = substr($str, 0, 1);
                switch ($flag) {
                    case '$': // 解析模板变量 格式 {$varName}
                        $this->parseVar($str);
                        // 是否带有?号
                        if (false !== $pos = strpos($str, '?')) {
                            $array = preg_split('/([!=]={1,2}|(?<!-)[><]={0,1})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name  = trim($array[0]);
                            $this->parseVarFunction($name);

                            $str   = trim(substr($str, $pos + 1));
                            $first = substr($str, 0, 1);
                            if (isset($array[1])) {
                                // XXX: 加入这句原本是为解决变量末声明的问题，但$name中是多个条件时会解析错误，故注释掉
                                /*if (strpos($name, '[')) {
                                    $name = 'isset(' . $name . ') && ' . $name;
                                }*/
                                $name .= $array[1] . trim($array[2]);
                                if ('=' == $first) {
                                    // {$varname?='xxx'} $varname为真时才输出xxx
                                    $str = '<?php if( ' . $name . ' ) echo ' . substr($str, 1) . '; ?>';
                                } else {
                                    $str = '<?php echo (' . $name . ') ? ' . $str . '; ?>';
                                }
                            } else {
                                switch ($first) {
                                    case '?':
                                        // {$varname??'xxx'} $varname有定义则输出$varname,否则输出xxx
                                        $str = '<?php echo isset(' . $name . ') ? ' . $name . ' : ' . substr($str, 1) . '; ?>';
                                        break;
                                    case '=':
                                        // {$varname?='xxx'} $varname为真时才输出xxx
                                        $str = '<?php if(!empty(' . $name . ')) echo ' . substr($str, 1) . '; ?>';
                                        break;
                                    case ':':
                                        // {$varname?:'xxx'} $varname为真时输出$varname,否则输出xxx
                                        $str = '<?php echo !empty(' . $name . ') ? ' . $name . $str . '; ?>';
                                        break;
                                    default:
                                        if (strpos($str, ':')) {
                                            // {$varname ? 'a' : 'b'} $varname为真时输出a,否则输出b
                                            $str = '<?php echo !empty(' . $name . ') ? ' . $str . '; ?>';
                                        } else {
                                            $str = '<?php echo ' . $name . '?' . $str . '; ?>';
                                        }
                                }
                            }
                        } else {
                            $this->parseVarFunction($str);
                            $str = '<?php echo ' . $str . '; ?>';
                        }
                        break;
                    case ':': // 输出某个函数的结果
                        $str = substr($str, 1);
                        $this->parseVar($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~': // 执行某个函数
                        $str = substr($str, 1);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '-':
                    case '+': // 输出计算
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '/': // 注释标签
                        $flag2 = substr($str, 1, 1);
                        if ($flag2 == '/' || ($flag2 == '*' && substr(rtrim($str), -2) == '*/')) {
                            $str = '';
                        }
                        break;
                    default:
                        static $_tmplDelimiter;
                        if (is_null($_tmplDelimiter)) {
                            $_tmplDelimiter = array(C('TMPL_L_DELIM'), C('TMPL_R_DELIM'));
                        }
                        // 未识别的标签直接返回
                        $str = $_tmplDelimiter[0] . $str . $_tmplDelimiter[1];
                        break;
                }
                $content = str_replace($match[0], $str, $content);
            }
            unset($matches);
        }
        return;
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr 变量数据
     * @return void
     */
    public function parseVar(&$varStr)
    {
        $varStr = trim($varStr);
        if (preg_match_all('/\$[a-zA-Z_](?>\w*)(?:[:\.][a-zA-Z_](?>\w*))+/', $varStr, $matches, PREG_OFFSET_CAPTURE)) {
            static $_varParseList = array();
            while ($matches[0]) {
                $match = array_pop($matches[0]);
                //如果已经解析过该变量字串，则直接返回变量值
                if (isset($_varParseList[$match[0]])) {
                    $parseStr = $_varParseList[$match[0]];
                } else {
                    if (strpos($match[0], '.')) {
                        $vars  = explode('.', $match[0]);
                        $first = array_shift($vars);
                        if ($first == '$Think') {
                            // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                            $parseStr = $this->parseThinkVar($vars);
                        } else {
                            switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                                case 'array': // 识别为数组
                                    $parseStr = $first . '[\'' . implode('\'][\'', $vars) . '\']';
                                    break;
                                case 'obj':  // 识别为对象
                                    $parseStr = $first . '->' . implode('->', $vars);
                                    break;
                                default:  // 自动判断数组或对象 只支持二维
                                    $parseStr = 'is_array(' . $first . ')?' . $first . '[\'' . implode('\'][\'', $vars) . '\']:' . $first . '->' . implode('->', $vars);
                            }
                        }
                    } else {
                        $parseStr = str_replace(':', '->', $match[0]);
                    }
                    $_varParseList[$match[0]] = $parseStr;
                }
                $varStr = substr_replace($varStr, $parseStr, $match[1], strlen($match[0]));
            }
            unset($matches);
        }
        return;
    }

    /**
     * 对模板中使用了函数的变量进行解析
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param  string $varStr 变量字符串
     * @return void
     */
    public function parseVarFunction(&$varStr)
    {
        if (false == strpos($varStr, '|')) {
            return;
        }
        static $_varFunctionList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varFunctionList[$varStr])) {
            $varStr = $_varFunctionList[$varStr];
        } else {
            $varArray = explode('|', $varStr);
            // 取得变量名称
            $name = array_shift($varArray);
            // 对变量使用函数
            $length = count($varArray);
            // 取得模板禁止使用函数列表
            $template_deny_funs = explode(',', C('TMPL_DENY_FUNC_LIST'));
            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);
                // 模板函数过滤
                $fun = trim($args[0]);
                switch ($fun) {
                    case 'default':  // 特殊模板函数
                        $varStr = '(isset(' . $name . ') && (' . $name . ' !== \'\'))?(' . $name . '):' . $args[1];
                        break;
                    default:  // 通用模板函数
                        if (!in_array($fun, $template_deny_funs)) {
                            if (isset($args[1])) {
                                if (strstr($args[1], '###')) {
                                    $args[1] = str_replace('###', $name, $args[1]);
                                    $name    = "$fun($args[1])";
                                } else {
                                    $varStr = "$fun($name,$args[1])";
                                }
                            } else {
                                if (!empty($args[0])) {
                                    $name = "$fun($name)";
                                }
                            }
                        }
                }
            }
            $varStr = $name;
        }
        return;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param  array $vars 变量数组
     * @return string
     */
    public function parseThinkVar(&$vars)
    {
        $vars[0]  = strtoupper(trim($vars[0]));
        $parseStr = '';
        if (count($vars) >= 2) {
            $vars[1] = trim($vars[1]);
            switch ($vars[0]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . strtoupper($vars[1]) . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[1] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[1] . '\']';
                    break;
                case 'COOKIE':
                    if (isset($vars[2])) {
                        $parseStr = '$_COOKIE[\'' . $vars[1] . '\'][\'' . $vars[2] . '\']';
                    } else {
                        $parseStr = 'cookie(\'' . $vars[1] . '\')';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[2])) {
                        $parseStr = '$_SESSION[\'' . $vars[1] . '\'][\'' . $vars[2] . '\']';
                    } else {
                        $parseStr = 'session(\'' . $vars[1] . '\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . strtoupper($vars[1]) . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[1] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[1]);
                    break;
                case 'LANG':
                    $parseStr = 'L(\'' . $vars[1] . '\')';
                    break;
                case 'CONFIG':
                    if (isset($vars[2])) {
                        $vars[1] .= '.' . $vars[2];
                    }
                    $parseStr = 'C(\'' . $vars[1] . '\')';
                    break;
                default:
                    break;
            }
        } else {
            if (count($vars) == 1) {
                switch ($vars[0]) {
                    case 'NOW':
                        $parseStr = "date('Y-m-d g:i a',time())";
                        break;
                    case 'VERSION':
                        $parseStr = 'THINK_VERSION';
                        break;
                    case 'TEMPLATE':
                        $parseStr = "'" . $this->templateFile . "'"; //'C("TEMPLATE_NAME")';
                        break;
                    case 'LDELIM':
                        $parseStr = 'C("TMPL_L_DELIM")';
                        break;
                    case 'RDELIM':
                        $parseStr = 'C("TMPL_R_DELIM")';
                        break;
                    default:
                        if (defined($vars[0])) {
                            $parseStr = $vars[0];
                        }
                }
            }
        }
        return $parseStr;
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access public
     * @param  string $tmplPublicName 模板文件名
     * @return string
     */
    public function parseTemplateName($templateName)
    {
        if ('$' == substr($templateName, 0, 1)) {
            //支持加载变量文件名
            $templateName = $this->get(substr($templateName, 1));
        }
        $array    = explode(',', $templateName);
        $parseStr = '';
        foreach ($array as $templateName) {
            if (empty($templateName)) {
                continue;
            }
            if (false === strpos($templateName, $this->config['template_suffix'])) {
                // 解析规则为 模块@主题/控制器/操作
                $templateName = T($templateName);
            }
            // 获取模板文件内容
            $parseStr .= file_get_contents($templateName);
        }
        return $parseStr;
    }

    /**
     * 按标签生成正则
     * @access private
     * @param  string $tagName 标签名
     * @return string
     */
    private function getRegex($tagName)
    {
        $begin  = $this->config['taglib_begin'];
        $end    = $this->config['taglib_end'];
        $single = strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1 ? true : false;
        $regex  = '';
        switch ($tagName) {
            case 'block':
                if ($single) {
                    $regex = $begin . '(?:' . $tagName . '\b(?>(?:(?!name=).)*)\bname=([\'\"])(?<name>[\w\/\:@,]+)\\1(?>[^' . $end . ']*)|\/' . $tagName . ')' . $end;
                } else {
                    $regex = $begin . '(?:' . $tagName . '\b(?>(?:(?!name=).)*)\bname=([\'\"])(?<name>[\w\/\:@,]+)\\1(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end;
                }
                break;
            case 'literal':
                if ($single) {
                    $regex = '(' . $begin . $tagName . '\b(?>[^' . $end . ']*)' . $end . ')';
                    $regex .= '(?:(?>[^' . $begin . ']*)(?>(?!' . $begin . '(?>' . $tagName . '\b[^' . $end . ']*|\/' . $tagName . ')' . $end . ')' . $begin . '[^' . $begin . ']*)*)';
                    $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                } else {
                    $regex = '(' . $begin . $tagName . '\b(?>(?:(?!' . $end . ').)*)' . $end . ')';
                    $regex .= '(?:(?>(?:(?!' . $begin . ').)*)(?>(?!' . $begin . '(?>' . $tagName . '\b(?>(?:(?!' . $end . ').)*)|\/' . $tagName . ')' . $end . ')' . $begin . '(?>(?:(?!' . $begin . ').)*))*)';
                    $regex .= '(' . $begin . '\/' . $tagName . $end . ')';
                }
                break;
            case 'restoreliteral':
                $regex = '<!--###literal(\d+)###-->';
                break;
            case 'include':
                $name = 'file';
            case 'taglib':
            case 'layout':
            case 'extend':
                if (empty($name)) {
                    $name = 'name';
                }
                if ($single) {
                    $regex = $begin . $tagName . '\b(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?<name>[\w\/\:@,\\\\]+)\\1(?>[^' . $end . ']*)' . $end;
                } else {
                    $regex = $begin . $tagName . '\b(?>(?:(?!' . $name . '=).)*)\b' . $name . '=([\'\"])(?<name>[\w\/\:@,\\\\]+)\\1(?>(?:(?!' . $end . ').)*)' . $end;
                }
                break;
            case 'tag':
                $begin = $this->config['tmpl_begin'];
                $end   = $this->config['tmpl_end'];
                if (strlen(ltrim($begin, '\\')) == 1 && strlen(ltrim($end, '\\')) == 1) {
                    $regex = $begin . '((?:[\$\:\-\+][a-wA-w_][\w\.\:\[\(\*\/\-\+\%_]|\/[\*\/])(?>[^' . $end . ']*))' . $end;
                } else {
                    $regex = $begin . '((?:[\$\:\-\+][a-wA-w_][\w\.\:\[\(\*\/\-\+\%_]|\/[\*\/])(?>(?:(?!' . $end . ').)*))' . $end;
                }
                break;
        }
        return '/' . $regex . '/is';
    }

    /**
     * 分析标签属性
     * @access public
     * @param  string $str 属性字符串
     * @param  string $name 不为空时返回指定的属性名
     * @return array
     */
    public function parseAttr($str, $name = null)
    {
        $regex = '/\s+(?>(?<name>\w+)\s*)=(?>\s*)([\"\'])(?<value>(?:(?!\\2).)*)\\2/is';
        $array = array();
        if (preg_match_all($regex, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }
        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        } else {
            return $array;
        }
    }
}
