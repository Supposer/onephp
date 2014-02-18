<?php
/** 
* SearchModel.class.php
* @copyright (C) 2012-2013 QILONG
* @license http://www.qilong.com/
* @lastmodify 2014-2-8
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class SearchModel extends Model{
	
	public $search;
	
	public function __construct(){
		//parent::__construct(C('database_test'));		//切换数据库
		$this->search = get_instance_of('SphinxClient');
		$this->search->SetServer (C('search_server'),(int)C('search_port'));
		$this->search->SetConnectTimeout (C('search_ttl'));
		$this->search->SetArrayResult (C('search_array'));
	}
	
	public function shopsearch($setting){
		$this->search->ResetFilters();
		$this->search->ResetGroupBy();
		$this->search->SetSelect("*");
		
		/**
		$this->setModelTable('test');			//设置表名
		$cc = $this->where('id = 1')->select();	//数据库连贯操作
		return $cc;
		**/
		
		//每页显示条数
		$perpage = $setting['perpage']!=''?$setting['perpage']:15;
		//当前页数
		$page = $setting['page']!=''?$setting['page']:1;
		//输出总数
		$sum= $setting['sum']!=''?$setting['sum']:5000;
		//默认状态 2
		$setting['status'] = $setting['status']!=''?$setting['status']:2;
		//默认搜索模式
		$setting['searchmodel'] = $setting['searchmodel']!=''?$setting['searchmodel']:2;
		
		$this->search->SetSortMode ( SPH_SORT_EXTENDED, 'type desc,@weight desc,listorder asc,hits desc' ); //默认价钱降序
		
		//搜索模式			
		if($setting['searchmodel']==1){
			$this->search->SetMatchMode(SPH_MATCH_ANY);		//模糊
		}elseif($setting['searchmodel']==2){
			$this->search->SetMatchMode(SPH_MATCH_EXTENDED2);	//扩展型
		}
		
		//设置字段权重
		$this->search->SetFieldWeights(array( "subject" => 10, "characteristic" => 5 ,"address"=> 8) );
		
		$key=implode(' ', $key);
		$key=str_replace(array('!','[',']','"','\'','*','/','\\','~','=','<','>','=','^','$',':','#','%','&','+'),'',$key);
		
		//搜索引擎设置分页
		$this->search->SetLimits($page*$perpage-$perpage, $perpage,$sum);
		$res = $this->search->query($key,'shop,shop_delta');
		$index = $res['matches'];
		return $index;
	}
	
}
