<?php
/** 
* SecurityCode.class.php
* @copyright (C) 2010-2012 YWCMS
* @license http://www.b2bvip.com/
* @lastmodify 2012-10-9
* @author liang
*/
defined('IN_ONE') or exit('Access Denied');
class SecurityCode{	
private $height; //图片高度
private $width; //图片宽度
private $textNum; //字符个数
private $textContent; //字符内容
private $fontColor; //@字符颜色
private $randFontColor; //随机出的文字颜色
private $fontSize; //字符大小
private $fontFamily; //字体
private $bgColor; //@背景颜色
private $randBgColor; //随机出的背景颜色
private $textLang; //字符语言
private $noisePoint; //@是否干扰点
private $noiseLine; //@是否干扰线
private $distortion; //是否扭曲
private $distortionImage; //扭曲图片源
private $showBorder; //是否有边框
private $image; //验证码图片源
		
		public function imageCaptcha(){ // 构造函数
				//设置一些默认值
				$this->textNum = 4;
				$this->fontSize = 15;
				$this->fontFamily = 'FetteSteinschrif.ttf';//设置字体，可以改成linux的目录
				$this->textLang = 'en';
				$this->noisePoint = false;
				$this->noiseLine = false;
				$this->distortion = false;
				$this->showBorder = false;
				
		}

		public function set_show_mode($w,$h,$num,$fc,$fz,$ff_url,$lang,$bc,$m,$n,$b,$border){
				$this->width=$w; //图片宽度
				$this->height=$h; //图片高度
				$this->textNum=$num; //字符个数
				$this->fontColor=sscanf($fc,'#%2x%2x%2x'); //字符颜色
				$this->fontSize=$fz; //字号
				$this->fontFamily=$ff_url; //字体url
				$this->textLang=$lang; //字符语言
				$this->bgColor=sscanf($bc,'#%2x%2x%2x'); //图片背景
				$this->noisePoint=$m; //@是否干扰点
				$this->noiseLine=$n; //@是否干扰线
				$this->distortion=$b; //是否扭曲字符
				$this->showBorder=$border; //是否显示边框
		}
		
		public function initImage(){    //初始化图片
				if(empty($this->width)){
					$this->width=floor($this->fontSize*1.3)*$this->textNum+10;
				}
				if(empty($this->height)){
					$this->height=floor($this->fontSize*2.5);
				}
				$this->image=imagecreatetruecolor($this->width,$this->height);
				if(empty($this->bgColor)){
				$this->randBgColor=imagecolorallocate($this->image,mt_rand(100,255),mt_rand(100,255),mt_rand(100,255));
					}else{
				$this->randBgColor=imagecolorallocate($this->image,$this->bgColor[0],$this->bgColor[1],$this->bgColor[2]);
					}
				imagefill($this->image,0,0,$this->randBgColor);
		}
		
		public function randText($type){    //@产生随机字符
				$string='';
				switch($type){
					case 'en':
						$string = strtoupper(random($this->textNum));
//						$str='ABCDEFGHJKLMNOPQRSTUVWXYabcdehkmnprsuvwxy3456789';	//要随机的字符内容
//						for($i=0;$i<$this->textNum;$i++){
//							$string=$string.','.$str[mt_rand(0,strlen($str)-1)];
//				}
				break;
					case 'cn':
						for($i=0;$i<$this->textNum;$i++) {
							$string=$string.','.chr(mt_rand(0xB0,0xCC)).chr(mt_rand(0xA1,0xBB));
				}
							$string=iconv('GB2312','UTF-8',$string); //转换编码到utf8
				break;
				}
				return $string;
		}
		
		public function createText($randText){    //输出文字到验证码
				$text_array=explode(',',$randText);
				$this->textContent=join('',$text_array);
				if(empty($this->fontColor)){
				$this->randFontColor=imagecolorallocate($this->image,mt_rand(0,100),mt_rand(0,100),mt_rand(0,100));
				}else{
				$this->randFontColor=imagecolorallocate($this->image,$this->fontColor[0],$this->fontColor[1],$this->fontColor[2]);
				}
				for($i=0;$i<$this->textNum;$i++){
				$angle=mt_rand(-1,1)*mt_rand(1,5);
				imagettftext($this->image,$this->fontSize,$angle,5+$i*floor($this->fontSize*1.3),floor($this->height*0.75),$this->randFontColor,$this->fontFamily,$text_array[$i]);
				}
		}

		public function createNoisePoint(){    //@生成干扰点
				$noisePoint=mt_rand(150,200);
				for($i=0;$i<$noisePoint;$i++){
					$pointColor=imagecolorallocate($this->image,mt_rand(0,255),mt_rand(0,255),mt_rand(0,255));
				imagesetpixel($this->image,mt_rand(0,$this->width),mt_rand(0,$this->height),$pointColor);
				}
		}

		public function createNoiseLine(){    //@产生干扰线
				$noiseLine=mt_rand(1,4);
				for($i=0;$i<$noiseLine;$i++) {
					$lineColor=imagecolorallocate($this->image,mt_rand(0,255),mt_rand(0,255),20);
				imageline($this->image,0,mt_rand(0,$this->width),$this->width,mt_rand(0,$this->height),$lineColor);
				}
		}

		public function distortionText(){    //@扭曲文字
				$this->distortionImage=imagecreatetruecolor($this->width,$this->height);
				imagefill($this->distortionImage,0,0,$this->randBgColor);
				for($x=0;$x<$this->width;$x++){
					for($y=0;$y<$this->height;$y++){
						$rgbColor=imagecolorat($this->image,$x,$y);
				imagesetpixel($this->distortionImage,(int)($x+sin($y/$this->height*2*M_PI-M_PI*0.5)*3),$y,$rgbColor);
				}
					}
				$this->image=$this->distortionImage;
		}

		public function createImage($randText){    //生成验证码图片
				$this->initImage(); //创建基本图片
				$this->createText($randText); //输出验证码字符
				if($this->noisePoint){
					$this->createNoisePoint(); //产生干扰点
				}
				if($this->noiseLine){
					$this->createNoiseLine(); //产生干扰线
				}
				if($this->distortion){
					$this->distortionText();
						}//扭曲文字
				if($this->showBorder){
					imagerectangle($this->image,0,0,$this->width-1,$this->height-1,$this->randFontColor);
						} //添加边框					
			if(imagetypes() & IMG_JPG){					
	            header('Content-type:image/jpeg');	            
	            imagejpeg($this->image);	            
	        }elseif(imagetypes() & IMG_GIF){	        
	            header('Content-type: image/gif');
	            imagegif($this->image);
	        }elseif(imagetype() & IMG_PNG){        	
	            header('Content-type: image/png');
	            imagepng($this->image);
	        }else{
	            die("Don't support image type!");
	        }
				imagedestroy($this->image);
				if($this->distortion !=false){
					imagedestroy($this->distortionImage);
				}
				return $this->textContent;
		}
		
		public function textContent(){
			return $this->textContent;
		}
		
		
		
		
		
		
		


}