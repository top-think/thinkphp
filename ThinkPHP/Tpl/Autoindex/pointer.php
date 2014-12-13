<?php
// ThinkPHP环境探针布署模式
// Author：caolei@topthink.com
//检查当前脚本环境
function check_env(){
        //预定义查询变量
      $info = array(
          'safe_mode'=>array('运行于安全模式',0),
          'allow_url_fopen'=>array('访问 URL 对象','OFF','建议关闭'),
          'register_globals'=>array('注册全局变量','OFF','建议关闭'),
          'magic_quotes_gpc'=>array('魔术引号开启','OFF','建议关闭'),
          'short_open_tag'=>array('短标记支持','OFF','建议关闭'),
          'magic_quotes_runtime'=>array('自动转义溢出字符','OFF','建议关闭'),
          'enable_dl'=>array('允许动态加载链接库','OFF','建议打开'),
          'display_errors'=>array('显示错误信息',0),
          'post_max_size'=>array('post最大数据量',0),
          'upload_max_filesize'=>array('上传文件的最大大小',0),
          'memory_limit'=>array('脚本最大内存使用量',1),
       );

       $fun = array(
            'mysql_close'=>array('MySQL数据库','ON','推荐使用MySQL数据库'),
            'gd_info'=>array('图形处理 GD 库','ON','建议打开GD库'),
            'socket_accept'=>array('Socket支持',0),
            'xml_set_object'=>array('XML解析支持','ON','建议开启'),
            'gzclose'=>array('压缩文件支持(Zlib)',0),
            'mcrypt_cbc'=>array('MCrypt加密处理',0),
            'preg_match'=>array('PREL相容语法 PCRE','ON','必须打开PREL语法兼容'),
        );
        //根据查询条件,动态赋值
        $phpversiON = phpversion();//获取php版本号
        $versiON_msg=versiON_compare(PHP_VERSION,'5.2.0','<')?'推荐使用5.2.0以上版本':'正常使用';
        $run_env = strtoupper(php_sapi_name());//查看运行环境
        $os = PHP_OS;
        $is_rewrite = false;//预设rewrite为否定
        if (($is_apache=strpos($run_env,'APACHE'))!==false)
        {
            $apache_list = apache_get_modules();
            $is_rewrite = array_search('mod_rewrite',$apache_list)?true:false;
            unset($apache_list);
        }

        foreach ($info as $key => &$val)
        {
            $var = get_cfg_var($key)?get_cfg_var($key):ini_get($key);
            $val['value'] = ($var==0)?'OFF':(($var==1)?'ON':$var);
            if (is_numeric($val[1]) || $val['value']==$val[1])
            {
               $val['message'] = '<span class="blue">√</span>';
            }else{
                $val['message'] = '<span class="red">'.$val[2].'</span>';
            }
        }
        foreach ($fun as $key => &$val)
        {
            $val['value'] = functiON_exists($key)?'ON':'OFF';
            if (is_numeric($val[1]) || $val['value']==$val[1])
            {
               $val['message'] = '<span class="blue">√</span>';
            }else{
                $val['message'] = '<span class="red">'.$val[2].'</span>';
            }
        }
        $moban =<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-cn">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<meta name="author" content="Topthink - <www.thinkphp.cn>" />
<meta name="Copyright" content="" />
<meta name="description" content="" />
<meta name="keywords" content="" />
<title>ThinkPHP环境检测</title>
<style type="text/css">
*{margin:0;padding:0}
    body{font:400 14px/2em 'Microsoft Yahei','黑体',Tahoma,sans-serif;text-align:center;background:#fff;}
    table {margin:10px auto;width:700px;}
    .red{color:red;font-weight:bold;}
    .blue{color:blue;}
    .title{background:#e8efff;font-weight:bold;font-size:14px;padding:10px;color:blue;}
    .thead{background-color:#E8EFFF;color:blue;}
    .thead td{padding:4px;font-weight:bold;font-size:14px;}
    .tbhead{font-weight:normal;text-align:left;}
    td{text-align:left;}
</style>
</head>
<body>
    <div class="title">环境检测</div>
    <table>
        <tr class='thead'><td colspan="2">服务器参数</td></tr>
        <tr><th class="tbhead">服务器域名/IP：</th><td>{$_SERVER['SERVER_NAME']} [{$_SERVER['REMOTE_ADDR']}]</td></tr>
        <tr><th class="tbhead">服务端口：</th><td>{$_SERVER['SERVER_PORT']}</td></tr>
        <tr><th class="tbhead">服务器类型/版本：</th><td>{$_SERVER['SERVER_SOFTWARE']}</td></tr>
        <tr><th class="tbhead">服务器操作系统：</th><td>{$os}</td></tr>
        <tr><th class="tbhead">网站根目录：</th><td>{$_SERVER['DOCUMENT_ROOT']}</td></tr>
        <tr><th class="tbhead">当前文件所在位置：</th><td>{$_SERVER['SCRIPT_FILENAME']}</td></tr>
    </table>
    <table>
    <tr>
        <th class='title'>变量名称</th><th class='title'>当前状态</th><th class='title'>推荐</th>
    </tr>
    <tbody>
        <tr><th class='tbhead'>PHP版本</th><td>{$phpversiON}</td><td>{$versiON_msg}</td></tr>
        <tr><th class='tbhead'>PHP运行环境</th><td>{$run_env}</td><td></td></tr>
HTML;
        $moban .="<tr><th class='tbhead'>是否开启rewrite</th><td>";
        if ($is_apache===false)
        {
            $moban .= "无法检测</td><td></td></tr>";
        }else{
            $moban .= ($is_rewrite)?'<span color="green">开启</span>':'<span class="red">未开启</span>';
            $moban .="</td><td></td></tr>";
        }
        $moban .="<tr class='thead'><td colspan='3'>PHP环境</td></tr>";
        foreach ($info as $key => $val)
        {
            $moban.="<tr><th class='tbhead'>{$val[0]} [{$key}]</th><td>{$val['value']}</td><td>{$val['message']}</td></tr>";
        }
        $moban .="<tr class='thead'><td colspan='3'>PHP模块检测</td></tr>";

        foreach($fun as $key=>$val)
        {
            $moban.="<tr><th class='tbhead'>{$val[0]} [{$key}]</th><td>{$val['value']}</td><td>{$val['message']}</td></tr>";
        }
        $moban .="</tbody></table></body></html>";
        return $moban;
    }
?>