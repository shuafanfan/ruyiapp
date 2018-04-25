<?php
class CashAction extends CommonAction{
    public function index(){
		if($this->_CONFIG['cash']['is_cash'] !=1){
			$this->error('网站暂时没开启提现功能，请联系管理员');
		}
		if (false == D('Userscash')->check_cash_addtime($this->uid,1)){
			$this->error('请不要频繁操作');
		}
        $Users = D('Users');
        if(!$detail = $Users->find($this->uid)){
			$this->error('会员信息不存在');
		}elseif($detail['is_lock'] == 1){
			$this->error('您的账户已被锁，暂时无法提现');
		}
		$Connect = D('Connect')->where(array('user_id' => $this->uid,'type'=>weixin,))->find();
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
                $this->fengmiMsg('提现金额不合法');
            }
            if ($money < $cash_money * 100) {
                $this->fengmiMsg('提现金额小于最低提现额度');
            }
            if ($money > $cash_money_big * 100) {
                $this->fengmiMsg('您单笔最多能提现' . $cash_money_big . '元');
            }
            if ($money > $detail['money'] || $detail['money'] == 0) {
                $this->fengmiMsg('余额不足，无法提现');
            }
            if (!($data['bank_name'] = htmlspecialchars($_POST['bank_name']))) {
                $this->fengmiMsg('开户行不能为空');
            }
            if (!($data['bank_num'] = htmlspecialchars($_POST['bank_num']))) {
                $this->fengmiMsg('银行账号不能为空');
            }
            if (!($data['bank_realname'] = htmlspecialchars($_POST['bank_realname']))) {
                $this->fengmiMsg('开户姓名不能为空');
            }
            $data['bank_branch'] = htmlspecialchars($_POST['bank_branch']);
			if (!($code = $this->_post('code'))) {
				$this->fengmiMsg('请选择提现方式！');
			}
			if (empty($Connect['open_id'])) {
				if ($code = weixin) {
					$this->fengmiMsg('您暂时不能选择微信提现方式！');
				}
			}
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
            $arr['account'] = $detail['account'];
            $arr['bank_name'] = $data['bank_name'];
            $arr['bank_num'] = $data['bank_num'];
            $arr['bank_realname'] = $data['bank_realname'];
            $arr['bank_branch'] = $data['bank_branch'];
			$arr['code'] = $code;
			
			if(!empty($commission)){
				$intro = '您申请提现，扣款'.round($money/100,2).'元，其中手续费：'.round($commission/100,2).'元';
			}else{
				$intro = '您申请提现，扣款'.round($money/100,2).'元';
			}
			if($cash_id = D('Userscash')->add($arr)){
				if($Users->addMoney($detail['user_id'], -$money,$intro)){
					D('Usersex')->save($data);
					D('Weixintmpl')->weixin_cash_user($this->uid,1);//申请提现：1会员申请，2商家同意，3商家拒绝
					$this->fengmiMsg('申请成功，请等待管理员审核', U('cash/cashlog'));
				}else{
					$this->fengmiMsg('抱歉，提现扣余额失败！');
				}
			}else{
				$this->fengmiMsg('抱歉，提现操作失败！');
			}	
           
        }
		$this->assign('cash_money', $cash_money);
        $this->assign('cash_money_big', $cash_money_big);
		$this->assign('connect', $Connect);
        $this->assign('money', $detail['money'] / 100);
		$this->assign('detail', $detail);
        $this->assign('info', D('Usersex')->getUserex($this->uid));
        $this->display();
    }
	
	public function cashlog(){
        $this->display();
    }
    public function cashlogloaddata(){
        $Userscash = D('Userscash');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'type' => user);
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}