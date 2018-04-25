<?php
class UserscashModel extends CommonModel {
    protected $pk = 'cash_id';
    protected $tableName = 'users_cash';
	public function getError() {
        return $this->error;
    }
	
	//退款逻辑封装
	public function weixin_cash_user_refund($cash_id,$tpye){
		$detail = $this->where('cash_id =' . $cash_id)->find();
		if($tpye ==1){
			$money = $detail['money']; 
		}else{
			$money = $detail['gold']; 
		}
		if(!$detail || $detail['status'] !=0 ) {
           $this->error = '请不要重复操作或者提现不存在';
		   return false;
        }else{
		    $payment = D('Payment') -> getPayment('weixin');
		    define('WEIXIN_APPID', $payment['appid']);
		    define('WEIXIN_MCHID', $payment['mchid']);
			define('WEIXIN_APPSECRET', $payment['appsecret']);
			define('WEIXIN_KEY', $payment['appkey']);
			define('WEIXIN_SSLCERT_PATH', APP_PATH . 'Lib/Payment/cacert/apiclient_cert.pem');
			define('WEIXIN_SSLKEY_PATH', APP_PATH . 'Lib/Payment/cacert/apiclient_key.pem');
			define('WEIXIN_SSLCA_PATH', APP_PATH . 'Lib/Payment/cacert/rootca.pem');
			include (APP_PATH . 'Lib/Payment/weixin/WxPay.Api.php');
			if (!file_exists(WEIXIN_SSLCERT_PATH && WEIXIN_SSLKEY_PATH && WEIXIN_SSLCA_PATH)) {
				$this->error = '证书路径错误或者不存在';
				return false;
			} 
			$input = new WxPayRefund();
			$input -> SetOut_trade_no($detail['cash_id']);//商户号,这里暂时用ID
			$input -> SetTotal_fee($money);
			$input -> SetRefund_fee($money);
			$input -> SetOut_refund_no(WEIXIN_MCHID . date("YmdHis"));
			$input -> SetOp_user_id(WEIXIN_MCHID);
			$return = WxPayApi::refund($input);
				if (is_array($return) && $return['result_code'] == 'SUCCESS') {
					//如果退款成功
					D('Weixintmpl')->weixin_cash_user_refund($detail['user_id'],$money,$cash_id,1);//申请提现：1会员申请提现，2商家申请提现
				} else {
					$this->error = '配置或者其他原因不正常';
					return false;
				}
        }
    }

    //检测分站的提现每天提现多少次
	public function check_cash_addtime($user_id,$type){
		$config = D('Setting')->fetchAll();
		$bg_time = strtotime(TODAY);
		
		if($type == 1){
			$count = $this->where(array('user_id'=>$user_id,'type'=>user,'addtime' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
			if($config['cash']['user_cash_second']){
				if($count > $config['cash']['user_cash_second']){
					return false;
				}
			}
			return true; 
		}elseif($type == 2){
			$count = $this->where(array('user_id'=>$user_id,'type'=>shop,'addtime' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
			if($config['cash']['shop_cash_second']){
				if($count > $config['cash']['shop_cash_second']){
					return false;
				}
			}
			return true;
		}else{
			return true;
		}

    }
}
