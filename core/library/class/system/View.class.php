<?php
/** 
* View.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-10-9
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class View{
	protected $tVar = array();
	public function __construct(){}
	
	/**
	 * 
	 * 模板变量赋值
	 * @param $name 变量名
	 * @param $value 变量值
	 */
	public function assign($name,$value=''){
		if(is_array($name)){
			$this->tVar = array_merge($this->tVar,$name);
		}elseif(is_string($name)){
			$this->tVar[$name] = $value;
		}
	}
	
	/**
	 * 
	 * 清除模板变量
	 */
	public function tVarClear(){
		$tVar = array();
	}
	
	/**
	 * 
	 * 模板显示
	 * @param $templateFile 模板文件
	 * @param $charset 编码
	 * @param $contentType 输出类型
	 */
	public function display($templateFile='',$charset='',$contentType='') {		
		if(empty($charset))  $charset = CHARSET;
        if(empty($contentType)) $contentType = 'text/html';
        header('Content-Type:'.$contentType.'; charset='.$charset);
        ob_clean();
		Ob::obStart();		
		extract($this->tVar, EXTR_SKIP);
		$this->tVar=array();		
        include Template::temp($templateFile);
        Ob::Output();
        
	}
	
	/**
	 * 
	 * 后台模板显示
	 * @param $templateFile 模板文件
	 * @param $app 应用
	 * @param $charset 编码
	 * @param $contentType 输出类型
	 */
	public function adminTemp($templateFile='',$app='',$charset='',$contentType=''){
		if(empty($charset))  $charset = CHARSET;
        if(empty($contentType)) $contentType = 'text/html';
        header('Content-Type:'.$contentType.'; charset='.$charset);
        ob_clean();
		Ob::obStart();
		extract($this->tVar, EXTR_SKIP);
		$this->tVar=array();
        include Template::adminTemp($templateFile,$app);
        Ob::Output();
	}
	
	/**
	 * 
	 * 获取输出页面内容
	 * @param $templateFile 模板文件
	 */
	public function fetch($templateFile=''){
		ob_start();
		//extract($this->tVar, EXTR_SKIP);
		//$this->tVar=array();
       // include template($templateFile);
		@readfile($templateFile);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
	}
}