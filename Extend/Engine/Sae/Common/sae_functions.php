<?php
//平滑函数，sae和本地都可以用，增加系统平滑性
function sae_unlink($filePath) {
    if (IS_SAE) {
        $arr = explode('/', ltrim($filePath, './'));
        $domain = array_shift($arr);
        $filePath = implode('/', $arr);
        $s = Think::instance('SaeStorage');
        return $s->delete($domain, $filePath);
    } else {
        return unlink($filePath);
    }
}
// ==================================================================
//
// 短信发送函数， 在SAE平台下会发送短信
// 此函数发送短信的等级为USER
// 如果是在本地环境，只记录日志，如果想不记录日志，需要在config.php中配置SMS_ON为false
//
// ------------------------------------------------------------------

function sae_send_sms($msg,$detail,$mobile=NULL){
	$sms_on=C('SMS_ON');
	if($sms_on!==null && !$sms_on) return ;// 如果关闭短信不进行操作
	//判断平台
	if(!IS_SAE){
		//非SAE平台只记录日志
		 Log::record($msg.'；detail：'.$detail , 'SEND_SMS',true);
	}else{
		Sms::send($msg,$detail,Sms::USER,$mobile);
	}

}