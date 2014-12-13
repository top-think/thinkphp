扩展类库的使用方法
把扩展类库放入ThinkPHP\Lib 目录下面即可，然后使用import方法按照目录层次加载。
例如加载 Lib\ORG\Util\Image.class.php 使用
import('ORG.Util.Image');

目前支持的扩展类库包包括 ORG和Com

