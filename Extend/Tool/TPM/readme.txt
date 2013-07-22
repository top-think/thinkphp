配置项目配置文件：  
'LAYOUT_ON'=>true

在项目Conf目录下添加文件 tags.php
<?php
return array(
 'action_begin'=>array('SwitchMobileTpl')
)


将Tpl文件夹复制到项目中，作为项目的模板。

将SwitchMobileTplBehavior.class.php 复制到 项目目录下 Lib/Behavior 目录下。

将TemplateMobile.class.php 文件复制到 ThinkPHP/Extend/Driver/Template 下。 


支持手机客户端调整，需要修改核心文件 ThinkPHP/Common/functions.php 中得redirect函数，
修改如下：


function redirect($url, $time=0, $msg='') {
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
           //手机客户端跳转发送redirect的header
            if(defined('IS_CLIENT') && IS_CLIENT){
                if(''!==__APP__){
                    $url=substr($url,strlen(__APP__));
                }
                header('redirect:'.$url);
            }else{
                header('Location: ' . $url);
            }
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

