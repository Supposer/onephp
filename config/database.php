<?php
/** 
* database.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2013-4-14
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
return array(
	'test' => array(
		'type' => 'mysql',
		'dbhost' => '127.0.0.1',
		'dbuser' => 'root',
		'dbpw' => '',
		'dbname' => 'test',
		'dbpre' => 'qilong_',
		'charset' => 'utf8',
		'pconnect' => 0,
		'dblp' => 1,
	),
	
	'default' => array(
		'type' => 'mysql',
		'dbhost' => '192.168.1.144',
		'dbuser' => 'root',
		'dbpw' => '123456',
		'dbname' => 'qilong',
		'dbpre' => 'qilong_',
		'charset' => 'utf8',
		'pconnect' => 0,
		'dblp' => 1,
	),
);


