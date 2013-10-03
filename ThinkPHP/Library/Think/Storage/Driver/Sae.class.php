<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Storage\Driver;
use Think\Storage;
// 本地文件写入存储类
class Sae extends Storage{

    /**
     * 架构函数
     * @access public
     */
    private $mc;
    private $kvs=array(); 
    private $htmls=array();
    public function __construct() {
        if(!function_exists('memcache_init')){
              header('Content-Type:text/html;charset=utf-8');
              exit('请在SAE平台上运行代码。');
        }
        $this->mc=@memcache_init();
        if(!$this->mc){
              header('Content-Type:text/html;charset=utf-8');
              exit('您未开通Memcache服务，请在SAE管理平台初始化Memcache服务');
        }
    }

    /**
     * 获得SaeKv对象
     */

    public function getKv(){
        static $kv;
        if(!$kv){
           $kv=new \SaeKV();
           if(!$kv->init()) 
               E('您没有初始化KVDB，请在SAE管理平台初始化KVDB服务');
        } 
        return $kv;
    } 


    /**
     * 文件内容读取
     * @access public
     * @param string $filename  文件名
     * @return string     
     */
    public function read($filename,$type=''){
        return $this->get($filename,'content',$type);
    }

    public function readHtml($filename,$type=''){
        return $this->getHtml($filename,'content');
    }


    public function readF($filename){
        $kv=$this->getKv();
        if(!isset($this->kvs[$filename])){
            $this->kvs[$filename]=$kv->get($filename);
        }
        return $this->kvs[$filename];
    }


    /**
     * 文件写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  文件内容
     * @return boolean         
     */
    public function put($filename,$content,$type=''){
        if(!$this->mc->set($filename,time().$content,MEMCACHE_COMPRESSED,0)){
            E(L('_STORAGE_WRITE_ERROR_').':'.$filename);
        }else{
            return true;
        }
    }

    public function putHtml($filename,$content){
        $kv=$this->getKv(); 
        $content=time().$content;
        $this->htmls[$filename]=$content;
        return $kv->set($filename,$content);
    }

    public function putF($filename,$content){
        $kv=$this->getKv(); 
        $this->kvs[$filename]=$content;
        return $kv->set($filename,$content);
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  追加的文件内容
     * @return boolean        
     */
    public function append($filename,$content,$type=''){
        if($old_content=$this->read($filename,$type)){
            $content =  $old_content.$content;
        }
        return $this->put($filename,$content,$type);
    }

    /**
     * 加载文件
     * @access public
     * @param string $filename  文件名
     * @param array $vars  传入变量
     * @return void        
     */
    public function load($filename,$vars=null){
        if(!is_null($vars))
            extract($vars, EXTR_OVERWRITE);
        eval('?>'.$this->read($filename));
    }

    /**
     * 文件是否存在
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    public function has($filename,$type=''){
        if($this->read($filename,$type)){
            return true; 
        }else{
            return false;
        }
    }

    public function hasF($filename){
        if(false!==$this->readF($filename)){
            return true; 
        }
        return false;
    }

    /**
     * 文件删除
     * @access public
     * @param string $filename  文件名
     * @return boolean     
     */
    public function unlink($filename,$type=''){
        $this->mc->delete($filename);
    }

    public function unlinkF($filename){
        $kv=$this->getKv();  
        unset($this->kvs[$filename]);
        return $kv->delete($filename);
    }

    /**
     * 读取文件信息
     * @access public
     * @param string $filename  文件名
     * @param string $name  信息名 mtime或者content
     * @return boolean     
     */
    public function get($filename,$name,$type=''){
        $content=$this->mc->get($filename);
        if(false===$content){
            return false;
        }
        $info   =   array(
            'mtime'     =>  substr($content,0,10),
            'content'   =>  substr($content,10)
        );
        return $info[$name];
    }

    public function getHtml($filename,$name){
        if(!isset($this->htmls[$filename])){
            $kv=$this->getKv();
            $this->htmls[$filename]=$kv->get($filename);
        }
        $content=$this->htmls[$filename];
        if(false===$content){
            return false;
        }
        $info   =   array(
            'mtime'     =>  substr($content,0,10),
            'content'   =>  substr($content,10)
        );
        return $info[$name];
    }

}
