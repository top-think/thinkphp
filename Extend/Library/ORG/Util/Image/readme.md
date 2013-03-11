## ThinkImage 是什么？

ThinkImage是一个PHP图片处理工具。目前支持图片缩略图，图片裁剪，图片添加水印和文字水印等功能。可自由切换系统支持的图片处理工具，目前支持GD库和Imagick库。在GD库下也能良好的处理GIF图片。

## ThinkImage 怎么使用？

ThinkImage的使用比较简单，你只需要引入ThinkImage类，实例化一个ThinkImage的对象并传入要使用的图片处理库类型和要处理的图片，就可以对图片进行操作了。关键代码如下：（以ThinkPHP为例，非ThinkPHP框架请使用PHP原生的文件引入方法）

	//引入图片处理库
	import('ORG.Util.Image.ThinkImage'); 
	//使用GD库来处理1.gif图片
	$img = new ThinkImage(THINKIMAGE_GD, './1.gif'); 
	//将图片裁剪为440x440并保存为corp.gif
	$img->crop(440, 440)->save('./crop.gif');
	//给裁剪后的图片添加图片水印，位置为右下角，保存为water.gif
	$img->water('./11.png', THINKIMAGE_WATER_SOUTHEAST)->save("water.gif");
	//给原图添加水印并保存为water_o.gif（需要重新打开原图）
	$img->open('./1.gif')->water('./11.png', THINKIMAGE_WATER_SOUTHEAST)->save("water_o.gif");

## ThinkImage有哪些可以使用的常量？

ThinkImage提供了部分常量，方便记忆，在使用的过程中，可以直接使用常量或对应的整型值。

	/* 驱动相关常量定义 */
	define('THINKIMAGE_GD',      1); //常量，标识GD库类型
	define('THINKIMAGE_IMAGICK', 2); //常量，标识imagick库类型

	/* 缩略图相关常量定义 */
	define('THINKIMAGE_THUMB_SCALING',   1); //常量，标识缩略图等比例缩放类型
	define('THINKIMAGE_THUMB_FILLED',    2); //常量，标识缩略图缩放后填充类型
	define('THINKIMAGE_THUMB_CENTER',    3); //常量，标识缩略图居中裁剪类型
	define('THINKIMAGE_THUMB_NORTHWEST', 4); //常量，标识缩略图左上角裁剪类型
	define('THINKIMAGE_THUMB_SOUTHEAST', 5); //常量，标识缩略图右下角裁剪类型
	define('THINKIMAGE_THUMB_FIXED',     6); //常量，标识缩略图固定尺寸缩放类型

	/* 水印相关常量定义 */
	define('THINKIMAGE_WATER_NORTHWEST', 1); //常量，标识左上角水印
	define('THINKIMAGE_WATER_NORTH',     2); //常量，标识上居中水印
	define('THINKIMAGE_WATER_NORTHEAST', 3); //常量，标识右上角水印
	define('THINKIMAGE_WATER_WEST',      4); //常量，标识左居中水印
	define('THINKIMAGE_WATER_CENTER',    5); //常量，标识居中水印
	define('THINKIMAGE_WATER_EAST',      6); //常量，标识右居中水印
	define('THINKIMAGE_WATER_SOUTHWEST', 7); //常量，标识左下角水印
	define('THINKIMAGE_WATER_SOUTH',     8); //常量，标识下居中水印
	define('THINKIMAGE_WATER_SOUTHEAST', 9); //常量，标识右下角水印

## ThinkImage有哪些可以使用的方法？

以下方法为ThinkImage提供的图片处理接口，可直接使用。

打开一幅图像

	/**
     * @param  string $imgname 图片路径
     * @return Object          当前图片处理库对象
     */
    public function open($imgname){}

保存图片

    /**
     * @param  string  $imgname   图片保存名称
     * @param  string  $type      图片类型
     * @param  boolean $interlace 是否对JPEG类型图片设置隔行扫描
     * @return Object             当前图片处理库对象
     */
    public function save($imgname, $type = null, $interlace = true){}

获取图片宽度

    /**
     * @return integer 图片宽度
     */
    public function width(){}

获取图片高度

    /**
     * @return integer 图片高度
     */
    public function height(){}

获取图像类型

    /**
     * @return string 图片类型
     */
    public function type(){}

获取图像MIME类型

    /**
     * @return string 图像MIME类型
     */
    public function mime(){}

获取图像尺寸数组 0 - 图片宽度，1 - 图片高度

    /**
     * @return array 图片尺寸
     */
    public function size(){}

裁剪图片

    /**
     * @param  integer $w      裁剪区域宽度
     * @param  integer $h      裁剪区域高度
     * @param  integer $x      裁剪区域x坐标
     * @param  integer $y      裁剪区域y坐标
     * @param  integer $width  图片保存宽度
     * @param  integer $height 图片保存高度
     * @return Object          当前图片处理库对象
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null){}

生成缩略图

    /**
     * @param  integer $width  缩略图最大宽度
     * @param  integer $height 缩略图最大高度
     * @param  integer $type   缩略图裁剪类型
     * @return Object          当前图片处理库对象
     */
    public function thumb($width, $height, $type = THINKIMAGE_THUMB_SCALE){}

添加水印

    /**
     * @param  string  $source 水印图片路径
     * @param  integer $locate 水印位置
     * @param  integer $alpha  水印透明度
     * @return Object          当前图片处理库对象
     */
    public function water($source, $locate = THINKIMAGE_WATER_SOUTHEAST){}

图像添加文字

    /**
     * @param  string  $text   添加的文字
     * @param  string  $font   字体路径
     * @param  integer $size   字号
     * @param  string  $color  文字颜色
     * @param  integer $locate 文字写入位置
     * @param  integer $offset 文字相对当前位置的偏移量
     * @param  integer $angle  文字倾斜角度
     * @return Object          当前图片处理库对象
     */
    public function text($text, $font, $size, $color = '#00000000', 
        $locate = THINKIMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0){}