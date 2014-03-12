<?php
/** 
* Template.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-29
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Template{
	/**
	 * 前台模板调用
	 *
	 * @param $template 模板前缀 
	 * @param $dir 路径
	 * @param $suffix 模板后缀
	 * @return string
	 */
	static public function temp($template = 'index') {
		$template = $template?$template:'index';
		$template_suffix = C('system_template_suffix')?C('system_template_suffix'):'html';
		$to = TPL_CACHE_PATH.$template.'.php';
		$from = TPL_PATH.$template.'.'.$template_suffix;
		if (!is_file($from)){
			!is_file($from) && SYS_DEBUG && halt(array('header'=>'Did not find the template','message'=>$from));	
		}
		if(C('system_template_refresh') || !is_file($to) || filemtime($from) > filemtime($to)) {
			Template::templateCompile($from, $to);
		}

		return $to;
		//return $from;
	}
	
	/**
	 * 编译模板
	 *
	 * @param $from 未编译路径
	 * @param $to 编译后路径
	 * @return string
	 */
	static public function templateCompile($from, $to) {
		$content = C('system_template_parse') ? Template::templateParse(File::fileGet($from)) : "<?php defined('IN_ONE') or exit('Access Denied');?>".File::fileGet($from);
		File::filePut($to, $content);
	}
	
	/**
	 * 模板正则更替
	 *
	 * @param $str 已读取模板内容
	 * @return string
	 */
	static public function templateParse($str,$js){
		$str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);//去除没用的标签
		$str = preg_replace("/\{template\s+(.+)\}/", "<?php include Template::temp(\\1);?>", $str);//包含前台模板
		
		/*IF语句S*/
		$str = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str);
		$str = preg_replace("/\{else\}/", "<?php } else { ?>", $str);
		$str = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } else if(\\1) { ?>", $str);
		$str = preg_replace("/\{\/if\}/", "<?php } ?>", $str);
		/*IF语句E*/
		
		/*FOREACH语句S*/
		$str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>", $str);
		$str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>", $str);
		$str = preg_replace("/\{\/loop\}/", "<?php } } ?>", $str);
		/*FOREACH语句E*/
		
		/*FOR循环S*/
		$str = preg_replace("/\{for\s+(.+?)\}/", "<?php for(\\1) { ?>", $str);
		$str = preg_replace("/\{\/for\}/", "<?php } ?>", $str);
		/*FOR循环E*/
		
		$str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[[\$]{0,1}[a-zA-Z0-9_\$\[\]\x7f-\xff]*\]|\['[\$a-zA-Z0-9_\x7f-\xff]*'\])*)\}/", "<?php echo \\1;?>", $str);//输出变量
		$str = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str);//输出常量
		
		$str = preg_replace("/\{date\(([^{}]*)\)\}/", "<?php echo date(\\1);?>",$str);
		$str = preg_replace("/\{substrs\(([^{}]*)\)\}/", "<?php echo substrs(\\1);?>",$str);
		$str = preg_replace("/\{C\(([^{}]*)\)\}/", "<?php echo C(\\1);?>",$str);//C函数

		$str = preg_replace("/\{stripslashes\(([^{}]*)\)\}/", "<?php echo stripslashes(\\1);?>",$str);
		$str = preg_replace("/\{sys_date\(([^{}]*)\)\}/", "<?php echo sys_date(\\1);?>",$str);
		$str = preg_replace("/\{search_out\(([^{}]*)\)\}/", "<?php echo search_out(\\1);?>",$str);
		$str = preg_replace("/\{print_r\(([^{}]*)\)\}/", "<?php print_r(\\1);?>",$str);

		$str = preg_replace("/<\?php([^\?]+)\?>/es", "Template::templateAddquote('<?php\\1?>')", $str);
		$str = "<?php defined('IN_ONE') or exit('Access Denied');?>".$str;
		
		$str = preg_replace("/\{block\s*(\'[A-Za-z0-9_\/]+\'[\.*\$\[\'A-Za-z0-9_\'\]]*)\}/", "<?php block(\\1);?>",$str);
		
		
		/*获取数据标签S*/
		//{content:articlelist catid=$catid,ischild=1,aa='dsfsdfsdf'|$array}
		$str = preg_replace("/\{([a-zA-Z][a-zA-Z0-9_]*):([a-zA-Z][a-zA-Z0-9_]*)\s+(([a-zA-Z][a-zA-Z0-9_]*\s*\=\s*('.*'|[0-9]*|\\$[a-zA-Z_][a-zA-Z0-9_]*))|(([a-zA-Z][a-zA-Z0-9_]*\s*\=\s*('.*'|[0-9]*|\\$[a-zA-Z_][a-zA-Z0-9_]*.+?))|(([a-zA-Z][a-zA-Z0-9_]*\s*\=\s*('.*'|[0-9]*|\\$[a-zA-Z_][a-zA-Z0-9_]*)),)+([a-zA-Z][a-zA-Z0-9_]*\s*\=\s*('.*?'|[0-9]*|\\$[a-zA-Z_][a-zA-Z0-9_]*))))((\s*\|\s*(\\$[a-zA-Z_][a-zA-Z0-9_]*))?)\s*\}/es","Template::templateEval('\\1','\\2','\\3','\\16')",$str);
		/*获取数据标签E*/
		return $str;
		
	}
	
	/**
	 * 
	 * 编译变量
	 * @param $var
	 */
	static public function templateAddquote($var){
		return str_replace("\\\"", "\"", preg_replace("/(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x	7f-\xff]*)\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "\\1['\\2']", $var));
	}
	
	static public function templateEval($apps,$func,$param,$return=''){
		$return = $return?$return:'$array';
		$param = preg_replace("/=\s*'(.*?)'/es","Template::paramTxt('\\1')",$param);//文字参数
		$param = preg_replace("/=\s*(\\$[a-zA-Z_][a-zA-Z0-9_]*(\[(([\$]?[a-zA-Z_])[a-zA-Z0-9_\x7f-\xff]*|'[a-zA-Z0-9_\x7f-\xff]*')\])*)/es","Template::paramVariable('\\1')",$param);//变量参数
		$param = str_replace(',', '&', $param);
		$return = "<?php\r\n ".$return."=Template::appTag('".addslashes($apps)."','".addslashes($func)."','".$param."');\r\n?>";
		return $return;
	}
	
	static public function paramTxt($param){
		return '='.urlencode($param);
	}
	
	static public function paramVariable($param){
		return '='."'.{$param}.'";
	}
	static public function appTag($apps,$func,$param){
		app::loadAppFunc('template',$apps);
		$func = $apps.'_'.$func;

		parse_str($param,$para);
		if(is_array($para)){
			foreach($para as $k=>$v){
				$para[$k] = addslashes($v);
			}
		}else{
			$para =array();
		}
		return $func($para);
	}

	
}