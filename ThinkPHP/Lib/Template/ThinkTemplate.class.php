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
 * ThinkPHP内置模板引擎类
 * 支持XML标签和普通标签的模板解析
 * 编译型模板引擎 支持动态缓存
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author liu21st <liu21st@gmail.com>
 */
class  ThinkTemplate {

    // 模板页面中引入的标签库列表
    protected   $tagLib          =   array();
    // 当前模板文件
    protected   $templateFile    =   '';
    // 模板变量
    public      $tVar            =   array();
    public      $config          =   array();
    private     $literal         =   array();

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

    private function stripPreg($str) {
        return str_replace(array('{','}','(',')','|','[',']'),array('\{','\}','\(','\)','\|','\[','\]'),$str);
    }

    // 模板变量获取和设置
    public function get($name) {
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    public function set($name,$value) {
        $this->tVar[$name]= $value;
    }

    // 加载模板
    public function fetch($templateFile,$templateVar) {
        $this->tVar = $templateVar;
        $templateCacheFile  =  $this->loadTemplate($templateFile);
        // 模板阵列变量分解成为独立变量
        extract($templateVar, EXTR_OVERWRITE);
        //载入模版缓存文件
        include $templateCacheFile;
    }

    /**
     * 加载主模板并缓存
     * @access public
     * @param string $tmplTemplateFile 模板文件
     * @return string
     * @throws ThinkExecption
     */
    public function loadTemplate ($tmplTemplateFile) {
        if(is_file($tmplTemplateFile)) {
            $this->templateFile    =  $tmplTemplateFile;
            // 读取模板文件内容
            $tmplContent =  file_get_contents($tmplTemplateFile);
        }else{
            $tmplContent =  $tmplTemplateFile;
        }
         // 根据模版文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'].md5($tmplTemplateFile).$this->config['cache_suffix'];

        // 判断是否启用布局
        if(C('LAYOUT_ON')) {
            if(false !== strpos($tmplContent,'{__NOLAYOUT__}')) { // 可以单独定义不使用布局
                $tmplContent = str_replace('{__NOLAYOUT__}','',$tmplContent);
            }else{ // 替换布局的主体内容
                $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').$this->config['template_suffix'];
                $tmplContent = str_replace($this->config['layout_item'],$tmplContent,file_get_contents($layoutFile));
            }
        }
        //编译模板内容
        $tmplContent = $this->compiler($tmplContent);
        // 检测分组目录
        if(!is_dir($this->config['cache_path']))
            mkdir($this->config['cache_path'],0777,true);
        //重写Cache文件
        if( false === file_put_contents($tmplCacheFile,trim($tmplContent)))
            throw_exception(L('_CACHE_WRITE_ERROR_').':'.$tmplCacheFile);
        return $tmplCacheFile;
    }

    /**
     * 编译模板文件内容
     * @access protected
     * @param mixed $tmplContent 模板内容
     * @return string
     */
    protected function compiler($tmplContent) {
        //模板解析
        $tmplContent =  $this->parse($tmplContent);
        // 还原被替换的Literal标签
        $tmplContent =  preg_replace('/<!--###literal(\d+)###-->/eis',"\$this->restoreLiteral('\\1')",$tmplContent);
        // 添加安全代码
        $tmplContent =  '<?php if (!defined(\'THINK_PATH\')) exit();?>'.$tmplContent;
        if(C('TMPL_STRIP_SPACE')) {
            /* 去除html空格与换行 */
            $find           = array("~>\s+<~","~>(\s+\n|\r)~");
            $replace        = array('><','>');
            $tmplContent    = preg_replace($find, $replace, $tmplContent);
        }
        // 优化生成的php代码
        $tmplContent = str_replace('?><?php','',$tmplContent);
        return strip_whitespace($tmplContent);
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param string $content 要解析的模板内容
     * @return string
     */
    public function parse($content) {
        // 内容为空不解析
        if(empty($content)) return '';
        $begin      =   $this->config['taglib_begin'];
        $end        =   $this->config['taglib_end'];
        // 检查include语法
        $content    =   $this->parseInclude($content);
        // 检查PHP语法
        $content    =   $this->parsePhp($content);
        // 首先替换literal标签内容
        $content    =   preg_replace('/'.$begin.'literal'.$end.'(.*?)'.$begin.'\/literal'.$end.'/eis',"\$this->parseLiteral('\\1')",$content);

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
        //解析普通模板标签 {tagName}
        $content = preg_replace('/('.$this->config['tmpl_begin'].')(\S.+?)('.$this->config['tmpl_end'].')/eis',"\$this->parseTag('\\2')",$content);
        return $content;
    }

    // 检查PHP语法
    protected function parsePhp($content) {
        if(ini_get('short_open_tag')){
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        // PHP语法检查
        if(C('TMPL_DENY_PHP') && false !== strpos($content,'<?php')) {
            throw_exception(L('_NOT_ALLOW_PHP_'));
        }
        return $content;
    }

    // 解析模板中的布局标签
    protected function parseLayout($content) {
        // 读取模板中的布局标签
        $find = preg_match('/'.$this->config['taglib_begin'].'layout\s(.+?)\s*?\/'.$this->config['taglib_end'].'/is',$content,$matches);
        if($find) {
            //替换Layout标签
            $content    =   str_replace($matches[0],'',$content);
            //解析Layout标签
            $layout     =   $matches[1];
            $xml        =   '<tpl><tag '.$layout.' /></tpl>';
            $xml        =   simplexml_load_string($xml);
            if(!$xml)
                throw_exception(L('_XML_TAG_ERROR_'));
            $xml = (array)($xml->tag->attributes());
            $array = array_change_key_case($xml['@attributes']);
            if(!C('LAYOUT_ON') || C('LAYOUT_NAME') !=$array['name'] ) {
                // 读取布局模板
                $layoutFile =   THEME_PATH.$array['name'].$this->config['template_suffix'];
                $replace    =   isset($array['replace'])?$array['replace']:$this->config['layout_item'];
                // 替换布局的主体内容
                $content    =   str_replace($replace,$content,file_get_contents($layoutFile));
            }
        }
        return $content;
    }

    // 解析模板中的include标签
    protected function parseInclude($content) {
        // 解析布局
        $content    =  $this->parseLayout($content);
        // 读取模板中的布局标签
        $find = preg_match_all('/'.$this->config['taglib_begin'].'include\s(.+?)\s*?\/'.$this->config['taglib_end'].'/is',$content,$matches);
        if($find) {
            for($i=0;$i<$find;$i++) {
                $include    =   $matches[1][$i];
                $xml        =   '<tpl><tag '.$include.' /></tpl>';
                $xml        =   simplexml_load_string($xml);
                if(!$xml)   throw_exception(L('_XML_TAG_ERROR_'));
                $xml        =   (array)($xml->tag->attributes());
                $array      =   array_change_key_case($xml['@attributes']);
                $file       =   $array['file'];
                unset($array['file']);
                $content    =   str_replace($matches[0][$i],$this->parseIncludeItem($file,$array),$content);
            }
        }
        return $content;
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param string $content  模板内容
     * @return string|false
     */
    private function parseLiteral($content) {
        if(trim($content)=='')  return '';
        $content            =   stripslashes($content);
        $i                  =   count($this->literal);
        $parseStr           =   "<!--###literal{$i}###-->";
        $this->literal[$i]  =   $content;
        return $parseStr;
    }

    /**
     * 还原被替换的literal标签
     * @access private
     * @param string $tag  literal标签序号
     * @return string|false
     */
    private function restoreLiteral($tag) {
        // 还原literal标签
        $parseStr   =  $this->literal[$tag];
        // 销毁literal记录
        unset($this->literal[$tag]);
        return $parseStr;
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
        $find = preg_match('/'.$this->config['taglib_begin'].'taglib\s(.+?)(\s*?)\/'.$this->config['taglib_end'].'\W/is',$content,$matches);
        if($find) {
            //替换TagLib标签
            $content        = str_replace($matches[0],'',$content);
            //解析TagLib标签
            $tagLibs        = $matches[1];
            $xml            = '<tpl><tag '.$tagLibs.' /></tpl>';
            $xml            = simplexml_load_string($xml);
            if(!$xml)
                throw_exception(L('_XML_TAG_ERROR_'));
            $xml            = (array)($xml->tag->attributes());
            $array          = array_change_key_case($xml['@attributes']);
            $this->tagLib   = explode(',',$array['name']);
        }
        return;
    }

    /**
     * TagLib库解析
     * @access public
     * @param string $tagLib 要解析的标签库
     * @param string $content 要解析的模板内容
     * @param boolen $hide 是否隐藏标签库前缀
     * @return string
     */
    public function parseTagLib($tagLib,&$content,$hide=false) {
        $begin = $this->config['taglib_begin'];
        $end   = $this->config['taglib_end'];
        $className = 'TagLib'.ucwords($tagLib);
        if(!import($className)) {
            if(is_file(EXTEND_PATH.'Driver/TagLib/'.$className.'.class.php')) {
                // 扩展标签库优先识别
                $file   = EXTEND_PATH.'Driver/TagLib/'.$className.'.class.php';
            }else{
                // 系统目录下面的标签库
                $file   = CORE_PATH.'Driver/TagLib/'.$className.'.class.php';
            }
            require_cache($file);
        }
        $tLib =  Think::instance($className);
        foreach ($tLib->getTags() as $name=>$val){
            $tags = array($name);
            if(isset($val['alias'])) {// 别名设置
                $tags = explode(',',$val['alias']);
                $tags[]  =  $name;
            }
            $level = isset($val['level'])?$val['level']:1;
            $closeTag = isset($val['close'])?$val['close']:true;
            foreach ($tags as $tag){
                $parseTag = !$hide? $tagLib.':'.$tag: $tag;// 实际要解析的标签名称
                if(!method_exists($tLib,'_'.$tag)) {
                    // 别名可以无需定义解析方法
                    $tag  =  $name;
                }
                $n1 = empty($val['attr'])?'(\s*?)':'\s([^'.$end.']*)';
                if (!$closeTag){
                    $patterns       = '/'.$begin.$parseTag.$n1.'\/(\s*?)'.$end.'/eis';
                    $replacement    = "\$this->parseXmlTag('$tagLib','$tag','$1','')";
                    $content        = preg_replace($patterns, $replacement,$content);
                }else{
                    $patterns       = '/'.$begin.$parseTag.$n1.$end.'(.*?)'.$begin.'\/'.$parseTag.'(\s*?)'.$end.'/eis';
                    $replacement    = "\$this->parseXmlTag('$tagLib','$tag','$1','$2')";
                    for($i=0;$i<$level;$i++) 
                        $content=preg_replace($patterns,$replacement,$content);
                }
            }
        }
    }

    /**
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     * @access public
     * @param string $tagLib  标签库名称
     * @param string $tag  标签名
     * @param string $attr  标签属性
     * @param string $content  标签内容
     * @return string|false
     */
    public function parseXmlTag($tagLib,$tag,$attr,$content) {
        //if (MAGIC_QUOTES_GPC) {
            $attr   = stripslashes($attr);
            $content= stripslashes($content);
        //}
        if(ini_get('magic_quotes_sybase'))
            $attr   =  str_replace('\"','\'',$attr);
        $tLib       =  Think::instance('TagLib'.ucwords(strtolower($tagLib)));
        $parse      = '_'.$tag;
        $content    = trim($content);
        return $tLib->$parse($attr,$content);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param string $tagStr 标签内容
     * @return string
     */
    public function parseTag($tagStr){
        //if (MAGIC_QUOTES_GPC) {
            $tagStr = stripslashes($tagStr);
        //}
        //还原非模板标签
        if(preg_match('/^[\s|\d]/is',$tagStr))
            //过滤空格和数字打头的标签
            return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
        $flag   =  substr($tagStr,0,1);
        $name   = substr($tagStr,1);
        if('$' == $flag){ //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        }elseif('-' == $flag || '+'== $flag){ // 输出计算
            return  '<?php echo '.$flag.$name.';?>';
        }elseif(':' == $flag){ // 输出某个函数的结果
            return  '<?php echo '.$name.';?>';
        }elseif('~' == $flag){ // 执行某个函数
            return  '<?php '.$name.';?>';
        }elseif(substr($tagStr,0,2)=='//' || (substr($tagStr,0,2)=='/*' && substr($tagStr,-2)=='*/')){
            //注释标签
            return '';
        }
        // 未识别的标签直接返回
        return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
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
        $varExists  =   true;
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            //取得变量名称
            $var = array_shift($varArray);
            //非法变量过滤 不允许在变量里面使用 ->
            //TODO：还需要继续完善
            //if(preg_match('/->/is',$var))   return '';
            if('Think.' == substr($var,0,6)){
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseThinkVar($var);
            }elseif( false !== strpos($var,'.')) {
                //支持 {$var.property}
                $vars = explode('.',$var);
                $var  =  array_shift($vars);
                switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    case 'array': // 识别为数组
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '["'.$val.'"]';
                        break;
                    case 'obj':  // 识别为对象
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '->'.$val;
                        break;
                    default:  // 自动判断数组或对象 只支持二维
                        $name = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
                }
            }elseif(false !== strpos($var,'[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$".$var;
                preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                $var = $match[1];
            }elseif(false !==strpos($var,':') && false ===strpos($var,'::') && false ===strpos($var,'?')){
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $name = "$".$var;
                $var  = $vars[0];
            }else {
                $name = "$$var";
            }
            //对变量使用函数
            if(count($varArray)>0)
                $name = $this->parseVarFunction($name,$varArray);
            $parseStr = '<?php echo ('.$name.'); ?>';
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
            $fun = strtolower(trim($args[0]));
            switch($fun) {
            case 'default':  // 特殊模板函数
                $name   = '('.$name.')?('.$name.'):'.$args[1];
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
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     * @access public
     * @param string $tmplPublicName  公共模板文件名
     * @param array $vars  要传递的变量列表
     * @return string
     */
    protected function parseIncludeItem($tmplPublicName,$vars=array()){
        if(substr($tmplPublicName,0,1)=='$')
            //支持加载变量文件名
            $tmplPublicName = $this->get(substr($tmplPublicName,1));
        $array  =   explode(',',$tmplPublicName);
        $parseStr   =   '';
        foreach ($array as $tmplPublicName){
            if(false === strpos($tmplPublicName,$this->config['template_suffix'])) {
                // 解析规则为 模板主题:模块:操作 不支持 跨项目和跨分组调用
                $path   =  explode(':',$tmplPublicName);
                $action = array_pop($path);
                $module = !empty($path)?array_pop($path):MODULE_NAME;
                if(!empty($path)) {// 设置模板主题
                    $path = dirname(THEME_PATH).'/'.array_pop($path).'/';
                }else{
                    $path = THEME_PATH;
                }
                $depr = defined('GROUP_NAME')?C('TMPL_FILE_DEPR'):'/';
                $tmplPublicName  =  $path.$module.$depr.$action.$this->config['template_suffix'];
            }
            // 获取模板文件内容
            $parseStr .= file_get_contents($tmplPublicName);
        }
        foreach ($vars as $key=>$val) {
            $parseStr = str_replace('['.$key.']',$val,$parseStr);
        }
        //再次对包含文件进行模板分析
        return $this->parseInclude($parseStr);
    }
}