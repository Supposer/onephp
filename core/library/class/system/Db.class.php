<?php
/** 
* Db.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-7
* @author bin
*/
defined('IN_ONE') or exit('Access Denied'); 
class Db{
	protected $_dbconf;//配置
	protected $query_num;//查询次数
	protected $last_sql;
	protected $build_select_sql = 'SELECT %DISTINCT% %FIELD% FROM %TABLE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% %UNION%';
	public function __construct(){}
    final public function factory($config = array()) {
    	$class_name = parse_name('db_'.strtolower($config['type']),1);
    	load_driver($class_name);
    	if(class_exists($class_name)){
    		$db = new $class_name($config);
    		return $db;
    	}else{
    		return ;
    	}
    }
    
	/**
	 * 根据sql语句来判断是否为查询
	 * @param string $sql
	 */
	public function isSelect($SQL) {
		$SQL = trim(strtolower($SQL));
		$is_select = (strpos($SQL, "select") === 0 && strpos($SQL, "from") !== false) ? true : false;
		return $is_select;
	}
	
	/**
	 * 
	 * 查询数据(多条)
	 * @param $options
	 */
	public function select($SQL){
		$query = $this->query($SQL);
		$rows = array();
		while ($row = $this->fetchArray($query)){
			$rows[] = $row;
		}
		return $rows;
	}
	
	/**
	 * 
	 * 私有属性可读取的
	 * @param $property_name
	 */
	public function __get($property_name){
		$read = array('query_num','last_sql','build_select_sql','build_upload_sql','build_delete_sql');
		if(in_array($property_name, $read)){
			return $this->$property_name;
		}
	}
	
	/**
	 * 
	 * 私有属性可修改的
	 * @param $property_name
	 * @param $value
	 */
	public function __set($property_name,$value){
		$write = array();
		if(in_array($property_name, $write)){
			$this->$property_name = $value;
		}
	}
}

