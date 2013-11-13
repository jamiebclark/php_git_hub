<?php
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

$dir = dirname(__FILE__);
require_once $dir . DS . 'include' . DS . "service_hook.php";

if (!is_file($dir . DS . 'config.php')) {
	throw new Exception('No config file found.');
} else {
	require_once $dir . DS . 'config.php';
}