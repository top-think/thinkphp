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
// $Id: SaeCounter.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
/**
 * SaeCounter模拟器
 * 使用了数据库存储统计器信息，
 * 相关数据表：think_sae_counter
 */
class SaeCounter extends SaeObject {

    //创建统计器
    public function create($name, $value=0) {
        //判断是否存在
        if ($this->exists($name))
            return false;
        return self::$db->runSql("insert into sae_counter(name,val) values('$name','$value')");
    }

    //减法
    public function decr($name, $value=1) {
        if (!$this->exists($name))
            return false;
        self::$db->runSql("update sae_counter set val=val-$value where name='$name'");
        return self::$db->getVar("select val from sae_counter where name='$name'");
    }

    //是否存在
    public function exists($name) {
        $num = self::$db->getVar("select count(*) from sae_counter where name='$name'");
        return $num != 0 ? true : false;
    }

    public function get($name) {
        if (!$this->exists($name))
            return false;
        return self::$db->getVar("select val from sae_counter where name='$name'");
    }

    public function getall() {
        $data = self::$db->getData("select * from sae_counter where name='$name'");
        $ret = array();
        foreach ($data as $r) {
            $ret[$r['name']] = $r['val'];
        }
        return $ret;
    }

    //加法
    public function incr($name, $value=1) {
        if (!$this->exists($name))
            return false;
        self::$db->runSql("update sae_counter set val=val+$value where name='$name'");
        return self::$db->getVar("select val from sae_counter where name='$name'");
    }

    public function length() {
        return self::$db->getVar("select count(*) from sae_counter");
    }

    //获得多个统计器，names为数组
    public function mget($names) {
        array_walk($names, function(&$name) {
                    $name = "'$name'";
                });
        $where = implode(',', $names);
        $data = self::$db->getData("select * from sae_counter where name in($where)");
        $ret = array();
        foreach ($data as $r) {
            $ret[$r['name']] = $r['val'];
        }
        return $ret;
    }

    public function remove($name) {
        if (!$this->exists($name))
            return false;
        return self::$db->runSql("delete from sae_counter where name='$name'");
    }

    //设置值
    public function set($name, $value) {
        return self::$db->runSql("update sae_counter set val='$value' where name='$name'");
    }

}

?>