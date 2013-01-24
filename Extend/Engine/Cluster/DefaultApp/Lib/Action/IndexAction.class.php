<?php
// 本类由系统自动生成，仅供测试用途
class IndexAction extends Action {
    public function index(){
	$this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b> (Cluster Engine for '.IO_NAME.')！</p></div><div>测试：<a href="__URL__/f" target="_blank">F函数</a>&nbsp;<a href="__URL__/s" target="_blank">S函数</a>&nbsp;<a href="__URL__/upload" target="_blank">上传图片</a>&nbsp;<a href="__URL__/log" target="_blank">日志记录</a></div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
	}
	public function f(){
		F('name','success');
		dump(F('name'));
	}
	public function s(){
		$name=s('name');
		if(!$name){
			s('name','success',10);
			$this->show('S缓存已过期，现在已经重新设置了值，请重新刷新浏览器');	
		}else{
			dump(s('name'));
			$this->show('缓存过期时间是10秒，请10秒后查看是否过期');
		}
	}
	public function upload(){
		    if (!empty($_FILES)) {
            import("@.ORG.UploadFile");
            $config=array(
                'allowExts'=>array('jpg','gif','png'),
                'savePath'=>'./Public/upload/',
                'saveRule'=>'time',
            );
            $upload = new UploadFile($config);
            $upload->thumb=true;
            $upload->thumbMaxHeight=100;
            $upload->thumbMaxWidth=100;
            if (!$upload->upload()) {
                $this->error($upload->getErrorMsg());
            } else {
                $info = $upload->getUploadFileInfo();
				$this->assign('filename',$info[0]['savename']);
            }
		}
			$this->show('
<form action="" method="post" enctype="multipart/form-data"><input type="file" name="files[]"/><input name="" value="上传" type="submit" /></form>
<notempty name="filename">
	<img src="__PUBLIC__/upload/{$filename}" /> <a href="__URL__/delete/filename/{$filename}">删除图片</a>
</notempty>
');

	}
	public function delete($filename){
		if(file_delete('./Public/upload/'.$filename) && file_delete('./Public/upload/thumb_'.$filename)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	public function log(){
		Log::write('一条测试日志');
		$this->show('日志已记录，请查看日志是否生成');
	}
}
