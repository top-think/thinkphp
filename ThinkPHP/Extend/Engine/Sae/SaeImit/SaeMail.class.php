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
// $Id: SaeMail.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
/**
*Mail模拟器
*todu， 支持ssl和附件上传。
*现在暂不支持ssl，建议不用使用gmail测试。
*/
class SaeMail extends SaeObject{
	private $msp = array(
	"sina.com"    => array("smtp.sina.com",25,0),
	"sina.cn"        => array("smtp.sina.cn",25,0),
	"163.com"        => array("smtp.163.com",25,0),
	"263.com"        => array("smtp.263.com",25,0),
	"gmail.com"    => array("smtp.gmail.com",587,1),
	"sohu.com"    => array("smtp.sohu.com",25,0),
	"qq.com"        => array("smtp.qq.com",25,0),
	"vip.qq.com"    => array("smtp.qq.com",25,0),
	"126.com"        => array("smtp.126.com",25,0),
	);
	private $_post=array();
	const mail_limitsize=1048576;
	const subject_limitsize=256;
	private $_count;
	private $_attachSize;
	private $_allowedAttachType = array("bmp","css","csv","gif","htm","html","jpeg","jpg","jpe","pdf","png","rss","text","txt","asc","diff","pot","tiff","tif","wbmp","ics","vcf");
	public function __construct($options=array()){
		$this->setOpt($options);
	}
	public function clean(){
		$this->_post = array();
		$this->_count = 0;
		$this->_attachSize = 0;
		return true;
	}
	public function quickSend($to,$subject,$msgbody,$smtp_user,$smtp_pass,$smtp_host='',$smtp_port=25,$smtp_tls=false){
		$to = trim($to);
		$subject = trim($subject);
		$msgbody = trim($msgbody);
		$smtp_user = trim($smtp_user);
		$smtp_host = trim($smtp_host);
		$smtp_port = intval($smtp_port);

		$this->_count = strlen($msgbody) + $this->_attachSize;
		if(strlen($subject) > self::subject_limitsize) {
			$this->errno = SAE_ErrParameter;
			$this->errmsg = "subject cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}
		if($this->_count > self::mail_limitsize) {
			$this->errno = SAE_ErrParameter;
			$this->errmsg = "mail size cannot larger than ".self::subject_limitsize." bytes";
			return false;
		}
		if (filter_var($smtp_user, FILTER_VALIDATE_EMAIL)) {
			preg_match('/([^@]+)@(.*)/', $smtp_user, $match);
			$user = $match[1]; $host = $match[2];
			if(empty($smtp_host)) {
				//print_r($match);
				if(isset($this->msp[$host])) { $smtp_host = $this->msp[$host][0]; }
				else {
					$this->errno = SAE_ErrParameter;
					$this->errmsg = "you can set smtp_host explicitly or choose msp from sina,gmail,163,265,netease,qq,sohu,yahoo";
					return false;
				}
			}
			if($smtp_port == 25 and isset($this->msp[$host])) {
				$smtp_port = $this->msp[$host][1];
			}
			if(!$smtp_tls and isset($this->msp[$host])) {
				$smtp_tls = $this->msp[$host][2];
			}
			$smtp_tls = ($smtp_tls == true);
			$username = $user;
		} else {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "invalid email address";
			return false;
		}
		$this->_post = array_merge($this->_post, array("from"=>$smtp_user, "smtp_username"=>$username, "smtp_password"=>$smtp_pass, "smtp_host"=>$smtp_host, "smtp_port"=>$smtp_port, 'to'=>$to,'subject'=>$subject,'content'=>$msgbody, 'tls'=>$smtp_tls));
		return $this->send();
	}
	public function send(){
		if ( empty($this->_post['from']) 
				|| empty($this->_post['to']) 
				|| empty($this->_post['smtp_host'])
				|| empty($this->_post['smtp_username'])
				|| empty($this->_post['smtp_password'])
				|| empty($this->_post['subject']) ) {
			$this->_errno = SAE_ErrParameter;
			$this->_errmsg = "parameters from, to, subject, smtp_host, smtp_username, smtp_password can no be empty";
			return false;
		}
		if($this->_count > self::mail_limitsize) {
			$this->_errno = SAE_ErrForbidden;
			$this->_errmsg = "mail size cannot larger than ".self::mail_limitsize." bytes";
			return false;
		}
		//连接服务器 
		$fp = fsockopen ( $this->_post['smtp_host'], $this->_post['smtp_port'], $errno, $errstr, 60); 
		if (!$fp ) return "联接服务器失败".__LINE__;
		stream_set_blocking($fp, true ); 

		$lastmessage=fgets($fp,512);
		if ( substr($lastmessage,0,3) != 220 ) return "error1:".$lastmessage.__LINE__; 

		//HELO
		$yourname = "YOURNAME";
		$lastact="EHLO ".$yourname."\r\n";

		fputs($fp, $lastact);
		$lastmessage == fgets($fp,512);
		if (substr($lastmessage,0,3) != 220 ) return "error2:$lastmessage".__LINE__; 
		while (true) {
			$lastmessage = fgets($fp,512);
			if ( (substr($lastmessage,3,1) != "-")  or  (empty($lastmessage)) )
			break;
		} 
		//身份验证
		//验证开始
		$lastact="AUTH LOGIN"."\r\n";
		fputs( $fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != 334) return "error3:$lastmessage".__LINE__; 
		//用户姓名
		$lastact=base64_encode($this->_post['smtp_username'])."\r\n";
		fputs( $fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != 334) return "error4:$lastmessage".__LINE__;
		//用户密码
		$lastact=base64_encode($this->_post['smtp_password'])."\r\n";
		fputs( $fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != "235") return "error5:$lastmessage".__LINE__;

		//FROM:
		$lastact="MAIL FROM: ". $this->_post['from'] . "\r\n"; 
		fputs( $fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != 250) return "error6:$lastmessage".__LINE__;

		//TO:
		$lastact="RCPT TO: ".$this->_post['to']. "\r\n"; 
		fputs( $fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != 250) return "error7:$lastmessage".__LINE__;

		//DATA
		$lastact="DATA\r\n";
		fputs($fp, $lastact);
		$lastmessage = fgets ($fp,512);
		if (substr($lastmessage,0,3) != 354) return "error8:$lastmessage".__LINE__;


		//处理Subject头
		$head="Subject: ".$this->_post['subject']."\r\n"; 
		$message = $head."\r\n".$this->_post['content']; 


		//处理From头 
		$head="From: ".$this->_post['from']."\r\n"; 
		$message = $head.$message; 

		//处理To头 
		$head="To: ".$this->_post['to']."\r\n";
		$message = $head.$message;


		//加上结束串 
		$message .= "\r\n.\r\n";

		//发送信息 
		fputs($fp, $message); 
		$lastact="QUIT\r\n"; 

		fputs($fp,$lastact); 
		fclose($fp); 	
	}
	public function setAttach($attach){
		if(!is_array($attach)) {
			$this->errmsg = "attach parameter must be an array!";
			$this->errno = SAE_ErrParameter;
			return false;
		}
		$this->_attachSize = 0;
		foreach($attach as $fn=>$blob) {
			$suffix = end(explode(".", $fn));
			if(!in_array($suffix, $this->_allowedAttachType)) {
				$this->errno = SAE_ErrParameter;
				$this->errmsg = "Invalid attachment type";
				return false;
			}
			$this->_attachSize += strlen($blob);
			$this->_count = $this->_attachSize + strlen($this->_post['content']);
			if($this->_count > self::mail_limitsize) {
				$this->errno = SAE_ErrForbidden;
				$this->errmsg = "mail size cannot larger than ".self::mail_limitsize." bytes";
				return false;
			}
			//$this->_post = array_merge($this->_post, array("attach:$fn:B:".$this->_disposition[$suffix] => base64_encode($blob)));
		}
		return true;
	}
	public function setOpt($options){
		if (isset($options['subject']) && strlen($options['subject']) > self::subject_limitsize) {
			$this->errno = SAE_ErrParameter;
			$this->errmsg = Imit_L("_SAE_MAIL_SIZE_lARGER_");
			return false;
		}
		if(isset($options['content']))
		$this->_count = $this->_attachSize + strlen($options['content']);
		if($this->_count > self::mail_limitsize) {
			$this->errno = SAE_ErrParameter;
			$this->errmsg = Imit_L("_SAE_MAIL_SIZE_lARGER_");
			return false;
		}
		$this->_post = array_merge($this->_post, $options);
		return true;

	}



}

