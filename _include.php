<?php
if (!defined(DS)) {
	define(DS, DIRECTORY_SEPARATOR);
}

$dir = dirname(__FILE__);
require_once $dir . 'include' . DS . "git_hub_hook.php";

if (!is_file($dir . 'config.php')) {
	throw new Exception('No config file found.');
} else {
	require_once $dir . 'config.php';
}