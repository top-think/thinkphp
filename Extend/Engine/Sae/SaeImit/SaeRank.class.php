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
// $Id: SaeRank.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
class SaeRank extends SaeObject{
	public function __construct(){
	parent::__construct();
	}
	public function clear($namespace){
		if($this->emptyName($namespace)) return false;
                self::$db->runSql("delete from sae_rank where namespace='$namespace'");
                self::$db->runSql("delete from sae_rank_list where namespace='$namespace'");
		return true;
	}
	//创建
	//expire过期时间的单位为分钟
	public function create($namespace,$number,$expire=0){
		//判断是否存在
		if(!$this->emptyName($namespace)){
			$this->errno=-10;
			$this->errmsg=Imit_L("_SAE_THE_RANK_IS_EXISTED_");
			return false;
		}
                $ret=self::$db->runSql("insert into sae_rank(namespace,num,expire,createtime) values('$namespace','$number','$expire','".time()."')");
		if($ret===false){
			$this->errno=-6;
			$this->errmsg=Imit_L("_SAE_ERR_");
			return false;
		}else{
		return true;
		}
	
	}
	//减去
	public function decrease($namespace,$key,$value,$renkReurn=false){
		if($this->emptyName($namespace)) return false;
		$this->check($namespace);
		if(self::$db->getVar("select count(*) from sae_rank_list where namespace='$namespace' and k='$key'")==0){
			//如果不存在
			$this->errno=-3;
			$this->errmsg=Imit_L("_SAE_NOT_IN_BILLBOARD_");
			return false;
		}else{
                        $ret=self::$db->runSql("update sae_rank_list set v=v-$value where namespace='$namespace' and k='$key'");
			if($ret===false) return false;
			if(rankReturn){
			return $this->getRank($namespace,$key);
			}
			return true;
		}
	}
	//删除键
	public function delete($namespace,$key,$rankReturn=false){
		if($this->emptyName($namespace)) return false;
		if($rankReturn) $r=$this->getRank($namespace,$key);
                $ret=self::$db->runSql("delete from sae_rank_list where namespace='$namespace' and k='$key'");
		if($ret===false){
			$this->errno=-6;
			$this->errmsg=Imit_L("_SAE_ERR_");
			return false;
		}else{
			if($rankReturn) return $r;
			return true;
		}
	}
	//获得排行榜
	public function getList($namespace,$order=false,$offsetFrom=0,$offsetTo=PHP_INT_MAX){
		//判断是否存在
		if($this->emptyName($namespace)) return false;
                $ord="v asc";
		//获得列表
		if($order) $ord="v desc";
		//判断是否有长度限制
                $num=self::$db->getVar("select num from sae_rank where namespace='$namespace'");
		if($num!=0){
		$ord="v desc";//todu，完善和sae数据一致。
		if($offsetTo>$num) $offsetTo=$num;
		}
                $data=self::$db->getData("select * from sae_rank_list where namespace='$namespace' order by $ord limit $offsetFrom,$offsetTo");
                $ret=array();
                foreach($data as $r){
                    $ret[$r['k']]=$r['v'];
                }
		$this->check($namespace);//检查过期
		if($data===false){
			$this->errno=-6;
			$this->errmsg=Imit_L("_SAE_ERR_");
			return false;
		}else{
		return $ret;
		}
	}
	//获得某个键的排名
	//注意排名是从0开始的
	public function getRank($namespace,$key){
		if($this->emptyName($namespace)) return false;
                $v=self::$db->getVar("select v from sae_rank_list where namespace='$namespace' and k='$key'");
                $ret=self::$db->getVar("select count(*) from sae_rank_list where namespace='$namespace' and v>=$v");
		if(!$ret){
		$this->errno=-3;
		$this->errmsg=Imit_L("_SAE_NOT_IN_BILLBOARD_");	
		return false;
		}
		return $ret-1;
	}
	//增加值
	public function increase($namespace,$key,$value,$rankReturn=false){
		if($this->emptyName($namespace)) return false;
		$this->check($namespace);
		if(self::$db->getVar("select count(*) from sae_rank_list where namespace='$namespace' and k='$key'")==0){
			//如果不存在
			$this->errno=-3;
			$this->errmsg=Imit_L("_SAE_NOT_IN_BILLBOARD_");
			return false;
		}else{
                        $ret=self::$db->runSql("update sae_rank_list set v=v+$value where namespace='$namespace' and k='$key'");
			if($ret===false) return false;
			if(rankReturn){
			return $this->getRank($namespace,$key);
			}
			return true;
		}
	}
	//设置值
	public function set($namespace,$key,$value,$rankReturn=false){
		//判断是否存在
		if($this->emptyName($namespace)) return false;
		//检查是否过期
		$this->check($namespace);
		//设置值
		//判断是否有此key
		if(self::$db->getVar("select count(*) from sae_rank_list where namespace='$namespace' and k='$key'")==0){
			$setarr=array(
			'namespace'=>$namespace,
			'k'=>$key,
			'v'=>$value
			);
                        $ret=self::$db->runSql("insert into sae_rank_list(namespace,k,v) values('$namespace','$key','$value')");
		}else{
                $ret=self::$db->runSql("update sae_rank_list set v='$value' where namespace='$namespace' and k='$key'");
		}
	   if($ret===false) return false;
		if($rankReturn){
			//返回排名
			return $this->getRank($namespace,$key);
		}
		return true;
	}
	//判断是否为空
	private function emptyName($name){
                $num=self::$db->getVar("select count(*) from sae_rank where namespace='$name'");
		if($num==0){
		return true;
		}else{
		$this->errno=-4;
		$this->errmsg=Imit_L("_SAE_BILLBOARD_NOT_EXISTS_");
		return false;
		}
	}
	//检查是否过期
	private function check($name){
                $data=self::$db->getLine("select * from sae_rank where namespace='$name'");
		if($data['expire'] && $data['createtime']+$data['expire']*60<=time()){
                self::$db->runSql("delete from sae_rank_list where namespace='$name'");
		//重新设置创建时间
                self::$db->runSql("update sae_rank set createtime='".time()."' where namespace='$name'");
		}
		}
		
	}



?>