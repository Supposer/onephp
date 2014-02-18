<?php
/** 
* SessionFiles.class.php session文件驱动
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-27
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class SessionFiles extends Session{
	private $save_path;//保存session路径
	private $maxlifetime;
	/**
	 * 
	 * 初始化session
	 */
    public function __construct() {
    	if(!class_exists('File')) require_cache(CLASS_PATH.'File.class.php');
    	if(is_string(C('system_session_savepath')) && C('system_session_savepath')){
    		ini_set('session.save_handler', 'user');
    		session_set_save_handler(array(&$this,'open'), array(&$this,'close'), array(&$this,'read'), array(&$this,'write'), array(&$this,'destroy'), array(&$this,'gc'));
    	}
    	$this->save_path = CACHE_PATH.C('system_session_savepath').C(sys_session_pre).'/';
    	$this->maxlifetime = intval(C('system_session_ttl'))>0?intval(C('system_session_ttl')):1800;
    	if(!is_dir($this->save_path)) File::dirCreate($this->save_path);
    }

    /**
     * 
     * 打开session
     * @param $save_path
     * @param $session_name
     */
    public function open($save_path, $session_name){
    	return true;
    }
	
    /**
     * 
     * 关闭session
     */
    public function close() {
        return $this->gc($this->maxlifetime);
    }
    
    /**
     * 
     * 读取session
     * @param $id SESSIONID
     */
    public function read($id) {
		$expiretime = time() - $this->maxlifetime;
    	$session_name = $id.'.php';
    	if(is_file($this->save_path.$session_name) && filemtime($this->save_path.$session_name)>=$expiretime){
			return require $this->save_path.$session_name;
    	}else{
    		return '';
    	}
    }
    
    /**
     * 
     * 写入session值
     * @param $id SESSIONID
     * @param $data 值
     */
	public function write($id, $data) {
		if(!class_exists('File')) require(CLASS_PATH.'File.class.php');
		$session_name = $id.'.php';
		if(!preg_match("/^[A-Za-z0-9]+$/", $id)) return false;
		$data ="<?php\r\ndefined('IN_ONE') or exit('Access Denied');\r\nreturn ".var_export($data, true).';';
		return File::filePut($this->save_path.$session_name, $data);
    }
    
    /**
     * 
     * 注销session
     * @param $id SESSIONID
     */
    public function destroy($id) {
    	if(!class_exists('File')) require(CLASS_PATH.'File.class.php');
    	$session_name = $id.'.php';
    	return File::fileDel($this->save_path.$session_name);
    }
    
    /**
     * 
     * 设置session有效时间
     * @param $maxlifetime 有效时间
     */
	public function gc($maxlifetime) {
		if(!class_exists('File')) require(CLASS_PATH.'File.class.php');
		$maxlifetime = intval($maxlifetime)>0?intval($maxlifetime):1800;
		$expiretime = time() - $maxlifetime;
		$filelist = File::fileList($this->save_path);
		if(is_array($filelist)){
			foreach($filelist as $filename){
				if(File::fileExt($filename)=='php' && is_file($filename) && filemtime($filename)<$expiretime){
					File::fileDel($filename);
				}
			}
		}
		return true;
    }
}