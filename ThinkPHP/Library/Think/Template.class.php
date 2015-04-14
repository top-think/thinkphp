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
 * ThinkPHP内置模板引擎类
 * 支持XML标签和普通标签的模板解析
 * 编译型模板引擎 支持动态缓存
 */
class  Template {

    // 模板页面中引入的标签库列表
    protected   $tagLib          =   array();
    // 当前模板文件
    protected   $templateFile    =   '';
    // 模板变量
    public      $tVar            =   array();
    public      $config          =   array();
    private     $literal         =   array();
    private     $block           =   array();

    /**
     * 架构函数
     * @access public
     */
    public function __construct(){
        $this->config['cache_path']         =   C('CACHE_PATH');
        $this->config['template_suffix']    =   C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix']       =   C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache']         =   C('TMPL_CACHE_ON');
        $this->config['cache_time']         =   C('TMPL_CACHE_TIME');
        $this->config['taglib_begin']       =   $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end']         =   $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin']         =   $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end']           =   $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl']       =   C('TEMPLATE_NAME');
        $this->config['layout_item']        =   C('TMPL_LAYOUT_ITEM');
    }

    /**
     * 标签位转换
     * @access private
     * @param  string $str  标签位
     * @return string
     */
    private function stripPreg($str) {
        return str_replace(
            array('{','}','(',')','|','[',']','-','+','*','.','^','?'),
            array('\{','\}','\(','\)','\|','\[','\]','\-','\+','\*','\.','\^','\?'),
            $str);        
    }

    /**
     * 模板变量获取
     * @access public
     * @param  string $name  变量名
     * @return string|false
     */
    public function get($name) {
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    /**
     * 模板变量设置
     * @access public
     * @param  string $name  变量名
     * @param  string $value 变量值
     * @return void
     */
    public function set($name,$value) {
        $this->tVar[$name]= $value;
    }

    /**
     * 加载模板
     * @access public
     * @param string $tmplTemplateFile 模板文件
     * @param array  $templateVar 模板变量
     * @param string $prefix 模板标识前缀
     * @return void
     */
    public function fetch($templateFile,$templateVar,$prefix='') {
        $this->tVar         =   $templateVar;
        $templateCacheFile  =   $this->loadTemplate($templateFile,$prefix);
        Storage::load($templateCacheFile,$this->tVar,null,'tpl');
    }

    /**
     * 加载主模板并缓存
     * @access public
     * @param string $tmplTemplateFile 模板文件
     * @param string $prefix 模板标识前缀
     * @return string
     * @throws ThinkExecption
     */
    public function loadTemplate ($tmplTemplateFile,$prefix='') {
        if(is_file($tmplTemplateFile)) {
            $this->templateFile    =  $tmplTemplateFile;
            // 读取模板文件内容
            $tmplContent =  file_get_contents($tmplTemplateFile);
        }else{
            $tmplContent =  $tmplTemplateFile;
        }
         // 根据模版文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'].$prefix.md5($tmplTemplateFile).$this->config['cache_suffix'];

        // 判断是否启用布局
        if(C('LAYOUT_ON')) {
            if(false !== strpos($tmplContent,'{__NOLAYOUT__}')) { // 可以单独定义不使用布局
                $tmplContent = str_replace('{__NOLAYOUT__}','',$tmplContent);
            }else{ // 替换布局的主体内容
                $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').$this->config['template_suffix'];
                // 检查布局文件
                if(!is_file($layoutFile)) {
                    E(L('_TEMPLATE_NOT_EXIST_').':'.$layoutFile);
                }
                $tmplContent = str_replace($this->config['layout_item'],$tmplContent,file_get_contents($layoutFile));
            }
        }
        // 编译模板内容
        $this->compiler($tmplContent);
        Storage::put($tmplCacheFile,trim($tmplContent),'tpl');
        return $tmplCacheFile;
    }

    /**
     * 编译模板文件内容
     * @access protected
     * @param mixed $tmplContent 模板内容
     * @return string
     */
    protected function compiler(&$tmplContent) {
        //模板解析
        $this->parse($tmplContent);
        // 还原被替换的Literal标签
        $this->parseLiteral($tmplContent, true);
        // 添加安全代码
        $tmplContent =  '<?php if (!defined(\'THINK_PATH\')) exit();?>'.$tmplContent;
        // 优化生成的php代码
        $tmplContent = preg_replace('/\?>\s*<\?php\s?/is','',$tmplContent);
        // 模版编译过滤标签
        Hook::listen('template_filter',$tmplContent);
        return strip_whitespace($tmplContent);
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param string $content 要解析的模板内容
     * @return string
     */
    public function parse(&$content) {
        // 内容为空不解析
        if(empty($content)) return '';
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
        if(C('TAGLIB_LOAD')) {
            $this->getIncludeTagLib($content);
            if(!empty($this->tagLib)) {
                // 对导入的TagLib进行解析
                foreach($this->tagLib as $tagLibName) {
                    $this->parseTagLib($tagLibName,$content);
                }
            }
        }
        // 预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀
        if(C('TAGLIB_PRE_LOAD')) {
            $tagLibs =  explode(',',C('TAGLIB_PRE_LOAD'));
            foreach ($tagLibs as $tag){
                $this->parseTagLib($tag,$content);
            }
        }
        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $tagLibs =  explode(',',C('TAGLIB_BUILD_IN'));
        foreach ($tagLibs as $tag){
            $this->parseTagLib($tag,$content,true);
        }
        //解析普通模板标签 {$tagName}
        $this->parseTag($content);
        return $content;
    }

    /**
     * 检查PHP语法
     * @access protected
     * @param string $content 要解析的模板内容
     * @return string|false
     */
    protected function parsePhp(&$content) {
        if(ini_get('short_open_tag')){
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        // PHP语法检查
        if(C('TMPL_DENY_PHP') && false !== strpos($content,'<?php')) {
            E(L('_NOT_ALLOW_PHP_'));
        }
    }

    /**
     * 解析模板中的布局标签
     * @access protected
     * @param string $content 要解析的模板内容
     * @return string|false
     */
    protected function parseLayout(&$content) {
        // 读取模板中的布局标签
        if(preg_match($this->getRegex('layout'), $content, $matches)) {
            //替换Layout标签
            $content    =   str_replace($matches[0],'',$content);
            //解析Layout标签
            $array      =   $this->parseAttrs($matches[0]);
            if(!C('LAYOUT_ON') || C('LAYOUT_NAME') != $array['name'] ) {
                // 读取布局模板
                $layoutFile =   THEME_PATH.$array['name'].$this->config['template_suffix'];
                $replace    =   isset($array['replace'])?$array['replace']:$this->config['layout_item'];
                // 替换布局的主体内容
                $content    =   str_replace($replace, $content, file_get_contents($layoutFile));
            }
        }else{
            $content = str_replace('{__NOLAYOUT__}','',$content);
        }
        return;
    }

    /**
     * 解析模板中的include标签
     * @access protected
     * @param string $content 要解析的模板内容
     * @return string|false
     */
    protected function parseInclude(&$content) {
        $reg        =   $this->getRegex('include');
        $self = &$this;
        $funReplace = function($template) use(&$funReplace, &$reg, &$content, &$self) {
            if (preg_match_all($reg, $template, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array      =   $self->parseAttrs($match[0]);
                    $file       =   $array['file'];
                    unset($array['file']);
                    // 分析模板文件名并读取内容
                    $parseStr = $self->parseTemplateName($file);
                    // 替换变量
                    foreach ($array as $k => $v) {
                        $parseStr = str_replace('['.$k.']', $v, $parseStr);
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
     * @param string $content 要解析的模板内容
     * @return string|false
     */
    protected function parseExtend(&$content) {
        $reg = $this->getRegex('extend');
        $flag = array();
        $blocks = $extBlocks = array();
        $extend = '';
        $self = &$this;
        $fun = function($template) use(&$self, &$fun, &$reg, &$flag, &$extend, &$blocks, &$extBlocks) {
            if (preg_match($reg, $template, $matches)) {
                if (!isset($flag[$matches['name']])) {
                    $flag[$matches['name']] = 1;
                    // 读取继承模板
                    $extend    =   $self->parseTemplateName($matches['name']);
                    // 递归检查继承
                    $fun($extend);
                    // 取得block标签内容
                    $blocks    =   array_merge($blocks, $self->parseBlock($template));
                    return;
                }
            } else {
                // 取得顶层模板block标签内容
                $extBlocks = $self->parseBlock($template);
                if (empty($extend)) {
                    // 无extend标签但有block标签的情况
                    $extend   =   $template;
                }
            }
        };

        $fun($content);
        if (!empty($extend)) {
            if ($extBlocks) {
                foreach ($extBlocks as $name => $v) {
                    $replace = isset($blocks[$name]) ? $blocks[$name]['content'] : $v['content'];
                    $extend = str_replace($v['begin']['tag'].$v['content'].$v['end']['tag'], $replace, $extend);
                }
            }
            $content = $extend;
        }
        return;
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param string $content  模板内容
     * @return string|false
     */
    private function parseLiteral(&$content, $restore = false) {
        $reg = $this->getRegex($restore ? 'restoreliteral' : 'literal');
        if (preg_match_all($reg, $content, $matches, PREG_SET_ORDER)) {
            if (!$restore) {
                // 替换literal标签
                foreach ($matches as $i => $match) {
                    $this->literal[$i] = substr($match[0], strlen($match[1]), -strlen($match[2]));
                    $content = str_replace($match[0], "<!--###literal{$i}###-->", $content);
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
     * @access public
     * @param string $content  模板内容
     * @return array
     */
    public function parseBlock(&$content){
        $reg = $this->getRegex('block');
        $array = array();
        if (preg_match_all($reg, $content, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)){
            $right = array();
            foreach ($matches as $match) {
                if (empty($match['name'][0])) {
                    if (!empty($right)) {
                        $begin = array_pop($right);
                        $end = array('offset' => $match[0][1], 'tag' => $match[0][0]);
                        $start = $begin['offset']+strlen($begin['tag']);
                        $len = $end['offset']-$start;
                        $array[$begin['name']] = array(
                            'begin' => $begin,
                            'content' => substr($content, $start, $len),
                            'end' => $end
                        );
                    } else {
                        continue;
                    }
                } else {
                    $right[] = array('name' => $match[2][0], 'offset' => $match[0][1], 'tag' => $match[0][0]);
                }
            }
            unset($right,$matches);
        }
        return $array;
    }

    /**
     * 搜索模板页面中包含的TagLib库
     * 并返回列表
     * @access public
     * @param string $content  模板内容
     * @return string|false
     */
    public function getIncludeTagLib(& $content) {
        //搜索是否有TagLib标签
        if (preg_match($this->getRegex('taglib'), $content, $matches)) {
            //替换TagLib标签
            $content        =   str_replace($matches[0],'',$content);
            //解析TagLib标签
            $this->tagLib   =   explode(',', $matches['name']);
        }
        return;
    }

    /**
     * TagLib库解析
     * @access public
     * @param string $tagLib 要解析的标签库
     * @param string $content 要解析的模板内容
     * @param boolean $hide 是否隐藏标签库前缀
     * @return string
     */
    public function parseTagLib($tagLib,&$content,$hide=false) {
        if(strpos($tagLib,'\\')){
            // 支持指定标签库的命名空间
            $className  =   $tagLib;
            $tagLib     =   substr($tagLib,strrpos($tagLib,'\\')+1);
        }else{
            $className  =   'Think\\Template\TagLib\\'.ucwords($tagLib);            
        }
        $tLib       =   \Think\Think::instance($className);
        $tags       =   array();
        $nodes      =   array();
        foreach ($tLib->getTags() as $name => $val) {
            $close = !isset($val['close']) || $val['close'] ? 1 : 0;
            $tags[$close][$name] = $name;
            if(isset($val['alias'])) {// 别名设置
                foreach (explode(',', $val['alias']) as $v) {
                    $tags[$close][$v] = $name;
                }
            }
        }
        // 闭合标签
        if (!empty($tags[1])) { 
            $reg = $this->getRegex('taglibtag', array_keys($tags[1]), 1, !$hide?$tabLib:'');
            if (preg_match_all($reg, $content, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)){
                $right = array();
                foreach ($matches as $match) {
                    if ($match[1][0] == '') {
                        $name = $match[2][0];
                        // 如果有没闭合的标签头则取出最后一个
                        if (!empty($right[$name])) {
                            // $match[0][1]为标签结束符在模板中的位置
                            $nodes[$match[0][1]] = array(
                                'name'=>$name,
                                'begin'=>array_pop($right[$name]), // 标签开始符
                                'end'=>$match[0] // 标签结束符
                            );
                        } else {
                            continue;
                        }
                    } else {
                        // 标签头压入栈
                        $right[$match[1][0]][] = $match[0];
                    }
                }
                unset($right,$matches);
                // 按标签在模板中的位置从后向前排序
                krsort($nodes); 
            }

            $break = '<!--###break###--!>';
            if ($nodes) {
                // 模板中第一个闭合标签
                $firstNode = end($nodes);
                $beginArray = array();
                // 标签替换 从后向前
                foreach ($nodes as $pos => $node) {
                    $name = $tags[1][$node['name']]; // 对应的标签名
                    $attrs = $tLib->parseAttr($node['begin'][0], $name); // 解析标签属性
                    $method = '_'.$name;
                    // 读取标签库中对应的标签内容 replace[0]用来替换标签头，replace[1]用来替换标签尾
                    $replace = explode($break, $tLib->$method($attrs, $break, $node['name'])); 
                    while ($beginArray) {
                        $last = end($beginArray);
                        // 判断当前标签尾的位置是否在栈中最后一个标签头的后面，是则为子标签
                        if ($node['end'][1] > $last['pos']) {
                            break;
                        } else {
                            // 不为子标签时，取出栈中最后一个标签头
                            $begin = array_pop($beginArray);
                            // 替换标签头部
                            $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                        }
                    }
                    // 替换标签尾部
                    $content = substr_replace($content, $replace[1], $node['end'][1], strlen($node['end'][0])); 
                    if ($node != $firstNode) {
                        // 如果不是模板中第一个标签则把标签头压入栈
                        $beginArray[] = array('pos'=>$node['begin'][1], 'len'=>strlen($node['begin'][0]), 'str'=>$replace[0]);
                    } else {
                        // 替换标签头部
                        $content = substr_replace($content, $replace[0], $node['begin'][1], strlen($node['begin'][0])); 
                        while ($beginArray) {
                            $begin = array_pop($beginArray);
                            $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                        } 
                    }
                }
            }
        }
        // 自闭合标签
        if (!empty($tags[0])) {
            $reg = $this->getRegex('taglibtag', array_keys($tags[0]), 0, !$hide?$tabLib:'');
            /*
            if (preg_match_all($reg, $content, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
            	krsort($matches);
                foreach ($matches as $match) {
                    $tagName = $match[1]; // 对应的标签名
                    $name = $tags[$tagName];
                    $method = '_'.$name;
                    $attrs = $tLib->parseAttr($match[0], $name); // 解析标签属性
                    $content = substr_replace($content, $tLib->$method($attrs, '', $tagName), $match[0][1], strlen($match[0][0]));
                }
            }
            */
            $content = preg_replace_callback($reg, function($matches) use(&$tags, &$tLib) {
                $name = $tags[0][$matches[1]];
                $attrs = $tLib->parseAttr($matches[0], $name); // 解析标签属性
                $method = '_'.$name;
                return $tLib->$method($attrs, '', $matches[1]);
            }, $content);

        }
        return;
    }

    /**
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     * @access public
     * @param object $tagLib  标签库对象实例
     * @param string $tag  标签名
     * @param string $attr  标签属性
     * @param string $content  标签内容
     * @return string|false
     */
    public function parseXmlTag($tagLib,$tag,$attr,$content) {
        if(ini_get('magic_quotes_sybase'))
            $attr   =   str_replace('\"','\'',$attr);
        $parse      =   '_'.$tag;
        $content    =   trim($content);
        $tags       =   $tagLib->parseXmlAttr($attr,$tag);
        return $tagLib->$parse($tags,$content);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param string $tagStr 标签内容
     * @return string
     */
    public function parseTag(&$content){
        $reg = $this->getRegex('tag');
        if (preg_match_all($reg, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tag = stripslashes($match[1]);
                $flag   =  substr($tag, 0, 1);
                $str   = substr($tag, 1);
                switch ($flag) {
                    case '$': // 解析模板变量 格式 {$varName}
                        // 是否带有?号
                        if(false !== $pos = strpos($str,'?')) {
                            $array = preg_split('/([!|=]={1,2})/', substr($str, 0, $pos), 2, PREG_SPLIT_DELIM_CAPTURE);
                            $name = $this->parseVar($array[0]);
                            if (count($array)>1) $name .= $array[1].$array[2];

                            $str = substr($str, $pos+1);
                            $pos = strpos($str, ':');
                            if (false === $pos) { // {$varname?'XXX'} $varname为真时才输出
                                $str = '<?php if('.$name.') echo ('.$str.'); ?>';                                
                            } elseif (0 === $pos) { // {$varname?:'XXX'} $varname为真时输出$varname,否则输出XXX
                                $str = '<?php echo '.$name.'?:'.substr($str,1).'; ?>';
                            } else {
                                $str = '<?php echo '.$name.'?'.$str.'; ?>';
                            }
                        } else {
                            $str = '<?php echo '.$this->parseVar($str).'; ?>';
                        }
                        break;
                    case ':': // 输出某个函数的结果
                        $str = '<?php echo '.$str.'; ?>';
                        break;
                    case '~': // 执行某个函数
                        $str = '<?php '.$str.'; ?>';
                        break;
                    case '-':
                    case '+': // 输出计算
                        $str = '<?php echo '.$flag.$str.'; ?>';
                        break;
                    case '/': // 注释标签
                        $flag2  =  substr($str, 0, 1);
                        if ($flag2 == '/' || ($flag2 == '*' && substr(rtrim($str),-2)=='*/'))
                            $str = '';
                        break;
                    default:
                        // 未识别的标签直接返回
                        $str = C('TMPL_L_DELIM') . $tag .C('TMPL_R_DELIM');
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
     * @param string $varStr 变量数据
     * @return string
     */
    public function parseVar($varStr){
        $varStr     =   trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr   =   '';
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            //取得变量名称
            $var = array_shift($varArray);
            if('Think.' == substr($var,0,6)){
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $parseStr = $this->parseThinkVar($var);
            }elseif( false !== strpos($var,'.')) {
                //支持 {$var.property}
                $vars = explode('.',$var);
                $var  =  array_shift($vars);
                switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    case 'array': // 识别为数组
                        $parseStr = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $parseStr .= '["'.$val.'"]';
                        break;
                    case 'obj':  // 识别为对象
                        $parseStr = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $parseStr .= '->'.$val;
                        break;
                    default:  // 自动判断数组或对象 只支持二维
                        $parseStr = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
                }
            }elseif(false !== strpos($var,'[')) {
                //支持 {$var['key']} 方式输出数组
                $parseStr = '$'.$var;
                //preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                //$var = $match[1];
            }elseif(false !==strpos($var,':') && false ===strpos($var,'(') && false ===strpos($var,'::')){
                //支持 {$var:property} 方式输出对象的属性
                //$vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $parseStr = '$'.$var;
                //$var  = $vars[0];
            }else {
                $parseStr = '$'.$var;
            }
            //对变量使用函数
            if(count($varArray)>0)
                $parseStr = $this->parseVarFunction($parseStr,$varArray);
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray  函数列表
     * @return string
     */
    public function parseVarFunction($name,$varArray){
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
        $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
        for($i=0;$i<$length ;$i++ ){
            $args = explode('=',$varArray[$i],2);
            //模板函数过滤
            $fun = trim($args[0]);
            switch($fun) {
            case 'default':  // 特殊模板函数
                $name = '(isset('.$name.') && ('.$name.' !== ""))?('.$name.'):'.$args[1];
                break;
            default:  // 通用模板函数
                if(!in_array($fun,$template_deny_funs)){
                    if(isset($args[1])){
                        if(strstr($args[1],'###')){
                            $args[1] = str_replace('###',$name,$args[1]);
                            $name = "$fun($args[1])";
                        }else{
                            $name = "$fun($name,$args[1])";
                        }
                    }else if(!empty($args[0])){
                        $name = "$fun($name)";
                    }
                }
            }
        }
        return $name;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param string $varStr  变量字符串
     * @return string
     */
    public function parseThinkVar($varStr){
        $vars = explode('.',$varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if(count($vars)>=3){
            $vars[2] = trim($vars[2]);
            switch($vars[1]){
                case 'SERVER':
                    $parseStr = '$_SERVER[\''.strtoupper($vars[2]).'\']';break;
                case 'GET':
                    $parseStr = '$_GET[\''.$vars[2].'\']';break;
                case 'POST':
                    $parseStr = '$_POST[\''.$vars[2].'\']';break;
                case 'COOKIE':
                    if(isset($vars[3])) {
                        $parseStr = '$_COOKIE[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = 'cookie(\''.$vars[2].'\')';
                    }
                    break;
                case 'SESSION':
                    if(isset($vars[3])) {
                        $parseStr = '$_SESSION[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = 'session(\''.$vars[2].'\')';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\''.strtoupper($vars[2]).'\']';break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\''.$vars[2].'\']';break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);break;
                case 'LANG':
                    $parseStr = 'L("'.$vars[2].'")';break;
                case 'CONFIG':
                    if(isset($vars[3])) {
                        $vars[2] .= '.'.$vars[3];
                    }
                    $parseStr = 'C("'.$vars[2].'")';break;
                default:break;
            }
        }else if(count($vars)==2){
            switch($vars[1]){
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = "'".$this->templateFile."'";//'C("TEMPLATE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if(defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access public
     * @param string $tmplPublicName  模板文件名
     * @return string
     */    
    public function parseTemplateName($templateName){
        if(substr($templateName,0,1)=='$')
            //支持加载变量文件名
            $templateName = $this->get(substr($templateName,1));
        $array  =   explode(',',$templateName);
        $parseStr   =   ''; 
        foreach ($array as $templateName){
            if(empty($templateName)) continue;
            if(false === strpos($templateName,$this->config['template_suffix'])) {
                // 解析规则为 模块@主题/控制器/操作
                $templateName   =   T($templateName);
            }
            // 获取模板文件内容
            $parseStr .= file_get_contents($templateName);
        }
        return $parseStr;
    }

    /**
     * 按标签生成正则
     * @access private
     * @param string $tagName  标签名
     * @return string
     */
    private function getRegex($tagName, $tags = null, $close = 1, $lib = '') {
        $begin      =   $this->config['taglib_begin'];
        $end        =   $this->config['taglib_end'];
        $single     =   strlen(ltrim($begin,'\\')) == 1 && strlen(ltrim($end,'\\')) == 1 ? true : false;
        $reg        =   '';
        switch ($tagName) {
            case 'block':
                if ($single) {
                    $reg = $begin.'(?:'.$tagName.'\b(?>(?:(?!name=).)*)\bname=([\'\"])(?<name>[\w\/\:@,]+)\\1(?>[^'.$end.']*)|\/'.$tagName.')'.$end;
                } else {
                    $reg = $begin.'(?:'.$tagName.'\b(?>(?:(?!name=).)*)\bname=([\'\"])(?<name>[\w\/\:@,]+)\\1(?>(?:(?!'.$end.').)*)|\/'.$tagName.')'.$end;
                }
                break;
            case 'literal':
                if ($single) {
                    $reg = '('.$begin.$tagName.'\b(?>[^'.$end.']*)'.$end.')';
                    $reg .= '(?:(?>[^'.$begin.']*)(?>(?!'.$begin.'(?>'.$tagName.'\b[^'.$end.']*|\/'.$tagName.')'.$end.')'.$begin.'[^'.$begin.']*)*)';
                    $reg .= '('.$begin.'\/'.$tagName.$end.')';
                } else {
                    $reg = '('.$begin.$tagName.'\b(?>(?:(?!'.$end.').)*)'.$end.')';
                    $reg .= '(?:(?>(?:(?!'.$begin.').)*)(?>(?!'.$begin.'(?>'.$tagName.'\b(?>(?:(?!'.$end.').)*)|\/'.$tagName.')'.$end.')'.$begin.'(?>(?:(?!'.$begin.').)*))*)';
                    $reg .= '('.$begin.'\/'.$tagName.$end.')';
                }
                break;
            case 'restoreliteral':
                $reg = '<!--###literal(\d+)###-->';
                break;
            case 'include':
                $name = 'file';
            case 'taglib':
            case 'layout':
            case 'extend':
                if (empty($name)) $name = 'name';
                if ($single) {
                    $reg = $begin.$tagName.'\b(?>(?:(?!'.$name.'=).)*)\b'.$name.'=([\'\"])(?<name>[\w\/\:@,\\\\]+)\\1(?>[^'.$end.']*)'.$end;
                } else {
                    $reg = $begin.$tagName.'\b(?>(?:(?!'.$name.'=).)*)\b'.$name.'=([\'\"])(?<name>[\w\/\:@,\\\\]+)\\1(?>(?:(?!'.$end.').)*)'.$end;
                }
                break;
            case 'taglibtag':
                // lib不为空时需给标签名加上前缀
                if (is_array($tags)) {
                    $tagName = !empty($lib) ? $lib.':'.implode('|'.$lib.':', $tags) : implode('|', $tags);
                } else {
                    $tagName = !empty($lib) ? $lib.':'.$tags : $tags;
                }
                if ($single) {
                    if ($close) { // 如果是闭合标签
                        $reg = $begin.'(?:('.$tagName.')\b(?>[^'.$end.']*)|\/('.$tagName.'))'.$end;
                    } else {
                        $reg = $begin.'('.$tagName.')\b(?>[^'.$end.']*)'.$end;
                    } 
                } else {
                    if ($close) { // 如果是闭合标签
                        $reg = $begin.'(?:('.$tagName.')\b(?>(?:(?!'.$end.').)*)|\/('.$tagName.'))'.$end;
                    } else {
                        $reg = $begin.'('.$tagName.')\b(?>(?:(?!'.$end.').)*)'.$end;
                    }
                }
                break;
            case 'tag':
                $begin = $this->config['tmpl_begin'];
                $end = $this->config['tmpl_end'];
                if (strlen(ltrim($begin,'\\')) == 1 && strlen(ltrim($end,'\\')) == 1) {
                    $reg = $begin.'((?:[\$\:\-\+][a-wA-w_][\w\.\:\[\(\*\/\-\+\%_]|\/[\*\/])(?>[^'.$end.']*))'.$end;
                } else {
                    $reg = $begin.'((?:[\$\:\-\+][a-wA-w_][\w\.\:\[\(\*\/\-\+\%_]|\/[\*\/])(?>(?:(?!'.$end.').)*))'.$end;
                }
                break;
        }        
        return '/'.$reg.'/is';
    }

    /**
     * 分析标签属性
     * @access public
     * @param string $attrs  属性字符串
     * @param string $name  不为空时返回指定的属性名
     * @return array
     */
    public function parseAttrs($attrs, $name = null) {
        $reg = '/\s+(?>(?<name>\w+)\s*)=(?>\s*)([\"\'])(?<value>(?:(?!\\2).)*)\\2/is';
        $array = array();
        if (preg_match_all($reg, $attrs, $matches, PREG_SET_ORDER)){
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
