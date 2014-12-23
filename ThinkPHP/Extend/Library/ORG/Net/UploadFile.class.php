<?php

//上传文件相关
class UploadModel extends Model
{
	protected $autoCheckFields = false;

	//定义上传所需字段
	private $user_id = 0;
	private $exts = null;
	private $path = null;
	private $rule = "time";

	private function upload($file)
	{
		// 过滤危险的文件 php sh exe
		
		$pathinfo = pathinfo($file["name"]);
		if(in_array(strtolower($pathinfo["extension"]), array("php","sh","bat","exe","sql"))){
			return array('status' => -1, 'msg' =>"不能上传危险类型({$pathinfo["extension"]})的文件");
		}
		
		import('ORG.Net.UploadFile');
		$upload = new UploadFile();// 实例化上传类
		$upload->maxSize  = 131457280 ;// 设置附件上传大小
		//$upload->allowExts  = $this->exts;// 设置附件上传类型
		$upload->allowExts  = array();

		$upload->savePath = $this->path;// 设置附件上传目录

		$upload->autoSub = true;
		$upload->subType = 'custom';
		$upload->subDir = $this->user_id;
		$upload->saveRule = empty($this->rule) ? 'time' : $this->rule; //默认采用时间戳命名
		$upload->uploadReplace = true;	//覆盖

		$data = $upload->uploadOne($file);

		if (!$data)
		{
			return array('status' => -1, 'msg' => $upload->getErrorMsg());
		}
		else
		{
			return array('status' => 0, 'file' => $data);
		}
	}





	//购物车页面上传文件
	public function cart($user_id, $cart_id, $image_id, $file){
		$this->tableName = 'user_files';
		$this->user_id = $user_id;
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'bmp');
		$this->path = C('UPLOAD_PATH');

		$return = $this->upload($file);
		if ($return['status'] == 0){
			$info = $return['file'];
			$data = array(
				'user_id'	=> $user_id,
				'cart_id'	=> $cart_id,
				'image_id'	=> $image_id,
				'path'		=> $info['savepath'],
				'url'		=> C('UPLOAD_PATH_URL') . $info['savename'],
				'filename'	=> $info['savename'],
				'created_on'=> time(),
			);
			$file_id = $this->add($data);
			$return = array(
				'status' => 0,
				'url' => $data['url'],
				'file_id' => $file_id,
			);
			D('Material/Cart')->set_status($cart_id, 2);	//标记上传作品完成
		}
		return $return;
	}




	public function copyright_user_record($user_id, $file){
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'bmp');
		$this->path = C('UPLOAD_PATH');
		$this->user_id = $user_id;
		return $this->upload($file);
	}





	//服务申请文件上传
	public function service($user_id, $file, $source = 0, $service_id = 0)
	{
		$this->tableName = 'service_files';
		$this->user_id = $user_id;
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'ppt', 'pdf', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'sql', 'zip', 'rar', 'psd', '7z', 'bmp');
		$this->path = C('SERVICE_PATH');

		$return = $this->upload($file);
		if ($return['status'] == 0)
		{
			$info = $return['file'];
			$data = array(
				'user_id'		=> $user_id,
				'source'		=> $source,
				'service_id'	=> $service_id,
				'path'			=> $info['savepath'],
				'url'			=> C('SERVICE_PATH_URL') . $info['savename'],
				'filename'		=> $info['savename'],
				'information'	=> $file['name'],
				'created_on'	=> time(),
			);
			$file_id = $this->add($data);
			$return = array(
				'status' => 0,
				'file_id' => $file_id,
			);
		}
		return $return;
	}

	//作品集上传文件
	public function source()
	{
		
	}

	//管理员上传源文件	done
	public function image_source($image_id, $file)
	{
		$this->tableName = 'image_files';
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'zip', 'rar', '7z', 'bmp');

		$image = D('Material/Image')->get_image($image_id);
		if (!$image)
		{
			$return = array('status' => -1, 'msg' => '图片不存在');
		}
		else
		{
			//构造本地保存样图的路径./Public/Sources/Images/1/8745000/a9365bf4e.jpg
			$source = $image['source'];
			$source_id = $image['source_id'];
			$number = $image['number'];
			$this->path = C('SOURCE_PATH').'Images'.DIRECTORY_SEPARATOR.$source.DIRECTORY_SEPARATOR.substr($source_id, 0, -3).'000';
			$this->rule = $number;

			$return = $this->upload($file);
			if ($return['status'] == 0)
			{
				$info = $return['file'];
				$image_file = $this->where('image_id='.$image_id)->find();
				$data = array(
					'image_id'	=> $image_id,
					'path'		=> $info['savepath'],
					'url'		=> C('SOURCE_PATH_URL') . "Images/$source/".substr($source_id, 0, -3).'000' . $info['savename'],
					'filename'	=> $info['savename'],
					'created_on'=> time(),
				);
				if (!empty($image_file))
				{
					unset($data['image_id']);
					$this->where('image_id='.$image_id)->save($data);
				}
				else
				{
					$this->add($data);
				}

				$return = array(
					'status' => 0,
					'url' => $data['url'],
				);
			}
		}
		return $return;
	}



	//Prowork item条目上传文件
	public function calendar_item($user_id, $item, $file)
	{
		$this->tableName = 'item_files';
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'ppt', 'pdf', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'sql', 'zip', 'rar', 'psd', '7z', 'bmp');
		$this->user_id = $user_id;
		$this->path = C('ITEM_PATH');

		$return = $this->upload($file);
		if ($return['status'] == 0)
		{
			$time = time();
			$info = $return['file'];
			$data = array(
				'item_id'		=> $item['id'],
				'user_id'		=> $user_id,
				'project_id'	=> $item['project_id'],
				'path'			=> $info['savepath'],
				'url'			=> C('ITEM_PATH_URL') . $info['savename'],
				'filename'		=> $info['savename'],
				'filesize'		=> $info['size'],
				'information'	=> $file['name'],
				'created_on'	=> $time,
			);
			$file_id = $this->add($data);
			$return = array(
				'status'	=> 0,
				'file_id'	=> $file_id,
				'url'		=> $data['url'],
				'information'=> $data['information'],
			);

			//插入feeds
			if ($item['project_id'] != 0)
			{
				D('Feeds')->item_files($item['project_id'], $user_id, array('id' => $item['id'], 'title' => $item['title']), $return);
			}

			if ($item['type'] == 'meeting')
			{
				//会议需要插入多条记录
				$item_meeting = M('item_meetings')->where('item_id='.$item['id'])->find();
				$item_ids = M('item_meetings')->where(array('order_id' => $item['id'], 'meeting_type' => $item_meeting['meeting_type'], 'item_id' => array('NEQ', $item['id'])))->getField('item_id', true);
				if (!empty($item_ids))
				{
					$data = array();
					foreach ($item_ids as $item_id)
					{
						$data[] = array(
							'item_id'		=> $item_id,
							'user_id'		=> $user_id,
							'project_id'	=> $item['project_id'],
							'path'			=> $info['savepath'],
							'url'			=> C('ITEM_PATH_URL') . $info['savename'],
							'filename'		=> $info['savename'],
							'filesize'		=> $info['size'],
							'information'	=> $file['name'],
							'created_on'	=> $time,
						);
					}
					$this->addAll($data);
				}
			}
		}
		return $return;
	}

	//Prowork project logo
	public function project_logo($user_id, $file)
	{
		$this->tableName = 'project_logos';
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'bmp');
		$this->user_id = $user_id;
		$this->path = C('PROJECT_LOGO_PATH');

		$return = $this->upload($file);
		if ($return['status'] == 0)
		{
			$info = $return['file'];
			$data = array(
				'logo_path'		=> $info['savepath'],
				'logo'			=> C('PROJECT_LOGO_PATH_URL') . $info['savename'],
				'logo_name'		=> $info['savename'],
			);
			$file_id = $this->add($data);
			$return = array(
				'status'	=> 0,
				'file_id'	=> $file_id,
			);
		}
		return $return;
	}




	//ckeditor文件上传
	public function ckeditor($user_id, $file)
	{
		$this->tableName = 'ckeditor_files';
		$this->exts = array('jpg', 'gif', 'png', 'jpeg', 'bmp');
		$this->user_id = $user_id;
		$this->path = C('CKEDITOR_PATH');

		$return = $this->upload($file);
		if ($return['status'] == 0)
		{
			$info = $return['file'];
			$data = array(
				'user_id'		=> $user_id,
				'path'			=> $info['savepath'],
				'url'			=> C('CKEDITOR_PATH_URL') . $info['savename'],
				'filename'		=> $info['savename'],
				'created_on'	=> time(),
			);
			$file_id = $this->add($data);
			$return = array(
				'status'	=> 0,
				'url'	=> $data['url'],
			);
		}
		return $return;
	}

}
