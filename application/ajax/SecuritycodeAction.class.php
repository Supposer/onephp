<?php
/** 
* SecuritycodeAction.class.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2014-2-12
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class SecuritycodeAction{
	protected $code_obj;
	public function __construct(){
		$this->code_obj = get_instance_of('SecurityCode');
	}
	
	public function index(){
		$secode = get_gp('secode');		//name
		$this->code_obj->set_show_mode(
			C('securitycode_c_width'),
			C('securitycode_c_height'),
			C('securitycode_c_num'),
			C('securitycode_c_fc'),
			C('securitycode_c_fz'),
			SITE_PATH.'public/fonts/'.C('securitycode_c_ff_url'),
			C('securitycode_c_lng'),
			C('securitycode_c_bc'),
			C('securitycode_c_point'),
			C('securitycode_c_line'),
			C('securitycode_c_b'),
			C('securitycode_c_bor')
		);
		if ($secode == 'ywcms_seccode'){
				$secode_name = 'ywcms_seccode';
		}else{
				$secode_name = 'ywcms_home_seccode';
		}
		$code = $this->code_obj->randText(C('securitycode_c_lng'));
		session_start();
		$_SESSION[$secode_name.'_content'] = md5(strtolower($code));
		Set_Cookie($secode_name.'_content',md5(strtolower($code)),5*60);
		$code = $this->code_obj->createImage($code);
		

 		
	}
	
}
