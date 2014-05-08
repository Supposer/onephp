<?php
/** 
* UploadAction.class.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2014-2-15
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class UploadAction{
	protected $errormessage = '';
	protected $upload=null;
	protected $ext_arr=array();
	protected $member;
	
	public function __construct(){
		$this->upload = get_instance_of('FilesUpload');		
		$this->ext_arr = C('upload_ext');	
	}
	

	/**
	 * 上传处理
	 * Enter description here ...
	 */
	public function upload_handle(){
		//$userid = $_POST['session_id'];
		//File::filePut('1.txt', $_POST['HTTP_USER_AGENT']);	
		//File::filePut('1.txt', $_COOKIE);			//测试是否有写入权限
		
		# 用户登录判断.....
		
		if ($this->upload->init($_FILES['Filedata'],$this->ext_arr)){
			if ($this->upload->save(1)){				
				$attach = array();
				foreach ($this->upload->attach as $k => $v){
					$attach[$k] = iconv(CHARSET, 'utf-8', $v);					
				}						
				
				if ($data = $this->attach_handle($attach,$userid)){
					echo json_encode($data);
				}
			}else{
				echo $this->upload->errormessage();
			}
		}else{
			echo $this->upload->errormessage();
		}
		
	}
	
	private function attach_handle($attach){
		if ($attach['isimage']){
			$thumb = get_instance_of('Images');
			
			$thumb->param($attach['attachment_path'])->thumb($attach['attachment_path'].'.thumb.jpg',200,200,0);      // 等比缩放
			//$thumb->param($attach['attachment'])->thumb($attach['attachment_path'].'.middle.jpg',450,450,0);      // 等比缩放
			
		}
		
		# FTP.....
		$attach_url = $this->ftp($attach);
		
		# 其它操作,如写入附件表...
		
		$data=array(
			'attid' => $attid,
			'fileurl' => $attach_url,
			'filename' => $attach['name'],
			'filetype' => $attach['ext'],
			'filesize' => $attach['size'],
			'fileext' => $attach['extension'],
			'addtime' => SYS_TIME,
			'userid'=> $userid,	
			'ip' => get_client_ip(1),
		);
		
		return $data;
	
	}
	
	public function ftp($attach){
		$dftp = get_instance_of('Dftp');
		$ftp_settings = is_array(C('system_ftpserver'))?C('system_ftpserver'): array(); 
		$ftp_count = count($ftp_settings);
		if($ftp_count<=0){
			return $attach['attachment_url'];
		}else{
			//$key = fmod(abs(crc32($file_path)), $ftp_count);	# 分布式存储
			$key = 'default';
			$dftp->dftp($ftp_settings[$key]['ftp_host'],$ftp_settings[$key]['ftp_user'],$ftp_settings[$key]['ftp_paswd'],$ftp_settings[$key]['ftp_port'],$ftp_settings[$key]['root'],$ftp_settings[$key]['ftp_pasv']);		
			if ($dftp->dftp_put($attach['attachment_path'],$attach['attachment'])){
				File::fileDel($attach['attachment_path']);     //删除中转服务器文件
				
				if ($attach['isimage']){
					$dftp->dftp_put($attach['attachment_path'].'.thumb.jpg',$attach['attachment'].'.thumb.jpg');
					//$dftp->dftp_put($attach['attachment_path'].'.middle.jpg',$attach['attachment'].'.middle.jpg');	
					File::fileDel($attach['attachment_path'].'.thumb.jpg');     //删除中转服务器文件
					//File::fileDel($attach['attachment_path'].'.middle.jpg');     //删除中转服务器文件
				}
				
				return $ftp_settings[$key]['host'].$attach['attachment'];
			}else{
				return $attach['attachment_url'];
			}
			
		}
	}
	
}