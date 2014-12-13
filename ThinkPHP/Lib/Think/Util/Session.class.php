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
// $Id: Session.class.php 2701 2012-02-02 12:27:51Z liu21st $

define("HTTP_SESSION_STARTED",      1);
define("HTTP_SESSION_CONTINUED",    2);

/**
 +------------------------------------------------------------------------------
 * Session管理类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Session.class.php 2701 2012-02-02 12:27:51Z liu21st $
 +------------------------------------------------------------------------------
 */
class Session extends Think
{//类定义开始

    /**
     +----------------------------------------------------------
     * 启动Session
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function start() {
        session_start();
        if (!isset($_SESSION['__HTTP_Session_Info'])) {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_STARTED;
        } else {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_CONTINUED;
        }
        Session::setExpire(C('SESSION_EXPIRE'));
    }

    /**
     +----------------------------------------------------------
     * 暂停Session
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function pause() {
        session_write_close();
    }

    /**
     +----------------------------------------------------------
     * 清空Session
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function clearLocal() {
        $local = Session::localName();
        unset($_SESSION[$local]);
    }

    /**
     +----------------------------------------------------------
     * 清空Session
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function clear() {
        $_SESSION = array();
    }

    /**
     +----------------------------------------------------------
     * 销毁Session
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function destroy() {
        unset($_SESSION);
        session_destroy();
    }

    /**
     +----------------------------------------------------------
     * 检测SessionID
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function detectID() {
        if(session_id()!='') {
            return session_id();
        }
        if (Session::useCookies()) {
            if (isset($_COOKIE[Session::name()])) {
                return $_COOKIE[Session::name()];
            }
        } else {
            if (isset($_GET[Session::name()])) {
                return $_GET[Session::name()];
            }
            if (isset($_POST[Session::name()])) {
                return $_POST[Session::name()];
            }
        }
        return null;
    }

    /**
     +----------------------------------------------------------
     * 设置或者获取当前Session name
     +----------------------------------------------------------
     * @param string $name session名称
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string 返回之前的Session name
     +----------------------------------------------------------
     */
    static function name($name = null) {
        return isset($name) ? session_name($name) : session_name();
    }

    /**
     +----------------------------------------------------------
     * 设置或者获取当前SessionID
     +----------------------------------------------------------
     * @param string $id sessionID
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void 返回之前的sessionID
     +----------------------------------------------------------
     */
    static function id($id = null) {
        return isset($id) ? session_id($id) : session_id();
    }

    /**
     +----------------------------------------------------------
     * 设置或者获取当前Session保存路径
     +----------------------------------------------------------
     * @param string $path 保存路径名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function path($path = null) {
        return !empty($path)? session_save_path($path):session_save_path();
    }

    /**
     +----------------------------------------------------------
     * 设置Session 过期时间
     +----------------------------------------------------------
     * @param integer $time 过期时间
     * @param boolean $add  是否为增加时间
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function setExpire($time, $add = false) {
        if ($add) {
            if (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
                $_SESSION['__HTTP_Session_Expire_TS'] = time() + $time;
            }

            // update session.gc_maxlifetime
            $currentGcMaxLifetime = Session::setGcMaxLifetime(null);
            Session::setGcMaxLifetime($currentGcMaxLifetime + $time);

        } elseif (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
                $_SESSION['__HTTP_Session_Expire_TS'] = $time;
        }
    }

    /**
     +----------------------------------------------------------
     * 设置Session 闲置时间
     +----------------------------------------------------------
     * @param integer $time 闲置时间
     * @param boolean $add  是否为增加时间
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function setIdle($time, $add = false) {
        if ($add) {
            $_SESSION['__HTTP_Session_Idle'] = $time;
        } else {
            $_SESSION['__HTTP_Session_Idle'] = $time - time();
        }
    }

    /**
     +----------------------------------------------------------
     * 取得Session 有效时间
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function sessionValidThru() {
        if (!isset($_SESSION['__HTTP_Session_Idle_TS']) || !isset($_SESSION['__HTTP_Session_Idle'])) {
            return 0;
        } else {
            return $_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle'];
        }
    }

    /**
     +----------------------------------------------------------
     * 检查Session 是否过期
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function isExpired() {
        if (isset($_SESSION['__HTTP_Session_Expire_TS']) && $_SESSION['__HTTP_Session_Expire_TS'] < time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 检查Session 是否闲置
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function isIdle() {
        if (isset($_SESSION['__HTTP_Session_Idle_TS']) && (($_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle']) < time())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 更新Session 闲置时间
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function updateIdle() {
        $_SESSION['__HTTP_Session_Idle_TS'] = time();
    }

    /**
     +----------------------------------------------------------
     * 设置Session 对象反序列化时候的回调函数
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $callback  回调函数方法名
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function setCallback($callback = null) {
        $return = ini_get('unserialize_callback_func');
        if (!empty($callback)) {
            ini_set('unserialize_callback_func',$callback);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 设置Session 是否使用cookie
     * 返回之前设置
     +----------------------------------------------------------
     * @param boolean $useCookies  是否使用cookie
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function useCookies($useCookies = null) {
        $return = ini_get('session.use_cookies') ? true : false;
        if (isset($useCookies)) {
            ini_set('session.use_cookies', $useCookies ? 1 : 0);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 检查Session 是否新建
     +----------------------------------------------------------
     * @param boolean $useCookies  是否使用cookie
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function isNew() {
        return !isset($_SESSION['__HTTP_Session_Info']) ||
            $_SESSION['__HTTP_Session_Info'] == HTTP_SESSION_STARTED;
    }


    /**
     +----------------------------------------------------------
     * 取得当前项目的Session 值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function getLocal($name) {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        return $_SESSION[$local][$name];
    }

    /**
     +----------------------------------------------------------
     * 取得当前项目的Session 值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function get($name) {
        if(isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }else {
            return null;
        }
    }

    /**
     +----------------------------------------------------------
     * 设置当前项目的Session 值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $name
     * @param mixed  $value
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function setLocal($name, $value) {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        if (null === $value) {
            unset($_SESSION[$local][$name]);
        } else {
            $_SESSION[$local][$name] = $value;
        }
        return;
    }

    /**
     +----------------------------------------------------------
     * 设置当前项目的Session 值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $name
     * @param mixed  $value
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function set($name, $value) {
        if (null === $value) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 检查Session 值是否已经设置
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function is_setLocal($name) {
        $local = Session::localName();
        return isset($_SESSION[$local][$name]);
    }

    /**
     +----------------------------------------------------------
     * 检查Session 值是否已经设置
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function is_set($name) {
        return isset($_SESSION[$name]);
    }

    /**
     +----------------------------------------------------------
     * 设置或者获取 Session localname
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function localName($name = null) {
        $return = (isset($GLOBALS['__HTTP_Session_Localname'])) ? $GLOBALS['__HTTP_Session_Localname'] : null;
        if (!empty($name)) {
            $GLOBALS['__HTTP_Session_Localname'] = md5($name);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * Session 初始化
     +----------------------------------------------------------
     * @static
     * @access private
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    static function _init() {
        ini_set('session.auto_start', 0);
        if (is_null(Session::detectID())) {
            Session::id(uniqid(dechex(mt_rand())));
        }
        // 设置Session有效域名
        Session::setCookieDomain(C('COOKIE_DOMAIN'));
        //设置当前项目运行脚本作为Session本地名
        Session::localName(APP_NAME);
        Session::name(C('SESSION_NAME'));
        Session::path(C('SESSION_PATH'));
        Session::setCallback(C('SESSION_CALLBACK'));
    }

    /**
     +----------------------------------------------------------
     * 设置Session use_trans_sid
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $useTransSID
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function useTransSID($useTransSID = null) {
        $return = ini_get('session.use_trans_sid') ? true : false;
        if (isset($useTransSID)) {
            ini_set('session.use_trans_sid', $useTransSID ? 1 : 0);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 设置Session cookie_domain
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $sessionDomain
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function setCookieDomain($sessionDomain = null) {
        $return = ini_get('session.cookie_domain');
        if(!empty($sessionDomain)) {
            ini_set('session.cookie_domain', $sessionDomain);//跨域访问Session
        }
        return $return;
    }


    /**
     +----------------------------------------------------------
     * 设置Session gc_maxlifetime值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $gc_maxlifetime
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function setGcMaxLifetime($gcMaxLifetime = null) {
        $return = ini_get('session.gc_maxlifetime');
        if (isset($gcMaxLifetime) && is_int($gcMaxLifetime) && $gcMaxLifetime >= 1) {
            ini_set('session.gc_maxlifetime', $gcMaxLifetime);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 设置Session gc_probability 值
     * 返回之前设置
     +----------------------------------------------------------
     * @param string $gc_maxlifetime
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function setGcProbability($gcProbability = null) {
        $return = ini_get('session.gc_probability');
        if (isset($gcProbability) && is_int($gcProbability) && $gcProbability >= 1 && $gcProbability <= 100) {
            ini_set('session.gc_probability', $gcProbability);
        }
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 当前Session文件名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static function getFilename() {
        return Session::path().'/sess_'.session_id();
    }

}//类定义结束
Session::_init();
?>