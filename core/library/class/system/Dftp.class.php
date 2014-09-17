<?php
/** 
* Dftp.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2013-1-18
* @author Tao
*/
defined('IN_ONE') or exit('Access Denied');
 class dftp {
	var $fp;
	var $root;
	var $connected = 0;
	var $data_connection;
	var $pasv;
	
	function dftp($ftphost, $ftpuser, $ftppass, $ftpport = 21, $root = '/', $pasv = 0, $ssl = 0) {
		$errno = 0;
		$errstr = '';
		if ($ssl){
			$this->fp = @fsockopen('ssl://'.$ftphost, $ftpport, $errno, $errstr);	
		}else{
			$this->fp = @fsockopen($ftphost, $ftpport, $errno, $errstr);	
		}
		if (!$this->fp || !$this->checkcmd()) {
			return false;
		}
		@stream_set_timeout($this->fp, 10);
		$this->connected = $this->ftp_login($ftpuser,$ftppass);
		$this->root = '/'.$root;
		$this->pasv = $pasv;
	}

	function dftp_chdir($dir = '') {
		if (!$this->sendcmd('CWD', $this->root.$dir)) {
			return false;
		}
		return true;
	}

	function dftp_chmod($path, $mode = 0777) {
		$path = $this->root.$path;
		$base777 = base_convert($mode, 10, 8);
		return $this->sendcmd('SITE CHMOD', "$base777 $path");
	}

	function dftp_mkdir($dir, $mode = 0777) {
		$temp = explode('/', $dir);
		$cur_dir = '';
		$max = count($temp);
		for($i = 0; $i < $max; $i++) {
			$cur_dir .= $temp[$i].'/';
			if($this->dftp_chdir($cur_dir)) continue;
			$this->sendcmd('MKD', $this->root.$cur_dir);	
			$this->dftp_chmod($cur_dir, $mode);
		}
		return $this->dftp_chdir($dir);
	}

	function dftp_rmdir($dir) {
		$this->dftp_chdir($this->root.$dir);
		return $this->sendcmd('RMD', $this->root.$dir);
	}

	function dftp_delete($file) {
		return $this->sendcmd('DELE', $this->root.$file);
	}

	function dftp_put($local, $remote = '') {
		$remote or $remote = $local;
//		$local = UPLOAD_URL.'/'.$local;
		$this->dftp_mkdir(dirname($remote));
		
		if (!($fp = @fopen($local, 'rb'))) {
			return false;
		}
		$this->dftp_delete($remote);
		if (!$this->sendcmd('TYPE', 'I')) {
			return false;
		}
		$this->open_data_connection();
		$this->sendcmd('STOR', $this->root.$remote);
		while (!@feof($fp)) {
			@fwrite($this->data_connection, @fread($fp, 4096));
		}
		@fclose($fp);
		$this->close_data_connection();			
		if (!$this->checkcmd()) {
			return false;
		} else {
			$this->dftp_chmod($remote,0664);
		}

		
		return true;
	}
	
	////////////////////
	function sendcmd($cmd, $args = '', $check = true) {//运行FTP命令
		!empty($args) && $cmd .= " $args";
		fputs($this->fp, "$cmd\r\n");
		if ($check === true && !$this->checkcmd()) {
			return false;
		}
		return true;
	}
	
	function ftp_login($username,$password){
		return($this->sendcmd('USER', $username) && $this->sendcmd('PASS', $password));
	}
	
	function checkcmd($return = false) {//检查FTP
		
		$resp = $rcmd = '';
		$i = 0;
		do {
			$rcmd = fgets($this->fp, 512);
			$resp .= $rcmd;
		} while (++$i < 20 && !preg_match('/^\d{3}\s/is', $rcmd));
		if (!preg_match('/^[123]/', $rcmd)) {
			return false;
		}
		return $return ? $resp : true;
	}
	
	function pwd() {//当前目录
		$this->sendcmd('PWD', '', false);
		if (!($path = $this->checkcmd(true)) || !preg_match("/^[0-9]{3} \"(.+?)\"/", $path, $matchs)) {
			return '/';
		}
		return $matchs[1] . ((substr($matchs[1], -1) == '/') ? '' : '/');
	}
	
	function open_data_connection() {//打开数据连接
		$this->sendcmd('PASV', $this->pasv, false);
		if (!($ip_port = $this->checkcmd(true))) {
			return false;
		}
		if (!preg_match('/[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]+,[0-9]+/', $ip_port, $temp)) {
			return false;
		}
		$temp = explode(',', $temp[0]);
		$server_ip = "$temp[0].$temp[1].$temp[2].$temp[3]";
		$server_port = $temp[4] * 256 + $temp[5];
		if (!$this->data_connection = @fsockopen($server_ip, $server_port, $errno, $errstr)) {
			return false;
		}
		@stream_set_timeout($this->data_connection,10);
		return true;
	}
	
	function close_data_connection() {//关闭数据连接
		return @fclose($this->data_connection);
	}
	
} 