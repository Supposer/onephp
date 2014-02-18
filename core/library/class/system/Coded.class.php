<?php
/** 
* Coded.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-8-24
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Coded{
/**
 * 
 * 动态加解密码
 * @param 加解密字符串 $string
 * @param 加解密选项ENCODE:加密 ,DECODE:解密 $operation 
 * @param 密钥 $key
 * @param 生存有效期 $expiry
 */
static  function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key ? $key : UC_KEY);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

/**
 * base64Encode没特殊字符编码
 * @param $str 要编码字符串
 * @return string
*/
static function base64Encode($str) {
	$strencode = base64_encode($str);
	$result = '';
	for ($i = 0; $i < strlen($strencode); $i++) {
		$char=substr($strencode, $i, 1);
		$asciivalue = ord($char);  
		//判断是否为数字  
		if ($asciivalue >= 48 && $asciivalue <= 57) {  
			$strencodetemp = '99' . $char;  
		}elseif ($asciivalue >= 97 && $asciivalue <= 122) {//判断是否为小写字母
			$strencodetemp = '88' . $char; 
		}else{//判断ascii值是否为三位数，若是则直接返回，若不是则补全三位
			switch (strlen($asciivalue)) {  
				case 1:  
					$strencodetemp = '77' . strval($asciivalue);  
					break;  
				case 2:  
					$strencodetemp = '6' . strval($asciivalue);  
					break;
				case 3:  
					$strencodetemp = strval($asciivalue);  
					break; 
				default:
					break;
			}
		}
		 $result.= $strencodetemp;
	}
    return $result;  
}

/**
 * base64Decode没特殊字符解码
 * @param $str 要解码字符串
 * @return string
*/
static function base64Decode($str) {
	$result='';
	for ($i = 0; $i < strlen($str); $i+=3) {
		$strtemp = substr($str, $i, 3);
		$judge = substr($strtemp, 0, 2); 
		$strdecodetemp = '';
		//判断字符串类型  
		switch ($judge) {  
			case '99':  
			case '88':  
				$strdecodetemp = substr($strtemp, 2, 1);  
				break;  
			case '77':  
				$strdecodetemp = chr(intval(substr($strtemp, 2, 1)));  
				break;  
			default:
				if (substr($judge, 0, 1) == '6') {  
					$strdecodetemp = chr(intval(substr($strtemp, 1, 2)));  
				} else {
					$strdecodetemp = chr(intval(substr($strtemp, 2, 1)));  
				}  
				break;
		}
		$result.=$strdecodetemp;
	}
	$result = base64_decode($result);
	return $result;
}
	
	
}