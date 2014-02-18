<?php
/** 
* mail.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2014-2-15
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
return array (
	'default' => array(
		'Host' => 'smtp.b2bvip.com',
		'Username' => 'qilongservice@b2bvip.com',
		'Password' => 'c2F0H1PuYT89CDxs266',
		'Port' => '25',
		'From' => 'qilongservice@b2bvip.com',  
	),
	
	
	
	
	
	
	
	
	
	
	
	# 后备邮箱,用于发送失败时,用后备邮箱发送错误信息到后备邮箱
	'backup' => array(
		'Host' => 'smtp.163.com',
		'Username' => 'b2bvip2012@163.com',
		'Password' => 'yw_web_G5pass',
		'Port' => '25',
		'From' => 'b2bvip2012@163.com',  	
	),
);
