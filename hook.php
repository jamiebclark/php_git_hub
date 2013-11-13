<?php
require_once '_include.php';
$Hook = new GitHubHook();
if (isset($os)) {
	$Hook->setOs($os);
}
if (isset($logFile)) {
	$Hook->logFile = $logFile;
}
if (empty($repositories)) {
	throw new Exception('No repositories have been set. Please see the config.php file');
}
$Hook->fetch($repositories);