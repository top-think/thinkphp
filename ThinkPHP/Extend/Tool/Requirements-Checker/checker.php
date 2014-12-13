<?php

/**
 * Requirements Checker: This script will check if your system meets
 * the requirements for running ThinkPHP Framework.
 *
 * This file is part of the ThinkPHP Framework 参考了nette框架(http://nette.org)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */


/**
 * Check PHP configuration.
 */
foreach (array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function) {
	if (!function_exists($function)) {
		die("Error: function '$function' is required by ThinkPHP Framework and this Requirements Checker.");
	}
}

/**
 * Check assets目录, 模板文件必须可读
 */
define('TEMPLATE_FILE', __DIR__ . '/assets/checker.phtml');
if (!is_readable(TEMPLATE_FILE)) {
	die("Error: 模板文件不可读.检查assets目录(发布的部分),应当存在,可读且包含可读的模板文件.");
}

/**
 * Check ThinkPHP Framework requirements.
 */
define('CHECKER_VERSION', '1.0');

$tests[] = array(
	'title' => 'Web服务器',
	'message' => $_SERVER['SERVER_SOFTWARE'],
);

$tests[] = array(
	'title' => 'PHP版本',
	'required' => TRUE,
	'passed' => version_compare(PHP_VERSION, '5.2.0', '>='),
	'message' => PHP_VERSION,
	'description' => '你的PHP太低了.ThinkPHP框架需要至少PHP 5.2.0或更高.',
);

$tests[] = array(
	'title' => 'Memory限制',
	'message' => ini_get('memory_limit'),
);

$tests['hf'] = array(
	'title' => '.htaccess文件保护',
	'required' => FALSE,
	'description' => '通过<code>.htaccess</code>的File保护不支持.你必须小心的放入文件到document_root目录.',
	'script' => '<script src="assets/denied/checker.js"></script><script>displayResult("hf", typeof fileProtectionChecker == "undefined")</script>',
);

$tests['hr'] = array(
	'title' => '.htaccess mod_rewrite',
	'required' => FALSE,
	'description' => 'Mod_rewrite可能不支持.你将无法使用Cool URL(URL_MODEL=2不启作用，入口文件无法隐藏).',
	'script' => '<script src="assets/rewrite/checker"></script><script>displayResult("hr", typeof modRewriteChecker == "boolean")</script>',
);

$tests[] = array(
	'title' => '函数ini_set()',
	'required' => FALSE,
	'passed' => function_exists('ini_set'),
	'description' => '函数<code>ini_set()</code>不支持.部分ThinkPHP框架功能可能工作不正常.',
);

$tests[] = array(
	'title' => '函数error_reporting()',
	'required' => TRUE,
	'passed' => function_exists('error_reporting'),
	'description' => '函数<code>error_reporting()</code>不支持. ThinkPHP框架需要这个被启用',
);

// $tests[] = array(
// 	'title' => 'Function flock()',
// 	'required' => TRUE,
// 	'passed' => flock(fopen(__FILE__, 'r'), LOCK_SH),
// 	'description' => 'Function <code>flock()</code> is not supported on this filesystem. ThinkPHP Framework requires this to process atomic file operations.',
// );

$tests[] = array(
	'title' => 'Register_globals',
	'required' => TRUE,
	'passed' => iniFlag('register_globals'),
	'message' => '启用',
	'errorMessage' => '不支持',
	'description' => '配置Configuration显示<code>register_globals</code>禁用了. ThinkPHP框架要求此项开启.',
);

// $tests[] = array(
// 	'title' => 'Variables_order',
// 	'required' => TRUE,
// 	'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'), 'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
// 	'description' => 'Configuration directive <code>variables_order</code> is missing. ThinkPHP Framework requires this to be set.',
// );

$tests[] = array(
	'title' => 'Session auto-start',
	'required' => FALSE,
	'passed' => session_id() === '' && !defined('SID'),
	'description' => 'Session auto-start启用了. ThinkPHP框架默认情况下，初始化之后系统会自动启动session.',
);

$tests[] = array(
	'title' => 'Reflection扩展',
	'required' => TRUE,
	'passed' => class_exists('ReflectionFunction'),
	'description' => 'ThinkPHP必须开启Reflection扩展.',
);

// $tests[] = array(
// 	'title' => 'SPL extension',
// 	'required' => TRUE,
// 	'passed' => extension_loaded('SPL'),
// 	'description' => 'SPL extension is required.',
// );

$tests[] = array(
	'title' => 'PCRE扩展',
	'required' => TRUE,
	'passed' => extension_loaded('pcre') && @preg_match('/pcre/u', 'pcre'),
	'message' => '支持并且工作正常',
	'errorMessage' => '禁用或者不支持UTF-8',
	'description' => 'PCRE扩展推荐开启并支持UTF-8.',
);

$tests[] = array(
	'title' => 'ICONV扩展',
	'required' => TRUE,
	'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
	'message' => '支持并且工作正常',
	'errorMessage' => '禁用或者工作不正常',
	'description' => 'ICONV扩展必须且工作正常.',
);

// $tests[] = array(
// 	'title' => 'PHP tokenizer',
// 	'required' => TRUE,
// 	'passed' => extension_loaded('tokenizer'),
// 	'description' => 'PHP tokenizer is required.',
// );

$tests[] = array(
	'title' => 'PDO扩展',
	'required' => FALSE,
	'passed' => $pdo = extension_loaded('pdo') && PDO::getAvailableDrivers(),
	'message' => $pdo ? '可用驱动有drivers: ' . implode(' ', PDO::getAvailableDrivers()) : NULL,
	'description' => 'PDO扩展或者PDO驱动不支持.你将不能使用<code>ThinkPHP\DbPdo</code>.',
);

$tests[] = array(
	'title' => '多字节字符串扩展',
	'required' => FALSE,
	'passed' => extension_loaded('mbstring'),
	'description' => 'Multibyte String扩展不支持.一些国际化组件可能无法正常工作.',
);

$tests[] = array(
	'title' => '多字节字符串overloading函数',
	'required' => TRUE,
	'passed' => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
	'message' => '禁用',
	'errorMessage' => '启用',
	'description' => '启用了多字节字符串重载函数. ThinkPHP框架要求这项被禁用.如果它开启着，一些字符串函数将可能工作不正常.',
);

$tests[] = array(
	'title' => 'Memcache扩展',
	'required' => FALSE,
	'passed' => extension_loaded('memcache'),
	'description' => 'Memcache扩展不支持.你将不能使用<code>Memcache作为ThinkPHP的缓存方式</code>.',
);

$tests[] = array(
	'title' => 'GD扩展',
	'required' => TRUE,
	'passed' => extension_loaded('gd'),
	'description' => 'GD扩展不支持. 你将不能使用<code>ThinkPHP\Image</code>类.',
);

$tests[] = array(
	'title' => 'Imagick扩展',
	'required' => FALSE,
	'passed' => extension_loaded('imagick'),
	'description' => 'Imagick扩展不支持. 你将不能使用Imagick进行高效图像处理.',
);

// $tests[] = array(
// 	'title' => 'Bundled GD extension',
// 	'required' => FALSE,
// 	'passed' => extension_loaded('gd') && GD_BUNDLED,
// 	'description' => 'Bundled GD extension is absent. You will not be able to use some functions such as <code>ThinkPHP\Image::filter()</code> or <code>ThinkPHP\Image::rotate()</code>.',
// );

$tests[] = array(
	'title' => 'Fileinfo扩展 或 mime_content_type()',
	'required' => FALSE,
	'passed' => extension_loaded('fileinfo') || function_exists('mime_content_type'),
	'description' => 'Fileinfo 扩展或者 函数<code>mime_content_type()</code> 不支持.你将不能检测上传文件的mime类型.',
);

// $tests[] = array(
// 	'title' => 'HTTP_HOST or SERVER_NAME',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER["HTTP_HOST"]) || isset($_SERVER["SERVER_NAME"]),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => 'Either <code>$_SERVER["HTTP_HOST"]</code> or <code>$_SERVER["SERVER_NAME"]</code> must be available for resolving host name.',
// );

$tests[] = array(
	'title' => 'REQUEST_URI 或 ORIG_PATH_INFO',
	'required' => TRUE,
	'passed' => isset($_SERVER["REQUEST_URI"]) || isset($_SERVER["ORIG_PATH_INFO"]),
	'message' => '支持',
	'errorMessage' => '不支持',
	'description' => ' <code>$_SERVER["REQUEST_URI"]</code> 或者<code>$_SERVER["ORIG_PATH_INFO"]</code>必学能获取到用于分解请求的URL.',
);

// $tests[] = array(
// 	'title' => 'DOCUMENT_ROOT & SCRIPT_FILENAME or SCRIPT_NAME',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME']) || isset($_SERVER['SCRIPT_NAME']),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => '<code>$_SERVER["DOCUMENT_ROOT"]</code> and <code>$_SERVER["SCRIPT_FILENAME"]</code> or <code>$_SERVER["SCRIPT_NAME"]</code> must be available for resolving script file path.',
// );

// $tests[] = array(
// 	'title' => 'SERVER_ADDR or LOCAL_ADDR',
// 	'required' => TRUE,
// 	'passed' => isset($_SERVER["SERVER_ADDR"]) || isset($_SERVER["LOCAL_ADDR"]),
// 	'message' => 'Present',
// 	'errorMessage' => 'Absent',
// 	'description' => '<code>$_SERVER["SERVER_ADDR"]</code> or <code>$_SERVER["LOCAL_ADDR"]</code> must be available for detecting development / production mode.',
// );

paint($tests);
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++//
/**
 * Paints checker.
 * @param  array
 * @return void
 */
function paint($requirements){
	$errors = $warnings = FALSE;

	foreach ($requirements as $id => $requirement){
		$requirements[$id] = $requirement = (object) $requirement;
		if (isset($requirement->passed) && !$requirement->passed) {
			if ($requirement->required) {
				$errors = TRUE;
			} else {
				$warnings = TRUE;
			}
		}
	}

	require TEMPLATE_FILE;
}

/**
 * 获取配置项的布尔值.
 * @param  string  配置项名称
 * @return bool
 */
function iniFlag($var){
	$status = strtolower(ini_get($var));
	return $status === 'on' || $status === 'true' || $status === 'yes' || (int) $status;
}