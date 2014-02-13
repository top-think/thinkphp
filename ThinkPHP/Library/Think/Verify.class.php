<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Think;

class Verify {
    protected $config =	array(
        'seKey'     =>  'ThinkPHP.CN',   // 验证码加密密钥
        'codeSet'   =>  '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',             // 验证码字符集合
        'expire'    =>  1800,            // 验证码过期时间（s）
        'useZh'     =>  false,           // 使用中文验证码 
        'zhSet'     =>  '',              // 中文验证码字符串
        'useImgBg'  =>  false,           // 使用背景图片 
        'fontSize'  =>  25,              // 验证码字体大小(px)
        'useCurve'  =>  true,            // 是否画混淆曲线
        'useNoise'  =>  true,            // 是否添加杂点	
        'imageH'    =>  0,               // 验证码图片高度
        'imageW'    =>  0,               // 验证码图片宽度
        'length'    =>  5,               // 验证码位数
        'fontttf'   =>  '',              // 验证码字体，不设置随机获取
        'bg'        =>  array(243, 251, 254),  // 背景颜色
        );

    private $_image   = NULL;     // 验证码图片实例
    private $_color   = NULL;     // 验证码字体颜色

    // 架构方法 设置参数
    public function __construct($config=array()){
        $this->config   =   array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @param  string $name 配置名称
     * @return multitype    配置值
     */
    public function __get($name) {
        return $this->config[$name];
    }

    public function __set($name,$value){
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    public function __isset($name){
        return isset($this->config[$name]);
    }

    /**
     * 验证验证码是否正确
     *
     * @param string $code 用户验证码
     * @return bool 用户验证码是否正确
     */
    public function check($code, $id = '') {
        $key = $this->authcode($this->seKey).$id;
        // 验证码不能为空
        $secode = session($key);
        if(empty($code) || empty($secode)) {
            return false;
        }
        // session 过期
        if(NOW_TIME - $secode['verify_time'] > $this->expire) {
            session($key, null);
            return false;
        }

        if($this->authcode(strtoupper($code)) == $secode['verify_code']) {
            session($key, null);
            return true;
        }

        return false;
    }

    /**
     * 输出验证码并把验证码的值保存的session中
     * 验证码保存到session的格式为： array('code' => '验证码值', 'time' => '验证码创建时间');
     */
    public function entry($id = '') {
        // 图片宽(px)
        $this->imageW || $this->imageW = $this->length*$this->fontSize*1.5 + $this->length*$this->fontSize/2; 
        // 图片高(px)
        $this->imageH || $this->imageH = $this->fontSize * 2.5;
        // 建立一幅 $this->imageW x $this->imageH 的图像
        $this->_image = imagecreate($this->imageW, $this->imageH); 
        // 设置背景      
        imagecolorallocate($this->_image, $this->bg[0], $this->bg[1], $this->bg[2]); 

        // 验证码字体随机颜色
        $this->_color = imagecolorallocate($this->_image, mt_rand(1,150), mt_rand(1,150), mt_rand(1,150));
        // 验证码使用随机字体
        $ttfPath = dirname(__FILE__) . '/Verify/' . ($this->useZh ? 'zhttfs' : 'ttfs') . '/';

        if(empty($this->fontttf)){
            $dir = dir($ttfPath);
            $ttfs = array();		
            while (false !== ($file = $dir->read())) {
                if($file[0] != '.' && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $dir->close();
            $this->fontttf = $ttfs[array_rand($ttfs)];
        } 
        $this->fontttf = $ttfPath . $this->fontttf;
        
        if($this->useImgBg) {
            $this->_background();
        }
        
        if ($this->useNoise) {
            // 绘杂点
            $this->_writeNoise();
        } 
        if ($this->useCurve) {
            // 绘干扰线
            $this->_writeCurve();
        }
        
        // 绘验证码
        $code = array(); // 验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        for ($i = 0; $i<$this->length; $i++) {
            if($this->useZh) {
                if($this->zhSet){
                    $code[$i] = iconv_substr($this->zhSet,floor(mt_rand(0,mb_strlen($this->zhSet,'utf-8')-1)),1,'utf-8');
                }else{
                    $code[$i] = chr(mt_rand(0xB0,0xF7)).chr(mt_rand(0xA1,0xFE));
                }
            } else {
                $code[$i] = $this->codeSet[mt_rand(0, 51)];
                $codeNX += mt_rand($this->fontSize*1.2, $this->fontSize*1.6);
                // 写一个验证码字符
                $this->useZh || imagettftext($this->_image, $this->fontSize, mt_rand(-40, 40), $codeNX, $this->fontSize*1.6, $this->_color, $this->fontttf, $code[$i]);
            }
        }
        
        $this->useZh && imagettftext($this->_image, $this->fontSize, 0, ($this->imageW - $this->fontSize*$this->length*1.2)/3, $this->fontSize * 1.5, $this->_color, $this->fontttf, iconv("GB2312","UTF-8", join('', $code)));
        
        // 保存验证码
        $key        =   $this->authcode($this->seKey);
        $code       =   $this->authcode(strtoupper(implode('', $code)));
        $secode     =   array();
        $secode['verify_code'] = $code; // 把校验码保存到session
        $secode['verify_time'] = NOW_TIME;  // 验证码创建时间
        session($key.$id, $secode);
                        
        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);		
        header('Pragma: no-cache');
        header("content-type: image/png");

        // 输出图像
        imagepng($this->_image);
        imagedestroy($this->_image);
    }

    /** 
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数) 
     *      
     *      高中的数学公式咋都忘了涅，写出来
     *		正弦型函数解析式：y=Asin(ωx+φ)+b
     *      各常数值对函数图像的影响：
     *        A：决定峰值（即纵向拉伸压缩的倍数）
     *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *        ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    private function _writeCurve() {
        $px = $py = 0;
        
        // 曲线前部分
        $A = mt_rand(1, $this->imageH/2);                  // 振幅
        $b = mt_rand(-$this->imageH/4, $this->imageH/4);   // Y轴方向偏移量
        $f = mt_rand(-$this->imageH/4, $this->imageH/4);   // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageW*2);  // 周期
        $w = (2* M_PI)/$T;
                        
        $px1 = 0;  // 曲线横坐标起始位置
        $px2 = mt_rand($this->imageW/2, $this->imageW * 0.8);  // 曲线横坐标结束位置

        for ($px=$px1; $px<=$px2; $px = $px + 1) {
            if ($w!=0) {
                $py = $A * sin($w*$px + $f)+ $b + $this->imageH/2;  // y = Asin(ωx+φ) + b
                $i = (int) ($this->fontSize/5);
                while ($i > 0) {	
                    imagesetpixel($this->_image, $px + $i , $py + $i, $this->_color);  // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多				
                    $i--;
                }
            }
        }
        
        // 曲线后部分
        $A = mt_rand(1, $this->imageH/2);                  // 振幅		
        $f = mt_rand(-$this->imageH/4, $this->imageH/4);   // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageW*2);  // 周期
        $w = (2* M_PI)/$T;		
        $b = $py - $A * sin($w*$px + $f) - $this->imageH/2;
        $px1 = $px2;
        $px2 = $this->imageW;

        for ($px=$px1; $px<=$px2; $px=$px+ 1) {
            if ($w!=0) {
                $py = $A * sin($w*$px + $f)+ $b + $this->imageH/2;  // y = Asin(ωx+φ) + b
                $i = (int) ($this->fontSize/5);
                while ($i > 0) {			
                    imagesetpixel($this->_image, $px + $i, $py + $i, $this->_color);	
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function _writeNoise() {
        for($i = 0; $i < 10; $i++){
            //杂点颜色
            $noiseColor = imagecolorallocate($this->_image, mt_rand(150,225), mt_rand(150,225), mt_rand(150,225));
            for($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->_image, 5, mt_rand(-10, $this->imageW),  mt_rand(-10, $this->imageH), $this->codeSet[mt_rand(0, 27)], $noiseColor);
            }
        }
    }

    /**
     * 绘制背景图片
     * 注：如果验证码输出图片比较大，将占用比较多的系统资源
     */
    private function _background() {
        $path = dirname(__FILE__).'/Verify/bgs/';
        $dir = dir($path);

        $bgs = array();		
        while (false !== ($file = $dir->read())) {
            if($file[0] != '.' && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();

        $gb = $bgs[array_rand($bgs)];

        list($width, $height) = @getimagesize($gb);
        // Resample
        $bgImage = @imagecreatefromjpeg($gb);
        @imagecopyresampled($this->_image, $bgImage, 0, 0, 0, 0, $this->imageW, $this->imageH, $width, $height);
        @imagedestroy($bgImage);
    }

    /* 加密验证码 */
    private function authcode($str){
        $key = substr(md5($this->seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }

}
