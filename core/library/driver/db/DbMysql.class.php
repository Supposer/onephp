<?php
/** 
* DbMysql.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-7
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class DbMysql extends Db{
	private $_connectid = 0;//数据库连接ID
	private $_reconnect = 0;//重链标记
	protected $build_select_sql = 'SELECT %DISTINCT% %FIELD% FROM %TABLE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% %UNION%';
	protected $build_upload_sql = 'UPDATE %TABLE% %DATA% %JOIN% %WHERE% %ORDER% %LIMIT%';
	protected $build_delete_sql = 'DELETE FROM %TABLE% %JOIN% %WHERE% %ORDER% %LIMIT%';
	
	/**
	 * 
	 * 初始化
	 */
	public function __construct($db_conf = array()){
		$this->_dbconf['dbhost'] = $db_conf['dbhost'];
		$this->_dbconf['dbuser'] = $db_conf['dbuser'];
		$this->_dbconf['dbpw'] = $db_conf['dbpw'];
		$this->_dbconf['dbname'] = $db_conf['dbname'];
		$this->_dbconf['charset'] = $db_conf['charset'];
		$this->_dbconf['pconnect'] = $db_conf['pconnect'] == 1?1:0;
		$this->_dbconf['lp'] = $db_conf['dblp']==1?1:0;
		$this->_dbconf['dbpre'] = $db_conf['dbpre'];
		$this->query_num = 0;
		$this->connect();
	}
	
	/**
	 * 
	 * 连接
	 */
	public function connect() {
		$this->_connectid = $this->_dbconf['pconnect'] == 0 ? @mysql_connect($this->_dbconf['dbhost'], $this->_dbconf['dbuser'], $this->_dbconf['dbpw'], true) : @mysql_pconnect($this->_dbconf['dbhost'], $this->_dbconf['dbuser'], $this->_dbconf['dbpw']);
		if(mysql_errno($this->_connectid) != 0){
			$this->halt('Connect('.$this->_dbconf['pconnect'].') to MySQL failed');
		}
		$serverinfo = $this->serverInfo();
		if ($serverinfo > '4.1' && $this->_dbconf['charset']) {
			mysql_query("SET character_set_connection=" . $this->_dbconf['charset'] . ",character_set_results=" . $this->_dbconf['charset'] . ",character_set_client=binary", $this->_connectid);
		}
		if ($serverinfo > '5.0') {
			mysql_query("SET sql_mode=''", $this->_connectid);
		}
		$this->selectDb($this->_dbconf['dbname']);
		
	}
	
	/**
	 * 
	 * 选择数据库
	 * @param 数据库名 $dbname
	 */
	public function selectDb($dbname) {
		if (!@mysql_select_db($dbname,$this->_connectid)) {
			$this->halt('Cannot use database');
		}
	}
	
	/**
	 * 
	 * 添加表前缀
	 * @param $table 表名
	 */
	public function table($table){
		return $this->parseSep($this->_dbconf['dbname'].'.'.$this->_dbconf['dbpre'].$table);
	}
	
	/**
	 * 
	 * 表字段分符符
	 * @param $var
	 */
	public function parseSep($var){
		$var = str_replace(array('`', ' '), '',$var);
		$var = explode('.', $var);
		foreach($var as $k => $v){
			if($v != '*')$var[$k]='`'.$v.'`';
		}
		$var = implode('.', $var);
		return $var;
	}
	
	/**
	 * 
	 * 获取数据库版本号
	 */
	public function serverInfo() {
		return mysql_get_server_info($this->_connectid);
	}
	
	/**
	 * 
	 * 执行SQL语句
	 * @param $SQL SQL语句
	 * @param $method 运行方法 U：更新,Q:只缓存第一条,空：普通查询
	 * @param $error 是否显示错误
	 */
	public function query($SQL,$method = null,$error = true) {
		if ($method && function_exists('mysql_unbuffered_query')) {
			$query = @mysql_unbuffered_query($SQL,$this->_connectid);
		} else {
			$query = @mysql_query($SQL,$this->_connectid);
		}
		/**
		 * 连接失效重新连接
		 */
		if (in_array(mysql_errno($this->_connectid),array(2006,2013)) && empty($query) && $this->_dbconf['pconnect']==0) {
			if($this->_reconnect==0)$this->_reconnect++;
			$this->close();
			sleep(2);
			$this->connect();
			$query = $this->query($SQL);
		}elseif($this->_reconnect>0){
			$this->_reconnect = 0;
		}
		
		if ($method<>'U') {
			$this->query_num++;
		}
		!$query && $error && SYS_DEBUG==true && $this->halt('Query Error: '.$SQL);
		$this->last_sql = $SQL;
		return $query;
	}
	
	/**
	 * 
	 * 从结果集中取得一行作为关联
	 * @param $query 查询结果数据源
	 * @param $result_type 数组模式
	 */
	public function fetchArray(&$query, $result_type = MYSQL_ASSOC){
		if(is_resource($query)){
			return mysql_fetch_array($query,$result_type);
		}else{
			return false;
		}
	}
	
	
	/**
	 * 
	 * 取第一行数据
	 * @param $SQL 查询SQL
	 * @param $result_type 关联数组形式
	 */
	public function getOne($SQL, $result_type = MYSQL_ASSOC) {
		$query = $this->query($SQL,'Q');
		$rt =& $this->fetchArray($query,$result_type);
		$this->freeResult($query);
		return $rt;
	}
	
	/**
	 * 
	 * 获取第一行某个数据
	 * @param $SQL 查询SQL
	 * @param $result_type 关联数组形式
	 * @param $field 
	 */
	public function getValue($SQL,$result_type = MYSQL_NUM,$field=0) {
		$query = $this->query($SQL,'Q');
		$rt =& $this->fetchArray($query,$result_type);
		$this->freeResult($query);
		return isset($rt[$field]) ? $rt[$field] : false;
	}
	
	/**
	 * 
	 * 最新ID
	 */
	public function insertId() {
		return $this->getValue('SELECT LAST_INSERT_ID()');
	}
	
	/**
	 * 
	 * 取得某表的字段
	 * @param $table 表名
	 */
	public function getFields($table){
		$fields = array();
		$query = $this->query('DESC '.$table);
		while($r = $this->fetchArray($query)){
			$fields[] = $r;
		}
		return $fields;
	}
	
	public function getPk($table){
		if($table && is_string($table)){
			$modle_fields = $this->getFields($table);
			if(is_array($modle_fields)){
				foreach ($modle_fields as $v){
					if($v['Key']=='PRI'){
						return $v['Field'];
					}
				}
			}
			return '';
		}else{
			return '';
		}
	}
	
	/**
	 * 
	 * 更新数据专用
	 * @param $SQL 更新SQL
	 * @param $lp 是否开启延时更新
	 */
	public function update($SQL,$lp = 0) {
		$SQL=trim($SQL);
		if ($this->_dbconf['lp'] == 1 && $lp) {
			$tmpsql6 = substr($SQL, 0, 6);
			if (strtoupper($tmpsql6.'E') == 'REPLACE') {
				$SQL = 'REPLACE LOW_PRIORITY'.substr($SQL,7);
			} else {
				$SQL = $tmpsql6.' LOW_PRIORITY'.substr($SQL,6);
			}
		}
		$this->query($SQL,'U');
		return $this->affectedRows();
	}
	
	/**
	 * 
	 * 取得前一次 MySQL 操作所影响的记录行数
	 */
	public function affectedRows(){
		return mysql_affected_rows($this->_connectid);
	}
	
	/**
	 * 
	 * 取得结果集中行的数目
	 * @param $query 查询结果数据源
	 */
	public function numRows($query){
		if (!is_bool($query)) {
			return mysql_num_rows($query);
		}
		return 0;
	}
	
	
	/**
	 * 
	 * 取得结果集中字段的数目
	 * @param $query 查询结果数据源
	 */
	public function numFields($query){
		return mysql_num_fields($query);
	}
	
	/**
	 * 
	 * 转义一个字符串用于 mysql_query
	 * @param $str 需转义字符串
	 */
	public function escapeString($str){
		return mysql_escape_string($str);
	}
	
	/**
	 * 
	 * 释放结果内存
	 */
	public function freeResult(&$query =''){
		if (is_resource($query) && get_resource_type($query)==='mysql result') {
			@mysql_free_result($query);
		}else{
			@mysql_free_result();
		}
	}

	/**
	 * 
	 * 关闭 MySQL 连接
	 * @param $linkid 连接LinkID
	 */
	public function close(){
		return @mysql_close($this->_connectid);
	}
	
	/**
	 * 
	 * MYSQL出错停止
	 * @param $msg 错误信息
	 */
	private function halt($msg=null){
		$sqlerror = mysql_error();
		$sqlerrno = mysql_errno();
		$sqlerror = str_replace($this->_dbconf['dbhost'],'dbhost',$sqlerror);
		// $error = '';
		// $error .= "<html><head><meta http-equiv='Content-Type' content='text/html; charset=".CHARSET."' /><title>MySQL Server Error</title><style type='text/css'>P,BODY{FONT-FAMILY:tahoma,arial,sans-serif;FONT-SIZE:11px;}A { TEXT-DECORATION: none;}a:hover{ text-decoration: underline;}TABLE{TABLE-LAYOUT:fixed;WORD-WRAP: break-word}TD { BORDER-RIGHT: 1px; BORDER-TOP: 0px; FONT-SIZE: 16pt; COLOR: #000000;}</style><body>\n\n";
		// $error .= "<table><tr><td>$msg";
		// $error .= "<br><br><b>The URL Is</b>:<br>http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		// $error .= "<br><br><b>MySQL Server Error</b>:<br>$sqlerror  ( $sqlerrno )";
		// $error .= "</td></tr></table></body></html>";
		halt(array('header'=>'MySQL Server Error','message'=>$msg,'error_code'=>"$sqlerror  ( $sqlerrno )"));
		exit;
	}
}