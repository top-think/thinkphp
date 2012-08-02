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
// $Id: Sms.class.php 2990 2012-06-12 04:56:04Z luofei614@gmail.com $

/**
 +------------------------------------------------------------------------------
 * 网站短信预警,SAE平台专用
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    luofei614 <www.3g4k.com>
 * @version   $Id: Sms.class.php 2990 2012-06-12 04:56:04Z luofei614@gmail.com $
 +------------------------------------------------------------------------------
 */

class Sms {
    // 日志级别 从上到下，由低到高
    const ERR = 'ERR'; // 一般错误: 一般性错误
    const NOTICE = 'NOTIC'; // 通知: 程序可以运行但是还不够完美的错误
    const MYSQL_ERROR = 'MYSQL_ERROR'; //mysql错误
    const USER = 'USER'; //用户自定义信息，使用send_sms函数发送
    static public function send($msg,$detail, $level = self::USER, $mobile = null) {
        //判断是否定义需要发送短信
        if (!in_array($level, explode(',', C('SMS_LEVEL')))) 
        	return;
            //判断发送频率
            $mc = memcache_init();
            $is_send = $mc->get('think_sms_send');
            //如果已经发送，则不发送
            if ($is_send ==='true') {
                $status = 'not send';
            } else {
                //TODU,如果apibus类调整，此类也得调整
                $sms = apibus::init('sms');
                if (is_null($mobile)) $mobile = C('SMS_MOBILE');
                $mc = memcache_init();
                $obj = $sms->send($mobile, mb_substr(C('SMS_SIGN').$msg, 0,65,'utf-8'), "UTF-8");
                if($sms->isError($obj)){
                    $status='failed';
                }else{
                    $status='success';
                    $mc->set('think_sms_send', 'true', 0, C('SMS_INTERVAL'));
                }
                
            }
        //记录日志
        if(C('LOG_RECORD'))
            Log::record($msg.'；detail：'.$detail . '【status:' . $status . '】', 'SEND_SMS',true);
        else
            Log::write($msg.'；detail：'.$detail .'【status:' . $status . '】', 'SEND_SMS');
    }
}
?>