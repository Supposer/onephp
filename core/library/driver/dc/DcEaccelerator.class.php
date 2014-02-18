<?php
/** 
* DcEaccelerator.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class DCEaccelerator extends Dc{
	function get($key) {
		return eaccelerator_get($this->_dcconf['pre'].$key);
	}

	function set($key, $value, $ttl = 0) {
		eaccelerator_lock($this->_dcconf['pre'].$key); 
		return eaccelerator_put($this->_dcconf['pre'].$key, $value, $ttl);
	}

	function rm($key) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){	
			return eaccelerator_rm($this->_dcconf['pre'].$key);
		}else{
			return ;
		}
	}
	function clear() {
		return eaccelerator_gc();
	}
	
	function is_support(){
		return function_exists('eaccelerator_put');
	}
}