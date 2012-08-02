第三方类库使用说明

第三方类库区别于系统扩展类库的地方就是无需遵循ThinkPHP的类库定义和文件规范。
使用第三方类库，需要在ThinkPHP系统目录下面创建Vendor目录，然后直接放入第三方类库。
导入第三方类库的方法：
// 假设在Vendor目录下面有一个Zend\Util\Array.php 类库文件
vendor('Zend.Util.Array');