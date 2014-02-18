<?php
/** 
* App.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-21
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class App {
	/**
	 * 
	 * 初始化程序
	 */
	static public function init(){
		// 设定错误和异常处理
        set_error_handler(array('App','appError'));
		set_exception_handler(array('App','appException'));
	}
	
	/**
	 * 
	 * 创建应用程序
	 */
	static public function run(){
		self::dispatcher();
		if(!is_dir(APP_PATH.APP_NAME)){
			halt(array('header'=>'Not found APP_NAME','message'=>APP_NAME));
		}
		$app_class = APP_CLASS.'Action';
		my_autoload($app_class);
		if(!class_exists($app_class)){
			halt(array('header'=>'Not found APP_CLASS','message'=>APP_CLASS));
		}else{
			$app = new $app_class;			
		}
		if(!method_exists($app,APP_METHOD)){
			halt(array('header'=>'Not found APP_METHOD','message'=>APP_METHOD));
		}else{
			$app = call_user_func(array($app, APP_METHOD));
		}
	}
	
	/**
	 * 
	 * 应用路由
	 */
	static private function dispatcher(){
		$app_name = trim($_GET['m']);
		$app_class = trim($_GET['c']);
		$app_method = trim($_GET['a']);
		//应用目录
		defined('APP_NAME')||define('APP_NAME', $app_name?$app_name:'index');
		//应用类
		defined('APP_CLASS')||define('APP_CLASS', parse_name($app_class?$app_class:'index',1));
		//应用方法
		defined('APP_METHOD')||define('APP_METHOD', $app_method?$app_method:'index');
	}
	
	
	/**
	 * 
	 * 包含应用函数
	 * @param $func_file 
	 * @param $app
	 */
	static function loadAppFunc($func_file = '',$app = ''){
		static $_instance = array();
		$mix = to_guid_string(func_get_args());		
		
		if(!isset($_instance[$mix])){			
			$_instance[$mix] = true;
			if($app && is_string($app) && is_dir(APP_PATH.$app)){				
				$appdir = $app;
			}elseif(empty($app) && APP_NAME){				
				$appdir = APP_NAME;
			}else{				
				return;
			}			
			if($func_file){
				if(is_string($func_file) && is_file(APP_PATH.$appdir.'/function/'.$func_file.'.php')){
					return require_cache(APP_PATH.$appdir.'/function/'.$func_file.'.php');
				}elseif(is_array($func_file)){
					foreach ($func_file as $func_file_v){
						load_app_func($func_file_v,$appdir);
					}
				}
			}
		}
	}
	
	/**
	 * 
	 * 包含应用公共类
	 * @param $class_file
	 * @param $app
	 */
	static function loadAppClass($class_file = '',$app = ''){
		static $_instance = array();
		$mix = to_guid_string(func_get_args());
		if(!isset($_instance[$mix])){
			$_instance[$mix] = true;
			if($app && is_string($app) && is_dir(APP_PATH.$app)){
				$appdir = $app;
			}elseif(empty($app) && defined(APP_NAME)){
				$appdir = APP_NAME;
			}else{
				return;
			}
			if($class_file){
				$class_file = parse_name($class_file, 1);
				if(is_string($class_file) && is_file(APP_PATH.$appdir.'/class/'.$class_file.'.class.php')){
					return require_cache(APP_PATH.$appdir.'/class/'.$class_file.'.class.php');
				}elseif(is_array($class_file)){
					foreach ($class_file as $class_file_v){
						load_app_func($class_file_v,$appdir);
					}
				}
			}
		}
	}
	
	/**
	 * 
	 * 自定义错误处理
	 * @param $errno 错误级别
	 * @param $errstr 错误信息
	 * @param $errfile 发生错误文件
	 * @param $errline 发生错误行数
	 */
	static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR://致命性的运行时错误
          case E_USER_ERROR://报告用户引发的致命错误信息
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
            halt($errorStr);
            break;
          case E_STRICT://编码标准化警告，允许PHP建议如何修改代码以确保最佳的互操作性向前兼容性。
          case E_USER_WARNING://报告用户引发的非致命错误信息
          case E_USER_NOTICE://报告用户引发的注意消息
          default://其它
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            Log::record($errorStr,Log::NOTICE);
          break;
      }
    }
    
    /**
     * 
     * 自定义异常处理
     * @param $e 异常对象
     */
    static public function appException($e) {
        halt($e->__toString());
    }
}
