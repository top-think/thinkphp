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
// 发送短信函数，如果不是在SAE环境，需要在配置文件中配置SAE的AKEY和SKEY。如果是在SAE环境则不用配置。
// 配置完AKEY和SKEY后还需要在SAE平台开启应用的短信服务。
//
// ------------------------------------------------------------------

function send_sms($mobile,$msg){
        $sae_akey=C('SAE_AKEY')?C('SAE_AKEY'):(defined('SAE_ACCESSKEY')?SAE_ACCESSKEY:false);
        $sae_skey=C('SAE_SKEY')?C('SAE_SKEY'):(defined('SAE_SECRETKEY')?SAE_SECRETKEY:false);
        if(!$sae_akey || !$sae_skey){
            trace('你没有设置配置项SAE_AKEY和SAE_SKEY','发送短信失败','NOTIC');
            return false;
        }
        if(!extension_loaded('curl')){
            trace('php环境需要安装curl模块','发送短信失败','NOTIC');
            return false;
        }
        $timestamp=time();
        $url = 'http://inno.smsinter.sina.com.cn/sae_sms_service/sendsms.php'; //发送短信的接口地址
        $content = "FetchUrl" . $url . "TimeStamp" . $timestamp . "AccessKey" . $sae_akey;
        $signature = (base64_encode(hash_hmac('sha256', $content, $sae_skey, true)));
        $headers = array(
            "FetchUrl: $url",
            "AccessKey: ".$sae_akey,
            "TimeStamp: " . $timestamp,
            "Signature: $signature"
        );
        $log=false;
        $msg_all=$msg;
        if(mb_strlen($msg,'utf-8')>65){
            $log=true;
            $msg=mb_substr($msg, 0,65,'utf-8');
        }
        $data = array(
            'mobile' => $mobile ,
            'msg' => $msg,
            'encoding' => 'UTF-8'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://g.apibus.io');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $txt = curl_exec($ch);
        if (curl_errno($ch)) {
             if(C('LOG_RECORD'))
                trace('短信内容：'.$msg_all.'错误信息：'.$curl_error($ch) , '发送短信错误', 'NOTIC', true);
            else
                Log::write('短信发送错误：短信内容：'.$msg_all.'错误信息：'.$curl_error($ch),'NOTIC');
            return false;
        }
        curl_close($ch);
        $ret = json_decode($txt, true);
        if (!$ret) {
             if(C('LOG_RECORD'))
                trace('短信内容：'.$msg_all.'错误信息：接口[' . $url . ']返回格式不正确' , '发送短信错误', 'NOTIC', true);
            else
                Log::write('短信发送错误：短信内容：'.$msg_all.'错误信息：接口[' . $url . ']返回格式不正确','NOTIC');
            return false;
        }
        if (isset($ret['ApiBusError'])) {
            if(C('LOG_RECORD'))
                trace('短信内容：'.$msg_all.'错误信息：errno:' . $ret['ApiBusError']['errcode'] . ',errmsg:' . $ret['ApiBusError']['errdesc'] , '发送短信错误', 'NOTIC', true);
            else
                Log::write('短信发送错误：短信内容：'.$msg_all.'错误信息：errno:' . $ret['ApiBusError']['errcode'] . ',errmsg:' . $ret['ApiBusError']['errdesc'],'NOTIC');
            return false;
        }
        if($log){
            if(C('LOG_RECORD'))
                trace('短信完整内容：'.$msg_all , '发送短信内容过长', 'NOTIC', true);
            else
                Log::write('发送短信内容过长，短信完整内容：'.$msg_all,'NOTIC');
        }
        return true;
}