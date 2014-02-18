<?php
/** 
* system.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2014-2-10
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
return array(
	//Session配置
	'session_storage' => 'files',
	'session_pre' => 'pre_',
	'session_ttl' => '1800', //session存储时间
	'session_savepath' => 'session/', //存储目录
	'session_n' => '1',

	//cookice配置
	'cookie_pre' => 'pre_',//前缀
	'cookie_domain' =>'',//作用域
 	'cookie_path' => '/',//作用路径
	'cookie_ttl' => '0', //Cookie 生命周期，0 表示随浏览器进程	
	
	//模板
	'template_parse' => false,//模板解释
	'template_refresh' => false,//强制更新模板缓存
	'template_suffix' => 'php',

	//系统杂项参数
	'enter_name' => 'index.php', 
	'web_dir' => '',
	'charset' => 'utf-8', //程序编码
	'gzip' => true, //页面是否Gzip压缩后输出
	'timezone' => 'Etc/GMT-8', //时间区域
	'timediff' => '0', //时间调整
	'file_mod' => '0777', //生成文件权限
	'lang' => 'zh-cn', //语言包
	'auth_key' => '3232QqeHn0P43BNff6m', //程序密钥
	
);