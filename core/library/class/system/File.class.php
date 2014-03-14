<?php
/** 
* File.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-24
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class File{
	/**
	 * 读取文件内容
	 * @param $filename 文件路径
	 * @return string
	 */
	static public function fileGet($filename) {
		return file_get_contents($filename);
	}
	
	/**
	 * 
	 * 获取文件后缀
	 * @param $filename 文件名
	 * @return string
	 */
	static public function fileExt($filename) {
		return strtolower(trim(substr(strrchr($filename, '.'), 1)));
	}
	
	/**
	 * 
	 * 过滤文件名
	 * @param $filename 文件名
	 * @return string
	 */
	static public function fileVname($filename) {
		return str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|', ' ', "'", '$', '&', '%', '#', '@'), array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''), $filename);
	}
	
	/**
	 * 
	 * 获取文件列表
	 * @param $dir 文件夹路径
	 * @param $fs 数组列表
	 * @param $flag 列表是否包含文件夹
	 * @return array
	 */
	static public function fileList($dir, $fs = array(),$flag=false) {
		$dir = rtrim($dir,'/').'/';
		$files = glob($dir.'*');
		if(!is_array($files)) return $fs;
		foreach($files as $file) {
			if(is_dir($file)) {
				$fs = self::fileList($file, $fs);
				if($flag){
					$fs[] = $file.'/';
				}
			} else {
				$fs[] = $file;
			}
		}
		return $fs;
	}
	
	/**
	 * 路径转换成linux文件完整路径格式
	 * @param $dirpath 文件路径
	 * @return string
	 */
	static public function dirPath($dirpath) {
		$dirpath = str_replace('\\', '/', $dirpath);
		if(substr($dirpath, -1) != '/') $dirpath = $dirpath.'/';
		return $dirpath;
	}
	
	/**
	 * 创建文件路径
	 * @param $path 文件路径
	 * @param $flag 创建文件夹时是否创建index.html文件
	 * @return
	 */
	static public function dirCreate($path,$flag=1) {
		$dir = self::dirPath($path);
		if(is_dir($path)) return true;
		$temp = explode('/', $dir);
		$cur_dir = '';
		$max = count($temp) - 1;
		for($i = 0; $i < $max; $i++) {
			$cur_dir .= $temp[$i].'/';
			if(is_dir($cur_dir)) continue;
			if(@mkdir($cur_dir)){
				if(SYS_CHMOD) @chmod($cur_dir, SYS_CHMOD);
				if(!is_file($cur_dir.'/index.html')&&$flag==1)self::filePut($cur_dir.'/index.html','<meta http-equiv="refresh" content="0;url=../">');
			}
		}
		return is_dir($path);
	}
	
	/**
	 * 修改文件权限
	 * @param $dir 文件路径
	 * @param $mode 权限值
	 * @param $require 是否子文件也要修改
	 * @return
	 */
	static public function dirChmod($dir, $mode = '', $require = 0) {
		if(empty($mode)) return;
		if(!$require) $require = substr($dir, -1) == '*' ? 2 : 0;
		if($require) {
			if($require == 2) $dir = substr($dir, 0, -1);
		    $dir = self::dirPath($dir);
			$list = glob($dir.'*');
			foreach($list as $v) {
				if(is_dir($v)) {
					self::dirChmod($v, $mode, 1);
				} else {
					@chmod($v, $mode);
				}
			}
		}
		if(is_dir($dir)) {
			@chmod($dir, $mode);
		} else {
			@chmod($dir, $mode);
		}
	}
	
	/**
	 * 写入文件内容
	 * @param $filename 文件路径
	 * @param $data 写入数据
	 * @param $createindexhtml 创建文件夹是否建立index.html文件
	 * @param $flags 文件写入方式
	 * @return
	 */
	static public function filePut($filename, $data,$createindexhtml = 1, $flags=0) {
		if($flags==0){//文件安全锁定写入
			$flags = LOCK_EX;
		}elseif($flags==1){//文件未尾增加
			$flags = FILE_APPEND;
		}elseif($flags==2){
			$flags = FILE_USE_INCLUDE_PATH;
		}
		self::dirCreate(dirname($filename),$createindexhtml);
		file_put_contents($filename, $data,$flags);
		if(SYS_CHMOD)@chmod($filename, SYS_CHMOD);
		return is_file($filename);
	}
	
	/**
	 * 要删除的文件路径
	 * @param $filename 文件路径
	 * @return
	 */
	static public function fileDel($filename) {
		@chmod($filename, 0777);
		return @unlink($filename);
	}
	
	/**
	 * 文件夹复制
	 * @param $fromdir 要复制的文件夹
	 * @param $todir 粘贴路径
	 * @return
	 */
	static public function dirCopy($fromdir, $todir) {
		$fromdir = self::dirPath($fromdir);
		$todir = self::dirPath($todir);
		$list = self::fileList($fromdir,array(),1);
		if(!is_dir($fromdir)) return false;
		if(!is_dir($todir)) self::dirCreate($todir,0);
		foreach($list as $v) {
			if (file_exists($v)){
				$path = $todir.substr($v,strlen($fromdir));
				if (is_file($v)){
					$copydir=dirname($path);
				}else{
					$copydir=$path;
				}
				self::dirCreate($copydir,0);
				if(!is_file($path) or is_writable($path)) {
					@copy($v, $path);
					if(SYS_CHMOD) @chmod($path, SYS_CHMOD);
				}
			}
		}
	    return true;
	}
	
	/**
	 * 删除目录
	 * @param $dir 要删除的目录路径
	 * @return
	 */
	static public function dirDelete($dir) {
		$dirpath = realpath($dir);
		$dir = self::dirPath($dir);
		if(!is_dir($dir)) return false;
		$dirs = array(CORE_PATH, CONF_PATH, TPL_PATH, UPLOAND_PATH);//核心文件列表
		if(in_array($dir, $dirs)) halt(array('header'=>'Cannot Remove System DIR','message'=>$dir));
		if(substr($dir, 0, strlen(SITE_PATH)) != SITE_PATH) halt(array('header'=>'Not in the system DIR','message'=>$dir));//判断是否系统范围内
		$list = glob($dir.'*');
		if($list) {
			foreach($list as $v) {
				is_dir($v) ? self::dirDelete($v) : self::fileDel($v);
			}
		}
		return @rmdir($dir);
	}
	
	/**
	 * 下载文件
	 * @param $file 下载的文件物理路径
	 * @param $filename 下载后的文件名
	 * @param $data 要下载的数据内容
	 * @return
	 */
	static public function fileDown($file = '', $filename = '', $data = '') {
		if(!$data && !is_file($file)) exit;
		$filename = $filename ? $filename : basename($file);
		$filetype = self::fileExt($filename);
		$filesize = $data ? strlen($data) : filesize($file);
	    ob_end_clean();
		@set_time_limit(0);
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Pragma: no-cache');
		}
		header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Content-Encoding: none');
		header('Content-Length: '.$filesize);
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Type: '.$filetype);
		if($data) { echo $data; } else { readfile($file); }
		exit;
	}
	
	/**
	 * 查找类型文件
	 * @param $dir 查找路径
	 * @param $ext 文件后缀
	 * @param $fs 上层文件
	 * @return
	 */
	static public function getFile($dir, $ext = '', $fs = array()) {
		$dir = dir_path($dir);
		$files = glob($dir.'*');
		if(!is_array($files)) return $fs;
		foreach($files as $file) {
			if(is_dir($file)) {
				$fs = self::getFile($file, $ext, $fs);
			} else {
				if($ext) {
					if(preg_match("/\.($ext)$/i", $file)) $fs[] = $file;
				} else {
					$fs[] = $file;
				}
			}
		}
		return $fs;
	}
	
	/**
	 * 文件是否存在
	 * @param $filename 文件路径
	 * @return 
	 */
	static public function fileExists($filename) {
		return file_exists($filename);
	}
}