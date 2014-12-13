<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <www.3g4k.com>
// +----------------------------------------------------------------------
// $Id: SaeStorage.class.php 2701 2012-02-02 12:27:51Z liu21st $
/**
 * storage模拟器
 * 在本地环境时，domain对应了Public文件夹下以domain名命名的的文件夹
 */
//本地默认的域在public中， 建立的文件夹名为域名。
class SaeStorage extends SaeObject {

    private $domainDir; //域的根目录
    private $filterPath;
    private $url;
    private $domainSize = array(); //记录域大小
    private $domainSizeFlag = "";

    //todu 增加api文档没有的函数
    public function __construct($_accessKey='', $_secretKey='') {
        global $sae_config;
        $this->domainDir = $sae_config['storage_dir'];
        $this->url = $sae_config['storage_url'];
        parent::__construct();
    }

    public function delete($domain, $filename) {
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        if (Empty($domain) || Empty($filename)) {
            $this->errMsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain,filename) can not be empty!]';
            $this->errNum = -101;
            return false;
        }
        $filepath = $this->domainDir . $domain . "/" . $filename;
        if (unlink($filepath)) {
            return true;
        } else {
            $this->errno = -1;
            $this->errmsg = Imit_L("_SAE_STORAGE_DELETE_ERR_");
            return false;
        }
    }

    public function deleteFolder($domain, $path) {
        $domain = trim($domain);
        $path = $this->formatFilename($path);
        if (Empty($domain) || Empty($path)) {
            $this->errmsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain,path) can not be empty!]';
            $this->errno = -101;
            return false;
        }
        $folder = $this->domainDir . $domain . "/" . $path;
        $this->delDir($folder);
        return true;
    }

    private function delDir($directory) {
        if (is_dir($directory) == false) {
            exit("The Directory Is Not Exist!");
        }
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                is_dir("$directory/$file") ?
                                $this->delDir("$directory/$file") :
                                unlink("$directory/$file");
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            rmdir($directory);
        }
    }

    public function fileExists($domain, $filename) {
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        if (Empty($domain) || Empty($filename)) {
            $this->errmsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain,filename) can not be empty!]';
            $this->errno = -101;
            return false;
        }
        $filepath = $this->domainDir . $domain . "/" . $filename;
        return file_exists($filepath);
    }

    public function getAttr($domain, $filename, $attrKey=array()) {
        $filepath = $this->domainDir . $domain . "/" . $filename;
        if (!is_file($filepath)) {
            $this->errno = -1;
            $this->errmsg = Imit_L("_SAE_STORAGE_FILE_NOT_EXISTS_");
            return false;
        }
        if (empty($attrKey))
            $attrKey = array('fileName', 'length', 'datetime');
        $ret = array();
        foreach ($attrKey as $key) {
            switch ($key) {
                case "fileName":
                    $ret['fileName'] = $filename;
                    break;
                case "length":
                    $ret['length'] = filesize($filepath);
                    break;
                case "datetime":
                    $ret['datetime'] = filemtime($filepath); //todu 需要验证一下
                    break;
            }
        }
        return $ret;
    }

    public function getDomainCapacity($domain) {
        if (!isset($this->domainSize[$domain]))
            $this->getList($domain);
        return $this->domainSize[$domain];
    }

    public function getFilesNum($domain, $path=NULL) {
        static $filesNum = array();
        if (isset($filesNum[md5($domin . $path)]))
            return $filesNum[md5($domin . $path)];
        if ($path == NULL)
            $path = "*";
        $filesNum[md5($domin . $path)] = count($this->getList($domain, $path));
        return $filesNum[md5($domin . $path)];
    }

    public function getList($domain, $prefix='*', $limit=10, $offset=0) {
        $domain = trim($domain);
        if (Empty($domain)) {
            $this->errMsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain) can not be empty!]';
            $this->errNum = -101;
            return false;
        }
        $path = $this->domainDir . $domain;
        $this->filterPath = $path . "/";
        //记录域的大小
        if ($prefix == "*" && !isset($this->domainSize[$domain]))
            $this->domainSizeFlag = $domain;
        $files = $this->getAllList($path, $prefix);
        $this->domainSizeFlag = "";
        //偏移
        return array_slice($files, $offset, $limit);
    }

    //获得所有文件
    private function getAllList($path, $prefix, &$files=array()) {
        $list = glob($path . "/" . $prefix);
        //循环处理，创建数组
        $dirs = array();
        $_files = array();
        foreach ($list as $i => $file) {
            if (is_dir($file) && !$this->isEmpty($file)) {//如果不是空文件夹
                $dirs[] = $file;
                continue;
            };
            if (!empty($this->domainSizeFlag))
                $this->domainSize[$this->domainSizeFlag]+=filesize($file); //统计大小


            $_files[$i]['name'] = str_replace($this->filterPath, '', $file); //不含域的名称
            $_files[$i]['isDir'] = is_dir($file);
        }
        //排序$_files
        $cmp_func = create_function('$a,$b', '
			$k="isDir";
			if($a[$k]  ==  $b[$k])  return  0;
			return  $a[$k]>$b[$k]?-1:1;
			');
        usort($_files, $cmp_func);
        foreach ($_files as $file) {
            //设置$files
            $files[] = $file['isDir'] ? $file['name'] . "/__________sae-dir-tag" : $file['name'];
        }
        //循环数组，读取二级目录
        foreach ($dirs as $dir) {
            $this->getAllList($dir, "*", $files);
        }
        return $files;
    }

    //判断是否为空目录
    private function isEmpty($directory) {
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    public function getListByPath($domain, $path=NULL, $limit=100, $offset=0, $fold=true) {
        $filepath = $this->domainDir . $domain . "/" . $path;
        $list = scandir($filepath);
        //读取非折叠数据
        $files = array();
        $dirnum = 0;
        $filenum = 0;
        foreach ($list as $file) {
            //统计
            if ($file == '.' || $file == '..')
                continue;
            $fullfile = $filepath . "/" . $file;
            if (is_dir($fullfile)) {
                $dirnum++;
                $filename = $fullfile . "/__________sae-dir-tag";
            } else {
                $filenum++;
                $filename = $fullfile;
            }

            $filename = str_replace($this->domainDir . $domain . "/", '', $filename);

            $files[] = array(
                'name' => basename($filename),
                'fullName' => $filename,
                'length' => filesize($fullfile),
                'uploadTime' => filectime($fullfile)
            );
        }
        //偏移
        $files = array_slice($files, $offset, $limit);
        if ($fold) {
            //折叠处理
            $rets = array(
                'dirNum' => $dirnum,
                'fileNum' => $filenum
            );
            foreach ($files as $file) {
                if ($file['name'] == "__________sae-dir-tag") {
                    //文件夹
                    $rets['dirs'][] = array(
                        'name' => $file['name'],
                        'fullName' => $file['fullName']
                    );
                } else {
                    $rets['files'][] = $file;
                }
            }
            return $rets;
        }
        return $files;
    }

    public function getUrl($domain, $filename) {
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        return $this->url. $domain . "/" . $filename;
    }

    public function read($domain, $filename) {
        $domain = trim($domain);
        $filename = $this->formatFilename($filename);
        if (Empty($domain) || Empty($filename)) {
            $this->errmsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain,filename) can not be empty!]';
            $this->errno = -101;
            return false;
        }
        $filepath = $this->domainDir . $domain . "/" . $filename;
        return file_get_contents($filepath);
    }

    public function setDomainAttr($domain, $attr=array()) {
        //pass
        return true;
    }

    public function setFileAttr($domain, $filename, $attr=array()) {
        //pass
        return true;
    }

    public function upload($domain, $destFileName, $srcFileName, $attr=array()) {
        $domain = trim($domain);
        $destFileName = $this->formatFilename($destFileName);
        if (Empty($domain) || Empty($destFileName) || Empty($srcFileName)) {
            $this->errmsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . '[the value of parameter (domain,destFile,srcFileName) can not be empty!]';
            $this->errno = -101;
            return false;
        }
        //文件地址
        $filepath = $this->domainDir . $domain . "/" . $destFileName;
        $this->mkdir(dirname($filepath));
        if (!move_uploaded_file($srcFileName, auto_charset($filepath, 'utf-8', 'gbk'))) {
            $this->errmsg = Imit_L('_SAE_STORAGE_SERVER_ERR_');
            $this->errno = -12;
            return false;
        } else {
            return true;
        }
    }

    public function write($domain, $destFileName, $content, $size=-1, $attr=array(), $compress=false) {
        if (Empty($domain) || Empty($destFileName)) {
            $this->errmsg = Imit_L("_SAE_STORAGE_PARAM_EMPTY_") . "[the value of parameter (domain,destFileName,content) can not be empty!]";
            $this->errno = -101;
            return false;
        }
        //定义文件路径
        $filepath = $this->domainDir . $domain . "/" . $destFileName;
        $this->mkdir(dirname($filepath));
        //设置长度
        if ($size > -1)
            $content = substr($content, 0, $size);
        //写入文件
        if (file_put_contents($filepath, $content)) {
            return true;
        } else {
            $this->errmsg = Imit_L('_SAE_STORAGE_SERVER_ERR_');
            $this->errno = -12;
            return false;
        }
    }

    //创建目录，无限层次。传递一个文件的
    private function mkdir($dir) {
        static $_dir; // 记录需要建立的目录
        if (!file_exists($dir)) {
            if (empty($_dir))
                $_dir = $dir;
            if (!file_exists(dirname($dir))) {
                $this->mkdir(dirname($dir));
            } else {
                mkdir($dir);
                if (!file_exists($_dir)) {
                    $this->mkdir($_dir);
                } else {
                    $_dir = "";
                }
            }
        }
    }

    private function formatFilename($filename) {
        $filename = trim($filename);

        $encodings = array('UTF-8', 'GBK', 'BIG5');

        $charset = mb_detect_encoding($filename, $encodings);
        if ($charset != 'UTF-8') {
            $filename = mb_convert_encoding($filename, "UTF-8", $charset);
        }

        return $filename;
    }

}

?>