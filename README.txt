注意：下载后的扩展需要放入系统目录下面的Extend目录的相同位置。

Extend目录为系统扩展目录（核心版不含任何扩展），子目录结构为：

|-Action	控制器扩展
|-Behavior	行为扩展
|-Driver	驱动扩展
|  ├Driver/Cache		缓存驱动
|  ├Driver/Db		数据库驱动
|  ├Driver/Session	SESSION驱动
|  ├Driver/TagLib	标签库驱动
|  ├Driver/Template	模板引擎驱动
|
|-Engine	引擎扩展
|-Function	函数扩展
|-Library	类库扩展
|  ├ORG	ORG类库包
|  ├COM	COM类库包
|
|-Mode	模式扩展
|-Model	模型扩展
|-Tool	其他扩展或工具
|-Vendor	第三方类库目录

关于扩展的详细使用，请参考开发手册的扩展章节。