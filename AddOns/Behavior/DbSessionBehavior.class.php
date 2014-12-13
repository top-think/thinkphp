<?php 
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: DbSessionBehavior.class.php 2207 2011-11-30 13:17:26Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 数据库方式Session处理行为扩展
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: DbSessionBehavior.class.php 2207 2011-11-30 13:17:26Z liu21st $
 +------------------------------------------------------------------------------
 */
class DbSessionBehavior extends Behavior
{//类定义开始

    /**
     +----------------------------------------------------------
     * Session有效时间
     +----------------------------------------------------------
     * @var array
     * @access protected
     +----------------------------------------------------------
     */
   protected $lifeTime=''; 

    /**
     +----------------------------------------------------------
     * session保存的数据库名
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
   protected $sessionTable='';

    /**
     +----------------------------------------------------------
     * 数据库句柄
     +----------------------------------------------------------
     * @var array
     * @access protected
     +----------------------------------------------------------
     */
   protected $dbHandle; 

    /**
     +----------------------------------------------------------
     * 行为运行入口
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
    public function run() {
        // 更改Session 处理机制
        session_set_save_handler(array(&$this,"open"), 
                         array(&$this,"close"), 
                         array(&$this,"read"), 
                         array(&$this,"write"), 
                         array(&$this,"destroy"), 
                         array(&$this,"gc")); 

    }

    /**
     +----------------------------------------------------------
     * 打开Session 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $savePath 
     * @param mixed $sessName  
     +----------------------------------------------------------
     */
    public function open($savePath, $sessName) { 
       // get session-lifetime 
       $this->lifeTime = C('SESSION_EXPIRE'); 
	   $this->sessionTable	 =	 C('SESSION_TABLE');
       $dbHandle = mysql_connect(C('DB_HOST'),C('DB_USER'),C('DB_PWD')); 
       $dbSel = mysql_select_db(C('DB_NAME'),$dbHandle); 
       // return success 
       if(!$dbHandle || !$dbSel) 
           return false; 
       $this->dbHandle = $dbHandle; 
       return true; 
    } 

    /**
     +----------------------------------------------------------
     * 关闭Session 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
   public function close() { 
       $this->gc(ini_get('session.gc_maxlifetime')); 
       // close database-connection 
       return mysql_close($this->dbHandle); 
   } 

    /**
     +----------------------------------------------------------
     * 读取Session 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sessID 
     +----------------------------------------------------------
     */
   public function read($sessID) { 
       // fetch session-data 
       $res = mysql_query("SELECT session_data AS d FROM ".$this->sessionTable." WHERE session_id = '$sessID'   AND session_expires >".time(),$this->dbHandle); 
       // return data or an empty string at failure 
       if($res) {
           $row = mysql_fetch_assoc($res);
           $data = $row['d'];
            if( function_exists('gzcompress')) {
                //启用数据压缩
                //$data   =   gzuncompress($data);
            }
           return $data; 
       }
       return ""; 
   } 

    /**
     +----------------------------------------------------------
     * 写入Session 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sessID 
     * @param String $sessData  
     +----------------------------------------------------------
     */
   public function write($sessID,$sessData) { 
       // new session-expire-time 
       $newExp = time() + $this->lifeTime; 
        if( function_exists('gzcompress')) {
            //数据压缩
            //$sessData   =   gzcompress($sessData,3);
        }
       // is a session with this id in the database? 
       $res = mysql_query("SELECT * FROM ".$this->sessionTable." WHERE session_id = '$sessID'",$this->dbHandle); 
       // if yes, 
       if(mysql_num_rows($res)) { 
           // ...update session-data 
           mysql_query("UPDATE ".$this->sessionTable."  SET session_expires = '$newExp', session_data = '$sessData' WHERE session_id = '$sessID'",$this->dbHandle); 
           // if something happened, return true 
           if(mysql_affected_rows($this->dbHandle)) 
               return true; 
       } 
       // if no session-data was found, 
       else { 
           // create a new row 
           mysql_query("INSERT INTO ".$this->sessionTable." (  session_id, session_expires, session_data)  VALUES( '$sessID', '$newExp',  '$sessData')",$this->dbHandle); 
           // if row was created, return true 
           if(mysql_affected_rows($this->dbHandle)) 
               return true; 
       } 
       // an unknown error occured 
       return false; 
   } 

    /**
     +----------------------------------------------------------
     * 删除Session 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sessID 
     +----------------------------------------------------------
     */
   public function destroy($sessID) { 
       // delete session-data 
       mysql_query("DELETE FROM ".$this->sessionTable." WHERE session_id = '$sessID'",$this->dbHandle); 
       // if session was deleted, return true, 
       if(mysql_affected_rows($this->dbHandle)) 
           return true; 
       // ...else return false 
       return false; 
   } 

    /**
     +----------------------------------------------------------
     * Session 垃圾回收
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param string $sessMaxLifeTime 
     +----------------------------------------------------------
     */
   public function gc($sessMaxLifeTime) { 
       // delete old sessions 
       mysql_query("DELETE FROM ".$this->sessionTable." WHERE session_expires < ".time(),$this->dbHandle); 
       // return affected rows 
       return mysql_affected_rows($this->dbHandle); 
   } 

}//类定义结束
?>