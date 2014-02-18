<?php
/**
 * global.func.php 全局核心函数
 * @copyright (C) 2010-2012 YWCMS
 * @license http://www.b2bvip.com/
 * @lastmodify 2012-8-15
 * @author bin
 */
defined('IN_ONE') or exit('Access Denied');

	/**
	 * 
	 * 获取模型
	 * 用数组形式标记对象是否应经被事例化(是否已是对象)
	 * @param $modelname
	 * @param $active_group 让你选择哪个连接组,默认'default'
	 */
	function M($model_name = '',$active_group = 'default') {
	    static $M = array();
	    $model_name = parse_name(trim($model_name) == '' ? $model_name = 0 : trim($model_name), 1);    
	    if (!is_object($M[$model_name.$db])) {
	    	//是否存在自定义Model类并自动载入
	        $model_class = $model_name . 'Model'; //表名格式化
	        my_autoload($model_class);
			//不存在自定义Model类或自动载入失败,实例化Model模型类
	        if (!class_exists($model_class)) $model_class = 'Model';
	        if ($model_class == 'Model') {     	
	            //if (!is_object($M[0])) $M[0] = new $model_class(C($db));
	            $M[$model_name.$db] = new $model_class(C('database_'.$active_group));
	        } else {
	            $M[$model_name.$db] = new $model_class;
	        }
	    }
	    if ($model_name && !class_exists($model_name . 'Model')) { //设置模型表名
	        $M[$model_name.$db]->setModelTable($model_name);
	    }
	    return $M[$model_name.$db];
	}

    /**
     * 
     *  读取配置文件
     * @param $filename 配置文件名
     * @param $key 配置键名
     * @param $reload 强制reload
     */
    function C($key, $reload = false) {
        static $_config = array();
        if ($key && is_string($key)) {
            $filename = explode('_', $key);
            $filename = $filename[0];
            $key = substr($key, strlen($filename) + 1);
            if (!isset($_config[$filename]) || $reload) {
                if (is_file(CONF_PATH . $filename . '.php')) {
                    $_config[$filename] = include CONF_PATH . $filename . '.php';
                } else {
                   // switch ($filename) {
                   //     case 'command':
                   //         $table = 'config';
                   //         break;
                   //     default:
                   //         return false;
                   // }
                   // $data = M($table)->select();
                   // $key = 'keyname';
                   // $value = 'value';
                   // $type = '1';
                   // if (!datasave($filename, $data, $key, $value, $type)) {
                   //     $_config[$filename] = false;
                   //     return false;
                   // }
    				return false;
                }
            }
            if ($key && is_string($key)) {
                if (isset($_config[$filename][$key])) {
                    return $_config[$filename][$key];
                } else {
                    return false;
                }
            } else {
                return $_config[$filename];
            }
        } else {
            return false;
        }
    }

	/**
	 * 读取语言包翻译
	 * Enter description here ...
	 * @param unknown_type $key			类型及语言项,error_operationFail
	 * @param unknown_type $language	语言,zh-cn,en-us
	 * @param unknown_type $reload		强制reload
	 */
    function L($key, $language = 'zh-cn', $reload = false) {
        static $_config = array();
        if ($key && is_string($key)) {
            $file = explode('_', $key);
            $file = $file[0];
            $key = substr($key, strlen($file) + 1);     //语言项
            $filename = $file.'_'.$language;            //文件名
            if (!isset($_config[$filename]) || $reload) {
                if (is_file(LANG_PATH . $filename . '.php')) {
                    $_config[$filename] = include LANG_PATH . $filename . '.php';
                } else {
                    return false;
                }
            }
            if ($key && is_string($key)) {
                if (isset($_config[$filename][$key])) {
                    return $_config[$filename][$key];
                } else {
                    return false;
                }
            } else {
                return $_config[$filename];
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * 获取缓存类
     */
    function get_dc() {
        return get_instance_of('Dc', 'factory', array(C('dc')));
    }

    /**
     * 
     * 优化的require_once
     * @param $filename 要包含的文件路径
     */
    function require_cache($filename) {
        static $_requireFiles = array();
        if (!isset($_requireFiles[$filename])) {
            if (file_exists_case($filename)) {
                require $filename;
                $_requireFiles[$filename] = true;
            } else {
                $_requireFiles[$filename] = false;
            }
        }
        return $_requireFiles[$filename];
    }

    /**
     * 
     * 针对windows区分大小写的文件存在判断
     * @param $filename 文件路径
     */
    function file_exists_case($filename) {
        if (is_file($filename)) {
            if (strstr(PHP_OS, 'WIN') && basename(realpath($filename)) != basename($filename)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * GPCF全局变量转义过滤
     */
    function filter_gpcf() {
        $allowed = array(
            'GLOBALS' => 1,
            '_GET' => 1,
            '_POST' => 1,
            '_COOKIE' => 1,
            '_FILES' => 1,
            '_SERVER' => 1);
        foreach ($GLOBALS as $key => $value) {
            if (!isset($allowed[$key])) {
                $GLOBALS[$key] = null;
                unset($GLOBALS[$key]);
            }
        }

        if (!function_exists('get_magic_quotes_gpc') || !get_magic_quotes_gpc()) {
            slashes($_POST);
            slashes($_GET);
            slashes($_COOKIE);
        }
        slashes($_FILES);
    }

    /**
     * 
     * 变量转义
     * @param $array
     */
    function slashes(&$array) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    slashes($array[$key]);
                } else {
                    $array[$key] = addslashes($value);
                }
            }
        }
    }

    /**
     * 
     * 自动载入类文件
     * @param $class 类名
     */
    function my_autoload($class) {
        if (strlen($class) > 5 && substr($class, -5) == 'Model' && require_cache(MODEL_PATH . $class . '.class.php')) { // 加载模型
            return;
        }
        if (strlen($class) > 6 && substr($class, -6) == 'Action' && defined('APP_NAME') && require_cache(APP_PATH . APP_NAME . '/' . $class . '.class.php')) { // 加载控制器
            return;
        }

        if (load_driver($class) && class_exists($class)) {
            return;
        }

        //核心类库
        if (require_cache(CLASS_PATH . $class . '.class.php') && class_exists($class)) {
            return;
        }
        //扩展类库
        if (require_cache(CLASS_EXTENSION_PATH . $class . '.class.php') && class_exists($class)) {
            return;
        }
    }

    // 错误输出
    function halt($error) {
        //echo $error;
        //exit;   
        $e = array();
        if (APP_DEBUG) {
    	    //调试模式下输出错误信息
    	    if (!is_array($error)) {
    		    $trace = debug_backtrace();
    		    $e['message'] = $error;
    		    $e['file'] = $trace[0]['file'];
    		    $e['class'] = $trace[0]['class'];
    		    $e['function'] = $trace[0]['function'];
    		    $e['line'] = $trace[0]['line'];
    		    $traceInfo = '';
    		    $time = date('y-m-d H:i:m');
    		    foreach ($trace as $t) {
    			    $traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
    			    $traceInfo .= $t['class'] . $t['type'] . $t['function'] . '(';
    			    $traceInfo .= implode(', ', $t['args']);
    			    $traceInfo .=')<br/>';
    		    }
    		    $e['trace'] = $traceInfo;
                // 包含异常页面模板
                include TPL_PATH.'error/error_php.html';            
    	    } else {
    	    	$e = $error;
                // 包含异常页面模板
                include TPL_PATH.'error/error.php';
    	    }	    
        } else {
    	    //否则定向到错误页面
    	    $error_page = C('ERROR_PAGE');
    	    if (!empty($error_page)) {
    	    	redirect($error_page);
    	    } else {
    		    if (C('SHOW_ERROR_MSG')){
    		    	$e['message'] = is_array($error) ? $error['message'] : $error;
    		    } else {
    			    $e['message'] = C('ERROR_MESSAGE');
    		    }
        	    // 包含异常页面模板
        	    include TPL_PATH.'error/error.php';
    	    }
        }
        
        exit;
    }

    /**
     * 
     * 载入所需驱动文件
     * @param $drivername 驱动名
     */
    function load_driver($drivername) {
        $dir = explode('_', parse_name($drivername));
        $dir = reset($dir);
        return require_cache(DRIVER_PATH . $dir . '/' . parse_name($drivername, 1) . '.class.php');
    }

    /**
     * 
     * 字符串命名风格转换
     * @param $name 字符串
     * @param $type 转换类型 $type=0 将Java风格转换为C的风格 $type=1 将C风格转换为Java的风格
     */
    function parse_name($name, $type = 0) {
        if ($type) {
            return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    /**
     * 
     * 取得对象实例 支持调用类的静态方法
     * @param $name 类名
     * @param $method 方法
     * @param $args 参数(为数组)
     */
    function get_instance_of($name, $method = '', $args = array()) {
        static $_instance = array();
        $identify = empty($args) ? $name . $method : $name . $method . to_guid_string($args);
        if (!isset($_instance[$identify])) {
            if (class_exists($name))
                my_autoload($name);
            if (class_exists($name)) {
                $o = new $name();
                if (method_exists($o, $method)) {
                    if (!empty($args)) {
                        $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                    } else {
                        $_instance[$identify] = $o->$method();
                    }
                } else {
                    $_instance[$identify] = $o;
                }
            } else {
                //halt(L('_CLASS_NOT_EXIST_') . ':' . $name);
                halt(array('header'=>'CLASS NOT EXIST','message'=>'class '.$name));
            }
        }
        return $_instance[$identify];
    }

    /**
     * 
     * 根据PHP各种类型变量生成唯一标识号
     * @param $mix 
     */
    function to_guid_string($mix) {
        if (is_object($mix) && function_exists('spl_object_hash')) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return md5($mix);
    }

    /**
     * 页面执行所需内存
     *
     * get_page_memory
     * @return
     */
    function get_page_memory() {
        return max_real_size(memory_get_usage());
    }

    /**
     * 容量单位转换为最小单位(B) 
     *
     * @param $size 转换容量
     * @return float
     */
    function min_real_size($size = 0) {
        if (!$size) {
            return 0;
        }
        $scan['gb'] = 1073741824; //1024 * 1024 * 1024;
        $scan['g'] = 1073741824; //1024 * 1024 * 1024;
        $scan['mb'] = 1048576;
        $scan['m'] = 1048576;
        $scan['kb'] = 1024;
        $scan['k'] = 1024;
        $scan['b'] = 1;
        foreach ($scan as $unit => $factor) {
            if (strlen($size) > strlen($unit) && strtolower(substr($size, strlen($size) - strlen($unit))) == $unit) {
                return substr($size, 0, strlen($size) - strlen($unit)) * $factor;
            }
        }
        return $size;
    }


    /**
     * 容量单位转换为可表示最大单位 
     *
     * @param $size 单位(B)
     * @return float
     */
    function max_real_size($size) {
        $kb = 1024; // Kilobyte
        $mb = 1024 * $kb; // Megabyte
        $gb = 1024 * $mb; // Gigabyte
        $tb = 1024 * $gb; // Terabyte
        if ($size < $kb) {
            return $size . " B";
        } else
            if ($size < $mb) {
                return round($size / $kb, 2) . " KB";
            } else
                if ($size < $gb) {
                    return round($size / $mb, 2) . " MB";
                } else
                    if ($size < $tb) {
                        return round($size / $gb, 2) . " GB";
                    } else {
                        return round($size / $tb, 2) . " TB";
                    }
    }

    /**
     * 
     * 随机字符
     * @param $length 随机长度
     * @param $numeric 是否为数字
     */
    function random($length, $numeric = 0) {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * 去除空字符 
     *
     * @param $string 去除的空字符串
     * @param $js 是否JS
     * @return string
     */
    function strip_nr($string, $js = false) {
        $string = str_replace(array(
            chr(13),
            chr(10),
            "\n",
            "\r",
            "\t",
            '	'), array(
            '',
            '',
            '',
            '',
            '',
            ''), $string);
        if ($js)
            $string = str_replace("'", "\'", $string);
        return $string;
    }

    /**
     * 
     * 获取客户端IP
     * @param $xforwardip 是否检查代理的ip
     */
    function get_client_ip($xforwardip = null) {
        if (empty($xforwardip)) {
            if ($_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['REMOTE_ADDR']) {
                if (strstr($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                    $x = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    $_SERVER['HTTP_X_FORWARDED_FOR'] = trim(end($x));
                }
                if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            } elseif ($_SERVER['HTTP_CLIENT_IP'] && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'Unknown';
    }

    /**
     * 
     * 获取密钥
     */
    function get_key() {
        return md5($_SERVER['HTTP_USER_AGENT'] . get_client_ip() . C('system_auth_key'));
    }
    /**
     * 
     * 设置响应头
     * @param $code
     */
    function send_http_status($code) {
        static $_status = array(
            // Success 2xx
            200 => 'OK',
            // Redirection 3xx
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            // Client Error 4xx
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            // Server Error 5xx
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            );
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:' . $code . ' ' . $_status[$code]);
        }
    }

    /**
     * 
     * 301跳转
     * @param $url
     */
    function header_301($url) {
        send_http_status('301');
        exit(header('location:' . $url));
    }

    /**
     * 指定key获取$_GET/$_POST变量
     * @param $key
     * @param $method
     */
    function get_gp($key, $method = null) {
        $method = $method ? strtolower($method) : null;
        if ($method == 'g') {
            return $_GET[$key];
        } elseif ($method == 'p') {
            return $_POST[$key];
        } else {
            return isset($_POST[$key]) && $_POST[$key] ? $_POST[$key] : $_GET[$key];
        }
    }


    /**
     * 
     * 提示信息
     * @param $message 提示内容
     * @param $url_forward 跳转网址
     * @param $ttl 跳转时间
     */
    function showmessage($message, $url_forward = 'goback', $ttl = 1.5, $template = 'showmessage') {
        $url_forward = empty($url_forward) ? 'goback' : $url_forward;
        $ttl = intval($ttl) > 0 ? $ttl : 0;
        if(is_array($message)){
            foreach ($message as $k => $v) {
                $message[$k] = addslashes($v);
            }
        }else{
            $addslmsg = addslashes($message);    
        }
        $addslurl_forward = addslashes($url_forward);
        include Template::Temp('showmessage/'.$template);
        exit;
    }

    /**
     * 分页
     * @param $total 总条数
     * @param  $page 第几页
     * @param  $time 每页条数
     * @param $isajax 是否无刷新分页
     * @return string 最多页数
     * 
     */
    function listpages($total, $page = 1, $perpage = 20,$url='',$maxpage=null) {
    	$totalcount = $total;
    	if(empty($url) || !is_string($url) ){
    		$url= array();
    		foreach ($_GET as $k => $v){
    			if($k!='page'){
    				$url[]= urlencode($k).'='.urlencode($v);
    			}
    		}
    		$url[] = 'page={page}';
    		$url = '?'.implode('&', $url);
    	}
    	if($total <= $perpage) return '';
    	$total = ceil($total/$perpage);
    	$pagecount = $total;
    	$total = ($maxpage && $total>$maxpage)? $maxpage : $total;
    	$page = intval($page);
    	if($page < 1 || $page > $total) $page = 1;

    	$pages = '<div class="pages">';
        if($page-1 > 0){
            $pages .= '<a href="'.str_replace('{page}',$page-1,$url).'" title="上一页">上一页</a>';       
        }

    	if($page>4 && $page<=$total-4){
    		$mini = $page-3;
    		$maxi = $page+2;
    	}elseif($page<=4){
    		$mini = 2;
    		$maxi = $total-2<7?$total-2:7;	
    	}elseif($page>$total-4){
    		$mini = $total-7<3?2:$total-7;
    		$maxi = $total-2;
    	}

    	for ($i=1;$i<=$total;$i++){
    		if($i!=$page){
    			$pages .= '<a href="'.str_replace('{page}',$i,$url).'">&nbsp;'.$i.'&nbsp;</a>';		
    		}else{
    			$pages .= '<strong>&nbsp;'.$i.'&nbsp;</strong>';
    		}	
    		if( $maxi && $i>=$maxi){
    			$i = $total-2;
    			$maxi = 0;			
    		}
    		if(($i==2 or $total-2==$i) && $total>10){
    			$pages.='&nbsp;…&nbsp;';
    		}
    		if($mini && $i>=2){
    			$i = $mini;	
    			$mini = 0;
    		}
    	}

        if ($page+1 < $total) {
            $pages.= '<a href="'.str_replace('{page}',$page+1,$url).'" title="下一页">下一页</a><span>共'.$totalcount.'条 <input type="text" id="pageno" value="'.$page.'" onkeydown="if(event.keyCode==13 &amp;&amp; this.value) {window.location.href=\''.$url.'\'.replace(/\{page\}/, this.value);return false;}">/'.$total.'页 <input type="button" class="pages_btn" value="GO" onclick="if(document.getElementById(\'pageno\').value>0)window.location.href=\''.$url.'\'.replace(/\{page\}/, document.getElementById(\'pageno\').value);"></span></div>';
        }
    	
    	return $pages;
    }


/**
 * 
 * 编辑器
 * @param $editorid 编辑器ID
 * @param $editorname 编辑器name
 * @param $text 已有内容
 * @param $mode 模式 0:简洁模式;1:普通模式;2:高级模式
 * @param $w 宽度
 * @param $h 高度
 * @param $language 编辑器语包调用
 */
function showeditor($editorid = '', $editorname = '', $text = '', $mode = 0, $w = '100%', $h = '500px', $language = 'zh_CN') { 
    $editorid = $editorid ? $editorid : 'content';
    $editorname = $editorname ? $editorname : $editorid;
    $editor = '';
    $w = $w ? $w : '100%';
    $h = $h ? $h : '250px';
    $language = $language ? $language : 'zh_CN';
    switch ($mode) {
        default: //默认模式
            $editor = "
                <script type=\"text/javascript\">
                $(function(){
                       KindEditor.ready(function(K) {
                          window.kind = K.create('#{$editorid}', {
                            allowPreviewEmoticons : false,
                            allowImageUpload : true,
                            uploadJson : '?m=upload&c=editor&a=upload_handle',
                            urlType : 'domain',
                            langType : '{$language}',
                        });
                    });
                });
                </script>
            ";
            break;   //uploadJson : '?m=upload&c=upload&a=upload_handle',
    }
    $editor .= "<textarea id=\"{$editorid}\" name=\"{$editorname}\" style=\"width:{$w};height:{$h};\">{$text}</textarea>";
    return $editor;
}

    /******************录入字符处理**********************/
    /**
     * 字符转换
     * @param $string
     * @return string
     */
    function escapeStr($string) {
        $string = str_replace(array(
            "\0",
            "%00",
            "\r"), '', $string); //modified@2010-7-5
        $string = preg_replace(array('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '/&(?!(#[0-9]+|[a-z]+);)/is'), array('', '&amp;'), $string);
        $string = str_replace(array("%3C", '<'), '&lt;', $string);
        $string = str_replace(array("%3E", '>'), '&gt;', $string);
        $string = str_replace(array(
            '"',
            "'",
            "\t",
            '  '), array(
            '&quot;',
            '&#39;',
            '    ',
            '&nbsp;&nbsp;'), $string);
        return $string;
    }


    function filter_html_illegal($var) {
        return str_replace(array(
            '<iframe',
            '</iframe',
            '<meta',
            '<script',
            '</script'), array(
            '&lt;iframe',
            '&lt;/iframe',
            '&lt;meta',
            '&lt;script',
            '&lt;/script'), $var);
    }

    /**
     * 字符过滤(编辑器)
     * @param $string	目标字符串
     * @param $tagname	需要过滤的标签('script|IFRAME')
     * @param $clear    是否清除中间内容
     */
    function replace_html_tag($string, $tagname, $clear = false) {
        $re = $clear ? '' : '\1';
        $tagname = split('[|]', $tagname);
        foreach ($tagname as $value) {
            $sc[] = '/<' . $value . '(?:\s[^>]*)?>([\s\S]*?)?<\/' . $value . '>/i';
        }
        return preg_replace($sc, $re, $string);
    }

    /**
     * 清除非本站链接(编辑器)
     * @param $string	目标字符串
     * @param $host    指定域名
     */
    function linkclear($string, $host = null) {
        $string = stripslashes($string);
        $basehost = empty($host) ? $_SERVER['HTTP_HOST'] : $host;
        preg_match_all('/<a[\s\S]*?href=["|\']?([^>"\' ]+)["|\']?\s*[^>]*>(.+?)<\/a>/si', $string, $att);
        if (count($att[1]) > 0) {
            foreach ($att[1] as $key => $value) {
                $leftstr = substr($value, 0, 1);
                $leftlent = stripos('#' . $value, $basehost);
                if ($leftlent < 1 && $leftstr != '/' && $leftstr != '.') {
                    $string = str_replace($att[0][$key], $att[2][$key], $string);
                }
            }
        }
        return addslashes($string);
    }


    /**
     * 
     * 是否为邮箱
     * @param $email 邮箱
     */
    function is_email($email) {
        return preg_match("/^[a-zA-Z0-9_]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/", trim($email));
    }

    /**
     * 
     * 用户名格式
     * @param $username 用户名
     * 字母开头,6-30个字符
     */
    function is_username($username) {
        return preg_match("/^[a-zA-Z][a-zA-Z0-9]{5,30}$/", $username);
    }

    /**
     * 
     * 昵称格式
     * @param $nickname 昵称
     * 小于30个字符
     */
    function is_nickname($nickname) {
        if (strlen($nickname) > 30) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 
     * 禁止用户名
     * @param $name 用户名
     */
    function is_name($name) {
        $restrict = explode('|', C('command_notallowname'));
        foreach ($restrict as $v) {
            if (stristr($name, $v)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 
     * 密码格式
     * @param $password 密码
     * 大于6 小于18
     */
    function is_password($password) {
        return strlen($password) >= 6 && strlen($password) <= 18;
    }

    /**
     * 
     * 是否为手机号码
     * @param $mobile 手机号码
     */
    function is_mobile($mobile) {
        return preg_match("/^1[3-9][0-9]{9}$/", trim($mobile));
    }
    /**
     * 
     * 是否为固话号码
     * @param $telephone 固话号码
     */
    function is_telephone($telephone) {
        return preg_match("/^(0[1-9][0-9]{1,2}-[2-9][0-9]{4,})|([4|8]00[0-9]{7})$/", trim($telephone));
    }

    /*
    * 是否为中国邮编
    * 
    */
    function is_postcode($postcode) {
        return preg_match("/^[1-9][0-9]{5}$/", trim($postcode));
    }

    /*
    * 网址是否正确
    * 
    */
    function is_url($url) {
        return preg_match("/^(https?:\/\/)+([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/", trim($url));
    }

    /**
     * is_price()
     * 
     * 验证价格格式是否符合规定为：0.00
     * 
     * @param mixed $price
     * @return
     */
    function is_price($price){
        $price=strval($price);
        $preg="/^(?!0{2,}|0[1-9]+)\d+\.\d{1,2}$/";
        return preg_match($preg,$price);
    }

    /**
     * 
     * JS转换
     * @param $js
     */
    function jstransform($jstxt) {
        $jstxt = str_replace(array(
            "\n",
            "\r",
            "\t",
            "'"), array(
            '\n',
            '\r',
            '\t',
            '\\\''), $jstxt);
        return $jstxt;
    }

    /**
     * 
     * JS2转换
     * @param $js2
     */
    function jstransform2($jstxt) {
        $jstxt = str_replace(array(
            "\n",
            "\r",
            "\t"), array(
            '',
            '',
            ''), $jstxt);
        return $jstxt;
    }

    /**
     * 
     * 截断字符串
     * @param $content 内容
     * @param $length 截取字节数
     * @param $add 是否带省略号，Y|N
     */
    function substrs($content, $length, $add = 'Y') {
        if (strlen($content) > $length) {
            if (CHARSET != 'utf-8') {
                $cutStr = '';
                for ($i = 0; $i < $length - 1; $i++) {
                    $cutStr .= ord($content[$i]) > 127 ? $content[$i] . $content[++$i] : $content[$i];
                }
                $i < $length && ord($content[$i]) <= 127 && $cutStr .= $content[$i];
                return $cutStr . ($add == 'Y' ? ' ..' : '');
            }
            return utf8_trim(substr($content, 0, $length)) . ($add == 'Y' ? ' ..' : '');
        }
        return $content;
    }


    /**
     * 
     * utf8字符串整齐化
     * @param $str
     * @return string
     */
    function utf8_trim($str) {
        $hex = '';
        $len = strlen($str) - 1;
        for ($i = $len; $i >= 0; $i -= 1) {
            $ch = ord($str[$i]);
            $hex .= " $ch";
            if (($ch & 128) == 0 || ($ch & 192) == 192) {
                return substr($str, 0, $i);
            }
        }
        return $str . $hex;
    }

    /**
     * 从请求中获取cookie值
     *
     * @param string $cookieName cookie名
     * @param bool $needPrefix 是否加前缀
     * @return string
     */
    function Get_Cookie($cookieName, $needPrefix = true) {
        $cookieName = $needPrefix ? C('system_cookie_pre') . $cookieName : $cookieName;
        return $_COOKIE[$cookieName];
    }

    /**
     * 
     * 设置cookie
     * @param $cookieName cookie名
     * @param $cookieValue cookie值
     * @param $expireTime cookie过期时间，为Y M W D H表示1年 1月 1周 1日 1小时 后过期
     * @param $needPrefix cookie名是否加前缀
     * @return bool 是否设置成功
     */
    function Set_Cookie($cookieName, $cookieValue, $expireTime = 0, $needPrefix = true) {

        static $sIsSecure = null;
        if ($sIsSecure === null) {
            if (!$_SERVER['REQUEST_URI'] || ($parsed = @parse_url($_SERVER['REQUEST_URI'])) === false) {
                $parsed = array();
            }
            if ($parsed['scheme'] == 'https' || (empty($parsed['scheme']) && ($_SERVER['HTTP_SCHEME'] == 'https' || $_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) != 'off'))) {
                $sIsSecure = true;
            } else {
                $sIsSecure = false;
            }
        }
        $cookiePath = C('system_cookie_path') == '' ? '/' : C('system_cookie_path');
        $cookieDomain = C('system_cookie_domain');
        strlen($cookieValue) > 512 && $cookieValue = substr($cookieValue, 0, 512);
        $needPrefix && $cookieName = C('system_cookie_pre') ? C('system_cookie_pre') . $cookieName : $cookieName;
        if ($expireTime === 'Y') {
            $expireTime = SYS_TIME + 31536000;
        } elseif ($expireTime === 'M') {
            $expireTime = SYS_TIME + 2592000;
        } elseif ($expireTime === 'W') {
            $expireTime = SYS_TIME + 604800;
        } elseif ($expireTime === 'D') {
            $expireTime = SYS_TIME + 86400;
        } elseif ($expireTime === 'H') {
            $expireTime = SYS_TIME + 3600;
        } elseif ($cookieValue == '' && $expireTime == 0) {
            return setcookie($cookieName, '', SYS_TIME - 31536000, $cookiePath, $cookieDomain, $sIsSecure);
        } else {
            $expireTime = $expireTime > 0 ? SYS_TIME + $expireTime : (empty($cookieValue) ? SYS_TIME - 3600 : 0);
        }
        return setcookie($cookieName, $cookieValue, $expireTime, $cookiePath, $cookieDomain, $sIsSecure);
    }

    /**
     * crc32 64位-32位 转换
     *
     */
    function _get_hash_table($station) {
        $checksum = crc32($station);
        if (8 == PHP_INT_SIZE) { //64位机,进行移位从处理成和32机一样
            if ($checksum > 2147483647) {
                $checksum = $checksum & (2147483647); //对64位机的先进截取后32位
                $checksum = ~ ($checksum - 1); //取补码
                $checksum = $checksum & 2147483647; //由于补码操作的修改，但是这时的checksum是正值而不是负值
            }
        }
        return $checksum;
    }

    /**
     * 判断数据表是否需要添加
     * @param $table 表名
     * @param $radix 基数值
     * @param $id 目标值
     *
     */
    function check_table_tocreat($id, $table, $radix, $cityid = '') {
        $tartable = $table = trim(strtolower($table));
        $fieldvalue = $id + $radix;
        $allowtable = array('keyword','supply','supply_data','demand','demand_data','news','news_data','company_news','company_news_data','attachment','forum_post','forum_post_data','forum_reply','exhibit','exhibit_data','member_order');
        if (in_array($table, $allowtable)){
    		$tartable = $table . '_' . ceil($fieldvalue / $radix);
        }
        $newtable = M()->query("SHOW TABLES LIKE '" . C('db_dbpre') . $tartable . "'");
        if (empty($newtable)) {
            $CREATEtablesql = M()->query('show create table ' . M()->gettable($table));
            $CREATEtablesql = $CREATEtablesql[0];
            $sql = str_ireplace('CREATE TABLE `' . $CREATEtablesql['Table'] . '`', 'CREATE TABLE ' . M()->gettable($tartable), $CREATEtablesql['Create Table']);
            if (M()->query($sql)) {
                return get_savetable($table, $id);
            } else {
                return false;
            }
        } else {
            return get_savetable($table, $id);
        }
    }

	/**
	 * 
	 * 获取保存到哪个分表
	 * @param $table 表名
	 * @param $fieldvalue 用于分表的字段数据
	 * @return 返回所在表
	 */
	function get_savetable($table,$fieldvalue){
		$table = trim(strtolower($table));
		if(in_array($table, array('member','member_username','member_useremail','member_usermobile','member_contact','company','company_data','member_auth','member_credit_record'))){
			return sprintf($table.'_%03d',fmod(abs(_get_hash_table($fieldvalue)),100)+1);
		}elseif(in_array($table, array('member_count','member_setting','company_catid','company_setting','company_domain','member_credit','supply_mycat','company_news_mycat','attachment_cat','member_savetable'))){
			return sprintf($table.'_%04d',fmod(abs(_get_hash_table($fieldvalue)),1000)+1);
		}elseif(in_array($table,array('keyword','supply','supply_data','demand','demand_data','news','news_data','company_news','company_news_data','attachment','forum_post','forum_post_data','exhibit','exhibit_data'))){
			return $table.'_'.ceil($fieldvalue/500000);
		}elseif(in_array($table,array('forum_reply','','','',''))){
			return $table.'_'.ceil($fieldvalue/10000);
		}elseif(in_array($table,array('',''))){
			return $table.'_'.ceil($fieldvalue/500);
		}elseif(in_array($table,array('member_order'))){
			return $table.'_'.ceil($fieldvalue/2500);
		}elseif(in_array($table, array('','','',''))){
			return $table.'_'.ceil($fieldvalue/50000);
		}elseif(in_array($table,array('','','',''))){
			return $table.'_'.ceil($fieldvalue/1000);
		}else{
			return $table;
		}
	}
	
		
    /**
     * 
     * 用户数据所在表-记录数据操作
     * @param $id	  	   保存数据表的位置ID
     * @param $tablemark 表标识
     * @param $userid    用户id
     */
	function data_savetable($tableid,$tablemark,$id,$table,$idfield){
		$savetab = sprintf($table.'_savetable_%04d',fmod(abs(_get_hash_table($id)),1000)+1);
		$tableids = M($savetab)->where($idfield."= {$id} and tablemark = '{$tablemark}'")->getValue('tableids');
		
		$tableids = data_savetable_sametab($tableids,$tableid);			//是否重复ID
		
		if(M($savetab)->add(array($idfield=>$id,'tablemark'=>$tablemark,'tableids'=>$tableids),'',1)){
			return true;
		}else{
			return false;
		}
	}
	function data_savetable_sametab($tableids,$tableid){
		if(!$tableids){
			$tableids = $tableid;
		}else{
			$find = ',' . $tableid . ',';			
			$search = ',' . $tableids . ',';					
			if(strpos($search ,$find) === false){
				$tableids .=','.$tableid;
			}
		}
		return $tableids;
	}


	//订单号 、物流号
	function order_no($type=1){
		$mtime=explode(' ', microtime());
		if($type==1){
			return date('Ymdhis',time()).substr($mtime[0],2,5);
		}elseif($type==2){
			return time().substr($mtime[0],2,6);
		}
	}
	
	//发送短信
	function msm($mobile,$content){
		$sendresult=file_get_contents('http://n.020sms.com/MSMSEND.ewing?ECODE=qilong&USERNAME=qilong&PASSWORD=ADV_12_5@5KEL@s5s2sd&MOBILE='.$mobile.'&CONTENT='.urlencode($content));
		return $sendresult;
	}
	
	//包裹物流跟踪
	//@param $AppKey api接口key,读取配置文件
	//@param  $logisname 物流公司代码，必须根据快递100api接口
	//@param $logisnum 单号
	function kuaidi100($AppKey,$logisname,$logisnum){
		$url = 'http://api.kuaidi100.com/api?id='.$AppKey.'&com='.$logisname.'&nu='.$logisnum.'&show=2&muti=1&order=asc';
		if (function_exists('curl_init') == 1){
		  $curl = curl_init();
		  curl_setopt ($curl, CURLOPT_URL, $url);
		  curl_setopt ($curl, CURLOPT_HEADER,0);
		  curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		  curl_setopt ($curl, CURLOPT_TIMEOUT,5);
		  $get_content = curl_exec($curl);
		  curl_close ($curl);
		}else{
		  include_once(API_PATH.'kuaidi100/snoopy.php');
		  $snoopy = new snoopy();
		  $snoopy->referer = 'http://www.google.com/';//伪装来源
		  $snoopy->fetch($url);
		  $get_content = $snoopy->results;
		}
		return $get_content;
	}
	
	