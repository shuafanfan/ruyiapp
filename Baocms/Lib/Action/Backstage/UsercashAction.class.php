<?php
class UsercashAction extends CommonAction{
    public function index(){
        $Userscash = D('Userscash');
        import('ORG.Util.Page');
        $map = array('type' => user);
        if ($account = $this->_param('account', 'htmlspecialchars')) {
            $map['account'] = array('LIKE', '%' . $account . '%');
            $this->assign('account', $account);
        }
		if ($cash_id = (int) $this->_param('cash_id')) {
            $map['cash_id'] = $cash_id;
            $this->assign('cash_id', $cash_id);
        }
		if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
		if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
		if ($code = $this->_param('code', 'htmlspecialchars')) {
            if ($code != 999) {
                $map['code'] = $code;
            }
            $this->assign('code', $code);
        } else {
            $this->assign('code', 999);
        }
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $row) {
            $ids[] = $row['user_id'];
        }
        $Usersex = D('Usersex');
        $map = array();
        $map['user_id'] = array('in', $ids);
        $ex = $Usersex->where($map)->select();
        $tmp = array();
        foreach ($ex as $row) {
            $tmp[$row['user_id']] = $row;
        }
        foreach ($list as $key => $row) {
            $list[$key]['bank_name'] = empty($list[$key]['bank_name']) ? $tmp[$row['user_id']]['bank_name'] : $list[$key]['bank_name'];
            $list[$key]['bank_num'] = empty($list[$key]['bank_num']) ? $tmp[$row['user_id']]['bank_num'] : $list[$key]['bank_num'];
            $list[$key]['bank_branch'] = empty($list[$key]['bank_branch']) ? $tmp[$row['user_id']]['bank_branch'] : $list[$key]['bank_branch'];
            $list[$key]['bank_realname'] = empty($list[$key]['bank_realname']) ? $tmp[$row['user_id']]['bank_realname'] : $list[$key]['bank_realname'];
        }
		$this->assign('user_cash', round($user_cash = $Userscash->where(array('type' => user,'status' =>1))->sum('money')/100,2));
		$this->assign('user_cash_commission', round($user_cash_commission = $Userscash->where(array('type' => user,'status' =>1))->sum('commission')/100,2));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function gold(){
        $Userscash = D('Userscash');
        import('ORG.Util.Page');
        $map = array('type' => shop);
        if ($account = $this->_param('account', 'htmlspecialchars')) {
            $map['account'] = array('LIKE', '%' . $account . '%');
            $this->assign('account', $account);
        }
		if ($cash_id = (int) $this->_param('cash_id')) {
            $map['cash_id'] = $cash_id;
            $this->assign('cash_id', $cash_id);
        }
		if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
		if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
		if ($code = $this->_param('code', 'htmlspecialchars')) {
            if ($code != 999) {
                $map['code'] = $code;
            }
            $this->assign('code', $code);
        } else {
            $this->assign('code', 999);
        }
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $row) {
            $ids[] = $row['user_id'];
        }
        $Usersex = D('Usersex');
        $map = array();
        $map['user_id'] = array('in', $ids);
        $ex = $Usersex->where($map)->select();
        $tmp = array();
        foreach ($ex as $row) {
            $tmp[$row['user_id']] = $row;
        }
        foreach ($list as $key => $row) {
            $list[$key]['bank_name'] = empty($list[$key]['bank_name']) ? $tmp[$row['user_id']]['bank_name'] : $list[$key]['bank_name'];
            $list[$key]['bank_num'] = empty($list[$key]['bank_num']) ? $tmp[$row['user_id']]['bank_num'] : $list[$key]['bank_num'];
            $list[$key]['bank_branch'] = empty($list[$key]['bank_branch']) ? $tmp[$row['user_id']]['bank_branch'] : $list[$key]['bank_branch'];
            $list[$key]['bank_realname'] = empty($list[$key]['bank_realname']) ? $tmp[$row['user_id']]['bank_realname'] : $list[$key]['bank_realname'];
        }
		$this->assign('shop_cash', round($shop_cash = $Userscash->where(array('type' => shop,'status' =>1))->sum('gold')/100,2));
		$this->assign('shop_cash_commission', round($shop_cash_commission = $Userscash->where(array('type' => shop,'status' =>1))->sum('commission')/100,2));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	//微信提现
    public function weixin_audit($cash_id = 0, $status = 0){
        if (!$status) {
            $this->baoError('参数错误');
        }
        $obj = D('Userscash');
        $cash_id = (int) $cash_id;
		$detail = $obj->find($cash_id);
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
				if(false == $obj-> weixin_cash_user_refund($cash_id,1)) {//微信提现逻辑封装
					$this->baoError($obj->getError());
				}else{
					if($obj->save($data)){
						D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
						$this->baoSuccess('操作成功！', U('usercash/index'));	
					}else{
						$this->baoError('更新数据库出错');
					}
				}
            }else {
                $this->baoError('当前订单状态不正确');
			}
	    }else{
			$this->baoError('没找到对应的提现订单');
		}
    }
	//银行卡提现
	public function bank_audit($cash_id = 0, $status = 0){
        if (!$status) {
            $this->baoError('参数错误');
        }
        $obj = D('Userscash');
		$cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if($obj->save($data)){
					D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                	$this->baoSuccess('操作成功！', U('usercash/index'));
				}else{
					$this->baoError('更新数据库失败');
				}
            } else {
                $this->baoError('请不要重复操作');
            }
			
		}else{
			$this->baoError('没找到对应的提现订单');
		}
    }
		
	//商户微信提现
	public function weixin_audit_gold($cash_id = 0, $status = 0){
        if (!$status) {
            $this->baoError('参数错误');
        }
        $obj = D('Userscash');
        $cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
				if(false == $obj-> weixin_cash_user_refund($cash_id,2)) {//微信提现逻辑封装，1会员，2商家
					$this->baoError($obj->getError());
				}else{
					if($obj->save($data)){
						D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
						$this->baoSuccess('操作成功！', U('usercash/gold'));
					}else{
						$this->baoError('请不要重复操作');
					}
				}
			}else{
				$this->baoError('更新数据库失败');
			}
	    }else{
			$this->baoError('没找到对应的提现订单');
		}
    }
	
	//商户银行卡提现
	public function bank_audit_gold($cash_id = 0, $status = 0){
        if (!$status) {
            $this->baoError('参数错误');
        }
        $obj = D('Userscash');
		$cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if($obj->save($data)){
					D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                	$this->baoSuccess('操作成功！', U('usercash/index'));
				}else{
					$this->baoError('更新数据库失败');
				}
            } else {
                $this->baoError('请不要重复操作');
            }
			
		}else{
			$this->baoError('没找到对应的提现订单');
		}
    }
	
  	public function agree(){

        $obj = D('Userscash');
		$cash_id = (int) $_GET['cash_id'];
      	$status = (int) $_GET['status'];
		if($detail = $obj->find($cash_id)){
          	$money=$detail['money']/100;
			if ($detail['status'] == 0) {
              	$alipay=$this->userWithDraw($detail['user_id'],$detail['cash_id'],$detail['bank_name'],$money,$detail['bank_realname']);
              	//dump($alipay);die;
              	if($alipay!=1){
                  	$obj->where(['cash_id'=>$cash_id])->save(['status'=>2,'reason'=>$alipay]);
                  	
                	$this->baoError($alipay);
                }
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
               	$data['reason'] = '提现成功';
              	D('users')->where(['user_id'=>$detail['user_id']])->setDec('money',$detail['money']);
                if($obj->save($data)){
					//D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                	$this->baoSuccess('操作成功！', U('usercash/index'));
				}else{
					$this->baoError('更新数据库失败');
				}
            } else {
                $this->baoError('请不要重复操作');
            }
			
		}else{
			$this->baoError('没找到对应的提现订单');
		}
    
    }
  
  
    //拒绝用户提现
    public function jujue(){
        $status = (int) $_POST['status'];
        $cash_id = (int) $_POST['cash_id'];
        $value = $this->_param('value', 'htmlspecialchars');
        if (empty($value)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝理由请填写'));
        }
        if (empty($cash_id) || !($detail = D('Userscash')->find($cash_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '参数错误'));
        }
        $money = $detail['money'];
        if ($status == 2) {
            D('Users')->addMoney($detail['user_id'], $money, '提现拒绝，退款');
            D('Userscash')->save(array('cash_id' => $cash_id, 'status' => $status, 'reason' => $value));
            D('Weixintmpl')->weixin_cash_user($detail['user_id'],3);//申请提现：1会员申请，2商家同意，3商家拒绝
            $this->ajaxReturn(array('status' => 'success', 'msg' => '拒绝退款操作成功', 'url' => U('usercash/index')));
        }
    }
    //拒绝商家提现
    public function jujue_gold(){
        $status = (int) $_POST['status'];
        $cash_id = (int) $_POST['cash_id'];
        $value = $this->_param('value', 'htmlspecialchars');
        if (empty($value)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝理由请填写'));
        }
        if (empty($cash_id) || !($detail = D('Userscash')->find($cash_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '参数错误'));
        }
        $money = $detail['gold'];
        if ($status == 2) {
            D('Users')->Money($detail['user_id'], $money, '提现拒绝，退款');
            D('Userscash')->save(array('cash_id' => $cash_id, 'status' => $status, 'reason' => $value));
            D('Weixintmpl')->weixin_cash_user($detail['user_id'],3);//申请提现：1会员申请，2商家同意，3商家拒绝
            $this->ajaxReturn(array('status' => 'success', 'msg' => '拒绝退款操作成功', 'url' => U('usercash/gold')));
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
      	//$data['status']=1;
      	//$this->ajaxReturn($data);

        return true;
    } else {
        //$result->$responseNode->sub_msg 这个参数 是返回的错误信息
     	 //$data['status']=1;
      	$msg=$result->$responseNode->sub_msg;
      	return $msg;
      	//$this->ajaxReturn($data);
       throw new Exception($result->$responseNode->sub_msg);
    }
}
  public function test(){
     require_cache(APP_PATH . 'Lib/Payment/alipay/aop/SignData.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/AopClient.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/request/AlipayFundTransToaccountTransferRequest.php');
  	$aop = new \AopClient;
$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
$aop->appId = "2017091508746595";
$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAvl2CBQ1nc2Xv6lhjGo91ZbIwT5vIc1Ukce29Gbs7p3qR0A5wNVZ1V1z8zoE4i4LSvWwMssTtvSPJWG5dmh5YAOqFRCvWEQcvRQIkEyp0B/9Dc6N5V1+IXiR3js2eLJx6Kt1S4KtvNwkxCzpcpDh2pASxBWM1SYGjIQttK2mK6lCz/UTVjwGS+Vk9CSsll2jJXB7AhA7ohjWV3nSujwtXT4u2RjEoo7oMLULs9uebHYQ/YmeJ4zzHQB7WNTBaY4Fn0CUbfhy9LnBpfm4C31JYY31nRdGT1+guQf2FxGtInwNSeVb3omYpMRb+QJ21LDzJvbc5TLBSafVbKXLaBIKnDQIDAQABAoIBABsajy+O+AK7KcyQ5xNaB5oCI9TB1mltXvIFql3mhZjT37ziwWEmvTBCIhB434clikHEB47QcRTz6m/3zsXpuhfvTCgnoaPtBPLrWh2MdtbIpl7pkJY7GNxmjss7RWEOqmo99tXcMsqAOYZiudv72hCCRn4A4Q5lMce7q8B5l3jC48O5ITFPwqaIZp5vplLMIEEvkBFgPzacU1Xk2i2k7RxiHRc3nFvr4dBlpgKd581ZINoTG/QJoxwzi/KkKedLGUuRZumJf1AcvtcMPSfQzcsSRnIptm7o9D0ifmXYFLtbp27jdJr8baiqOcBQEfYstmsAyn9cqU9rDd/6DfB5B8ECgYEA7ecP9uw0GlUxyWFdPL7SdChWqf+4ctzZNkgAgQnfgTnP0T2SwjmwbeA63yBJsGgNm9SwpXxaX5IlZG6ahK3vV+sa69Dgg5/8BgzjI+prjFel55VSwcfiUacAabsVmMKrmX3xNTs9b/KTAWV3Mi0lAAQ3OQKUCJLSwwxM9CvdhTECgYEAzNiy/h6lvuw5vz6cCd20kLN/n8Aqb+BosYD+gVloqtaSSh/ITjqfXkJKoPCZvDLmbson3tscs4P66/7TKlzKGLXp/cX4hK91NnuG3BoT4lrMkLb+ROrraO9qNKMDzcfdgOOdMV3+pbwsSuwqUVTkd6xMPoJEN0q16hcPLwCJeJ0CgYAb8qASfu8kBMwjfzen0jcBBFgiAnOVstIlqVG91v4VvUfnqdca0BIk5kmkOrPNcHvgyZyy5CftwEp6QPAQFCrg5jK0b0Zg0COYRl1Ms9ZMl2NbEFS3hRbWRssqoJrJ5lyoXH5ApaPzCckxSc017M3C8bvXC7F+TFlMJp7HtHuCkQKBgQDCkoriCeZRhTwMxcGdNXOPhpARA4zLm5KWF/qmjnB25T0rHyk59UDvXXRZjm/YbVinn2ljqyiF8zTh+LhHIr/r3M8Xd3XpQmfJime8pSCB/tEUHF1ExQc6Mz7kJHs2eUOSa6EiBTAd6LEBsXU4r7QbYBNDxKRbCKHmamTHtnDdmQKBgBW8VzYGysx5tkvV6VovCVVUV2FbXS/mJHqA8E2VimEwKQPrlOdFCxalOUCSGUnLH48iXkY1g6qAPmfgpiD/CkcCC6ip7FxswMfrX4iI2RXhFVOhf92k7WReUhzRIQSRsbw6RqNip2+04/Z5ANpMqOyIocmQMgIjv9v2Rq/IHFSD';
$aop->format = "json";
$aop->charset = "UTF-8";
$aop->signType = "RSA2";
$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx3I4IanA+18jllg3lxlboGJMHm03TMW7ljtqZ+uQZVy9/VZSL1l5/LuCInVkiy4GgM4yFViB0bMOt9pfNCp8vX5mzi/PgEYEcWQt2N5CpKC/oFZEL2B4iY7Y652P8t2WbU3hs8iRsDwyct6nKe3EgZT7xzY2chAgnoXfrnaYx54eXvZFdwBY74FKeZbzUXNIov4o11RVhOa99REgTW5y75rGxJpG9sVnmCDreYBHQbM2Q8FjW1DALz+Ghz+EeulEzcZmtmP5vftPTfIm/UoYm3Uwzhnehtc7cTodeZUMTdXmDo5e0AYKkqBzvuxvUJMQ6l3jNcu5gFw2N2OT1313JwIDAQAB';
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
$request = new AlipayTradeAppPayRequest();
    dump($request);die;
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"20170125test01\","
                . "\"timeout_express\": \"30m\"," 
                . "\"total_amount\": \"0.01\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
$request->setNotifyUrl("http://cs.rytlan.com");
$request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
$response = $aop->sdkExecute($request);
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
echo htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
  }
}