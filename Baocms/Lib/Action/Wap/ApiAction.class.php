<?php
class ApiAction extends CommonAction {
	//新版生成带参数二维码
	public function qrcode(){
		$fuid = (int) $this->_param('fuid');
		if($detail = D('Users')->find($fuid)){
			if ($this->_CONFIG['weixin']['user_auto'] == 1) {
				require_cache(APP_PATH.'Lib/phpqrcode/phpqrcode.php'); 
				$size = 8;
				$token = 'fuid_' . $fuid;
				import("@/Net.Jssdk");
				$jssdk = new JSSDK($this->_CONFIG['weixin']["appid"], $this->_CONFIG['weixin']["appsecret"]);
				$wxqimg = $jssdk->getTemporaryQrcode($fuid);
				$md5 = md5($token);
				$dir = substr($md5,0,3).'/'.substr($md5,3,3).'/'.substr($md5,6,3).'/';
				$patch =BASE_PATH.'/attachs/'. 'weixinuid/'.$dir;
				if(!file_exists($patch)){
					mkdir($patch,0755,true);
				}
				$file = 'weixinuid/'.$dir.$md5.'.png';
				$fileName  = BASE_PATH.'/attachs/'.$file;
				if(!file_exists($fileName)){
					$level = 'L';
					$data = $wxqimg;
					QRcode::png($data, $fileName, $level, $size,2,true);
				}
			}else{
				$token = 'fuid_' . $fuid;
				$url = U('Wap/passport/register', array('fuid' => $fuid));
				$file = baoQrCode($token, $url);
			}
			$this->assign('detail',$detail);
			$this->assign('file', $file);
			$this->display();
		}else{
			$this->error('没有找到会员信息');
		}
   }


 public function poster(){
        $fuid = (int) $this->_param('fuid');
        $url = U('Wap/passport/register', array('fuid' => $this->uid));
        $file = baoQrCode($token, $url);
        $this->assign('file', $file);
        $this->display();
    }
}