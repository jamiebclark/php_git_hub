<?php
require_once '_include.php';
$ServiceHook = new ServiceHook();
if (isset($os)) {
	$ServiceHook->setOs($os);
}
if (isset($logFile)) {
	$ServiceHook->logFile = $logFile;
}
if (empty($repositories)) {
	throw new Exception('No repositories have been set. Please see the config.php file');
}
$ServiceHook->fetch($repositories);