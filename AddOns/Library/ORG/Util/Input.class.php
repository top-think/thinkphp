<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Input.class.php 2207 2011-11-30 13:17:26Z liu21st $

/** 输入数据管理类
 * 使用方法
 *  $Input = Input::getInstance();
 *  $Input->get('name','md5','0');
 *  $Input->session('memberId','','0');
 *
 * 下面总结了一些常用的数据处理方法。以下方法无需考虑magic_quotes_gpc的设置。
 *
 * 获取数据：
 *    如果从$_POST或者$_GET中获取，使用Input::getVar($_POST['field']);，从数据库或者文件就不需要了。
 *    或者直接使用 Input::magicQuotes来消除所有的magic_quotes_gpc转义。
 *
 * 存储过程：
 *    经过Input::getVar($_POST['field'])获得的数据，就是干净的数据，可以直接保存。
 *    如果要过滤危险的html，可以使用 $html = Input::safeHtml($data);
 *
 * 页面显示：
 *    纯文本显示在网页中，如文章标题<title>$data</title>： $data = Input::forShow($field);
 *    HTML 在网页中显示，如文章内容：无需处理。
 *    在网页中以源代码方式显示html：$vo = Input::forShow($html);
 *    纯文本或者HTML在textarea中进行编辑: $vo = Input::forTarea($value);
 *    html在标签中使用，如<input value="数据" /> ，使用 $vo = Input::forTag($value); 或者 $vo = Input::hsc($value);
 *
 * 特殊使用情况：
 *    字符串要在数据库进行搜索： $data = Input::forSearch($field);
 */
class Input extends Think
{

    private $filter =   null;   // 输入过滤
    private static $_input  =   array('get','post','request','env','server','cookie','session','globals','config','lang','call');
    //html标签设置
    public static $htmlTags = array(
        'allow' => 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a',
        'ban' => 'html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml',
    );
    static public function getInstance() {
        return get_instance_of(__CLASS__);
    }

    /**
     +----------------------------------------------------------
     * 魔术方法 有不存在的操作的时候执行
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $type 输入数据类型
     * @param array $args 参数 array(key,filter,default)
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($type,$args=array()) {
        $type    =   strtolower(trim($type));
        if(in_array($type,self::$_input,true)) {
            switch($type) {
                case 'get':      $input      =& $_GET;break;
                case 'post':     $input      =& $_POST;break;
                case 'request': $input      =& $_REQUEST;break;
                case 'env':      $input      =& $_ENV;break;
                case 'server':   $input      =& $_SERVER;break;
                case 'cookie':   $input      =& $_COOKIE;break;
                case 'session':  $input      =& $_SESSION;break;
                case 'globals':   $input      =& $GLOBALS;break;
                case 'files':      $input      =& $_FILES;break;
                case 'call':       $input      =   'call';break;
                case 'config':    $input      =   C();break;
                case 'lang':      $input      =   L();break;
                default:return NULL;
            }
            if('call' === $input) {
                // 呼叫其他方式的输入数据
                $callback    =   array_shift($args);
                $params  =   array_shift($args);
                $data    =   call_user_func_array($callback,$params);
                if(count($args)===0) {
                    return $data;
                }
                $filter =   isset($args[0])?$args[0]:$this->filter;
                if(!empty($filter)) {
                    $data    =   call_user_func_array($filter,$data);
                }
            }else{
                if(0==count($args) || empty($args[0]) ) {
                    return $input;
                }elseif(array_key_exists($args[0],$input)) {
                    // 系统变量
                    $data	 =	 $input[$args[0]];
                    $filter	=	isset($args[1])?$args[1]:$this->filter;
                    if(!empty($filter)) {
                        $data	 =	 call_user_func_array($filter,$data);
                    }
                }else{
                    // 不存在指定输入
                    $data	 =	 isset($args[2])?$args[2]:NULL;
                }
            }
            return $data;
        }
    }

    /**
     +----------------------------------------------------------
     * 设置数据过滤方法
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $filter 过滤方法
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function filter($filter) {
        $this->filter   =   $filter;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 字符MagicQuote转义过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function noGPC()
    {
        if ( get_magic_quotes_gpc() ) {
           $_POST = stripslashes_deep($_POST);
           $_GET = stripslashes_deep($_GET);
           $_COOKIE = stripslashes_deep($_COOKIE);
           $_REQUEST= stripslashes_deep($_REQUEST);
        }
    }

    /**
     +----------------------------------------------------------
     * 处理字符串，以便可以正常进行搜索
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $string 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function forSearch($string)
    {
        return str_replace( array('%','_'), array('\%','\_'), $string );
    }

    /**
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $string 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function forShow($string)
    {
        return self::nl2Br( self::hsc($string) );
    }

    /**
     +----------------------------------------------------------
     * 处理纯文本数据，以便在textarea标签中显示
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function forTarea($string)
    {
        return str_ireplace(array('<textarea>','</textarea>'), array('&lt;textarea>','&lt;/textarea>'), $string);
    }

    /**
     +----------------------------------------------------------
     * 将数据中的单引号和双引号进行转义
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function forTag($string)
    {
        return str_replace(array('"',"'"), array('&quot;','&#039;'), $string);
    }

    /**
     +----------------------------------------------------------
     * 转换文字中的超链接为可点击连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    function makeLink($string)
    {
        $validChars = "a-z0-9\/\-_+=.~!%@?#&;:$\|";
        $patterns = array(
                        "/(^|[^]_a-z0-9-=\"'\/])([a-z]+?):\/\/([{$validChars}]+)/ei",
                        "/(^|[^]_a-z0-9-=\"'\/])www\.([a-z0-9\-]+)\.([{$validChars}]+)/ei",
                        "/(^|[^]_a-z0-9-=\"'\/])ftp\.([a-z0-9\-]+)\.([{$validChars}]+)/ei",
                        "/(^|[^]_a-z0-9-=\"'\/:\.])([a-z0-9\-_\.]+?)@([{$validChars}]+)/ei");
        $replacements = array(
                        "'\\1<a href=\"\\2://\\3\" title=\"\\2://\\3\" rel=\"external\">\\2://'.Input::truncate( '\\3' ).'</a>'",
                        "'\\1<a href=\"http://www.\\2.\\3\" title=\"www.\\2.\\3\" rel=\"external\">'.Input::truncate( 'www.\\2.\\3' ).'</a>'",
                        "'\\1<a href=\"ftp://ftp.\\2.\\3\" title=\"ftp.\\2.\\3\" rel=\"external\">'.Input::truncate( 'ftp.\\2.\\3' ).'</a>'",
                        "'\\1<a href=\"mailto:\\2@\\3\" title=\"\\2@\\3\">'.Input::truncate( '\\2@\\3' ).'</a>'");
        return preg_replace($patterns, $replacements, $string);
    }

    /**
     +----------------------------------------------------------
     * 缩略显示字符串
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     * @param int $length 缩略之后的长度
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function truncate($string, $length = '50')
    {
        if ( empty($string) || empty($length) || strlen($string) < $length ) return $string;
        $len = floor( $length / 2 );
        $ret = substr($string, 0, $len) . " ... ". substr($string, 5 - $len);
        return $ret;
    }

    /**
     +----------------------------------------------------------
     * 把换行转换为<br />标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function nl2Br($string)
    {
        return preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $string);
    }

    /**
     +----------------------------------------------------------
     * 如果 magic_quotes_gpc 为关闭状态，这个函数可以转义字符串
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function addSlashes($string)
    {
        if (!get_magic_quotes_gpc()) {
            $string = addslashes($string);
        }
        return $string;
    }

    /**
     +----------------------------------------------------------
     * 从$_POST，$_GET，$_COOKIE，$_REQUEST等数组中获得数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function getVar($string)
    {
        return Input::stripSlashes($string);
    }

    /**
     +----------------------------------------------------------
     * 如果 magic_quotes_gpc 为开启状态，这个函数可以反转义字符串
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function stripSlashes($string)
    {
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
        return $string;
    }

    /**
     +----------------------------------------------------------
     * 用于在textbox表单中显示html代码
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function hsc($string)
    {
        return preg_replace(array("/&amp;/i", "/&nbsp;/i"), array('&', '&amp;nbsp;'), htmlspecialchars($string, ENT_QUOTES));
    }

    /**
     +----------------------------------------------------------
     * 是hsc()方法的逆操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function undoHsc($text)
    {
        return preg_replace(array("/&gt;/i", "/&lt;/i", "/&quot;/i", "/&#039;/i", '/&amp;nbsp;/i'), array(">", "<", "\"", "'", "&nbsp;"), $text);
    }

    /**
     +----------------------------------------------------------
     * 输出安全的html，用于过滤危险代码
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的字符串
     * @param mixed $tags 允许的标签列表，如 table|td|th|td
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function safeHtml($text, $tags = null)
    {
        $text =  trim($text);
        //完全过滤注释
        $text = preg_replace('/<!--?.*-->/','',$text);
        //完全过滤动态代码
        $text =  preg_replace('/<\?|\?'.'>/','',$text);
        //完全过滤js
        $text = preg_replace('/<script?.*\/script>/','',$text);

        $text =  str_replace('[','&#091;',$text);
        $text = str_replace(']','&#093;',$text);
        $text =  str_replace('|','&#124;',$text);
        //过滤换行符
        $text = preg_replace('/\r?\n/','',$text);
        //br
        $text =  preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
        $text = preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
        //过滤危险的属性，如：过滤on事件lang js
        while(preg_match('/(<[^><]+)(lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1],$text);
        }
        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].$mat[3],$text);
        }
        if( empty($allowTags) ) { $allowTags = self::$htmlTags['allow']; }
        //允许的HTML标签
        $text =  preg_replace('/<('.$allowTags.')( [^><\[\]]*)>/i','[\1\2]',$text);
        //过滤多余html
        if ( empty($banTag) ) { $banTag = self::$htmlTags['ban']; }
        $text =  preg_replace('/<\/?('.$banTag.')[^><]*>/i','',$text);
        //过滤合法的html标签
        while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
        }
        //转换引号
        while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].'|'.$mat[3].'|'.$mat[4],$text);
        }
        //空属性转换
        $text =  str_replace('\'\'','||',$text);
        $text = str_replace('""','||',$text);
        //过滤错误的单个引号
        while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
        }
        //转换其它所有不合法的 < >
        $text =  str_replace('<','&lt;',$text);
        $text = str_replace('>','&gt;',$text);
        $text = str_replace('"','&quot;',$text);
        //反转换
        $text =  str_replace('[','<',$text);
        $text =  str_replace(']','>',$text);
        $text =  str_replace('|','"',$text);
        //过滤多余空格
        $text =  str_replace('  ',' ',$text);
        return $text;
    }

    /**
     +----------------------------------------------------------
     * 删除html标签，得到纯文本。可以处理嵌套的标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $text 要处理的html
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function deleteHtmlTags($string, $br = false)
    {
        while(strstr($string, '>'))
        {
            $currentBeg = strpos($string, '<');
            $currentEnd = strpos($string, '>');
            $tmpStringBeg = @substr($string, 0, $currentBeg);
            $tmpStringEnd = @substr($string, $currentEnd + 1, strlen($string));
            $string = $tmpStringBeg.$tmpStringEnd;
        }
        return $string;
    }

    /**
     +----------------------------------------------------------
     * 处理文本中的换行
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $string 要处理的字符串
     * @param mixed $br 对换行的处理，
     *        false：去除换行；true：保留原样；string：替换成string
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static public function nl2($string, $br = '<br />')
    {
        if ($br == false) {
            $string = preg_replace("/(\015\012)|(\015)|(\012)/", '', $string);
        } elseif ($br != true){
            $string = preg_replace("/(\015\012)|(\015)|(\012)/", $br, $string);
        }
        return $string;
    }
}
?>