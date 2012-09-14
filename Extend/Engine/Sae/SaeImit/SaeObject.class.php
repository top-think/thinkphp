<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: SaeObject.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
class SaeObject {

    protected $errno = SAE_Success;
    protected $errmsg;
    static $db;

    //实现自动建表
    public function __construct() {
        $this->errmsg = Imit_L("_SAE_OK_");
        static $inited = false;
        //只初始化一次
        if ($inited)
            return;
        if (extension_loaded('sqlite3')) {
            self::$db = new ImitSqlite();
        } else {
            self::$db = get_class($this) == "SaeMysql" ? $this : new SaeMysql();
            $this->createTable();
        }
        $inited = true;
    }

    //获得错误代码
    public function errno() {
        return $this->errno;
    }

    //获得错误信息
    public function errmsg() {
        return $this->errmsg;
    }

    public function setAuth($accesskey, $secretkey) {
        
    }

    protected function createTable() {
        $sql = file_get_contents(dirname(__FILE__).'/sae.sql');
        $tablepre = C('DB_PREFIX');
        $tablesuf = C('DB_SUFFIX');
        $dbcharset = C('DB_CHARSET');
        $sql = str_replace("\r", "\n",$sql);
        $ret = array();
        $num = 0;
        foreach (explode(";\n", trim($sql)) as $query) {
            $queries = explode("\n", trim($query));
            foreach ($queries as $query) {
                $ret[$num] .= $query[0] == '#' || $query[0] . $query[1] == '--' ? '' : $query;
            }
            $num++;
        }
        unset($sql);
        foreach ($ret as $query) {
            $query = trim($query);
            if ($query) {
                if (substr($query, 0, 12) == 'CREATE TABLE') {
                    $name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
                    $type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $query));
                    $type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
                    $query = preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $query) .
                            (mysql_get_server_info() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
                }
                self::$db->runSql($query);
            }
        }
    }

}

?>