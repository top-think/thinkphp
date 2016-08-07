<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
 <head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>ThinkPHP示例：CURD操作</title>
<style type="text/css">
*{ padding: 0; margin: 0;font-size:16px; font-family: "微软雅黑"} 
div{ padding: 3px 20px;} 
body{ background: #fff; color: #333;}
h2{font-size:36px}
input,textarea {border:1px solid silver;padding:5px;width:350px}
input{height:32px}
input.button,input.submit{width:68px; margin:2px 5px;letter-spacing:4px;font-size:16px; font-weight:bold;border:1px solid silver; text-align:center; background-color:#F0F0FF;cursor:pointer}
div.result{border:1px solid #d4d4d4; background:#FFC;color:#393939; padding:8px 20px;float:auto; width:85%;}
</style>
 </head>
 <body>
 <div class="main">
 <h2>ThinkPHP示例之：CURD操作 </h2>
<input type="button" onclick="javascript:history.back(-1)" class="button" value="返 回">
<form method='post' action="/thinkphp/Curd/Index/update">
 <table cellpadding=2 cellspacing=2>
 <tr>
	<td>标题：</td>
	<td><input type="text" name="title" value="<?php echo ($vo["title"]); ?>"></td>
 </tr>
 <tr>
	<td >内容：</td>
	<td><textarea name="content" rows="5" cols="25"><?php echo ($vo["content"]); ?></textarea></td>
 </tr>
 <tr>
	<td><input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>"></td>
	<td><input type="submit" class="button" value="保 存"> <input type="reset" class="button" value="清 空"></td>
 </tr>
 </table>
   </form>
</div>
 </body>
</html>