<?php
class MoneyAction extends CommonAction{
    public function money(){
        //余额充值
        $this->assign('payment', D('Payment')->getPayments());
        $this->display();
    }
    //积分兑换余额
      public function exchange(){
        if($this->isPost()){
			$config = D('Setting')->fetchAll();
			$integral_buy = $config['integral']['buy'];
			//判断积分设置是否合法
			if (false == D('Users')->check_integral_buy($integral_buy)) {
				$this->baoError('网站后台积分设置不合法，请联系管理员');
			}
			
            $exchange = (int)$this->_post('exchange');
			if($exchange <=0){
                $this->baoError('要兑换的数量不能为空！');
            }
			$scale  = D('Users')->obtain_integral_scale($integral_buy);//获取积分比例便于同步
			
			//批量检测积分兑换余额批量代码封装
			if (!D('Users')->check_integral_exchange_legitimate($exchange,$scale)) {
				$this->baoError(D('Users')->getError(), 3000, true);	  
			}
	
            if($this->member['integral'] < $exchange){
                $this->baoError('账户积分不足');
            }
			$actual_integral = $exchange*$scale;
			$money = $actual_integral - intval(($actual_integral*$config['integral']['integral_exchange_tax'])/100);
			if($money > 0){
				if(D('Users')->addMoney($this->uid,$money,'积分兑换现金')){
					D('Users')->addIntegral($this->uid,-$exchange,'扣除兑换余额使用积分');          
				} 
			}
            $this->baoSuccess('您成功兑换余额'.round($money/100,2).'元',U('logs/moneylogs')); 
        }else{
             $this->display();
        }
    }
    public function moneypay(){
      	$order=$_POST;

        //后期优化
        $money = (int) ($this->_post('money') * 100);
        $code = $this->_post('code', 'htmlspecialchars');
        if ($money <= 0) {
          	$data['status']=1;
      		$data['msg']='请填写正确的充值金额！';
      		$this->ajaxReturn($data);
            $this->error('请填写正确的充值金额！');
        }
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
          	$data['status']=1;
      		$data['msg']='该支付方式不存在！';
      		$this->ajaxReturn($data);
            $this->error('该支付方式不存在');
        }
      	//if (empty($_POST['order_id'])) {
          	//$data['status']=1;
      		//$data['msg']='请填写订单号！';
      		//$this->ajaxReturn($data);
        //}
      	//if (empty($_POST['token'])) {
          	//$data['status']=1;
      		//$data['msg']='用户不存在！';
      		//$this->ajaxReturn($data);
        //}
      	$uid=D('users')->where(['token'=>$_POST['token']])->find();
      	$order_id=$_POST['order_id'];
        $logs = array(
			'user_id' => $uid['user_id'], 
			'type' => 'money', 
			'code' => $code, 
			'order_id' => $order_id, 
			'need_pay' => $money, 
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip()
		);
        $logs['log_id'] = D('Paymentlogs')->add($logs);
      	$button=D('Payment')->getCode($logs);
      	//Header("Location:$button"); 
      	//$this->redirect($button);
      	$data['status']=0;
      	$data['button']=$button;
      	$this->ajaxReturn($data);

    }
    public function recharge(){
        //代金券充值
        if ($this->isPost()) {
            $card_key = $this->_post('card_key', htmlspecialchars);
            if (empty($card_key)) {
                $this->baoError('充值卡号不能为空');
            }
            if (!($detail = D('Rechargecard')->where(array('card_key' => $card_key))->find())) {
                $this->baoError('该充值卡不存在');
            }
            if ($detail['is_used'] == 1) {
                $this->baoError('该充值卡已经使用过了');
            }
            $member = D('Users')->find($this->uid);
            $member['money'] += $detail['value'];
            if (D('Rechargecard')->save(array('card_id' => $detail['card_id'], 'is_used' => 1))) {
                D('Users')->save(array('user_id' => $this->uid, 'money' => $member['money']));
                D('Usermoneylogs')->add(array('user_id' => $this->uid, 'money' => $detail['value'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip(), 'intro' => '代金券充值' . $detail['card_id']));
                D('Rechargecard')->save(array('card_id' => $detail['card_id'], 'user_id' => $this->uid, 'used_time' => NOW_TIME));
                //微信通知
                $this->baoSuccess('充值成功！', U('money/recharge'));
            }
        } else {
            $this->display();
        }
    }
  
	//获取验证码
	  public function sendsms() {
        if (!$mobile = $this->_post('mobile')) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'请输入正确的手机号码'));
        }
        if (!isMobile($mobile)) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'请输入正确的手机号码'));
        }
        if (!$user = D('Users')->where(array('mobile' => $mobile))->find()) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'手机号码不存在！'));
        }
		if ($user['user_id'] != $this->uid) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'非法操作！'));
        }
        session('mobile', $mobile);
        $randstring = session('code');
        if (empty($randstring)) {
            $randstring = rand_string(6, 1);
            session('code', $randstring);
        }
		D('Sms')->sms_yzm($mobile, $randstring);//发送短信
        $this->ajaxReturn(array('status'=>'success','msg'=>'短信发送成功，请留意收到的短信','code'=>session('code')));
    }

	//检测手机号合法
	public function check_mobile(){
        $mobile = $this->_get('mobile');
		if(!empty($mobile)){
			$count_mobile = D('Users')->where(array('mobile' => $mobile))->count();
			if($count_mobile == 1){
				$user = D('Users')->where(array('mobile' => $mobile))->find();//这个版本不加手机号
				if (empty($user) || $user['mobile'] == $this->member['mobile']) {
					echo '0';
				} else {
					echo '您转账该对方昵称是'.'<font size="8" color="#F00">'.$user['nickname'].'</font>'.'请核对后转账，转账后无法退款';
				}
			}else{
				echo '0';
			}
		}else{
			echo '0';
		}
		
    }
	
	//好友转账
      public function transfer(){
        if($this->isPost()){
			$config = D('Setting')->fetchAll();
			$obj = D('Usertransferlogs');
			$cash_is_transfer = $config['cash']['is_transfer'];
			
			//判断网站后台设置是否合法
			if (false == $obj->check_admin_is_transfer($cash_is_transfer)) {
				$this->baoError('网站后台设置不合法，请联系管理员');
			}
			
			//检测被赠送的用户手机封装
            $mobile = $this->_post('mobile');
			if (false == $obj->check_transfer_user_mobile($mobile,$this->member['mobile'])) {
				$this->baoError($obj->getError(), 3000, true);
			}
	
			//检测余额小于0，用户余额是不是不足，超过最大限制，最小限制，检测用户转账间隔时间
			$money = ((int)$this->_post('money'))*100;
			
			if (false == $obj->check_transfer_user_money($money,$this->uid)) {
				$this->baoError($obj->getError(), 3000, true);
			}

			$yzm = $this->_post('yzm');
            if (empty($mobile) || empty($yzm))
                $this->baoError('请填写正确的手机及手机收到的验证码！');
            $session_mobile = session('mobile');
            $session_code = session('code');
            if ($this->member['mobile'] != $session_mobile)
                $this->baoError('手机号码和收取验证码的手机号不一致！');
            if ($yzm != $session_code){
				$this->baoError('验证码不正确');
			}
			
			if(!empty($config['cash']['is_transfer_commission'])){
				$commission = intval(($money*$config['cash']['is_transfer_commission'])/100);
				$receive_money = $money + $commission ;//实际扣除
			}
			
			//获取接收的USER
			$users = $obj->get_receive_users($mobile);
			$intro = $this->member['nickname'].'给您转账了'.round($money/100,2).'元';
			$intro1 = $this->member['nickname'].'给'.$users['nickname'].'转账了'.round($money/100,2).'元，手续费'.round($commission/100,2).'元';
			if($money > 0){
				if(D('Users')->addMoney($users['user_id'],$money,$intro)){
				    $logs = array();
					$logs['user_id'] = $this->uid;
					$logs['uid'] = $users['user_id'];
					$logs['money'] = $money;
					$logs['commission'] = $commission;
					$logs['intro'] = $intro1;
					$logs['create_time'] = time();
					$logs['create_ip'] = get_client_ip();
					$log_id = $obj->add($logs);
					if($log_id){
						$intro2 = '您给'.$users['nickname'].'转账了'.round($money/100,2).'元，手续费'.round($commission/100,2).'元';
						if(D('Users')->addMoney($this->uid,-$receive_money,$intro2)){
							$this->baoSuccess('恭喜您转账成功',U('money/transfer')); 
						}else{
							$this->baoError('操作失败！');
						}
					}else{
						$this->baoError('操作失败！');
					}        
				} 
			}
            
        }else{
             $this->display();
        }
    }
   
   
   //好友积分转账转账
      public function integral(){
        if($this->isPost()){
			$obj = D('Users');
	 		$mobile = $this->_post('mobile');
			if(!$mobile){
				$this->baoError('请填写手机号！');
			}
			$integral = (int)$this->_post('integral');
			if($integral <= 0){
				$this->baoError('积分填写错误！');
			}
			$intro = $this->_post('intro', 'htmlspecialchars');
			if(!$intro){
				$this->fengmiMsg('请填写备注');
			}
			if($words = D('Sensitive')->checkWords($intro)) {
                $this->fengmiMsg('备注中含有敏感词：' . $words);
            }	 
			//获取接收的USER
			$users = $obj->where(array('mobile'=>$mobile))->find();
			if($this->member['integral'] < $integral){
				$this->baoError('您的积分账户余额不足，无法转账！');
			}
			if($users){
				$intro = $this->member['nickname'].'给'.$users['nickname'].'转账了'.$integral.'积分：理由'.$intro;
				$obj->addIntegral($this->uid,-$integral,$intro);
				$obj->addIntegral($users['user_id'],$integral,$intro);
				$this->baoSuccess('恭喜您转账积分成功',U('logs/integral')); 
			}else{
				$this->baoError('没有找到会员！');
			}
        }else{
             $this->display();
        }
    }
	
	
   //检测扫码支付支付状态
	 public function check() {
		$log_id = $this->_get('log_id');
        $paymentlogs = D('Paymentlogs')->find($log_id);
        if (!empty($paymentlogs) && $paymentlogs['is_paid'] ==1) {
          $this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜您支付成功，正在为您跳转'));
        }
	 }
  
  public  function userWithDraw($userid,$out_biz_no,$payee_account,$amount,$payee_real_name)
{
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/SignData.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/AopClient.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/request/AlipayFundTransToaccountTransferRequest.php');
    $payer_show_name = '如逸科技测试';
    $remark = '如逸科技测试啊';
    $aop = new \AopClient();
    $aop->gatewayUrl =  "https://openapi.alipay.com/gateway.do";
    $aop->appId = "2017091508746595";
    $aop->rsaPrivateKey ="MIIEowIBAAKCAQEAvl2CBQ1nc2Xv6lhjGo91ZbIwT5vIc1Ukce29Gbs7p3qR0A5wNVZ1V1z8zoE4i4LSvWwMssTtvSPJWG5dmh5YAOqFRCvWEQcvRQIkEyp0B/9Dc6N5V1+IXiR3js2eLJx6Kt1S4KtvNwkxCzpcpDh2pASxBWM1SYGjIQttK2mK6lCz/UTVjwGS+Vk9CSsll2jJXB7AhA7ohjWV3nSujwtXT4u2RjEoo7oMLULs9uebHYQ/YmeJ4zzHQB7WNTBaY4Fn0CUbfhy9LnBpfm4C31JYY31nRdGT1+guQf2FxGtInwNSeVb3omYpMRb+QJ21LDzJvbc5TLBSafVbKXLaBIKnDQIDAQABAoIBABsajy+O+AK7KcyQ5xNaB5oCI9TB1mltXvIFql3mhZjT37ziwWEmvTBCIhB434clikHEB47QcRTz6m/3zsXpuhfvTCgnoaPtBPLrWh2MdtbIpl7pkJY7GNxmjss7RWEOqmo99tXcMsqAOYZiudv72hCCRn4A4Q5lMce7q8B5l3jC48O5ITFPwqaIZp5vplLMIEEvkBFgPzacU1Xk2i2k7RxiHRc3nFvr4dBlpgKd581ZINoTG/QJoxwzi/KkKedLGUuRZumJf1AcvtcMPSfQzcsSRnIptm7o9D0ifmXYFLtbp27jdJr8baiqOcBQEfYstmsAyn9cqU9rDd/6DfB5B8ECgYEA7ecP9uw0GlUxyWFdPL7SdChWqf+4ctzZNkgAgQnfgTnP0T2SwjmwbeA63yBJsGgNm9SwpXxaX5IlZG6ahK3vV+sa69Dgg5/8BgzjI+prjFel55VSwcfiUacAabsVmMKrmX3xNTs9b/KTAWV3Mi0lAAQ3OQKUCJLSwwxM9CvdhTECgYEAzNiy/h6lvuw5vz6cCd20kLN/n8Aqb+BosYD+gVloqtaSSh/ITjqfXkJKoPCZvDLmbson3tscs4P66/7TKlzKGLXp/cX4hK91NnuG3BoT4lrMkLb+ROrraO9qNKMDzcfdgOOdMV3+pbwsSuwqUVTkd6xMPoJEN0q16hcPLwCJeJ0CgYAb8qASfu8kBMwjfzen0jcBBFgiAnOVstIlqVG91v4VvUfnqdca0BIk5kmkOrPNcHvgyZyy5CftwEp6QPAQFCrg5jK0b0Zg0COYRl1Ms9ZMl2NbEFS3hRbWRssqoJrJ5lyoXH5ApaPzCckxSc017M3C8bvXC7F+TFlMJp7HtHuCkQKBgQDCkoriCeZRhTwMxcGdNXOPhpARA4zLm5KWF/qmjnB25T0rHyk59UDvXXRZjm/YbVinn2ljqyiF8zTh+LhHIr/r3M8Xd3XpQmfJime8pSCB/tEUHF1ExQc6Mz7kJHs2eUOSa6EiBTAd6LEBsXU4r7QbYBNDxKRbCKHmamTHtnDdmQKBgBW8VzYGysx5tkvV6VovCVVUV2FbXS/mJHqA8E2VimEwKQPrlOdFCxalOUCSGUnLH48iXkY1g6qAPmfgpiD/CkcCC6ip7FxswMfrX4iI2RXhFVOhf92k7WReUhzRIQSRsbw6RqNip2+04/Z5ANpMqOyIocmQMgIjv9v2Rq/IHFSD";//私钥 工具生成的 
    //$aop->alipayrsaPublicKey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvl2CBQ1nc2Xv6lhjGo91ZbIwT5vIc1Ukce29Gbs7p3qR0A5wNVZ1V1z8zoE4i4LSvWwMssTtvSPJWG5dmh5YAOqFRCvWEQcvRQIkEyp0B/9Dc6N5V1+IXiR3js2eLJx6Kt1S4KtvNwkxCzpcpDh2pASxBWM1SYGjIQttK2mK6lCz/UTVjwGS+Vk9CSsll2jJXB7AhA7ohjWV3nSujwtXT4u2RjEoo7oMLULs9uebHYQ/YmeJ4zzHQB7WNTBaY4Fn0CUbfhy9LnBpfm4C31JYY31nRdGT1+guQf2FxGtInwNSeVb3omYpMRb+QJ21LDzJvbc5TLBSafVbKXLaBIKnDQIDAQAB";//支付宝公钥 上传应用公钥后 支付宝生成的支付宝公钥
    //$aop->rsaPrivateKey="MIIEpAIBAAKCAQEAxAqkeXHVvS3rsqKcMJ0uiha5jtCNctt4nL551+08pkYHwPrY5cWsbTL3nVYfRr76YT0GLl2cfEuaAJesFLfb+FmQU6XCAK6cESPjqGGw5G8oZ4nbtRaGuDwtIeAkfH9HtWA3qss+OUgMXDcA6d5JSFkWBHCL0QqYa0TCDJv5fAhQ5bKUnQay12MDZAgqBLU6m77HNVLB6bP6SpvyCEm/zH6MTVmUSQwLlSAEL8DV8SmV1iXsUTo8tCusZlzPXb7PjvRynoX23NyTSl4dlKR9x4TAgGec7PVc3lTtz9s9zxycpJpBPVbF/JAa6aK3algnK75yYnZQdKacwM0QO+R1YQIDAQABAoIBAQCz8gDe6LqEl64Nnm03wk+BuTJCAPSVRRcE6WH17XEKPqXHYXnODxAie/IFnZO/4HT65ITT8mE9Rjfp606tBccHw05TOYdGEQB4SzUgzesNA2tW/peJNVAbtEDJI3DaRgqQVe1C/KP0+ElAK8zO3pbtrbgkAxqTmuIlNEvaPyFV0sC4ilg7CJTEZ0oyZNtjqaGQrYf/vIoTgtc30lYFzgJyw0E4VHc+1kcASR8tWvl8lwaNYkuHMNhc7DqEUdf86P2RVYy+2oIMytWRan58CEFTEiaKDCki3etRNkk6Ldd8AbWtgAONrLY1LycXJebhmP10RTvbJkkxqDJLJjpw1CUBAoGBAOkMlRqz8N6U1yoey1oUww9OE9Qa+iTEG0cSV9aYqy+5+QvNvSdE/7e0s2XEAcP4Pt1N4/NkDT/hEdbhK6YjKfTX8cxK2j2WkR1d/tSJuKYN7I1cuqgEpS6aByCSyWu4Ezag6h0hHqgh9zXPmrV7vBxVUJeKNQ7aGqHEqfv8+8Y5AoGBANdZDz4AtXuwK5wnIQHPjEjpnSiZVL33QfE4mZGfOx3mV1H6XpXSTVzJFgczRh0obJr/I3s6B8Lw3t7ubEuro9/llmDbTvtXbrzwk5QeJRmsMzumVABD+58Cih5mQDMcHw+T9nkhRhNTc1eiu+ijjuwORKj8BuKNAhCoP5W44WhpAoGAf5bL6xqovLNwOTcyzdagFDkC0hUS7h4PcRGx1WMwFakTmErDTESSW9kqfoSZEtlOUYrbSd11A4wNYD9WzlZiGhI/50DzytQsDo/vfB0KYp8s6xfheStR4/mf/U1fyQG2QypGAjyntBUkaumGIeXkTv7GaLDZ64+tFPO+vJ78mMECgYBmMdh+5x049nCrqRIL5u+/BkZTpvhFMlqz29Vc+wC1/sK/n55VYTjfaHowro9+dNmdcKjo6LAYPfC2QNVZz7l0HmMP0eOYeXSO62hNqUSX+1VQ4G0KtzhozG65YHLlDlpbLS+Rak30C+030H12OHAx689/liK6ToqFVyLdraAiyQKBgQCHlBk6UYFnZ2yaBBIt9U/7TypB+yvbWxIdNtCEnKnvhroynQEhfx8THdPVfYFy5Me4TutfzJd/utZr62CpW8y8TKtiGHn4Cl/2cRQVKt5OYVpzgvkjN1ihDeybBZ6NBCtFGKyPys6lAO1feXgYVOiBAYFSXZiy9500DuFRimbtkg==";
    //$aop->alipayrsaPublicKey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxAqkeXHVvS3rsqKcMJ0uiha5jtCNctt4nL551+08pkYHwPrY5cWsbTL3nVYfRr76YT0GLl2cfEuaAJesFLfb+FmQU6XCAK6cESPjqGGw5G8oZ4nbtRaGuDwtIeAkfH9HtWA3qss+OUgMXDcA6d5JSFkWBHCL0QqYa0TCDJv5fAhQ5bKUnQay12MDZAgqBLU6m77HNVLB6bP6SpvyCEm/zH6MTVmUSQwLlSAEL8DV8SmV1iXsUTo8tCusZlzPXb7PjvRynoX23NyTSl4dlKR9x4TAgGec7PVc3lTtz9s9zxycpJpBPVbF/JAa6aK3algnK75yYnZQdKacwM0QO+R1YQIDAQAB";
    $aop->alipayrsaPublicKey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx3I4IanA+18jllg3lxlboGJMHm03TMW7ljtqZ+uQZVy9/VZSL1l5/LuCInVkiy4GgM4yFViB0bMOt9pfNCp8vX5mzi/PgEYEcWQt2N5CpKC/oFZEL2B4iY7Y652P8t2WbU3hs8iRsDwyct6nKe3EgZT7xzY2chAgnoXfrnaYx54eXvZFdwBY74FKeZbzUXNIov4o11RVhOa99REgTW5y75rGxJpG9sVnmCDreYBHQbM2Q8FjW1DALz+Ghz+EeulEzcZmtmP5vftPTfIm/UoYm3Uwzhnehtc7cTodeZUMTdXmDo5e0AYKkqBzvuxvUJMQ6l3jNcu5gFw2N2OT1313JwIDAQAB";
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset='utf-8';
    $aop->format='json';
    $request = new \AlipayFundTransToaccountTransferRequest();
    $request->setBizContent("{" .
        "\"out_biz_no\":\"$out_biz_no\"," .
        "\"payee_type\":\"ALIPAY_LOGONID\"," .
        "\"payee_account\":\"$payee_account\"," .
        "\"amount\":\"$amount\"," .
        "\"payer_show_name\":\"$payer_show_name\"," .
        "\"payee_real_name\":\"$payee_real_name\"," .
        "\"remark\":\"$remark\"" .
        "}");
    $result = $aop->execute ($request);
	//dump($result);die;
    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
    $resultCode = $result->$responseNode->code;
				
    if(!empty($resultCode)&&$resultCode == 10000){
      	$data['status']=1;
      	$this->ajaxReturn($data);

        return true;
    } else {
        //$result->$responseNode->sub_msg 这个参数 是返回的错误信息 
       throw new Exception($result->$responseNode->sub_msg);
    }
}
}