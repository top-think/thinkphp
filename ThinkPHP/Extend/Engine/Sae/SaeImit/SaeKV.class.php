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
// $Id: SaeKV.class.php 177 2012-05-07 02:37:04Z luofei614@126.com $
/**
*KVDB模拟器
*使用到数据库表think_sae_kv
*/
class SaeKV extends SaeObject{

public function delete($key){
        $ret=self::$db->runSql("delete from sae_kv where k='$key'");
	return $ret?true:false;
}

public function get($key){
        $data=self::$db->getLine("select * from sae_kv where k='$key'");
	$value=$this->output(array($data));
	$ret=$value[$key];
	return $ret?$ret:false;
}
public function get_info(){
//todu
}
public function init(){
	return true;
}
public function mget($ary){
	if(empty($ary)) return null;
        array_walk($ary,function(&$r){
            $r="'$r'";
        });
        $where=implode(',', $ary);
        $data=self::$db->getData("select * from sae_kv where k in($where)");
	return $this->output($data);
}
public function pkrget($prefix_key,$count,$start_key){
//todu
}
public function set($key,$value){
	if(!is_string($value)){
		//如果不是字符串序列化
		$value=serialize($value);
		$isobj=1;
	}else{
		$isobj=0;
	}
	//判断是否存在键
	if(self::$db->getVar("select count(*) from sae_kv where k='$key'")>0){
                $ret=self::$db->runSql("update sae_kv set v='$value',isobj='$isobj' where k='$key'");
	}else{
                $ret=self::$db->runSql("insert into sae_kv(k,v,isobj) values('$key','$value','$isobj')");
	}
	return $ret?true:false;
}

private function output($arr){
	$ret=array();
	foreach($arr as $k=>$ary){
		$ret[$ary['k']]=$ary['isobj']?unserialize($ary['v']):$ary['v'];
	}
	return $ret;
}

}


?>