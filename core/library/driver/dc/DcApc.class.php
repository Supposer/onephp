<?php
/** 
* DcApc.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class DcApc extends Dc{
	function get($key) {
		return apc_fetch($this->_dcconf['pre'].$key);
	}

	function set($key, $value, $ttl = 0) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){	
			return apc_store($this->_dcconf['pre'].$key, $value, $ttl);
		}else{
			return;
		}
	}

	function rm($key) {
		return apc_delete($this->_dcconf['pre'].$key);
	}

	function clear() {
		return apc_clear_cache('user');
	}
	
	function is_support(){
		return function_exists('apc_store');
	}

}