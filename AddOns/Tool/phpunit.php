#! php -d safe_mode=Off
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: phpunit.php 2207 2011-11-30 13:17:26Z liu21st $

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

require_once 'PHPUnit/TextUI/Command.php';
?>