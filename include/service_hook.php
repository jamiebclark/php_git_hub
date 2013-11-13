<?php #!/usr/bin/env /usr/bin/php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(3600);

class ServiceHook {
	public $root = '~';
	
	public $logFile;
	public $repositories = array();

	private $logLines = array();
	
	//Additional commands to call before
	private $_preCommands = array();
	private $_postCommands = array();
	
	//TODO: Work on Windows integration
	private $_osTypes = array('Linux', 'Windows');
	private $_os = 0;
	
	private $test = true;
	
	public function fetch($repositories = null) {
		if (!empty($repositories)) {
			$this->setRepositories($repositories);
		}
		
		$payload = false;
		$this->logOpen('Git hook called');
		try {
			if (empty($_REQUEST['payload'])) {
				throw new Exception('No payload found');
			} else {
					$this->log('Loading Payload');
					$payload = json_decode(stripslashes($_REQUEST['payload']));					
					$branch = $this->getBranch($payload);
					$repository = $payload->repository->name;
					$this->log("Found Payload for $repository on branch $branch");
					if (empty($this->repositories[$repository][$branch])) {
						throw new Exception('No repository or branch information found');
					}
			}
		} catch(Exception $e) {
			$this->err($e . ' ' . $payload);
		}
		
		$repositoryDir = $this->repositories[$repository][$branch];
		$cmd = $this->getPullCmd($repositoryDir, $branch);
		
		$this->log("Executing: $cmd");
		/*
		$this->log(exec($cmd, $output, $status));
		$this->log('Shell Status: ' . $status . ' ' . round($status));
		*/
		$output = $this->syscall($cmd);
		$this->log("Shell Output:");
		$this->log($output);
		
		$this->logClose('End Git hook');
	}
	
	#region Getters and Setters
	public function setOs($os) {
		if (is_numeric($os)) {
			$success = isset($this->_osTypes[$os]);
		} else {
			$os = array_search($os, $this->_osTypes);
			$success = $os !== false;
		}
		if (!$success) {
			throw new Exception("Could not find OS");
		} else {
			$this->_os = $os;
		}		
	}
	
	public function setPosCommand($cmd) {
		$this->_postCommands[] = $cmd;
	}
	
	public function setPreCommand($cmd) {
		$this->_preCommands[] = $cmd;
	}
	
	public function setRepositories($repositories) {
		$this->repositories = $repositories;
	}
	
	public function setTestMode($testMode = true) {
		$this->test = $testMode;
	}
	
	#endregion
	
	//Private functions
	private function getPullCmd($dir, $branch = 'master') {
		$first = substr($dir, 0, 1);
		if ($first != DS && $first != '~') {
			$dir = $this->root . DS . $dir;
		}
		$cmds = array_merge(
			array("cd $dir"),
			$this->_preCommands,
			array(
				"git fetch origin",
				"git merge origin/$branch -m \"Automated merge from GitHub webhook\"",
				"git submodule update",
			),
			$this->_postCommands,
			array("cd ~")
		);
		return implode(' && ', $cmds);
	}
	
	private function getBranch($payload) {
		return substr($payload->ref, 11);
	}
	
	private function log($msg, $prefix = '') {
		if (is_array($msg) || is_object($msg)) {
			$this->log('Is Array: ' . round(is_array($msg)));
			$this->log('Is Object: ' . round(is_object($msg)));
			$this->log('Count: ' . count($msg));
			foreach ($msg as $key => $line) {
				$this->log($line, "$prefix - $key : ");
			}
			return true;
		} else {
			$msg = date('YmdHis') . ": $prefix $msg";
			$this->logLines[] = $msg;
			if (!empty($this->logFile)) {
				return file_put_contents($this->logFile, "$msg\n", FILE_APPEND);
			} else {
				return null;
			}
		}
	}
	
	private function logOpen($msg) {
		$this->log('');
		$this->logBar();
		$this->log($msg);
	}
	
	private function logClose($msg) {
		$this->log($msg);
		$this->logBar();
		$this->log('');
	}
	
	private function logBar() {
		return $this->log('--------------------');
	}
	
	private function err($msg) {
		$this->logClose($msg);
		exit($msg);
	}
	
	private function syscall ($cmd, $cwd = null) {
		if ($this->test) {
			$this->log('TESTING, System call skipped');
			return '';
		}

		$descriptorspec = array(
			1 => array('pipe', 'w') // stdout is a pipe that the child will write to
		);
		$resource = proc_open($cmd, $descriptorspec, $pipes, $cwd);
		if (is_resource($resource)) {
			$output = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($resource);
			return $output;
		} else {
			$this->log('Could not create proc_open resource');
		}
	}
}