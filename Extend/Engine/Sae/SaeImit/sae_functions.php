<?php
//SAE系统函数
function sae_set_display_errors($show=true){
//在本地设置不显示错误， 只不显示sae_debug的文字信息， 但是会显示系统错误，方便我们开发。
	global $sae_config;
	$sae_config['display_error']=$show;
}
function sae_debug($log){
    global $sae_config;
    error_log(date('[c]').$log.PHP_EOL,3,$sae_config['debug_file']);
	if($sae_config['display_error']!==false){
		echo $log.PHP_EOL;
	}
}

function memcache_init(){
	static $handler;
	if(is_object($handler)) return $handler;
    $handler=new Memcache;
    $handler->connect('127.0.0.1',11211);
    return $handler;
}

function sae_xhprof_start()
{
    //pass
}

function sae_xhprof_end()
{
    return true;
}
//向下兼容函数
function sae_image_init( $ak='', $sk='', $image_bin = '' )
{
    if( !isset( $GLOBALS['sae_image_instance'] ) )
    {
        $GLOBALS['sae_image_instance'] = new SaeImage($image_bin);
    }

    return $GLOBALS['sae_image_instance'];

}

function sae_storage_init( $accesskey ,  $secretkey , $ssl = false )
{
    if( !isset( $GLOBALS['sae_storage_instance'] ) )
    {
        include_once( 'sae_storage.class.php' );
        $GLOBALS['sae_storage_instance'] = new SaeStorage($accesskey,$secretkey);
    }

    return $GLOBALS['sae_storage_instance'];
}

function sae_mysql_init( $host , $port , $accesskey , $secretkey , $appname , $do_replication = true )
{
    if( !isset( $GLOBALS['sae_mysql_instance'] ) )
    {
        include_once( 'sae_mysql.class.php' );
        $GLOBALS['sae_mysql_instance'] = new SaeMysql();
    }

    return $GLOBALS['sae_mysql_instance'];
}


//TODU 完善 fetch url
//-------------------------------------------------------------------------------------------------

function _header_info($header)
{
 $hinfo = array();
 $header_lines = explode("\r",trim( $header));
 $first = array_shift($header_lines);
 // HTTP/1.1 301 Moved Permanently
 $reg ="/HTTP\/(.+?)\s([0-9]+)\s(.+)/is";
 if(preg_match($reg,trim($first),$out))
   {
    $hinfo['version'] = $out[1];
    $hinfo['code'] = $out[2];
    $hinfo['code_info'] = $out[3];
   }
 else
    return false;
 if(is_array($header_lines))
   {
    foreach($header_lines as $line)
           {
            $fs=explode( ":" , trim($line),2);
            if(strlen(trim($fs[0])) > 0 )
              {
               if(isset( $hinfo[strtolower(trim($fs[0]))] ) )
                  $hinfo[strtolower(trim($fs[0]))] = array_merge( (array)$hinfo[strtolower(trim($fs[0]))] , (array)trim($fs[1]) );
               else
                  $hinfo[strtolower(trim($fs[0]))] = trim($fs[1]);
              }
           }
    }
 return $hinfo;
}
//-------------------------------------------------------------------------------------------------

function _get_signature($accesskey,$securekey,&$header_array)
{
 $content="FetchUrl";
 $content.=$header_array["FetchUrl"];
 $content.="TimeStamp";
 $content.=$header_array['TimeStamp'];
 $content.="AccessKey";
 $content.=$header_array['AccessKey'];
 return base64_encode(hash_hmac('sha256',$content,$securekey,true));
}
//-------------------------------------------------------------------------------------------------

function _read_header($ch,$string)
{
 global $errno,$errmsg,$rheader;
 $rheader.=$string;
 $ret=explode(" ",$string);
 if(count($ret)==3 && $ret[0]=='HTTP/1.1')
   {
    if($ret[1]==200)
       $errno=0;
    else
       {
       $errno=$ret[1];
       $errmsg=$ret[2];
       }
   }
 return strlen($string);
}
//-------------------------------------------------------------------------------------------------

function _read_data($ch,$string)
{
 global $rdata;
 $rdata.=$string;
 return strlen($string);
}
//-------------------------------------------------------------------------------------------------

function _fetch_url($url,$accesskey,$securekey,&$header,&$error,$opt=NULL)
{
 global $errno,$errmsg,$rheader,$rdata;
 $rheader='';
 $rdata='';
 $errno=0;
 $errmsg='';
 $ch=curl_init();
 curl_setopt($ch,CURLOPT_HEADERFUNCTION,'_read_header');
 curl_setopt($ch,CURLOPT_WRITEFUNCTION,'_read_data');
 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,3);
 curl_setopt($ch,CURLOPT_TIMEOUT,10);
 $header_array=array();
 if($opt && is_array($opt))
   {
    if(array_key_exists('username',$opt) && array_key_exists('password',$opt))
       curl_setopt($ch,CURLOPT_USERPWD,$opt['username'].':'.$opt['password']);
    if(array_key_exists('useragent',$opt))
       curl_setopt($ch,CURLOPT_USERAGENT,$opt['useragent']);
    if(array_key_exists('post',$opt))
      {
       curl_setopt($ch,CURLOPT_POST,true);
       curl_setopt($ch,CURLOPT_POSTFIELDS,$opt['post']);
      }
    if(array_key_exists('truncated',$opt))
       $header_array['AllowTruncated']=$opt['truncated'];

//    if(array_key_exists('connecttimeout',$opt))
//       $header_array['ConnectTimeout']=$opt['connecttimeout'];
//    if(array_key_exists('sendtimeout',$opt))
//       $header_array['SendTimeout']=$opt['sendtimeout'];
//    if(array_key_exists('readtimeout',$opt))
//       $header_array['ReadTimeout']=$opt['readtimeout'];

    if(array_key_exists('headers',$opt))
      {
       $headers=$opt['headers'];
       if(is_array($headers))
         {
          foreach($headers as $k => $v)
                  $header_array[$k]=$v;
         }
      }
   }//end if is_array
 $header_array['FetchUrl']=$url;
 $header_array['AccessKey']=$accesskey;
 $header_array['TimeStamp']=date('Y-m-d H:i:s');
 $header_array['Signature']=_get_signature($accesskey,$securekey,$header_array);

 $header_array2=array();
 foreach($header_array as $k => $v)
         array_push($header_array2,$k.': '.$v);

 curl_setopt($ch,CURLOPT_HTTPHEADER,$header_array2);
 curl_setopt($ch,CURLOPT_URL,SAE_FETCHURL_SERVICE_ADDRESS);
 curl_exec($ch);
 curl_close($ch);
 $header=$rheader;
 if($errno==0)
    return $rdata;
 $error=$errno.': '.$errmsg;
 return false;
}//end function fetchurl
//-------------------------------------------------------------------------------------------------

function fetch_url($url,$accesskey,$securekey,&$header,&$error,$opt=NULL)
{
 if($opt && is_array($opt) && array_key_exists('redirect',$opt) && $opt['redirect']==true)
   {
    $times=0;
    while(true)
         {
          $rt=_fetch_url($url,$accesskey,$securekey,$header,$error,$opt);
          if($rt==false)
             return $rt;
          $info=_header_info($header);
          $jump=false;
          if(isset($info['location']) && ($info['code']==301|| $info['code']==302) && $times<5)
            $jump=true;
          if($jump==true)
            {
             $times++;
             $url=$info['location'];
             continue;
            }
          return $rt;
         }//end while
   }//end if
 return _fetch_url($url,$accesskey,$securekey,$header,$error,$opt);
}
//-------------------------------------------------------------------------------------------------


//实现wrapper


if ( ! in_array("saemc", stream_get_wrappers()) )
    stream_wrapper_register("saemc", "SaeMemcacheWrapper");




class SaeMemcacheWrapper // implements WrapperInterface
{
    public $dir_mode = 16895 ; //040000 + 0222;
    public $file_mode = 33279 ; //0100000 + 0777;


    public function __construct()
    {
        $this->mc = memcache_init();
    }

    public function mc() {
        if ( !isset( $this->mc ) ) $this->mc = new Memcache();
        return $this->mc;
    }

    public function stream_open( $path , $mode , $options , &$opened_path)
    {
        $this->position = 0;
        $this->mckey = trim(substr($path, 8));
        $this->mode = $mode;
        $this->options = $options;

        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->mccontent = memcache_get( $this->mc, $this->mckey ) ) {
                $this->get_file_info( $this->mckey );
                $this->stat['mode'] = $this->stat[2] = $this->file_mode;
            } else {
                trigger_error("fopen({$path}): failed to read from Memcached: No such key.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            if ( $this->mccontent = memcache_get( $this->mc , $this->mckey ) ) {
                $this->get_file_info( $this->mckey );
                $this->stat['mode'] = $this->stat[2] = $this->file_mode;
                $this->position = strlen($this->mccontent);
            } else {
                $this->mccontent = '';
                $this->stat['ctime'] = $this->stat[10] = time();
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( !memcache_get( $this->mc , $this->mckey ) ) {
                $this->mccontent = '';
                $this->statinfo_init();
                $this->stat['ctime'] = $this->stat[10] = time();
            } else {
                trigger_error("fopen({$path}): failed to create at Memcached: Key exists.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->mccontent = '';
            $this->statinfo_init();
            $this->stat['ctime'] = $this->stat[10] = time();
        } else {
            $this->mccontent = memcache_get( $this->mc , $this->mckey );
        }

        return true;
    }

    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }


        $ret = substr( $this->mccontent , $this->position, $count);
        $this->position += strlen($ret);

        $this->stat['atime'] = $this->stat[8] = time();
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;

        return $ret;
    }

    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        $left = substr($this->mccontent, 0, $this->position);
        $right = substr($this->mccontent, $this->position + strlen($data));
        $this->mccontent = $left . $data . $right;

        if ( memcache_set( $this->mc , $this->mckey , $this->mccontent ) ) {
            $this->stat['mtime'] = $this->stat[9] = time();
            $this->position += strlen($data);
            return $this->stat['size'] = $this->stat[7] = strlen( $data );
        }
        else return false;
    }

    public function stream_close()
    {

        memcache_set( $this->mc , $this->mckey.'.meta' ,  serialize($this->stat)  );
        //memcache_close( $this->mc );
    }


    public function stream_eof()
    {

        return $this->position >= strlen( $this->mccontent  );
    }

    public function stream_tell()
    {

        return $this->position;
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {

        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->mccontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->mccontent ) + $offset >= 0) {
                $this->position = strlen( $this->mccontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

    public function stream_stat()
    {
        return $this->stat;
    }

    // ============================================
    public function mkdir($path , $mode , $options)
    {
        $path = trim(substr($path, 8));


        //echo "回调mkdir\n";
        $path  = rtrim( $path  , '/' );

        $this->stat = $this->get_file_info( $path );
        $this->stat['ctime'] = $this->stat[10] = time();
        $this->stat['mode'] = $this->stat[2] = $this->dir_mode;

        //echo "生成新的stat数据" . print_r( $this->stat , 1 );

        memcache_set( $this->mc() , $path.'.meta' ,  serialize($this->stat)  );

        //echo "写入MC. key= " . $path.'.meta ' .  memcache_get( $this->mc , $path.'.meta'  );
        memcache_close( $this->mc );


        return true;
    }

    public function rename($path_from , $path_to)
    {
        $path_from = trim(substr($path_from, 8));
        $path_to = trim(substr($path_to, 8));


        memcache_set( $this->mc() , $path_to , memcache_get( $this->mc() , $path_from ) );
        memcache_set( $this->mc() , $path_to . '.meta' , memcache_get( $this->mc() , $path_from . '.meta' ) );
        memcache_delete( $this->mc() , $path_from );
        memcache_delete( $this->mc() , $path_from.'.meta' );
        clearstatcache( true );
        return true;
    }

    public function rmdir($path , $options)
    {
        $path = trim(substr($path, 8));


        $path  = rtrim( $path  , '/' );

        memcache_delete( $this->mc() , $path .'.meta'  );
        clearstatcache( true );
        return true;
    }

    public function unlink($path)
    {
        $path = trim(substr($path, 8));
        $path  = rtrim( $path  , '/' );

        memcache_delete( $this->mc() , $path );
        memcache_delete( $this->mc() , $path . '.meta' );
        clearstatcache( true );
        return true;
    }

    public function url_stat($path , $flags)
    {
        $path = trim(substr($path, 8));
        $path  = rtrim( $path  , '/' );

        if ( !$this->is_file_info_exists( $path ) ) {
            return false;
        } else {
            if ( $stat = memcache_get( $this->mc() , $path . '.meta' ) ) {
                $this->stat = unserialize($stat);
                if ( is_array($this->stat) ) {
                    if ( $this->stat['mode'] == $this->dir_mode || $c = memcache_get( $this->mc(), $path ) ) {
                        return $this->stat;
                    } else {
                        memcache_delete( $this->mc() , $path . '.meta' );
                    }
                }
            }
            return false;
        }
    }






    // ============================================

    public function is_file_info_exists( $path )
    {
        //echo "获取MC数据 key= " .  $path.'.meta' ;
        $d = memcache_get( $this->mc() , $path . '.meta' );
        //echo "\n返回数据为" . $d . "\n";
        return $d;
    }

    public function get_file_info( $path )
    {
        if ( $stat = memcache_get( $this->mc() , $path . '.meta' ) )
            return $this->stat =  unserialize($stat);
        else $this->statinfo_init();
    }

    public function statinfo_init( $is_file = true )
    {
        $this->stat['dev'] = $this->stat[0] = 0x8002;
        $this->stat['ino'] = $this->stat[1] = mt_rand(10000, PHP_INT_MAX);

        if( $is_file )
            $this->stat['mode'] = $this->stat[2] = $this->file_mode;
        else
            $this->stat['mode'] = $this->stat[2] = $this->dir_mode;

        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = 0;
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = 0;
        $this->stat['ctime'] = $this->stat[10] = 0;
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

    }

    public function dir_closedir() {
        return false;
    }

    public function dir_opendir($path, $options) {
        return false;
    }

    public function dir_readdir() {
        return false;
    }

    public function dir_rewinddir() {
        return false;
    }

    public function stream_cast($cast_as) {
        return false;
    }

    public function stream_flush() {
        return false;
    }

    public function stream_lock($operation) {
        return false;
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

}





/* BEGIN *******************  Storage Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/

class SaeStorageWrapper // implements WrapperInterface
{
    private $writen = true;

    public function __construct()
    {
        $this->stor = new SaeStorage();
    }

    public function stor() {
        if ( !isset( $this->stor ) ) $this->stor = new SaeStorage();
    }

    public function stream_open( $path , $mode , $options , &$opened_path)
    {
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');
        $this->position = 0;
        $this->mode = $mode;
        $this->options = $options;

        // print_r("OPEN\tpath:{$path}\tmode:{$mode}\toption:{$option}\topened_path:{$opened_path}\n");

        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
            } else {
                trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            trigger_error("fopen({$path}): Sorry, saestor does not support appending", E_USER_WARNING);
            if ( $this->fcontent = $this->stor->read($this->domain, $this->file) ) {
            } else {
                trigger_error("fopen({$path}): failed to read from Storage: No such domain or file.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( !$this->stor->getAttr($this->domain, $this->file) ) {
                $this->fcontent = '';
            } else {
                trigger_error("fopen({$path}): failed to create at Storage: File exists.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->fcontent = '';
        } else {
            $this->fcontent = $this->stor->read($this->domain, $this->file);
        }

        return true;
    }

    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }

        $ret = substr( $this->fcontent , $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        // print_r("WRITE\tcontent:".strlen($this->fcontent)."\tposition:".$this->position."\tdata:".strlen($data)."\n");

        $left = substr($this->fcontent, 0, $this->position);
        $right = substr($this->fcontent, $this->position + strlen($data));
        $this->fcontent = $left . $data . $right;

        //if ( $this->stor->write( $this->domain, $this->file, $this->fcontent ) ) {
        $this->position += strlen($data);
        if ( strlen( $data ) > 0 )
            $this->writen = false;

        return strlen( $data );
        //}
        //else return false;
    }

    public function stream_close()
    {
        if (!$this->writen) {
            $this->stor->write( $this->domain, $this->file, $this->fcontent );
            $this->writen = true;
        }
    }


    public function stream_eof()
    {

        return $this->position >= strlen( $this->fcontent  );
    }

    public function stream_tell()
    {

        return $this->position;
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {


        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->fcontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->fcontent ) + $offset >= 0) {
                $this->position = strlen( $this->fcontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

    public function unlink($path)
    {
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

        clearstatcache( true );
        return $this->stor->delete( $this->domain , $this->file );
    }

    public function stream_flush() {
        if (!$this->writen) {
            $this->stor->write( $this->domain, $this->file, $this->fcontent );
            $this->writen = true;
        }

        return $this->writen;
    }

    public function stream_stat() {
        return array();
    }

    public function url_stat($path, $flags) {
        self::stor();
        $pathinfo = parse_url($path);
        $this->domain = $pathinfo['host'];
        $this->file = ltrim(strstr($path, $pathinfo['path']), '/\\');

        if ( $attr = $this->stor->getAttr( $this->domain , $this->file ) ) {
            $stat = array();
            $stat['dev'] = $stat[0] = 0x8001;
            $stat['ino'] = $stat[1] = 0;;
            $stat['mode'] = $stat[2] = 33279; //0100000 + 0777;
            $stat['nlink'] = $stat[3] = 0;
            $stat['uid'] = $stat[4] = 0;
            $stat['gid'] = $stat[5] = 0;
            $stat['rdev'] = $stat[6] = 0;
            $stat['size'] = $stat[7] = $attr['length'];
            $stat['atime'] = $stat[8] = 0;
            $stat['mtime'] = $stat[9] = $attr['datetime'];
            $stat['ctime'] = $stat[10] = $attr['datetime'];
            $stat['blksize'] = $stat[11] = 0;
            $stat['blocks'] = $stat[12] = 0;
            return $stat;
        } else {
            return false;
        }
    }

    public function dir_closedir() {
        return false;
    }

    public function dir_opendir($path, $options) {
        return false;
    }

    public function dir_readdir() {
        return false;
    }

    public function dir_rewinddir() {
        return false;
    }

    public function mkdir($path, $mode, $options) {
        return false;
    }

    public function rename($path_from, $path_to) {
        return false;
    }

    public function rmdir($path, $options) {
        return false;
    }

    public function stream_cast($cast_as) {
        return false;
    }

    public function stream_lock($operation) {
        return false;
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

}


if ( in_array( "saestor", stream_get_wrappers() ) ) {
    stream_wrapper_unregister("saestor");
}
stream_wrapper_register( "saestor", "SaeStorageWrapper" )
    or die( "Failed to register protocol" );

/* END *********************  Storage Wrapper By Elmer Zhang At 16/Mar/2010 14:47 ****************/


/* BEGIN *******************  KVDB Wrapper By Elmer Zhang At 12/Dec/2011 12:37 ****************/

class SaeKVWrapper // implements WrapperInterface
{
    private $dir_mode = 16895 ; //040000 + 0222;
    private $file_mode = 33279 ; //0100000 + 0777;


    public function __construct() { }

    private function kv() {
        if ( !isset( $this->kv ) ) $this->kv = new SaeKV();
        $this->kv->init();
        return $this->kv;
    }

    private function open( $key ) {
        $value = $this->kv()->get( $key );
        if ( $value !== false && $this->unpack_stat(substr($value, 0, 20)) === true ) {
            $this->kvcontent = substr($value, 20);
            return true;
        } else {
            return false;
        }
    }

    private function save( $key ) {
        $this->stat['mtime'] = $this->stat[9] = time();
        if ( isset($this->kvcontent) ) {
            $this->stat['size'] = $this->stat[7] = strlen($this->kvcontent);
            $value = $this->pack_stat() . $this->kvcontent;
        } else {
            $this->stat['size'] = $this->stat[7] = 0;
            $value = $this->pack_stat();
        }
        return $this->kv()->set($key, $value);
    }

    private function unpack_stat( $str ) {
        $arr = unpack("L5", $str);

        // check if valid
        if ( $arr[1] < 10000 ) return false;
        if ( !in_array($arr[2], array( $this->dir_mode, $this->file_mode ) ) ) return false;
        if ( $arr[4] > time() ) return false;
        if ( $arr[5] > time() ) return false;

        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = $arr[1];
        $this->stat['mode'] = $this->stat[2] = $arr[2];
        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = $arr[3];
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = $arr[4];
        $this->stat['ctime'] = $this->stat[10] = $arr[5];
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

        return true;
    }

    private function pack_stat( ) {
        $str = pack("LLLLL", $this->stat['ino'], $this->stat['mode'], $this->stat['size'], $this->stat['ctime'], $this->stat['mtime']);
        return $str;
    }

    public function stream_open( $path , $mode , $options , &$opened_path)
    {
        $this->position = 0;
        $this->kvkey = rtrim(trim(substr(trim($path), 8)), '/');
        $this->mode = $mode;
        $this->options = $options;

        if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                trigger_error("fopen({$path}): No such key in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
            if ( $this->open( $this->kvkey ) === true ) {
                $this->position = strlen($this->kvcontent);
            } else {
                $this->kvcontent = '';
                $this->statinfo_init();
            }
        } elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
            if ( $this->open( $this->kvkey ) === false ) {
                $this->kvcontent = '';
                $this->statinfo_init();
            } else {
                trigger_error("fopen({$path}): Key exists in KVDB.", E_USER_WARNING);
                return false;
            }
        } elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
            $this->kvcontent = '';
            $this->statinfo_init();
        } else {
            $this->open( $this->kvkey );
        }

        return true;
    }

    public function stream_read($count)
    {
        if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
            return false;
        }

        $ret = substr( $this->kvcontent , $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
        if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
            return false;
        }

        $left = substr($this->kvcontent, 0, $this->position);
        $right = substr($this->kvcontent, $this->position + strlen($data));
        $this->kvcontent = $left . $data . $right;

        if ( $this->save( $this->kvkey ) === true ) {
            $this->position += strlen($data);
            return strlen( $data );
        } else return false;
    }

    public function stream_close()
    {
        $this->save( $this->kvkey );
    }


    public function stream_eof()
    {

        return $this->position >= strlen( $this->kvcontent  );
    }

    public function stream_tell()
    {

        return $this->position;
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {

        switch ($whence) {
        case SEEK_SET:

            if ($offset < strlen( $this->kvcontent ) && $offset >= 0) {
                $this->position = $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_CUR:

            if ($offset >= 0) {
                $this->position += $offset;
                return true;
            }
            else
                return false;

            break;

        case SEEK_END:

            if (strlen( $this->kvcontent ) + $offset >= 0) {
                $this->position = strlen( $this->kvcontent ) + $offset;
                return true;
            }
            else
                return false;

            break;

        default:

            return false;
        }
    }

    public function stream_stat()
    {
        return $this->stat;
    }

    // ============================================
    public function mkdir($path , $mode , $options)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        if ( $this->open( $path ) === false ) {
            $this->statinfo_init( false );
            return $this->save( $path );
        } else {
            trigger_error("mkdir({$path}): Key exists in KVDB.", E_USER_WARNING);
            return false;
        }
    }

    public function rename($path_from , $path_to)
    {
        $path_from = rtrim(trim(substr(trim($path_from), 8)), '/');
        $path_to = rtrim(trim(substr(trim($path_to), 8)), '/');

        if ( $this->open( $path_from ) === true ) {
            clearstatcache( true );
            return $this->save( $path_to );
        } else {
            trigger_error("rename({$path_from}, {$path_to}): No such key in KVDB.", E_USER_WARNING);
            return false;
        }
    }

    public function rmdir($path , $options)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        clearstatcache( true );
        return $this->kv()->delete($path);
    }

    public function unlink($path)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        clearstatcache( true );
        return $this->kv()->delete($path);
    }

    public function url_stat($path , $flags)
    {
        $path = rtrim(trim(substr(trim($path), 8)), '/');

        if ( $this->open( $path ) !== false ) {
            return $this->stat;
        } else {
            return false;
        }
    }






    // ============================================

    private function statinfo_init( $is_file = true )
    {
        $this->stat['dev'] = $this->stat[0] = 0x8003;
        $this->stat['ino'] = $this->stat[1] = crc32(SAE_APPNAME . '/' . $this->kvkey);

        if( $is_file )
            $this->stat['mode'] = $this->stat[2] = $this->file_mode;
        else
            $this->stat['mode'] = $this->stat[2] = $this->dir_mode;

        $this->stat['nlink'] = $this->stat[3] = 0;
        $this->stat['uid'] = $this->stat[4] = 0;
        $this->stat['gid'] = $this->stat[5] = 0;
        $this->stat['rdev'] = $this->stat[6] = 0;
        $this->stat['size'] = $this->stat[7] = 0;
        $this->stat['atime'] = $this->stat[8] = 0;
        $this->stat['mtime'] = $this->stat[9] = time();
        $this->stat['ctime'] = $this->stat[10] = 0;
        $this->stat['blksize'] = $this->stat[11] = 0;
        $this->stat['blocks'] = $this->stat[12] = 0;

    }

    public function dir_closedir() {
        return false;
    }

    public function dir_opendir($path, $options) {
        return false;
    }

    public function dir_readdir() {
        return false;
    }

    public function dir_rewinddir() {
        return false;
    }

    public function stream_cast($cast_as) {
        return false;
    }

    public function stream_flush() {
        return false;
    }

    public function stream_lock($operation) {
        return false;
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

}

if ( ! in_array("saekv", stream_get_wrappers()) )
    stream_wrapper_register("saekv", "SaeKVWrapper");

/* END *********************  KVDB Wrapper By Elmer Zhang At 12/Dec/2011 12:37 ****************/



/* START *********************  Supported for AppCookie By Elmer Zhang At 13/Jun/2010 15:49 ****************/
$appSettings = array();
if (isset($_SERVER['HTTP_APPCOOKIE']) && $_SERVER['HTTP_APPCOOKIE']) {
    $appCookie = trim($_SERVER['HTTP_APPCOOKIE']);
    $tmpSettings = array_filter(explode(';', $appCookie));
    if ($tmpSettings) {
        foreach($tmpSettings as $setting) {
            $tmp = explode('=', $setting);
            $appSettings[$tmp[0]] = $tmp[1];
        }
    }
}

if (isset($appSettings['xhprof']) && in_array($_SERVER['HTTP_APPVERSION'], explode(',', $appSettings['xhprof']))) {
    sae_xhprof_start();
    register_shutdown_function("sae_xhprof_end");
}

if (isset($appSettings['debug']) && in_array($_SERVER['HTTP_APPVERSION'], explode(',', $appSettings['debug']))) {
    sae_set_display_errors(true);
}

unset($appSettings);
unset($appCookie);
unset($tmpSettings);
unset($tmp);
