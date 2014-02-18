<?php
/** 
* Ob.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-10-7
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Ob{
	/**
	 * 打开输出控制缓冲
	 *
	 * @return bool
	 */
	static public function obStart(){
		if(self::obGetMode() == 1){
			ob_start('ob_gzhandler');
		}else{
			ob_start();
		}
	}

	/**
	 * 
	 * 压缩缓冲内容，并设置响应头为压缩格式
	 * @param $output 要压缩的内容
	 * @return string
	 */
	static public function obContents($output){
		ob_end_clean();
		$getHAE = $_SERVER['HTTP_ACCEPT_ENCODING'];
		if (!headers_sent() && C('system_gzip') && $getHAE && self::nOutputZip()!='ob_gzhandler') {
			$encoding = '';
			if (strpos($getHAE,'x-gzip') !== false) {
				$encoding = 'x-gzip';
			} elseif (strpos($getHAE,'gzip') !== false) {
				$encoding = 'gzip';
			}

			if ($encoding && function_exists('crc32') && function_exists('gzcompress')) {				
				header('Content-Encoding:'.$encoding);
				$outputlen  = strlen($output);				
				$outputzip  = "\x1f\x8b\x08\x00\x00\x00\x00\x00";				
				$outputzip .= substr(gzcompress($output,C('system_gzip')),0,-4);				
				$outputzip .= @pack('V',crc32($output));				
				$output = $outputzip.@pack('V',$outputlen);
			} else {
				self::obStart();
			}
		} else {
			self::obStart();
		}
		return $output;
	}
	
	/**
	 * 判断输出模式是否为可压缩
	 *
	 * @return int 1为可压缩
	 */
	static public function obGetMode(){
		static $mode = null;
		if ($mode!==null) {
			return $mode;
		}
		$mode = 0;
		if (C('system_gzip') && function_exists('ob_gzhandler') && self::nOutputZip()!='ob_gzhandler') {
			$mode = 1;
		}
		return $mode;
	}

	/**
	 * 
	 * 将输出缓冲的内容刷出
	 * @param $ob 是否使用ob_flush
	 */
	static public function nFlush($ob=null){
		if (php_sapi_name()!='apache2handler' && php_sapi_name()!='apache2filter') {
			if (self::nOutputZip() == 'ob_gzhandler') {
				return;
			}
			if ($ob && ob_get_length()!==false && ob_get_status() && !self::obGetMode()) {
				@ob_flush();
			}
			flush();
		}
	}

	/**
	 * 判断输出缓冲输出处理者
	 *
	 * @return string
	 */
	static public function nOutputZip(){
		static $output_handler = null;
		if ($output_handler === null) {
			if (@ini_get('zlib.output_compression')) {
				$output_handler = 'ob_gzhandler';
			} else {
				$output_handler = @ini_get('output_handler');
			}
		}
		return $output_handler;
	}

	/**
	 * 将输出缓冲的内容，并中断程序
	 *
	 *
	 */
	static public function Output() {		
		$output = ob_get_contents();
		echo self::obContents($output);
		unset($output);
		self::nFlush();
		exit();
	}
}
