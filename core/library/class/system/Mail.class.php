<?php
/** 
* Mail.class.php
* @copyright (C) 2012-2013 B2BVIP
* @license http://www.b2bvip.com/
* @lastmodify 2013-4-14
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class Mail{
	
	public $info = array();//邮局信息属性
	public $error_code = 0;//错误代号
	protected $mail;
	
	public function __construct(){
		$mail_set = 'default';
		$this->info = C('mail_'.$mail_set);		//发送方配置
	}
	
	/**
	 * 初始化
	 * Enter description here ...
	 * @param unknown_type $info['FromName'],$info['Subject'],$info['Body'],$info['address']	//发送内容等信息
	 */
	public function init($info=array()){
		if(!is_array($info) || empty($info)){
			$this->info=array();
			$this->error_code=-1;
			echo $this->errormessage();
			return false;
		}else{
			$this->mail=get_instance_of('Phpmailer');	
			$this->error_code=0;
			$this->info['per']=$info;
			return true;			
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function send(){		
		$this->mail->Host = $this->info['Host']; 												//邮箱域名
		$this->mail->Username = $this->info['Username']; 										//邮箱用户名(完整的email地址)
		$this->mail->IsSMTP(); 																	//使用SMTP方式发送
		$this->mail->SMTPAuth = true; 															//启用SMTP验证功能
		$this->mail->IsHTML(true);																//是否使用HTML格式
		$this->mail->Password = $this->info['Password']; 										//邮箱密码
		$this->mail->Port=$this->info['Port'];													//邮箱端口
		$this->mail->From = $this->info['From']; 												//邮件发送者email地址
		$this->mail->FromName = $this->info['per']['FromName'];									//发送者名字
		$this->mail->AddAddress($this->info['per']['address'],$this->info['per']['address']);	//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")		
		$this->mail->Subject = "=?utf-8?B?".base64_encode($this->info['per']['Subject'])."?="; 	//邮件标题
		$this->mail->Body = $this->info['per']['Body']; 										//邮件内容
		if(!$this->mail->Send()){
			$this->error_code=-2;
			$e = $this->errormessage() . $this->mail->ErrorInfo;
			$this->sendfailed($e,$this->info['per']['address']);
			return false;
		}else{
			$this->error_code=0;
			$this->mail->to = array();
			$this->mail->all_recipients = array();			
			return true;
		}								
	}
	
	/**
	 * 显示错误信息
	 * Enter description here ...
	 */
	public function errormessage() {
		$message=array(
			'mail_error_-1' => '发送失败！邮箱配置信息不完整。',
			'mail_error_0' => '邮件发送成功。',
			'error_code_-2' => '发送失败！错误原因: '
		);
		return $message['mail_error_'.$this->error_code];
	}

	/**
	 * 发送失败处理
	 * Enter description here ...
	 * @param unknown_type $errormessage
	 * @param unknown_type $emailto
	 */
	public function sendfailed($errormessage,$emailto){
		$mail_set = 'backup';
		$mail_backup = C('mail_'.$mail_set);		
		
		$this->mail->Host = $mail_backup['Host']; 																	//邮箱域名
		$this->mail->Username = $mail_backup['Username'];  															//邮箱用户名(完整的email地址)
		$this->mail->IsSMTP(); 																						//使用SMTP方式发送
		$this->mail->SMTPAuth = true; 																				//启用SMTP验证功能
		$this->mail->IsHTML(true);																					//是否使用HTML格式
		$this->mail->Password = $mail_backup['Password']; 															//邮箱密码
		$this->mail->Port= $mail_backup['Port'];																	//邮箱端口
		$this->mail->From = $mail_backup['From']; 																	//邮件发送者email地址
		$this->mail->FromName = $this->info['per']['FromName'];														//发送者名字
		$this->mail->AddAddress($mail_backup['From'],$mail_backup['From']);											//收件人地址	
		$this->mail->Subject = "=?utf-8?B?".base64_encode($errormessage.'!'.$this->info['per']['Subject'])."?="; 	//邮件标题
		$this->mail->Body = $errormessage.',['.$emailto.'],以下内容发送失败<br>'.$this->info['per']['Body']; 		//邮件内容
		
		$this->mail->Send();
		return '发送失败';
	}


} 