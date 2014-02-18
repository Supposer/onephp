<?php
/** 
* Dc.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-9-22
* @author bin
*/
defined('IN_ONE') or exit('Access Denied');
class Dc{
    protected $_dcconf;//配置
    public function __construct($config = array()){
        $this->_dcconf = array(
            'mode' => trim($config['mode']),//选择驱动
            'pre' => $config['pre'],//缓存前缀
            'ttl' => intval($config['ttl'])>0?intval($config['ttl']):0, //缓存时间(秒)
        );
    }
    final public function factory($config = array()){
        if(empty($config['mode'])||!is_string($config['mode']))$config['mode'] = 'file';
        $class_name = parse_name('dc_'.strtolower($config['mode']),1);
        load_driver($class_name);
        if(class_exists($class_name)){
            $dc = new $class_name($config);
            if($dc->is_support()){
                return $dc;
            }
        }
        load_driver('DcFile');
        $dc = new DcFile($config);
        return $dc;
    }
    public function get($key){}                     //Memcache key控制在250
    public function set($key, $value, $ttl = 0){}
    public function add($key, $value, $ttl = 0){}   //Memcache::add方法的作用和Memcache::set方法类似，区别是如果 Memcache::add方法的返回值为false，表示这个key已经存在，而Memcache::set方法则会直接覆写。
    public function increment($key, $value){}
    public function rm(){}
    public function clear(){}
    public function is_support(){}
}