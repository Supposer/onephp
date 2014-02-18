<?php
/** 
* DcFile.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class DcFile extends Dc{
	public function get($key) {
		$dir = $this->cutdir($key);
		if(is_file($dir)){
			$value = require($dir);
		}else{
			$value = null;
		}
		return $value;
	}

	public function set($key, $value, $ttl = 0) {
		$ttl = intval($ttl)>0 ? intval($ttl) : $this->_dcconf['ttl'];
		if($ttl>0){
			$str = "<?php\ndefined('IN_ONE') or exit('Access Denied');\n";
			$str .='if(SYS_TIME-'.SYS_TIME.'<='.$ttl.'){return '.var_export($value, true).';}else{return null;}'."\n";
			$str .= '?>';
			return File::filePut($this->cutdir($key), $str,0);
		}else{
			return;
		}
	}

	public function rm($key) {
		return File::fileDel($this->cutdir($key));
	}
	
	public function clear() {
		return File::dirDelete(DATA_CACHE_PATH.$this->_dcconf['pre'].'/');
	}
	
	private function cutdir($key, $v=2, $suffix='php'){
		$v = intval($v)>0?intval($v):2;
		$key = md5($key);
		return DATA_CACHE_PATH.$this->_dcconf['pre'].'/'.($suffix?trim(chunk_split($key,$v,'/'),'/').'.'.$suffix:trim(chunk_split($key,$v,'/'),'/'));
	}
	
	public function is_support(){
		return true;
	}
}