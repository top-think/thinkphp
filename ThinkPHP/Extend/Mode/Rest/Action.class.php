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
 * ThinkPHP RESTFul 控制器基类 抽象类
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author   liu21st <liu21st@gmail.com>
 */
abstract class Action {

    // 当前Action名称
    private $name =  '';
    // 视图实例
    protected $view   =  null;
    protected $_method =  ''; // 当前请求类型
    protected $_type = ''; // 当前资源类型
    // 输出类型
    protected $_types = array();

   /**
     * 架构函数 取得模板对象实例
     * @access public
     */
    public function __construct() {
        //实例化视图类
        $this->view       = Think::instance('View');

        defined('__EXT__') or define('__EXT__','');
        if(''== __EXT__ || false === stripos(C('REST_CONTENT_TYPE_LIST'),__EXT__)) {
            // 资源类型没有指定或者非法 则用默认资源类型访问
            $this->_type   =  C('REST_DEFAULT_TYPE');
        }else{
            $this->_type   =  __EXT__;
        }

        // 请求方式检测
        $method  =  strtolower($_SERVER['REQUEST_METHOD']);
        if(false === stripos(C('REST_METHOD_LIST'),$method)) {
            // 请求方式非法 则用默认请求方法
            $method = C('REST_DEFAULT_METHOD');
        }
        $this->_method = $method;
        // 允许输出的资源类型
        $this->_types  = C('REST_OUTPUT_TYPE');

        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
    }

   /**
     * 获取当前Action名称
     * @access protected
     */
    protected function getActionName() {
        if(empty($this->name)) {
            // 获取Action名称
            $this->name     =   substr(get_class($this),0,-6);
        }
        return $this->name;
    }

    /**
     * 是否AJAX请求
     * @access protected
     * @return bool
     */
    protected function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
            // 判断Ajax方式提交
            return true;
        return false;
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method,$args) {
        if( 0 === strcasecmp($method,ACTION_NAME.C('ACTION_SUFFIX'))) {
            if(method_exists($this,$method.'_'.$this->_method.'_'.$this->_type)) { // RESTFul方法支持
                $fun  =  $method.'_'.$this->_method.'_'.$this->_type;
                $this->$fun();
            }elseif($this->_method == C('REST_DEFAULT_METHOD') && method_exists($this,$method.'_'.$this->_type) ){
                $fun  =  $method.'_'.$this->_type;
                $this->$fun();
            }elseif($this->_type == C('REST_DEFAULT_TYPE') && method_exists($this,$method.'_'.$this->_method) ){
                $fun  =  $method.'_'.$this->_method;
                $this->$fun();
            }elseif(method_exists($this,'_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method,$args);
            }elseif(file_exists_case(C('TMPL_FILE_NAME'))){
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }else{
                // 抛出异常
                throw_exception(L('_ERROR_ACTION_').ACTION_NAME);
            }
        }else{
            switch(strtolower($method)) {
                // 获取变量 支持过滤和默认值 调用方式 $this->_post($key,$filter,$default);
                case '_get': $input =& $_GET;break;
                case '_post':$input =& $_POST;break;
                case '_put':
                case '_delete':parse_str(file_get_contents('php://input'), $input);break;
                case '_request': $input =& $_REQUEST;break;
                case '_session': $input =& $_SESSION;break;
                case '_cookie':  $input =& $_COOKIE;break;
                case '_server':  $input =& $_SERVER;break;
                default:
                    throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            }
            if(isset($input[$args[0]])) { // 取值操作
                $data	 =	 $input[$args[0]];
                $fun  =  $args[1]?$args[1]:C('DEFAULT_FILTER');
                $data	 =	 $fun($data); // 参数过滤
            }else{ // 变量默认值
                $data	 =	 isset($args[2])?$args[2]:NULL;
            }
            return $data;
        }
    }

    /**
     * 模板显示
     * 调用内置的模板引擎显示方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @return void
     */
    protected function display($templateFile='',$charset='',$contentType='') {
        $this->view->display($templateFile,$charset,$contentType);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name,$value='') {
        $this->view->assign($name,$value);
    }

    public function __set($name,$value) {
        $this->view->assign($name,$value);
    }

    /**
     * 设置页面输出的CONTENT_TYPE和编码
     * @access public
     * @param string $type content_type 类型对应的扩展名
     * @param string $charset 页面输出编码
     * @return void
     */
    public function setContentType($type, $charset=''){
        if(headers_sent()) return;
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        $type = strtolower($type);
        if(isset($this->_types[$type])) //过滤content_type
            header('Content-Type: '.$this->_types[$type].'; charset='.$charset);
    }

    /**
     * 输出返回数据
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type 返回类型 JSON XML
     * @param integer $code HTTP状态
     * @return void
     */
    protected function response($data,$type='',$code=200) {
        $this->sendHttpStatus($code);
        exit($this->encodeData($data,strtolower($type)));
    }

    /**
     * 编码数据
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type 返回类型 JSON XML
     * @return void
     */
    protected function encodeData($data,$type='') {
        if(empty($data))  return '';
        if(empty($type)) $type =  $this->_type;
        if('json' == $type) {
            // 返回JSON数据格式到客户端 包含状态信息
            $data = json_encode($data);
        }elseif('xml' == $type){
            // 返回xml格式数据
            $data = xml_encode($data);
        }elseif('php'==$type){
            $data = serialize($data);
        }// 默认直接输出
        $this->setContentType($type);
        header('Content-Length: ' . strlen($data));
        return $data;
    }

    // 发送Http状态信息
    protected function sendHttpStatus($code) {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if(isset($_status[$code])) {
            header('HTTP/1.1 '.$code.' '.$_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:'.$code.' '.$_status[$code]);
        }
    }

   /**
     * 析构方法
     * @access public
     */
    public function __destruct() {
        // 保存日志
        if(C('LOG_RECORD')) Log::save();
        // 执行后续操作
        tag('action_end');
    }
}
