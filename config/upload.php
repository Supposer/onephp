<?php
/** 
* upload.php
* @copyright (C) 2013-2014 ONE
* @license ONE
* @lastmodify 2014-2-24
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
return array(
	'ext' => array(
		'gif', 'jpg', 'jpeg', 'png', 'bmp',#img
		'swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb',#media
		'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'txt', 'zip', 'rar', 'gz', 'bz2',#file
	),
	'maxSize' => '10MB',
	'queueSize' => 10,		//_uploadify
	'uploadLimit' => 10,	//_uploadify


);