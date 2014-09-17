<?php
/** 
* FilesUpload.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-10-2
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class FilesUpload{ 
	protected $attach = array();//附件属性
	protected $type = '';//上传文件夹
	protected $error_code = 0;//错误代号
	protected $allow_ext;//允许扩展名数组
	protected $upload_max_size; //最大上传字节单位(B)
	public function __construct(){
		$this->upload_max_size = min_real_size(ini_get('upload_max_filesize'));	//2M
	}
	
	 /**
	 * 上传设置
	 * @param $attach 上传的控制件
	 * @param $allow_ext 允许扩展名
	 * @param $type 上传文件夹
	 * @return
	 */
	public function init($attach, $allow_ext=array() ,$type = '') {
		if(!is_array($attach) || empty($attach) || !$this->isUploadFile($attach['tmp_name']) || trim($attach['name']) == '' || $attach['size'] == 0) {
			$this->attach = array();
			$this->error_code = -1;
			return false;
		} else {
			$this->allow_ext = & $allow_ext;
			$this->type = $type;
			$fileArr = $this->getTargetFilename();
			$attach['size'] = intval($attach['size']);
			
			$attach['name'] =  trim($attach['name']);															//文件名
			$attach['ext'] = $this->fileExt($attach['name']);													//扩展名
			$attach['name'] =  htmlspecialchars($attach['name'], ENT_QUOTES);									//文件名转义
			
			if(strlen($attach['name']) > 90) {
				$attach['name'] = cutstr($attach['name'], 80, '').'.'.$attach['ext'];
			}
			$attach['isimage'] = $this->isImageExt($attach['ext']);
			$attach['extension'] = $this->getTargetExtension($attach['ext']);									//是否非安全扩展名,(attach)
			$attach['attachdir'] = $this->getTargetDir($this->type);											//指定存放文件夹
			$attach['attachment'] = $attach['attachdir'].$fileArr.'.'.$attach['extension'];						//相对路径
			
			$attach['attachment_url'] = UPLOAD_URL.$attach['attachdir'].$fileArr.'.'.$attach['extension'];		//虚拟路径
			$attach['attachment_path'] = UPLOAD_PATH.$attach['attachdir'].$fileArr.'.'.$attach['extension'];	//物理路径
			
			$this->attach = & $attach;
			$this->error_code = 0;
			return true;
		}

	}

	/**
	 * 保存上传文件
	 * @param $ignore 是否不检查
	 * @return
	 */
	public function save($ignore = 0){
		if($ignore) {
			if(!$this->saveToLocal($this->attach['tmp_name'], $this->attach['attachment_path'])) {
				$this->error_code = -103;
				return false;
			} else {
				$this->error_code = 0;
				return true;
			}
		}
		if(empty($this->attach) || empty($this->attach['tmp_name']) || empty($this->attach['target'])) {
			$this->error_code = -101;
		} elseif(!$this->attach['isimage'] && $this->attach['ext'] != 'swf') {
			$this->error_code = -102;
		}elseif(!$this->saveToLocal($this->attach['tmp_name'], $this->attach['target'])) {
			$this->error_code = -103;
		} elseif(($this->attach['isimage'] || $this->attach['ext'] == 'swf') && (!$this->attach['imageinfo'] = $this->getImageInfo($this->attach['target'], true))) {
			$this->error_code = -104;
			@unlink($this->attach['target']);
		} elseif($this->attach['size'] > $this->upload_max_size){
			$this->error_code = -105;
		} else {
			$this->error_code = 0;
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * 设置最大上传文件大小
	 * @param $upload_max_filesize 上传文件大小
	 */
	public function setUploadMaxSize($upload_max_filesize){
		$upload_max_filesize = min_real_size($upload_max_filesize);
		$sys_upload_max_filesize = min_real_size(ini_get('upload_max_filesize'));
		if($upload_max_filesize>0 && $sys_upload_max_filesize<=$upload_max_filesize){
			return $this->upload_max_size = $upload_max_filesize;
		}else{
			return $this->upload_max_size = $sys_upload_max_filesize;
		}
	}
	
	/**
	 * 返回错误代号
	 *
	 * 
	 */
	public function error() {
		return $this->error_code;
	}
	
	/**
	 * 显示错误
	 * 
	 */
	public function errormessage() {
		$message=array(
			'file_upload_error_-101' => '上传失败！上传文件不存在或不合法。',
			'file_upload_error_-102' => '上传失败！非图片类型文件。',
			'file_upload_error_-103' => '上传失败！无法写入文件或写入失败。',
			'file_upload_error_-104' => '上传失败！无法识别的图像文件格式。',
			'file_upload_error_-105' => '上传失败！文件太大无法上传，请控制在'.min_real_size($this->upload_max_size).'以内。',
		);
		return $message['file_upload_error_'.$this->error_code];
	}
	
	/**
	 * 获取文件后缀
	 * @param $filename 文件名
	 * @return string
	 */
	public function fileExt($filename) {
		return addslashes(strtolower(substr(strrchr($filename, '.'), 1, 10)));
	}

	/**
	 * 是否图片后缀
	 * @param $ext 后缀名
	 * @return
	 */
	public function isImageExt($ext) {
		static $imgext = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
		return in_array($ext, $imgext) ? true : false;
	}

	/**
	 * 获得图像信息
	 * @param $target 上传后的路径
	 * @param $allowswf 是否为SWF文件
	 * @return string
	 */
	public function getImageInfo($target, $allowswf = false) {//获得图像信息
		$ext = self::fileExt($target);
		$isimage = self::isImageExt($ext);
		if(!$isimage && ($ext != 'swf' || !$allowswf)) {
			return false;
		} elseif(!is_readable($target)) {
			return false;
		} elseif($imageinfo = @getimagesize($target)) {
			list($width, $height, $type) = !empty($imageinfo) ? $imageinfo : array('', '', '');
			$size = $width * $height;
			if($size > 16777216 || $size < 16 ) {
				return false;
			} elseif($ext == 'swf' && $type != 4 && $type != 13) {
				return false;
			} elseif($isimage && !in_array($type, array(1,2,3,6,13))) {
				return false;
			}
			return $imageinfo;
		} else {
			return false;
		}
	}
	
	/**
	 * 是否是上传的文件
	 * @param $source 上传后的数据源
	 * @return
	 */
	public function isUploadFile($source) {
		return $source && ($source != 'none') && (is_uploaded_file($source) || is_uploaded_file(str_replace('\\\\', '\\', $source)));
	}

	
	/**
	 * 生成目标文件名
	 * @return string
	 */
	public function getTargetFilename() {
		$filename = date('His').strtolower(random(16));
		return $filename;
	}

	/**
	 * 获取上传后目标扩展名
	 * @param $ext 上传文件扩展名
	 * @return
	 */
	public function getTargetExtension($ext) {
		if(empty($this->allow_ext)){
			$safeext  = array('attach', 'jpg', 'jpeg', 'gif', 'png', 'swf', 'bmp', 'txt', 'zip', 'rar', 'mp3');
		}else{
			$safeext  = $this->allow_ext;
		}
		return strtolower(!in_array(strtolower($ext), $safeext) ? 'attach' : $ext);
	}
	
	/**
	 * 得到上传后目标目录
	 * @param $type 增加外层目录
	 * @return
	 */
	public function getTargetDir($type) {
		$subdir = date('Y').'/'.date('m').'/'.date('d').'/'.date('H').'/';
		$subdir = $type?$type.'/'.$subdir:$subdir;
		self::checkDirExists($subdir);
		return $subdir;
	}

	/**
	 * 检查目录是否存在
	 * @param $subdir 目录路径
	 * @return
	 */
	public function checkDirExists($subdir = '') {
		$typedir = $subdir ? (UPLOAD_PATH.$subdir) : '';
		if(!is_dir($typedir))File::dirCreate($typedir);
		$res = is_dir($typedir);
		return $res;
	}

	/**
	 * 移动保存上传文件
	 * @param $source 源路径
	 * @param $target 目标路径
	 * @return
	 */
	public function saveToLocal($source, $target) {
		if(!self::isUploadFile($source)) {
			$succeed = false;
		}elseif(function_exists('move_uploaded_file') && @move_uploaded_file($source, $target)) {
			$succeed = true;
		}elseif(@copy($source, $target)) {
			$succeed = true;
		}elseif (@is_readable($source) && (@$fp_s = fopen($source, 'rb')) && (@$fp_t = fopen($target, 'wb'))) {
			while (!feof($fp_s)) {
				$s = @fread($fp_s, 1024 * 512);
				@fwrite($fp_t, $s);
			}
			fclose($fp_s); fclose($fp_t);
			$succeed = true;
		}
		
		if($succeed)  {
			$this->error_code = 0;
			@chmod($target, 0644); @unlink($source);
		} else {
			$this->error_code = 0;
		}

		return $succeed;
	}
	
	/**
	 * 
	 * 私有属性可读取的
	 * @param $property_name
	 */
	public function __get($property_name){
		$read = array('attach','type','error_code','allow_ext','upload_max_size');
		if(in_array($property_name, $read)){
			return $this->$property_name;
		}
	}
	
	/**
	 * 
	 * 私有属性可修改的
	 * @param $property_name
	 * @param $value
	 */
	public function __set($property_name,$value){
		$write = array();
		if(in_array($property_name, $write)){
			$this->$property_name = $value;
		}
	}
}