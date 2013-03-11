<?php

class SaeImage extends SaeObject {

    private $_img_data = ''; //图片数据
    private $_options = array(); //图片选项
    private $_width = 0;
    private $_height = 0;
    private $_image = null; //存储image资源

    const image_limitsize = 2097152;

    public function __construct($img_data='') {
        parent::__construct();
        return $this->setData($img_data);
    }

    //添加文字注释
    public function annotate($txt, $opacity=0.5, $gravity=SAE_Static, $font=array()) {
        $opacity = floatval($opacity);
        if ($this->imageNull())
            return false;
        //设置默认字体样式
        $font_default = array('name' => SAE_SimSun, 'size' => 15, 'weight' => 300, 'color' => 'black');
        $font = array_merge($font_default, $font);
        array_push($this->_options, array('act' => 'annotate', "txt" => $txt, "opacity" => $opacity,
            "gravity" => $gravity, "font" => array("name" => $font['name'], "size" => $font["size"],
                "weight" => $font["weight"], "color" => $font["color"])));
        return true;
    }

    //数据重新初始化
    public function clean() {
        $this->_img_data = '';
        $this->_options = array();
        $this->_width = 0;
        $this->_height = 0;
        $this->_image = null;
    }

    //图片合成, data为数组，在其他操作执行前进行操作
    public function composite($width, $height, $color="black") {
        $width = intval($width);
        $height = intval($height);
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'composite', "width" => $width, "height" => $height, "color" => $color));
        return true;
    }

    //裁剪图片
    public function crop($lx=0.25, $rx=0.75, $by=0.25, $ty=0.75) {
        $lx = floatval($lx);
        $rx = floatval($rx);
        $by = floatval($by);
        $ty = floatval($ty);
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'crop', "lx" => $lx, "rx" => $rx, "by" => $by, "ty" => $ty));
        return true;
    }

    //进行图片处理操作
    public function exec($format='jpg', $display=false) {
        if ($this->imageNull())
            return false;
        if (!in_array($format, array('jpg', 'gif', 'png'))) {
            $this->errno = SAE_ErrParameter;
            $this->errmsg = "format must be one of 'jpg', 'gif' and 'png'";
            return false;
        }
        if ($format == "jpg")
            $format = "jpeg";
        if ($this->_options[0]["act"] == "composite" && !is_array($this->_img_data)) {
            $this->errno = SAE_ErrParameter;
            $this->errmsg = "composite imagedata must be an array, pls see doc:";
            return false;
        }
        if ($this->_options[0]["act"] != "composite" && is_array($this->_img_data)) {
            $this->errno = SAE_ErrParameter;
            $this->errmsg = "imagedata is array only when composite image and composite must be the first operation";
            return false;
        }
        if (!$this->_image_create())
            return false;
        //循环处理
        foreach ($this->_options as $options) {
            call_user_func(array($this, "_" . $options['act']), $options);
        }
        $imgFun = 'image' . $format;
        if ($display) {
            header("Content-Type: image/" . $format);
            $imgFun($this->_image);
        } else {
            ob_start();
            $imgFun($this->_image);
            return ob_get_clean();
        }
        imagedestroy($this->_image);
    }

    //创建画布
    private function _image_create() {
        if ($this->_options[0]["act"] == "composite") {
            //合并多张图片
            $w = $this->_options[0]["width"];
            $h = $this->_options[0]["height"];
            $this->_width=$w;
            $this->_height=$h;
            $_image = imagecreatetruecolor($w, $h);
            //设置背景颜色
            $color = $this->toRGB($this->_options[0]['color']);
            $bg = imagecolorallocate($_image, $color['r'], $color['g'], $color['b']);
            imagefill($_image, 0, 0, $bg);
            foreach ($this->_img_data as $data) {
                $img_data = $data[0];
                $x = isset($data[1]) ? $data[1] : 0;
                $y = isset($data[2]) ? $data[2] : 0;
                $o = isset($data[3]) ? $data[3] * 100 : 100;
                $p = isset($data[4]) ? $data[4] : SAE_TOP_LEFT;
                $tmp_file = tempnam(SAE_TMP_PATH, "SAE_IMAGE");
                if (!file_put_contents($tmp_file, $img_data)) {
                    $this->errmsg = "file_put_contents to SAETMP_PATH failed when getImageAttr";
                    return false;
                }
                $info = getimagesize($tmp_file);
                $sw = $info[0];
                $sh = $info[1];
                $image_type = strtolower(substr(image_type_to_extension($info[2]), 1));
                $createFun = "imagecreatefrom" . $image_type;
                $sImage = $createFun($tmp_file);
                //设置位置
                switch ($p) {
                    case SAE_TOP_LEFT:
                        $dst_x = $x;
                        $dst_y = -$y;
                        break;
                    case SAE_TOP_CENTER:
                        $dst_x = ($w - $sw) / 2 + $x;
                        $dst_y = -$y;
                        break;
                    case SAE_TOP_RIGHT:
                        $dst_x = $w - $sw + $x;
                        $dst_y = -$y;
                        break;
                    case SAE_CENTER_LEFT:
                        $dst_x = $x;
                        $dst_y = ($h - $sh) / 2 - $y;
                        break;
                    case SAE_CENTER_CENTER:
                        $dst_x = ($w - $sw) / 2 + $x;
                        $dst_y = ($h - $sh) / 2 - $y;
                        break;
                    case SAE_CENTER_RIGHT:
                        $dst_x = $w - $sw + $x;
                        $dst_y = ($h - $sh) / 2 - $y;
                        break;
                    case SAE_BOTTOM_LEFT:
                        $dst_x = $x;
                        $dst_y = $h - $sh - $y;
                        break;
                    case SAE_BOTTOM_CENTER:
                        $dst_x = ($w - $sw) / 2 + $x;
                        $dst_y = $h - $sh - $y;
                        break;
                    case SAE_BOTTOM_RIGHT:
                        $dst_x = $w - $sw + $x;
                        $dst_y = $h - $sh - $y;
                        break;
                }
                $this->imagecopymerge_alpha($_image, $sImage, $dst_x, $dst_y, 0, 0, $sw, $sh, $o);
                unlink($tmp_file);
            }
      
            $this->_image = $_image;
            unset($this->_options[0]);
        } else {
            if (is_null($this->_image))
                $this->getImageAttr();
        }
        return true;
    }
    //修复合并时png透明问题
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        $w = imagesx($src_im);
        $h = imagesy($src_im);
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h,$pct);
    } 

    //水平翻转
    public function flipH() {
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'flipH'));
        return true;
    }

    //垂直翻转
    public function flipV() {
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'flipV'));
        return true;
    }

    //获取图像属性
    public function getImageAttr() {
        if ($this->imageNull())
            return false;
        $fn = tempnam(SAE_TMP_PATH, "SAE_IMAGE");
        if ($fn == false) {
            $this->errmsg = "tempnam call failed when getImageAttr";
            return false;
        }
        if (!file_put_contents($fn, $this->_img_data)) {
            $this->errmsg = "file_put_contents to SAETMP_PATH failed when getImageAttr";
            return false;
        }
        if (!($size = getimagesize($fn, $info))) {
            $this->errmsg = "getimagesize failed when getImageAttr";
            return false;
        }
        foreach ($info as $k => $v) {
            $size[$k] = $v;
        }
        $this->_width = $size[0];
        $this->_height = $size[1];
        //建立图片资源
        $image_type = strtolower(substr(image_type_to_extension($size[2]), 1));
        $createFun = "imagecreatefrom" . $image_type;
        $this->_image = $createFun($fn);
        unlink($fn); //删除临时文件
        return $size;
    }

    //去噪点，改善图片质量，通常用于exec之前
    public function improve() {
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'improve'));
        return true;
    }

    //等比例缩放
    public function resize($width=0, $height=0) {
        $width = intval($width);
        $height = intval($height);
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'resize', "width" => $width, "height" => $height));
        return true;
    }

    //按比例缩放
    public function resizeRatio($ratio=0.5) {
        $ratio = floatval($ratio);
        if ($this->imageNull())
            return false;
        if ($this->_width == 0) {
            $attr = $this->getImageAttr();
            if (!$attr)
                return false;
        }
        array_push($this->_options, array('act' => 'resize', "width" => $this->_width * $ratio, "height" => $this->_height * $ratio));
        return true;
    }

    //顺时针旋转图片
    public function rotate($degree=90) {
        $degree = intval($degree);
        if ($this->imageNull())
            return false;
        array_push($this->_options, array('act' => 'rotate', "degree" => $degree));
        return true;
    }

    //设置数据
    public function setData($img_data) {
        if (is_array($img_data)) {
            $_size = 0;
            foreach ($img_data as $k => $i) {
                if (count($i) < 1 || count($i) > 5) {
                    $this->errno = SAE_ErrParameter;
                    $this->errmsg = "image data array you supplied invalid";
                    return false;
                }
                if (is_null($i[1]) || $i[1] === false)
                    $img_data[$k][1] = 0;
                if (is_null($i[2]) || $i[1] === false)
                    $img_data[$k][2] = 0;
                if (is_null($i[3]) || $i[1] === false)
                    $img_data[$k][3] = 1;
                if (is_null($i[4]) || $i[1] === false)
                    $img_data[$k][4] = SAE_TOP_LEFT;
                $_size += strlen($i[0]);
            }
            if ($_size > self::image_limitsize) {
                $this->errno = SAE_ErrParameter;
                $this->errmsg = "image datas length more than 2M";
                return false;
            }
        } else if (strlen($img_data) > self::image_limitsize) {
            $this->errno = SAE_ErrParameter;
            $this->errmsg = "image data length more than 2M";
            return false;
        }
        $this->_img_data = $img_data;

        return true;
    }

    //判断图片数据是否可用
    private function imageNull() {
        if (empty($this->_img_data)) {
            $this->errno = SAE_ErrParameter;
            $this->errmsg = "image data cannot be empty";
            return true;
        } else {
            return false;
        }
    }

    //将颜色转换为RGB格式
    private function toRGB($color) {
        $color = trim($color);
        if (preg_match('/^rgb\((\d+),(\d+),(\d+)\)$/i', $color, $arr)) {
            //支持rgb(r,g,b)格式
            return array(
                'r' => $arr[1],
                'g' => $arr[2],
                'b' => $arr[3]
            );
        }
        $e_color = array(
            //支持英文颜色名
            'black' => '#000',
            'red' => '#f00',
            'blue'=>'#00f'
                //TODU 增加其他
        );
        $hexColor = str_replace(array_keys($e_color), array_values($e_color), $color);
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = str_replace('#', '', $hexColor);
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }

    /**
     * 添加文字
     * 参数格式
     *   array("txt" => $txt, "opacity" => $opacity,
      "gravity" => $gravity, "font" => array("name" => $font['name'], "size" => $font["size"],
      "weight" => $font["weight"], "color" => $font["color"]))
     * @param array $args 
     */
    private function _annotate($args) {
        $font = $args['font'];
        $rgb = $this->toRGB($font['color']);
        $color = imagecolorclosestalpha($this->_image, $rgb['r'], $rgb['g'], $rgb['b'], (1 - $args['opacity']) * 100);
        //设置位置
        $fontSize = imagettfbbox($font['size'], 0, $font['name'], $args['txt']);
        $textWidth = $fontSize [4]; //取出宽
        $textHeight = abs($fontSize[7]); //取出高
        switch ($args['gravity']) {
            case SAE_NorthWest:
                $x=0;
                $y=$textHeight;
                break;
            case SAE_North:
                $x=($this->_width-$textWidth)/2;;
                $y=$textHeight;
                break;
            case SAE_NorthEast:
                $x=$this->_width-$textWidth;;
                $y=$textHeight;
                break;
            case SAE_West:
                $x=0;
                $y=($this->_height-$textHeight)/2;
                break;
            case SAE_East:
                $x=$this->_width-$textWidth;
                $y=($this->_height-$textHeight)/2;
                break;
            case SAE_SouthWest:
                $x=0;
                $y=$this->_height-$textHeight;
                break;
            case SAE_South:
                $x=($this->_width-$textWidth)/2;
                $y=$this->_height-$textHeight;
                break;
            case SAE_SouthEast:
                $x=$this->_width-$textWidth;
                $y=$this->_height-$textHeight;
                break;
            case SAE_Static:
            default :
                $x=($this->_width-$textWidth)/2;
                $y=($this->_height-$textHeight)/2;
                break;
        }
        imagettftext($this->_image, $font['size'], 0, $x, $y, $color, $font['name'], $args['txt']);
    }
    /**
     *截取图片
     * 参数 array("lx" => $lx, "rx" => $rx, "by" => $by, "ty" => $ty)
     * @param array $args 
     */
    private function _crop($args){
        $width=($args['rx']-$args['lx'])*$this->_width;
        $height=($args['ty']-$args['by'])*$this->_height;
        $x=$args['lx']*$this->_width;
        $y=$args['by']*$this->_height;
        $_image=imagecreatetruecolor($width, $height);
        imagecopyresampled($_image,$this->_image,0,0,$x,$y,
	$width,$height,$width,$height);
        $this->_image=$_image;
    }
    /**
     *图片放缩
     * 参数：array( "width" => $width, "height" => $height)
     * @param array $args 
     */
    private function _resize($args){
        if($args['width']==0 && $args['heigth']==0) return ;
        if($args['width']==0){
            //高度固定等比例放缩
            $h=$args['heigth'];
            $w=$h/$this->_height*$this->_width;
        }elseif($args['heigth']==0){
            //宽度固定等比例放缩
            $w=$args['width'];
            $h=$w/$this->_width*$this->_height;
        }else{
        $w=$args['width'];
        $h=$args['height'];
        }
        $_image=imagecreatetruecolor($w, $h);
        imagecopyresampled($_image, $this->_image, 0, 0, 0, 0, $w, $h, $this->_width, $this->_height);
        $this->_image=$_image;
    }
    /**
     *旋转角度
     * @param array $args 
     */
    private function _rotate($args){
        $this->_image=imagerotate($this->_image, 360-$args['degree'],0);
    }
    //水平翻转
    private function _flipH($args) {
        $_image=imagecreatetruecolor($this->_width, $this->_height);
        for ($i = 0; $i < $this->_width; $i++) {
            imagecopyresampled($_image, $this->_image, ($this->_width - $i), 0, $i, 0, 1, $this->_height, 1, $this->_height);
        }
        $this->_image=$_image;
    }
    //垂直翻转
    private function _flipV($args) {
        $this->_flipH(array());
        $this->_rotate(array('degree'=>180));
    }
    //去除噪点
    private function _improve($args){
        //本地不做任何处理
    }

}