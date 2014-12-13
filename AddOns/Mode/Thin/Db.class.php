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
// $Id: Db.class.php 2701 2012-02-02 12:27:51Z liu21st $

define('CLIENT_MULTI_RESULTS', 131072);
/**
 +------------------------------------------------------------------------------
 * ThinkPHP 简洁模式数据库中间层实现类
 * 只支持mysql
 +------------------------------------------------------------------------------
 */
class Db extends Think
{

    static private $_instance = null;
    // 是否自动释放查询结果
    protected $autoFree         = false;
    // 是否显示调试信息 如果启用会在日志文件记录sql语句
    public $debug             = false;
    // 是否使用永久连接
    protected $pconnect         = false;
    // 当前SQL指令
    protected $queryStr          = '';
    // 最后插入ID
    protected $lastInsID         = null;
    // 返回或者影响记录数
    protected $numRows        = 0;
    // 返回字段数
    protected $numCols          = 0;
    // 事务指令数
    protected $transTimes      = 0;
    // 错误信息
    protected $error              = '';
    // 当前连接ID
    protected $linkID            =   null;
    // 当前查询ID
    protected $queryID          = null;
    // 是否已经连接数据库
    protected $connected       = false;
    // 数据库连接参数配置
    protected $config             = '';
    // 数据库表达式
    protected $comparison      = array('eq'=>'=','neq'=>'!=','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
    // 查询表达式
    protected $selectSql  =     'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config=''){
        if ( !extension_loaded('mysql') ) {
            throw_exception(L('_NOT_SUPPERT_').':mysql');
        }
        $this->config   =   $this->parseConfig($config);
    }

    /**
     +----------------------------------------------------------
     * 连接数据库方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function connect() {
        if(!$this->connected) {
            $config =   $this->config;
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['hostport']?":{$config['hostport']}":'');
            if($this->pconnect) {
                $this->linkID = mysql_pconnect( $host, $config['username'], $config['password'],CLIENT_MULTI_RESULTS);
            }else{
                $this->linkID = mysql_connect( $host, $config['username'], $config['password'],true,CLIENT_MULTI_RESULTS);
            }
            if ( !$this->linkID || (!empty($config['database']) && !mysql_select_db($config['database'], $this->linkID)) ) {
                throw_exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID);
            if ($dbVersion >= "4.1") {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                mysql_query("SET NAMES '".C('DB_CHARSET')."'", $this->linkID);
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                mysql_query("SET sql_mode=''",$this->linkID);
            }
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
    }

    /**
     +----------------------------------------------------------
     * 释放查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function free() {
        mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function query($str='') {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        N('db_query',1);
        // 记录开始执行时间
        G('queryStartTime');
        $this->queryID = mysql_query($this->queryStr, $this->linkID);
        $this->debug();
        if ( !$this->queryID ) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll();
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function execute($str='') {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        N('db_write',1);
        // 记录开始执行时间
        G('queryStartTime');
        $result =   mysql_query($this->queryStr, $this->linkID) ;
        $this->debug();
        if ( false === $result) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mysql_affected_rows($this->linkID);
            $this->lastInsID = mysql_insert_id($this->linkID);
            return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->connect(true);
        if ( !$this->linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->linkID);
        }
        $this->transTimes++;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获得所有的查询数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getAll() {
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            while($row = mysql_fetch_assoc($this->queryID)){
                $result[]   =   $row;
            }
            mysql_data_seek($this->queryID,0);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function close() {
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if ($this->linkID && !mysql_close($this->linkID)){
            throw_exception($this->error());
        }
        $this->linkID = 0;
    }

    /**
     +----------------------------------------------------------
     * 数据库错误信息
     * 并显示当前的SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function error() {
        $this->error = mysql_error($this->linkID);
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  SQL字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        return mysql_escape_string($str);
    }

   /**
     +----------------------------------------------------------
     * 析构方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __destruct() {
        // 关闭连接
        $this->close();
    }

    /**
     +----------------------------------------------------------
     * 取得数据库类实例
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return mixed 返回数据库驱动类
     +----------------------------------------------------------
     */
    public static function getInstance($db_config='') {
		if ( self::$_instance==null ){
			self::$_instance = new Db($db_config);
		}
		return self::$_instance;
    }

    /**
     +----------------------------------------------------------
     * 分析数据库配置信息，支持数组和DSN
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $db_config 数据库配置信息
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function parseConfig($db_config='') {
        if ( !empty($db_config) && is_string($db_config)) {
            // 如果DSN字符串则进行解析
            $db_config = $this->parseDSN($db_config);
        }else if(empty($db_config)){
            // 如果配置为空，读取配置文件设置
            $db_config = array (
                'dbms'        =>   C('DB_TYPE'),
                'username'  =>   C('DB_USER'),
                'password'   =>   C('DB_PWD'),
                'hostname'  =>   C('DB_HOST'),
                'hostport'    =>   C('DB_PORT'),
                'database'   =>   C('DB_NAME'),
                'dsn'          =>   C('DB_DSN'),
                'params'     =>   C('DB_PARAMS'),
            );
        }
        return $db_config;
    }

    /**
     +----------------------------------------------------------
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $dsnStr
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function parseDSN($dsnStr) {
        if( empty($dsnStr) ){return false;}
        $info = parse_url($dsnStr);
        if($info['scheme']){
            $dsn = array(
            'dbms'        => $info['scheme'],
            'username'  => isset($info['user']) ? $info['user'] : '',
            'password'   => isset($info['pass']) ? $info['pass'] : '',
            'hostname'  => isset($info['host']) ? $info['host'] : '',
            'hostport'    => isset($info['port']) ? $info['port'] : '',
            'database'   => isset($info['path']) ? substr($info['path'],1) : ''
            );
        }else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/',trim($dsnStr),$matches);
            $dsn = array (
            'dbms'        => $matches[1],
            'username'  => $matches[2],
            'password'   => $matches[3],
            'hostname'  => $matches[4],
            'hostport'    => $matches[5],
            'database'   => $matches[6]
            );
        }
        return $dsn;
     }

    /**
     +----------------------------------------------------------
     * 数据库调试 记录当前SQL
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     */
    protected function debug() {
        // 记录操作结束时间
        if ( $this->debug ) {
            G('queryEndTime');
            Log::record($this->queryStr." [ RunTime:".G('queryStartTime','queryEndTime',6)."s ]",Log::SQL);
        }
    }

    /**
     +----------------------------------------------------------
     * 获取最近一次查询的sql语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getLastSql() {
        return $this->queryStr;
    }

}//类定义结束
?>