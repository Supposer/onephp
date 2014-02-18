<?php
/** 
* Session.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-17
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Session{
	public function __construct($session_storage = ''){
		return $this->factory($session_storage);
	}
    final public function factory($session_storage = '') {
    	static $seesion = null;
    	if(empty($seesion)){
    		$session_storage = $session_storage ? $session_storage : C('system_session_storage');
    		if($session_storage && is_string($session_storage)){
	    		$class_name = parse_name('session_'.strtolower(C('system_session_storage')),1);
				load_driver($class_name);
				if(class_exists($class_name)){
					$seesion = new $class_name;
					return $seesion;
				}
    		}
    	}
    	return;
    }
	/**
     * 
     * 打开session
     * @param $save_path
     * @param $session_name
     */
    public function open($save_path, $session_name){return true;}
    
	/**
     * 
     * 关闭session
     */
    public function close() {return true;}
    
    /**
     * 
     * 读取session
     * @param $id SESSIONID
     */
    public function read($id) {}
    
    /**
     * 
     * 写入session值
     * @param $id SESSIONID
     * @param $data 值
     */
	public function write($id, $data) {return true;}
    
    /**
     * 
     * 注销session
     * @param $id SESSIONID
     */
    public function destroy($id) {return true;}
    
    /**
     * 
     * 设置session有效时间
     * @param $maxlifetime 有效时间
     */
	public function gc($maxlifetime) {return true;}
}