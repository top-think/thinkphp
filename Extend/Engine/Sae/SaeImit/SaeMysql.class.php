<?php

class SaeMysql extends SaeObject {

    static $link;
    static $charset;

    function __construct() {
        global $sae_config;
        self::$charset = $sae_config['db_charset'];
        $this->connect();
        parent::__construct();
    }

    //连接数据库
    protected function connect() {
        global $sae_config;
        if(empty($sae_config['db_name'])) die(Imit_L('_SAE_PLEASE_CONFIG_DB_'));
        self::$link = mysql_connect(SAE_MYSQL_HOST_M, SAE_MYSQL_USER, SAE_MYSQL_PASS) or die(Imit_L('_SAE_CONNECT_DB_ERR_'));
        mysql_select_db(SAE_MYSQL_DB, self::$link);
        mysql_query("set names " . self::$charset, self::$link);
        if (!mysql_select_db(SAE_MYSQL_DB, self::$link)) {
            //如果数据库不存在，自动建立
            mysql_query('create database ' . SAE_MYSQL_DB, self::$link);
            mysql_select_db(SAE_MYSQL_DB, self::$link) or Imit_L('_SAE_DATABASE_NOT_EXIST_');
        }
    }

    //返回影响条数
    public function affectedRows() {
        return mysql_affected_rows(self::$link);
    }

    //关闭数据库
    public function closeDb() {
        mysql_close(self::$link);
    }

    //escape
    public function escape($str) {
        return mysql_real_escape_string($str, self::$link);
    }

    //获得数据，返回数组
    public function getData($sql) {
        $this->last_sql = $sql;
        $result = mysql_query($sql, self::$link);
        if(!$result){
            return false;
        }
        $this->save_error();
        $data = array();
        while ($arr = mysql_fetch_array($result)) {
            $data[] = $arr;
        }
        mysql_free_result($result);
        return $data;
    }

    //返回第一条数据
    public function getLine($sql) {
        $data = $this->getData($sql);
        if ($data) {
            return @reset($data);
        } else {
            return false;
        }
    }

    //返回第一条记录的第一个字段值
    public function getVar($sql) {
        $data = $this->getLine($sql);
        if ($data) {
            return $data[@reset(@array_keys($data))];
        } else {
            return false;
        }
    }

    //返回最后一个id
    public function lastId() {
        return mysql_insert_id(self::$link);
    }

    //运行sql语句
    public function runSql($sql) {
        $ret = mysql_query($sql);
        $this->save_error();
        return $ret;
    }

    //设置项目名
    public function setAppname($appname) {
        
    }

    //设置字符集
    public function setCharset($charset) {
        self::$charset = $charset;
        mysql_query("set names " . self::$charset, self::$link);
    }

    //设置端口
    public function setPort($port) {
        
    }

    protected function save_error() {
        $this->errmsg = mysql_error(self::$link);
        $this->errno = mysql_errno(self::$link);
    }

}
