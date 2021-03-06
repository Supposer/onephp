<?php
defined('IN_ONE') or exit('Access Denied');
class EditorAction{
		protected $errormessage = '';
		protected $upload=null;
		protected $ext_arr=array();
		protected $member;
		

	public function __construct(){
		$this->upload = get_instance_of('FilesUpload');		
		$this->ext_arr = C('upload_ext');
	}

	public function upload_handle(){
			if ($this->upload->init($_FILES['imgFile'],$this->ext_arr)){
				if ($this->upload->save(1)){				
					$attach = array();
					foreach ($this->upload->attach as $k => $v){
						$attach[$k] = iconv(CHARSET, 'utf-8', $v);					
					}
					echo json_encode(array('error' => 0, 'url' => $attach['attachment_url']));
					exit;
				}
			}

	}
}
?>