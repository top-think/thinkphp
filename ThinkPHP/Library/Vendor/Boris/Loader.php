<?php
function __repl_autoload($class)
{
    if ($class[0] == '\\')
        $class = substr($class, 1);
    $path = sprintf('%s/%s.php', VENDOR_PATH, implode('/', explode('\\', $class)));

    if (is_file($path)) require($path);
}

//依赖解决
$error_msg = '';
if(version_compare(PHP_VERSION, "5.3.0", "<"))
	$error_msg = "PHP version 5.3+ is required, Your php version is ".PHP_VERSION."\n";

if(!function_exists('pcntl_signal'))
	$error_msg .= "pcntl_signal not working.\nRepl mode based on Linux OS or PHP for OS X(http://php-osx.liip.ch/)\n";

if(!empty($error_msg))
{
	if (MODE_NAME == 'cli')
		throw_exception($error_msg);
	else exit($error_msg);
}

//自动加载
spl_autoload_register('__repl_autoload');

$boris  = new \Boris\Boris(">>> ");
$config = new \Boris\Config();
$config->apply($boris, true);
$options = new \Boris\CLIOptionsHandler();
$options->handle($boris);
$boris->onStart(sprintf("echo 'REPL MODE FOR THINKPHP by 130775@cc.com\nTHINKPHP_VERSION: %s, PHP_VERSION: %s, BORIS_VERSION: %s\n';", THINK_VERSION, PHP_VERSION, $boris::VERSION));
$boris->start();