<?php
/** 
* Log.class.php 日志处理类
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Log {

    // 日志级别 从上到下，由低到高
    const EMERG = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR = 'ERR';  // 一般错误: 一般性错误
    const WARN = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO = 'INFO';  // 信息: 程序输出信息
    const DEBUG = 'DEBUG';  // 调试: 调试信息
    const SQL = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志记录方式
    const SYSTEM = 0;
    const MAIL = 1;
    const FILE = 3;
    const SAPI = 4;

    // 日志信息
    static $log =   array();

    // 日期格式
    static $format =  '[ c ]';

    /**
     * 
     * 记录日志 并且会过滤未经设置的级别
     * @param $message 日志信息
     * @param $level 日志级别
     * @param $record 是否强制记录
     * @return void
     */
    static function record($message,$level=self::ERR,$record=false) {
        if($record || strpos(C('log_level'),$level)) {
            $now = date(self::$format);
            self::$log[] = "{$now} ".$_SERVER['REQUEST_URI']." | {$level}: {$message}\r\n";
        }
    }

    /**
     * 
     * 日志保存
     * @param $type 日志记录方式
     * @param $destination 写入目标
     * @param $extra 额外参数
     * @return void
     */
    static function save($type='',$destination='',$extra='') {
        self::_wirte(implode('',self::$log),$type,$destination,$extra);
        // 保存后清空日志缓存
        self::$log = array();
        //clearstatcache();
    }
    
    /**
     * 
     * 日志直接写入
     * @param $message 日志信息
     * @param $level 日志级别
     * @param $type 日志记录方式
     * @param $destination 写入目标
     * @param $extra 额外参数
     * @return void
     */
    static function write($message,$level=self::ERR,$type='',$destination='',$extra='') {
        $now = date(self::$format);
        $message = "{$now} ".$_SERVER['REQUEST_URI']." | {$level}: {$message}\r\n";
        self::_wirte($message,$type,$destination,$extra);
    }
    
    /**
     * 
     * 写入日志
     * @param $message 日志信息
     * @param $type 日志记录方式
     * @param $destination 写入目标
     * @param $extra 额外参数
     * @return void
     */
	static private function _wirte($message,$type='',$destination='',$extra='') {
        $type = $type?$type:self::FILE;
        if(self::FILE == $type) { // 文件方式记录日志信息
            if(empty($destination)){
                $destination = 'web.log';
            }
            $destination = LOGS_CACHE_PATH.date('Y/m/d/').$destination;
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            if(is_file($destination) && floor(C('log_file_size')) <= filesize($destination) ){
            	rename($destination,dirname($destination).'/'.date('His').'-'.basename($destination));
            }
            $destdir = dirname($destination);
            if(!is_dir($destdir))File::dirCreate($destdir);
        }else{
            $destination = $destination?$destination:C('log_dest');
            $extra = $extra?$extra:C('log_extra');
        }
        error_log($message, $type,$destination ,$extra);
    }
    
}