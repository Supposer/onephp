<?php
/** 
* ywcore.php 单入口文件
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-10
* @author bin
*/
define('IN_ONE', true);
//是否调试模式
defined('SYS_DEBUG') || define('SYS_DEBUG',true);
ini_set('display_errors', 'ON');
error_reporting(SYS_DEBUG ? E_ALL & ~E_NOTICE: 0);


//系统需求PHP最低版本
if(version_compare(PHP_VERSION,'5.1.0','<'))  die('require PHP > 5.1.0 !');
//定义物理路径
$path = str_replace('\\','/',dirname(__FILE__));
//所有路径常量都必须以/ 结尾
// 核心物理路径
defined('CORE_PATH') || define('CORE_PATH', $path.'/');
// 系统核心类库目录
defined('LIB_PATH') || define('LIB_PATH',CORE_PATH.'library/');
// 系统核心类库目录
defined('CLASS_PATH') || define('CLASS_PATH',LIB_PATH.'class/system/');
// 系统扩展类库目录
defined('CLASS_EXTENSION_PATH') || define('CLASS_EXTENSION_PATH',LIB_PATH.'class/extension/');
// 系统核心函数库目录
defined('FUNC_PATH') || define('FUNC_PATH',LIB_PATH.'function/');
// 系统驱动类库目录
defined('DRIVER_PATH') || define('DRIVER_PATH',LIB_PATH.'driver/');

// 网站物理路径
defined('SITE_PATH') || define('SITE_PATH', substr($path, 0, strrpos($path, '/')).'/');
// 应用目录
defined('APP_PATH') || define('APP_PATH',SITE_PATH.'application/');
// 模型目录
defined('MODEL_PATH') || define('MODEL_PATH',SITE_PATH.'model/');
// 配置目录
defined('CONF_PATH') || define('CONF_PATH',  SITE_PATH.'config/');
// 语言包目录
defined('LANG_PATH') || define('LANG_PATH',  SITE_PATH.'language/');
// 上传目录
defined('UPLOAND_PATH') || define('UPLOAND_PATH',SITE_PATH.'attachment/');
// 模板目录
defined('TPL_PATH') || define('TPL_PATH', SITE_PATH.'template/');
// 缓存目录
defined('CACHE_PATH') || define('CACHE_PATH', SITE_PATH.'cache/');
// 已编译模板
defined('TPL_CACHE_PATH') || define('TPL_CACHE_PATH', CACHE_PATH.'tpl/');

// 日志目录
defined('LOGS_CACHE_PATH') || define('LOGS_CACHE_PATH', CACHE_PATH.'logs/');
// 数据缓存目录
defined('DATA_CACHE_PATH') || define('DATA_CACHE_PATH', CACHE_PATH.'data/');
// 数据库备份目录
defined('BACKUP_CACHE_PATH') || define('BACKUP_CACHE_PATH', CACHE_PATH.'backup/');

//网站外部接口文件路径
defined('API_PATH') || define('API_PATH',SITE_PATH.'api/');
//支付接口存放位置
defined('PAY_PATH') || define('PAY_PATH',API_PATH.'onlinepay/');

//SAE初始化MC
//memcache_init();

//获取程序版本信息
require_once CORE_PATH.'version.php';
// 加载系统基础函数库
require_once FUNC_PATH.'global.func.php';

//主机协议
define('SITE_PROTOCOL', isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://');
//网站虚拟路径
define('SITE_URL', (isset($_SERVER['HTTP_HOST'])? (SITE_PROTOCOL.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443'?':'.$_SERVER['SERVER_PORT'].'/':'/')): '').C('system_web_dir'));

//上传目录虚拟路径
defined('UPLOAND_URL') || define('UPLOAND_URL',SITE_URL.'attachment/');
//静态公共目录虚拟路径
defined('PUBLIC_URL') || define('PUBLIC_URL',SITE_URL.'public/');
//静态JS目录虚拟路径
defined('SCRIPT_URL') || define('SCRIPT_URL',PUBLIC_URL.'script/');
//静态图片目录虚拟路径
defined('IMG_URL') || define('IMG_URL',PUBLIC_URL.'images/');
//静态样式目录虚拟路径
defined('SKIN_URL') || define('SKIN_URL',PUBLIC_URL.'css/');

//来源
define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
//区域时间设置
function_exists('date_default_timezone_set')&& C('system_timezone') && date_default_timezone_set(C('system_timezone'));
//调整时间
define('SYS_TIME',time() + intval(C('system_timediff')));
//程序编码
define('CHARSET', C('system_charset'));
//生成文件权限
define('SYS_CHMOD',intval(C('system_file_mod')?C('system_file_mod'):'0777',8));

//GPCF全局变量转义过滤
filter_gpcf();

//初始化搜索引擎
//require_once CLASS_PATH.'sphinx.class.php';
//$GLOBALS['sph'] = new SphinxClient ();
//$GLOBALS['sph']->SetServer (C('search_server'),(int)C('search_port'));
//$GLOBALS['sph']->SetConnectTimeout (C('search_ttl'));
//$GLOBALS['sph']->SetArrayResult (C('search_array'));

// 注册AUTOLOAD方法
spl_autoload_register('my_autoload');

// 设定错误和异常处理
App::init();

//设置Session驱动
get_instance_of('Session');
