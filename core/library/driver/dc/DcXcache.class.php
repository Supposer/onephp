<?php
/** 
* DcXcache.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class cache_xcache extends Dc{
	function get($key) {
		return xcache_get($this->_dcconf['pre'].$key);
	}

	function set($key, $value, $ttl = 0) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){	
			return xcache_set($this->_dcconf['pre'].$key, $value, $ttl);
		}else{
			return ;
		}
	}

	function rm($key) {
		return xcache_unset($this->_dcconf['pre'].$key);
	}
	
	function clear() {
		return xcache_unset_by_prefix($this->_dcconf['pre']);
	}

	function is_support(){
		return function_exists('xcache_get');
	}
}