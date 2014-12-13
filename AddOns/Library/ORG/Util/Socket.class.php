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
// $Id: Socket.class.php 2207 2011-11-30 13:17:26Z liu21st $

class Socket extends Think {
	protected $_config = array(
		'persistent'	=> false,
		'host'			=> 'localhost',
		'protocol'		=> 'tcp',
		'port'			=> 80,
		'timeout'		=> 30
	);

	public $config = array();
	public $connection = null;
	public $connected = false;
	public $error = array();

	public function __construct($config = array()) {
		$this->config	=	array_merge($this->_config,$config);
		if (!is_numeric($this->config['protocol'])) {
			$this->config['protocol'] = getprotobyname($this->config['protocol']);
		}
	}

	public function connect() {
		if ($this->connection != null) {
			$this->disconnect();
		}

		if ($this->config['persistent'] == true) {
			$tmp = null;
			$this->connection = @pfsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		} else {
			$this->connection = fsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		}

		if (!empty($errNum) || !empty($errStr)) {
			$this->error($errStr, $errNum);
		}

		$this->connected = is_resource($this->connection);

		return $this->connected;
	}

	public function error() {
	}

	public function write($data) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}
		return fwrite($this->connection, $data, strlen($data));
	}

	public function read($length=1024) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		if (!feof($this->connection)) {
			return fread($this->connection, $length);
		} else {
			return false;
		}
	}

	public function disconnect() {
		if (!is_resource($this->connection)) {
			$this->connected = false;
			return true;
		}
		$this->connected = !fclose($this->connection);

		if (!$this->connected) {
			$this->connection = null;
		}
		return !$this->connected;
	}

 	public function __destruct() {
 		$this->disconnect();
 	}

}
?>