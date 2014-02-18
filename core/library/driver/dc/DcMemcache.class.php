<?php
/** 
* DcMemcache.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class DcMemcache extends Dc{
	private $enable;
	private $obj;
	function __construct($config = array()){
		$this->_dcconf = array(
			'pre' => $config['pre'],//缓存前缀
			'ttl' => intval($config['ttl'])>0?intval($config['ttl']):0, //缓存时间(秒)
			'server' => $config['server']?$config['server']:'127.0.0.1',
			'port' => $config['port']?$config['port']:'11211',
			'pconnect' => $config['pconnect']?1:0,
		);
		$this->_dcconf['pre'] = $cache_pre;
		if(class_exists('Memcache')) {
			$this->obj = new Memcache;
			if($config['pconnect']) {
				$connect = @$this->obj->pconnect($this->_dcconf['server'], $this->_dcconf['port']);
			} else {
				$connect = @$this->obj->connect($this->_dcconf['server'], $this->_dcconf['port']);
			}
			$this->enable = $connect ? true : false;
		}else{
			$this->enable = false;
		}
	}

	function get($key) {
		return $this->obj->get($this->_dcconf['pre'].$key);
	}

	function set($key, $value, $ttl = 0) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){	
			return $this->obj->set($this->_dcconf['pre'].$key,$value,MEMCACHE_COMPRESSED,$ttl);
		}else{
			return ;
		}
	}
	
	function add($key, $value, $ttl = 0) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){	
			return $this->obj->add($this->_dcconf['pre'].$key,$value,MEMCACHE_COMPRESSED,$ttl);
		}else{
			return ;
		}
	}
	
	function increment($key, $value) {
		return $this->obj->increment($this->_dcconf['pre'].$key,$value);
	}

	function rm($key) {
		return $this->obj->delete($this->_dcconf['pre'].$key);
	}

	function clear() {
		return $this->obj->flush();
	}
	
	function is_support(){
		return class_exists('Memcache') && $this->enable;
	}
}