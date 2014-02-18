<?php
/** 
* IndexAction.class.php
* @copyright (C)
* @license 
* @lastmodify 2014-2-5
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class IndexAction extends Action{
	
	public function index(){
		session_start();
		//$foo = M('Search')->shopsearch();
		//var_dump($foo);

		App::loadAppFunc('helper');
		
		//showmessage(L('error_operationFail','en-us'),HTTP_REFERER,3);
		
		$showpage = listpages(20,1,3,'?page={page}');
		
		$editor = showeditor('content','content');
		
		$this->assign(get_defined_vars());
		$this->display('index/index');
	}
}