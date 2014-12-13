<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ThinkTemplateCompiler.class.php 2701 2012-02-02 12:27:51Z liu21st $

class ThinkTemplateCompiler {
    protected $literal  =  array();  //Literal缓存
    protected $templateFile = '';   // 模板文件名
    protected $tpl = null; // 模板引擎实例
    protected $comparison = array(' nheq '=>' !== ',' heq '=>' === ',' neq '=>' != ',' eq '=>' == ',' egt '=>' >= ',' gt '=>' > ',' elt '=>' <= ',' lt '=>' < ');

    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'php'=>array('attr'=>'','close'=>0),
        'volist'=>array('attr'=>'name,id,offset,length,key,mod','level'=>3,'alias'=>'iterate'),
        'include'=>array('attr'=>'file','close'=>0),
        'if'=>array('attr'=>'condition'),
        'elseif'=>array('attr'=>'condition'),
        'else'=>array('attr'=>'','close'=>0),
        'switch'=>array('attr'=>'name','level'=>3),
        'case'=>array('attr'=>'value,break'),
        'default'=>array('attr'=>'','close'=>0),
        'compare'=>array('attr'=>'name,value,type','level'=>3,'alias'=>'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq'),
        'range'=>array('attr'=>'name,value,type','level'=>3,'alias'=>'in,notin'),
        'empty'=>array('attr'=>'name','level'=>3),
        'notempty'=>array('attr'=>'name','level'=>3),
        'present'=>array('attr'=>'name','level'=>3),
        'notpresent'=>array('attr'=>'name','level'=>3),
        'defined'=>array('attr'=>'name','level'=>3),
        'notdefined'=>array('attr'=>'name','level'=>3),
        'import'=>array('attr'=>'file,href,type,value,basepath','close'=>0,'alias'=>'load,css,js'),
        );

    // 构造方法
    public function __construct() {
        $this->tpl       = Think::instance('ThinkTemplateLite');
    }
    // 模板编译
    public function parse($tmplContent,$templateFile){
        $this->templateFile = $templateFile;
        //模板标签解析
        $tmplContent = $this->parseTag($tmplContent);
        // 还原被替换的Literal标签
        $tmplContent = preg_replace('/<!--###literal(\d)###-->/eis',"\$this->restoreLiteral('\\1')",$tmplContent);
        // 添加安全代码
        $tmplContent  =  '<?php if (!defined(\'THINK_PATH\')) exit();?>'.$tmplContent;
        if(C('TMPL_STRIP_SPACE')) {
            /* 去除html空格与换行 */
            $find     = array("~>\s+<~","~>(\s+\n|\r)~");
            $replace  = array("><",">");
            $tmplContent = preg_replace($find, $replace, $tmplContent);
        }
        return trim($tmplContent);
    }

    // 检查PHP语法
    protected function parsePhp($content) {
        // PHP语法检查
        if(C('TMPL_DENY_PHP') && false !== strpos($content,'<?php')) {
            throw_exception(L('_NOT_ALLOW_PHP_'));
        }elseif(ini_get('short_open_tag')){
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        return $content;
    }

    // 解析变量
    protected function parseVar($varStr) {
        $varStr = trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr ='';
        $varExists = true;
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            //取得变量名称
            $var = array_shift($varArray);
            //非法变量过滤 不允许在变量里面使用 ->
            //TODO：还需要继续完善
            if(preg_match('/->/is',$var))  return '';
            if('Think.' == substr($var,0,6)){
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseThink($var);
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
            }elseif(false !==strpos($var,':')){
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $name = "$".$var;
                $var  = $vars[0];
            }elseif(false !== strpos($var,'[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$".$var;
                preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                $var = $match[1];
            }else {
                $name = "$$var";
            }
            //对变量使用函数
            if(count($varArray)>0)
                $name = $this->parseFun($name,$varArray);
            $parseStr = '<?php echo ('.$name.'); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }
    // 解析变量函数
    protected function parseFun($name,$varArray) {
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
        $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
        for($i=0;$i<$length ;$i++ ){
            if (0===stripos($varArray[$i],'default='))
                $args = explode('=',$varArray[$i],2);
            else
                $args = explode('=',$varArray[$i]);
            //模板函数过滤
            $args[0] = trim($args[0]);
            switch(strtolower($args[0])) {
            case 'default':  // 特殊模板函数
                $name   = '('.$name.')?('.$name.'):'.$args[1];
                break;
            default:  // 通用模板函数
                if(!in_array($args[0],$template_deny_funs)){
                    if(isset($args[1])){
                        if(strstr($args[1],'###')){
                            $args[1] = str_replace('###',$name,$args[1]);
                            $name = "$args[0]($args[1])";
                        }else{
                            $name = "$args[0]($name,$args[1])";
                        }
                    }else if(!empty($args[0])){
                        $name = "$args[0]($name)";
                    }
                }
            }
        }
        return $name;
    }

    // 解析包含标签
    protected function parseInclude($tmplPublicName,$vars=array()) {
        if(substr($tmplPublicName,0,1)=='$')
            //支持加载变量文件名
            $tmplPublicName = $this->tpl->get(substr($tmplPublicName,1));
        if(is_file($tmplPublicName)) {
            // 直接包含文件
            $parseStr = file_get_contents($tmplPublicName);
        }else {
            $tmplPublicName = trim($tmplPublicName);
            if(strpos($tmplPublicName,'@')){
                // 引入其它模块的操作模板
                $tmplTemplateFile   =   dirname(dirname(dirname($this->templateFile))).'/'.str_replace(array('@',':'),'/',$tmplPublicName);
            }elseif(strpos($tmplPublicName,':')){
                // 引入其它模块的操作模板
                $tmplTemplateFile   =   dirname(dirname($this->templateFile)).'/'.str_replace(':','/',$tmplPublicName);
            }else{
                // 默认导入当前模块下面的模板
                $tmplTemplateFile = dirname($this->templateFile).'/'.$tmplPublicName;
            }
            $tmplTemplateFile .=  $this->tpl->template_suffix;
            $parseStr = file_get_contents($tmplTemplateFile);
        }
        foreach ($vars as $key=>$val) {
            $parseStr = str_replace('['.$key.']',$val,$parseStr);
        }
        //再次对包含文件进行模板分析
        return $this->parseTag($parseStr);
    }
    // 解析特殊变量
    protected function parseThink($varStr) {
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
                        $parseStr = '$_COOKIE[\''.$vars[2].'\']';
                    }break;
                case 'SESSION':
                    if(isset($vars[3])) {
                        $parseStr = '$_SESSION[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = '$_SESSION[\''.$vars[2].'\']';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\''.$vars[2].'\']';break;
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
                    $parseStr = 'C("TMPL_FILE_NAME")';
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
    // 解析标签
    protected function parseTag($content) {
        // 检查PHP语法
        $content  =  $this->parsePhp($content);
        // 解析XML标签
        $this->parseXmlTag($content);
        // 解析普通标签 {tagName:}
        $begin = $this->tpl->tmpl_begin;
        $end   = $this->tpl->tmpl_end;
        $content = preg_replace('/('.$begin.')(\S.+?)('.$end.')/eis',"\$this->parseCommonTag('\\2')",$content);
        return $content;
    }
    protected function parseCommonTag($tagStr) {
        //if (MAGIC_QUOTES_GPC) {
            $tagStr = stripslashes($tagStr);
        //}
        //还原非模板标签
        if(preg_match('/^[\s|\d]/is',$tagStr))
            //过滤空格和数字打头的标签
            return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
        $flag =  substr($tagStr,0,1);
        $name   = substr($tagStr,1);
        if('$' == $flag){ //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        }elseif(':' == $flag){// 输出某个函数的结果
            return  '<?php echo '.$name.';?>';
        }elseif('~' == $flag){ // 执行某个函数
            return  '<?php '.$name.';?>';
        }elseif('&' == $flag){ // 输出配置参数
            return '<?php echo C("'.$name.'");?>';
        }elseif('%' == $flag){ // 输出语言变量
            return '<?php echo L("'.$name.'");?>';
		}elseif('@' == $flag){// 输出SESSION变量
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_SESSION["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_SESSION["'.$name.'"];?>';
            }
		}elseif('#' == $flag){	// 输出COOKIE变量
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_COOKIE["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_COOKIE["'.$name.'"];?>';
            }
		}elseif('.' == $flag){ // 输出GET变量
            return '<?php echo $_GET["'.$name.'"];?>';
        }elseif('^' == $flag){ // 输出POST变量
            return '<?php echo $_POST["'.$name.'"];?>';
        }elseif('*' == $flag){ // 输出常量
            return '<?php echo constant("'.$name.'");?>';
        }
        $tagStr = trim($tagStr);
        if(substr($tagStr,0,2)=='//' || (substr($tagStr,0,2)=='/*' && substr($tagStr,-2)=='*/'))
            //注释标签
            return '';
        return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
    }
    // 解析XML标签
    protected function parseXmlTag(&$content) {
        $begin = $this->tpl->taglib_begin;
        $end   = $this->tpl->taglib_end;
        foreach ($this->tags as $tag=>$val){
            if(isset($val['alias'])) {// 别名设置
                $tags = explode(',',$val['alias']);
                $tags[]  =  $tag;
            }else{
                $tags[] = $tag;
            }
            $level = isset($val['level'])?$val['level']:1;
            $closeTag = isset($val['close'])?$val['close']:true;
            foreach ($tags as $tag){
                if(!$closeTag) {
                    $content = preg_replace('/'.$begin.$tag.'\s(.*?)\/(\s*?)'.$end.'/eis',"\$this->parseXmlItem('$tag','\\1','')",$content);
                }else{
                    for($i=0;$i<$level;$i++)
                        $content = preg_replace('/'.$begin.$tag.'\s(.*?)'.$end.'(.+?)'.$begin.'\/'.$tag.'(\s*?)'.$end.'/eis',"\$this->parseXmlItem('$tag','\\1','\\2')",$content);
                }
            }
        }
    }
    // 解析某个标签
    protected function parseXmlItem($tag,$attr,$content) {
        $attr = stripslashes($attr);
        $content = stripslashes(trim($content));
        $fun  =  '_'.$tag;
        return $this->$fun($attr,$this->parseTag($content));
    }
    // literal标签
    protected function parseLiteral($content) {
        if(trim($content)=='')
            return '';
        $content = stripslashes($content);
        $i  =   count($this->literal);
        $parseStr   =   "<!--###literal{$i}###-->";
        $this->literal[$i]  = $content;
        return $parseStr;
    }
    protected function restoreLiteral($tag) {
        // 还原literal标签
        $parseStr   =  $this->literal[$tag];
        // 销毁literal记录
        unset($this->literal[$tag]);
        return $parseStr;
    }
    // 条件解析
    public function parseCondition($condition) {
        $condition = str_ireplace(array_keys($this->comparison),array_values($this->comparison),$condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is','$\\1->\\2 ',$condition);
        switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
            case 'array': // 识别为数组
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1["\\2"] ',$condition);
                break;
            case 'obj':  // 识别为对象
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1->\\2 ',$condition);
                break;
            default:  // 自动判断数组或对象 只支持二维
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ',$condition);
        }
        return $condition;
    }
    // 创建变量
    public function autoBuildVar($name) {
        if('Think.' == substr($name,0,6)){
            // 特殊变量
            return $this->parseThink($name);
        }elseif(strpos($name,'.')) {
            $vars = explode('.',$name);
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
        }elseif(strpos($name,':')){
            // 额外的对象方式支持
            $name   =   '$'.str_replace(':','->',$name);
        }elseif(!defined($name)) {
            $name = '$'.$name;
        }
        return $name;
    }
    // 解析标签属性
    public function parseXmlAttr($attr,$tag) {
        //XML解析安全过滤
        $attr = str_replace('&','___', $attr);
        $xml =  '<tpl><tag '.$attr.' /></tpl>';
        $xml = simplexml_load_string($xml);
        if(!$xml) {
            throw_exception(L('_XML_TAG_ERROR_').' : '.$attr);
        }
        $xml = (array)($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        if($array) {
            $attrs  = explode(',',$this->tags[strtolower($tag)]['attr']);
            foreach($attrs as $name) {
                if( isset($array[$name])) {
                    $array[$name] = str_replace('___','&',$array[$name]);
                }
            }
            return $array;
        }
    }
    //---------------标签解析方法----------------------
    // php
    public function _php($attr,$content) {
        $parseStr = '<?php '.$content.' ?>';
        return $parseStr;
    }
    // include
    public function _include($attr,$content) {
        $tag    = $this->parseXmlAttr($attr,'include');
        $file   =   $tag['file'];
        unset($tag['file']);
        return $this->parseInclude($file,$tag);
    }
    // volist
    public function _volist($attr,$content) {
        static $_iterateParseCache = array();
        //如果已经解析过，则直接返回变量值
        $cacheIterateId = md5($attr.$content);
        if(isset($_iterateParseCache[$cacheIterateId]))
            return $_iterateParseCache[$cacheIterateId];
        $tag      = $this->parseXmlAttr($attr,'volist');
        $name   = $tag['name'];
        $id        = $tag['id'];
        $empty  = isset($tag['empty'])?$tag['empty']:'';
        $key     =   !empty($tag['key'])?$tag['key']:'i';
        $mod    =   isset($tag['mod'])?$tag['mod']:'2';
        $name   = $this->autoBuildVar($name);
        $parseStr  =  '<?php if(is_array('.$name.')): $'.$key.' = 0;';
		if(isset($tag['length']) && '' !=$tag['length'] ) {
			$parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].','.$tag['length'].');';
		}elseif(isset($tag['offset'])  && '' !=$tag['offset']){
            $parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].');';
        }else{
            $parseStr .= ' $__LIST__ = '.$name.';';
        }
        $parseStr .= 'if( count($__LIST__)==0 ) : echo "'.$empty.'" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach($__LIST__ as $key=>$'.$id.'): ';
        $parseStr .= '++$'.$key.';';
        $parseStr .= '$mod = ($'.$key.' % '.$mod.' )?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; else: echo "'.$empty.'" ;endif; ?>';
        $_iterateParseCache[$cacheIterateId] = $parseStr;

        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }
    // foreach
    public function _foreach($attr,$content) {
        static $_iterateParseCache = array();
        //如果已经解析过，则直接返回变量值
        $cacheIterateId = md5($attr.$content);
        if(isset($_iterateParseCache[$cacheIterateId]))
            return $_iterateParseCache[$cacheIterateId];
        $tag   = $this->parseXmlAttr($attr,'foreach');
        $name= $tag['name'];
        $item  = $tag['item'];
        $key   =   !empty($tag['key'])?$tag['key']:'key';
        $name= $this->autoBuildVar($name);
        $parseStr  =  '<?php if(is_array('.$name.')): foreach('.$name.' as $'.$key.'=>$'.$item.'): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; ?>';
        $_iterateParseCache[$cacheIterateId] = $parseStr;
        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }
    // if
    public function _if($attr,$content) {
        $tag          = $this->parseXmlAttr($attr,'if');
        $condition   = $this->parseCondition($tag['condition']);
        $parseStr  = '<?php if('.$condition.'): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    public function _elseif($attr,$content) {
        $tag          = $this->parseXmlAttr($attr,'elseif');
        $condition   = $this->parseCondition($tag['condition']);
        $parseStr   = '<?php elseif('.$condition.'): ?>';
        return $parseStr;
    }
    public function _else($attr) {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }
    // switch
    public function _switch($attr,$content) {
        $tag = $this->parseXmlAttr($attr,'switch');
        $name = $tag['name'];
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        $parseStr = '<?php switch('.$name.'): ?>'.$content.'<?php endswitch;?>';
        return $parseStr;
    }
    // case
    public function _case($attr,$content) {
        $tag = $this->parseXmlAttr($attr,'case');
        $value = $tag['value'];
        if('$' == substr($value,0,1)) {
            $varArray = explode('|',$value);
            $value	=	array_shift($varArray);
            $value  =  $this->autoBuildVar(substr($value,1));
            if(count($varArray)>0)
                $value = $this->parseFun($value,$varArray);
            $value   =  'case '.$value.': ';
        }elseif(strpos($value,'|')){
            $values  =  explode('|',$value);
            $value   =  '';
            foreach ($values as $val){
                $value   .=  'case "'.addslashes($val).'": ';
            }
        }else{
            $value	=	'case "'.$value.'": ';
        }
        $parseStr = '<?php '.$value.' ?>'.$content;
        if('' ==$tag['break'] || $tag['break']) {
            $parseStr .= '<?php break;?>';
        }
        return $parseStr;
    }
    // default
    public function _default($attr) {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }
    // compare
    public function _compare($attr,$content,$type='eq') {
        $tag      = $this->parseXmlAttr($attr,'compare');
        $name   = $tag['name'];
        $value   = $tag['value'];
        $type    =   $tag['type']?$tag['type']:$type;
        $type    =   $this->parseCondition(' '.$type.' ');
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        if('$' == substr($value,0,1)) {
            $value  =  $this->autoBuildVar(substr($value,1));
        }else {
            $value  =   '"'.$value.'"';
        }
        $parseStr = '<?php if(('.$name.') '.$type.' '.$value.'): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    // range
    public function _range($attr,$content,$type='in') {
        $tag      = $this->parseXmlAttr($attr,'range');
        $name   = $tag['name'];
        $value   = $tag['value'];
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        $type    =   $tag['type']?$tag['type']:$type;
        $fun  =  ($type == 'in')? 'in_array'    :   '!in_array';
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        if('$' == substr($value,0,1)) {
            $value  =  $this->autoBuildVar(substr($value,1));
            $parseStr = '<?php if('.$fun.'(('.$name.'), is_array('.$value.')?'.$value.':explode(\',\','.$value.'))): ?>'.$content.'<?php endif; ?>';
        }else{
            $value  =   '"'.$value.'"';
            $parseStr = '<?php if('.$fun.'(('.$name.'), explode(\',\','.$value.'))): ?>'.$content.'<?php endif; ?>';
        }
        return $parseStr;
    }
    public function _in($attr,$content) {
        return $this->_range($attr,$content,'in');
    }
    // range标签的别名 用于notin判断
    public function _notin($attr,$content) {
        return $this->_range($attr,$content,'notin');
    }
    // present
    public function _present($attr,$content) {
        $tag      = $this->parseXmlAttr($attr,'present');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(isset('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    public function _notpresent($attr,$content) {
        $tag      = $this->parseXmlAttr($attr,'present');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(!isset('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    // empty
    public function _empty($attr,$content) {
        $tag      = $this->parseXmlAttr($attr,'empty');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(empty('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    public function _notempty($attr,$content) {
        $tag      = $this->parseXmlAttr($attr,'empty');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(!empty('.$name.')): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    // define
    public function _defined($attr,$content) {
        $tag        = $this->parseXmlAttr($attr,'defined');
        $name     = $tag['name'];
        $parseStr = '<?php if(defined("'.$name.'")): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    public function _notdefined($attr,$content) {
        $tag        = $this->parseXmlAttr($attr,'defined');
        $name     = $tag['name'];
        $parseStr = '<?php if(!defined("'.$name.'")): ?>'.$content.'<?php endif; ?>';
        return $parseStr;
    }
    // import
    public function _import($attr,$content,$isFile=false,$type='') {
        $tag  = $this->parseXmlAttr($attr,'import');
        $file   = $tag['file']?$tag['file']:$tag['href'];
        $parseStr = '';
        $endStr   = '';
        // 判断是否存在加载条件 允许使用函数判断(默认为isset)
        if ($tag['value']) {
            $varArray  = explode('|',$tag['value']);
            $name      = array_shift($varArray);
            $name      = $this->autoBuildVar($name);
            if (!empty($varArray))
                $name  = $this->parseFun($name,$varArray);
            else
                $name  = 'isset('.$name.')';
            $parseStr .= '<?php if('.$name.'): ?>';
            $endStr    = '<?php endif; ?>';
        }
        if($isFile) {
            // 根据文件名后缀自动识别
            $type       = $type?$type:(!empty($tag['type'])?strtolower($tag['type']):strtolower(substr(strrchr($file, '.'),1)));
            // 文件方式导入
            $array =  explode(',',$file);
            foreach ($array as $val){
                switch($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="'.$val.'"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';
                    break;
                case 'php':
                    $parseStr .= '<?php require_cache("'.$val.'"); ?>';
                    break;
                }
            }
        }else{
            // 命名空间导入模式 默认是js
            $type       = $type?$type:(!empty($tag['type'])?strtolower($tag['type']):'js');
            $basepath   = !empty($tag['basepath'])?$tag['basepath']:__ROOT__.'/Public';
            // 命名空间方式导入外部文件
            $array =  explode(',',$file);
            foreach ($array as $val){
                switch($type) {
                case 'js':
                    $parseStr .= "<script type='text/javascript' src='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.js'."'></script> ";
                    break;
                case 'css':
                    $parseStr .= "<link rel='stylesheet' type='text/css' href='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.css'."' />";
                    break;
                case 'php':
                    $parseStr .= '<?php import("'.$val.'"); ?>';
                    break;
                }
            }
        }
        return $parseStr.$endStr;
    }
    public function _iterate($attr,$content) {
        return $this->_volist($attr,$content);
    }
    public function _eq($attr,$content) {
        return $this->_compare($attr,$content,'eq');
    }
    public function _equal($attr,$content) {
        return $this->_eq($attr,$content);
    }
    public function _neq($attr,$content) {
        return $this->_compare($attr,$content,'neq');
    }
    public function _notequal($attr,$content) {
        return $this->_neq($attr,$content);
    }
    public function _gt($attr,$content) {
        return $this->_compare($attr,$content,'gt');
    }
    public function _lt($attr,$content) {
        return $this->_compare($attr,$content,'lt');
    }
    public function _egt($attr,$content) {
        return $this->_compare($attr,$content,'egt');
    }
    public function _elt($attr,$content) {
        return $this->_compare($attr,$content,'elt');
    }
    public function _heq($attr,$content) {
        return $this->_compare($attr,$content,'heq');
    }
    public function _nheq($attr,$content) {
        return $this->_compare($attr,$content,'nheq');
    }
    public function _load($attr,$content) {
        return $this->_import($attr,$content,true);
    }
    // import别名使用 导入css文件 <css file="__PUBLIC__/Css/Base.css" />
    public function _css($attr,$content) {
        return $this->_import($attr,$content,true,'css');
    }
    // import别名使用 导入js文件 <js file="__PUBLIC__/Js/Base.js" />
    public function _js($attr,$content) {
        return $this->_import($attr,$content,true,'js');
    }

}
?>