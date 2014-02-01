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
	input{height:30px}
	input.button,input.submit{width:68px; margin:2px 5px;letter-spacing:4px;font-size:16px; font-weight:bold;border:1px solid silver; text-align:center; background-color:#F0F0FF;cursor:pointer}
	div.result{border:1px solid #d4d4d4; background:#FFC;color:#393939; padding:8px 20px;float:auto; width:85%;margin:2px}
	</style>
 </head>
 <body><script language="JavaScript">
 <!--
	function add(){
		window.location.href="/thinkphp/Curd/Index/add";
	}
	function edit(id){
		window.location.href="/thinkphp/Curd/Index/edit/id/"+id;
	}
	function del(id){
		window.location.href="/thinkphp/Curd/Index/delete/id/"+id;
	}
 //-->
 </script>
 <div class="main">
 <h2>ThinkPHP示例之：CURD操作</h2>
 <table cellpadding=2 cellspacing=2>
  <tr>
	<td colspan="2"><input type="button" class="button" value="新 增" onClick="add()"></td>
 </tr>
  <tr>
  <td></td>
	<td> <div id="list" >
	<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><div id="div_<?php echo ($vo["id"]); ?>" class="result" style='font-weight:normal;<?php if(($key%2) == "1"): ?>background:#ECECFF<?php endif; ?>'><div style="border-bottom:1px dotted silver"><?php echo ($vo["title"]); ?>  [<?php echo ($vo["email"]); ?> <?php echo (date('Y-m-d H:i:s',$vo["create_time"])); ?>] </div>
	<div class="content"><?php echo (nl2br($vo["content"])); ?></div>
	<input type="button" value="编辑" class="small button" onClick="edit(<?php echo ($vo["id"]); ?>)"> <input type="button" value="删除" class="small button" onClick="del(<?php echo ($vo["id"]); ?>)">
	</div><?php endforeach; endif; else: echo "" ;endif; ?></div></td>
  </tr>
 </table>
</div>
 </body>
</html>