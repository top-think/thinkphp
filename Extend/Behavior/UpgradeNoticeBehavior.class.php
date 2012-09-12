<?php
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]

// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<www.3g4k.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * 升级短信通知， 如果有ThinkPHP新版升级，或者重要的更新，会发送短信通知你。
 * 需要使用SAE的短信服务。请先找一个SAE的应用开通短信服务。
 */
class UpgradeNoticeBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
        'UPGRADENOTICE_ON'         => true,   // 是否开启升级
        'UPGRADENOTICE_AKEY'       => '',//SAE应用的AKEY
        'UPGRADENOTICE_SKEY'       => '',//SAE应用的SKEY
        'UPGRADENOTICE_MOBILE'       => '',//接受短信的手机号
        'UPGRADENOTICE_CHECK_INTERVAL'=>604800,//检测频率,单位秒,默认是一周
        );
    protected $header_='';
    protected $httpCode_;
    protected $httpDesc_;
    protected $accesskey_;
    protected $secretkey_;
     public function run(&$params){
     	if(C('UPGRADENOTICE_ON') && !S('think_upgrade_interval')){
                        $akey=C('UPGRADENOTICE_AKEY');
                        $skey=C('UPGRADENOTICE_SKEY');
                        $this->accesskey_=$akey?$akey:defined('SAE_ACCESSKEY')?SAE_ACCESSKEY:'';
                        $this->secretkey_=$skey?$skey:defined('SAE_SECRETKEY')?SAE_SECRETKEY:'';
     		$current_version=F('think_upgrade_version');
     		//读取接口
     		$info=$this->send('http://sinaclouds.sinaapp.com/thinkapi/upgrade.php?v='.$current_version);
     		if($info['version']!=$current_version){
     			if($current_version){
                                            if($this->send_sms($info['msg'])) F('think_upgrade_version',$info['version']);//发送升级短信
                                    }
     			
     		}
     		S('think_upgrade_interval',true,C('UPGRADENOTICE_CHECK_INTERVAL'));
     	}
    }

    private function send_sms($msg){
    	$timestamp = NOW_TIME;
    	$url='http://inno.smsinter.sina.com.cn/sae_sms_service/sendsms.php';//发送短信的接口地址
	$signature = $this->signature($url, $timestamp);
	$headers = array("FetchUrl: $url",
			"AccessKey: $this->accesskey_",
			"TimeStamp: $timestamp",
			"Signature: $signature"
	);
	$data=array('mobile'=>C('UPGRADENOTICE_MOBILE'),'msg'=>$msg,'encoding'=>'UTF-8');
	$ret=$this->send('http://g.apibus.io',$data,$headers);
	if (preg_match('/^(?:[^\s]+)\s([^\s]+)\s([^\r\n]+)/', $this->header_, $matchs)) {
		$this->httpCode_ = $matchs[1];
		$this->httpDesc_ = $matchs[2];
	}else{
		trace('errcode:1,errdesc:invalid response','升级通知出错','DEBUG',true);
		return false;
	} 
	 if ( $this->httpCode_ != 201 && $this->httpCode_ != 200) {
	 	trace('errcode:'.$this->httpCode_.',errdesc:'.$this->httpDesc_,'升级通知出错','DEBUG',true);
	 	return false;
	 }
	if(isset($ret['ApiBusError'])){
		trace('errno:'.$ret['ApiBusError']['errcode'].',errmsg:'.$ret['ApiBusError']['errdesc'],'升级通知出错','DEBUG',true);
		return false;
	}
            return true; 
    }
    private function signature($url, $timestamp) {
		$content = "FetchUrl"  . $url .
			"TimeStamp" . $timestamp .
			"AccessKey" . $this->accesskey_;
		$signature = (base64_encode(hash_hmac('sha256',$content,$this->secretkey_,true)));
		return $signature;
}
    private function send($url,$params=array(),$headers=array()){
        $this->header_='';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if(!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'writeHeader'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $txt = curl_exec($ch);
        if (curl_errno($ch)) {
            trace(curl_error($ch),'升级通知出错','DEBUG',true);
            return false;
        }
        curl_close($ch);
        $ret = json_decode($txt, true);
        if(!$ret){
        	trace('接口['.$url.']返回格式不正确','升级通知出错','DEBUG',true);
            return false;
        }
        return $ret;
    }

   public function writeHeader($ch, $header) {
		$this->header_ .= $header;
		return strlen($header);	
  }

 }