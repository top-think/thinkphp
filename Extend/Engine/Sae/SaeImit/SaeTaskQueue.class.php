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
// $Id: SaeTaskQueue.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
/**
*任务列队
*本地环境暂时需要支持curl才行。
*/
class SaeTaskQueue extends SaeObject{
	public $queue=array();
	//添加列队
	public function addTask($tasks,$postdata=null,$prior=false,$options=array()){
		if ( is_string($tasks) ) {
			if ( !filter_var($tasks, FILTER_VALIDATE_URL) ) {
				$this->errno = SAE_ErrParameter;
				$this->errmsg = Imit_L("_SAE_ERRPARAMTER_");
				return false;
			}

			//添加单条任务
			$item = array();
			$item['url'] = $tasks;
			if ($postdata != NULL) $item['postdata'] = $postdata;
			if ($prior) $item['prior'] = true;
			$tasks=$item;
		} 
		if ( empty($tasks) ) {
			$this->errno = SAE_ErrParameter;
			$this->errmsg = Imit_L("_SAE_ERRPARAMTER_");
			return false;
		}

		//记录任务，处理优先
		foreach($tasks as $k => $v) {
			if (is_array($v) && isset($v['url'])) {
				//当是二维数组时
				if($v['prior']){
					$this->queue=array_merge(array($v),$this->queue);
				}else{
					$this->queue[]=$v;
				}
			} elseif ( isset($tasks['url']) ) {
				//当是一维数组时
				if($tasks['prior']){
					$this->queue=array_merge(array($tasks),$this->queue);
				}else{
					$this->queue[]=$tasks;
				}
				break;
			} else {
				$this->errno = SAE_ErrParameter;
				$this->errmsg = Imit_L("_SAE_ERRPARAMTER_");
				return false;
			}
		}       
		

		return true;
	}

	public function curLength(){
		return true;
	}

	public function leftLength(){
		return true;
	}

	public function push(){
		//todu, 当用户环境不支持curl时用socket发送。
		if(empty($this->queue)) return false;
		$s = curl_init();
		foreach($this->queue as $k=>$v){
			curl_setopt($s,CURLOPT_URL,$v['url']);
			//curl_setopt($s,CURLOPT_TIMEOUT,5);
			curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($s,CURLOPT_HEADER, 1);
			curl_setopt($s,CURLINFO_HEADER_OUT, true);
			curl_setopt($s,CURLOPT_POST,true);
			curl_setopt($s,CURLOPT_POSTFIELDS,$v['postdata']); 
			$ret = curl_exec($s);
			$info = curl_getinfo($s);
			// print_r($info);
			if(empty($info['http_code'])) {
				$this->errno = SAE_ErrInternal;
				$this->errmsg = Imit_L("_SAE_TASKQUEUE_SERVICE_FAULT_");
				return false;
			} else if($info['http_code'] != 200) {
				$this->errno = SAE_ErrInternal;
				$this->errmsg = Imit_L("_SAE_TASKQUEUE_SERVICE_ERROR_");
				return false;
			} else {
                            //todu 这里好像有些问题
				 if($info['size_download'] == 0) { // get MailError header
					$this->errno = SAE_ErrUnknown;
					$this->errmsg = Imit_L("_SAE_UNKNOWN_ERROR_");
					return false;
				} 
			} 
		}
		//循环结束
		$this->queue=array();//清空列队
		
		return true;
	}
}


?>