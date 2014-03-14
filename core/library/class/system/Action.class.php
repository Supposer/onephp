<?php
/** 
* Action.class.php Action控制器基类 抽象类
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-10-7
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
abstract class Action{
	// 视图实例对象
    protected $view = null;
    // 当前Action名称
    protected $name =  '';
	public function __construct(){
		$this->view = get_instance_of('View');
		$this->name = substr(get_class($this),0,-6);
		$this->init();
	}
	
	protected function init(){}
	
	/**
	 * 
	 * 模板变量赋值
	 * @param $name 变量名
	 * @param $value 变量值
	 */
	final protected function assign($name,$value=''){
		$this->view->assign($name,$value);
	}
	
	/**
	 * 
	 * 模板显示
	 * @param $templateFile 模板文件
	 * @param $charset 编码
	 * @param $contentType 输出类型
	 */
	final protected function display($templateFile='',$charset='',$contentType='') {
		$templateFile = $templateFile?$templateFile:strtolower(APP_NAME.'/'.APP_CLASS.'/').APP_METHOD;
		$this->view->display($templateFile,$charset,$contentType);
	}
	
	/**
	 * 
	 * 获取输出页面内容
	 * @param $templateFile 模板文件
	 */
	final protected function fetch($templateFile='') {
		$templateFile = $templateFile?$templateFile:strtolower(APP_NAME.'/'.APP_CLASS.'/').APP_METHOD;
		return $this->view->fetch($templateFile);
	}
	
	/**
	 * 
	 * 创建静态页面
	 * @param $htmlfile 生成的静态文件路径
	 * @param $templateFile 模板文件
	 */
    final protected function buildHtml($htmlfile='',$templateFile='') {
		$htmlcontent = $this->fetch($templateFile);
		return File::filePut(SITE_PATH.$htmlfile, $htmlcontent,0);
    }
	
	/**
	 * 
	 * 私有属性可读取的
	 * @param $property_name
	 */
	final public function __get($property_name){}
	
	/**
	 * 
	 * 私有属性可修改的
	 * @param $property_name
	 * @param $value
	 */
	final public function __set($property_name,$value){
		$this->view->assign($property_name,$value);
	}
}