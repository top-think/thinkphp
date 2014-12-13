模式扩展 使用说明
1、在ThinkPHP系统目录下面创建Mode目录
2、放入相关的模式扩展文件（包括模式扩展文件和相关目录）
3、在项目的入口文件里面增加定义

例如，我们放入命令模式扩展cli文件cli.php 和 Cli目录到ThinkPHP\Mode 目录下面
然后在项目的入口文件增加一行定义如下：
define('THINK_MODE','thin');

