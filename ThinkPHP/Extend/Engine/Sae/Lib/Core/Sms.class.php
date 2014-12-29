<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Sms.class.php 1275 2012-12-04 03:13:00Z luofei614@126.com $

/**
 +------------------------------------------------------------------------------
 * 网站短信预警,SAE平台专用
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    luofei614 <www.3g4k.com>
 * @version   $Id: Sms.class.php 1275 2012-12-04 03:13:00Z luofei614@126.com $
 +------------------------------------------------------------------------------
 */

class Sms {
    // 日志级别 从上到下，由低到高
    const ERR = 'ERR'; // 一般错误: 一般性错误
    const NOTICE = 'NOTIC'; // 通知: 程序可以运行但是还不够完美的错误
    const MYSQL_ERROR = 'MYSQL_ERROR'; //mysql错误
    static public function send($msg,$detail, $level = self::NOTIC, $mobile = null) {
        //判断是否定义需要发送短信
        if (!in_array($level, explode(',', C('SMS_ALERT_LEVEL')))) 
        	return;
            //判断发送频率
            $mc = memcache_init();
            $is_send = $mc->get('think_sms_send');
            //如果已经发送，则不发送
            if ($is_send ==='true') {
                $status = 'not send';
            } else {
                $sms = apibus::init('sms');
                if (is_null($mobile)) $mobile = C('SMS_ALERT_MOBILE');
                $mc = memcache_init();
                $obj = $sms->send($mobile, mb_substr(C('SMS_ALERT_SIGN').$msg, 0,65,'utf-8'), "UTF-8");
                if($sms->isError($obj)){
                    $status='failed';
                }else{
                    $status='success';
                    $mc->set('think_sms_send', 'true', 0, C('SMS_ALERT_INTERVAL'));
                }
                
            }
        //记录日志
        if(C('LOG_RECORD'))
            trace($msg.'；detail：'.$detail . '【status:' . $status . '】','短信发送','SAE',true);
        else
            Log::write($msg.'；detail：'.$detail .'【status:' . $status . '】', 'SEND_SMS');
    }
}
?>