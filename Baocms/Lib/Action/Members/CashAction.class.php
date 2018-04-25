<?php
class CashAction extends CommonAction{
   
    public function index(){
      	 header('content-type:application:json;charset=utf8');  
          header('Access-Control-Allow-Origin:*');  
          header('Access-Control-Allow-Methods:POST');  
          header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
        define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
      	$this->uid=$this->getuid($_POST['token']);
      	 $this->_CONFIG = D('Setting')->fetchAll();
      	//dump($this->_CONFIG);die;
		if($this->_CONFIG['cash']['is_cash'] !=1){
			$this->error('网站暂时没开启提现功能，请联系管理员');
		}
		if (false == D('Userscash')->check_cash_addtime($this->uid,1)){
			$this->error('您提现太频繁了，明天再来试试吧');
		}
        $Users = D('Users');
        $data = $Users->find($this->uid);
        $shop = D('Shop')->where(array('user_id' => $this->uid))->find();
        if ($shop == '') {
            $cash_money = $this->_CONFIG['cash']['user'];
            $cash_money_big = $this->_CONFIG['cash']['user_big'];
        } elseif ($shop['is_renzheng'] == 0) {
            $cash_money = $this->_CONFIG['cash']['shop'];
            $cash_money_big = $this->_CONFIG['cash']['shop_big'];
        } elseif ($shop['is_renzheng'] == 1) {
            $cash_money = $this->_CONFIG['cash']['renzheng_shop'];
            $cash_money_big = $this->_CONFIG['cash']['renzheng_shop_big'];
        } else {
            $cash_money = $this->_CONFIG['cash']['user'];
            $cash_money_big = $this->_CONFIG['cash']['user_big'];
        }
        if (IS_POST) {
            $money = abs((int) ($_POST['money'] * 100));
            if ($money == 0) {
              	$data['status']=1;
              	$data['msg']="提现金额不合法";
              	$this->ajaxReturn($data);
                $this->baoError('提现金额不合法');
            }
            if ($money < $cash_money * 100) {
              $data['status']=1;
              	$data['msg']="提现金额小于最低提现额度";
              	$this->ajaxReturn($data);
                $this->baoError('提现金额小于最低提现额度');
            }
            if ($money > $cash_money_big * 100) {
              $data['status']=1;
              	$data['msg']='您单笔最多能提现' . $cash_money_big . '元';
              	$this->ajaxReturn($data);
                $this->baoError('您单笔最多能提现' . $cash_money_big . '元');
            }
            if ($money > $data['money'] || $data['money'] == 0) {
              $data['status']=1;
              	$data['msg']="余额不足，无法提现";
              	$this->ajaxReturn($data);
                $this->baoError('余额不足，无法提现');
            }
          	if (!($data['code'] = htmlspecialchars($_POST['code']))) {
              $data['status']=1;
              	$data['msg']="请传入提现方式";
              	$this->ajaxReturn($data);
                $this->baoError('请传入提现方式');
            }
            if (!($data['bank_name'] = htmlspecialchars($_POST['zfb_account']))) {
              $data['status']=1;
              	$data['msg']="支付宝账号不能为空";
              	$this->ajaxReturn($data);
                $this->baoError('开户行不能为空');
            }
            //if (!($data['bank_num'] = htmlspecialchars($_POST['bank_num']))) {
               // $this->baoError('银行账号不能为空');
           // }
            if (!($data['bank_realname'] = htmlspecialchars($_POST['zfb_realname']))) {
              $data['status']=1;
              	$data['msg']="支付宝姓名不能为空";
              	$this->ajaxReturn($data);
                $this->baoError('开户姓名不能为空');
            }
            $data['bank_branch'] = htmlspecialchars($_POST['bank_branch']);
            $data['user_id'] = $this->uid;
			
			if(!empty($this->_CONFIG['cash']['user_cash_commission'])){
				$commission = intval(($money*$this->_CONFIG['cash']['user_cash_commission'])/100);
				$money = $money - $commission;
			}
            $arr = array();
            $arr['user_id'] = $this->uid;
            $arr['money'] = $money;
			$arr['commission'] = $commission;
            $arr['type'] = user;
            $arr['addtime'] = NOW_TIME;
            $arr['account'] = $data['account'];
            $arr['bank_name'] = $data['bank_name'];
            $arr['bank_num'] = $data['bank_num'];
            $arr['bank_realname'] = $data['bank_realname'];
            $arr['bank_branch'] = $data['bank_branch'];
			$arr['code'] = $data['code'];
          //dump($arr);die;
			if(!empty($commission)){
				$intro = '您申请提现，扣款'.round($money/100,2).'元，其中手续费：'.round($commission/100,2);
			}else{
				$intro = '您申请提现，扣款'.round($money/100,2).'元';
			}
			
			if($cash_id = D('Userscash')->add($arr)){
              	$Users->addMoney($data['user_id'], -$money,$intro);
              	$data['status']=0;
              	$data['msg']='申请成功，请等待管理员审核';
              	$this->ajaxReturn($data);
				
				//D('Usersex')->save($data);
				//D('Weixintmpl')->weixin_cash_user($this->member['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
				//$this->baoSuccess('申请成功，请等待管理员审核', U('logs/cashlogs'));
			}else{
              	$data['status']=1;
              	$data['msg']='申请失败';
              	$this->ajaxReturn($data);
				//$this->baoError('抱歉，提现操作失败！');
			}	
        }
		$this->assign('cash_money', $cash_money);
        $this->assign('cash_money_big', $cash_money_big);
        $this->assign('money', $data['money'] / 100);
        $this->assign('info', D('Usersex')->getUserex($this->uid));
        $this->display();
    }
}