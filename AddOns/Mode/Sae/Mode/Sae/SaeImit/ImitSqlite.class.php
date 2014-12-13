<?php
// +----------------------------------------------------------------------
// | 模拟器数据库采用sqlite3
// +----------------------------------------------------------------------
// | Author: luofei614<www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: ImitSqlite.class.php 2442 2011-12-18 08:29:00Z luofei614@gmail.com $
class ImitSqlite extends SQLite3{
    function __construct()
    {
        $this->open(dirname(__FILE__).'/sae.db');
    }
    //获得数据，返回数组
    public function getData($sql){
        $this->last_sql = $sql;
        $result=$this->query($sql);
        if(!$result){
            return false;
        }
        $data=array();
        while($arr=$result->fetchArray(SQLITE3_ASSOC)){
            $data[]=$arr;
        }
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
    //运行sql语句
    public function runSql($sql) {
        return $this->exec($sql);
    }

}