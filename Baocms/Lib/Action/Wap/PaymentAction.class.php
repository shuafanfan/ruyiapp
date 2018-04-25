<?php

class PaymentAction extends CommonAction {

	protected function ele_success($message, $detail) {
		$order_id = $detail['order_id'];
		$eleorder = D('Eleorder') -> find($order_id);
		$detail['single_time'] = $eleorder['create_time'];
		$detail['settlement_price'] = $eleorder['settlement_price'];
		$detail['new_money'] = $eleorder['new_money'];
		$detail['fan_money'] = $eleorder['fan_money'];
		$addr_id = $eleorder['addr_id'];
		$product_ids = array();
		$ele_goods = D('Eleorderproduct') -> where(array('order_id' => $order_id)) -> select();
		foreach ($ele_goods as $k => $val) {
			if (!empty($val['product_id'])) {
				$product_ids[$val['product_id']] = $val['product_id'];
			}
		}
		$addr = D('Useraddr') -> find($addr_id);
		$this -> assign('addr', $addr);
		$this -> assign('ele_goods', $ele_goods);
		$this -> assign('products', D('Eleproduct') -> itemsByIds($product_ids));
		$this -> assign('message', $message);
		$this -> assign('detail', $detail);
		$this -> assign('paytype', D('Payment') -> getPayments());
		$this -> display('ele');
	}

	protected function goods_success($message, $detail) {
		$order_ids = array();
		if (!empty($detail['order_id'])) {
			$order_ids[] = $detail['order_id'];
		} else {
			$order_ids = explode(',', $detail['order_ids']);
		}
		$goods = $good_ids = $addrs = array();
		foreach ($order_ids as $k => $val) {
			if (!empty($val)) {
				$order = D('Order') -> find($val);
				$addr = D('Useraddr') -> find($order['addr_id']);
				$ordergoods = D('Ordergoods') -> where(array('order_id' => $val)) -> select();
				foreach ($ordergoods as $a => $v) {
					$good_ids[$v['goods_id']] = $v['goods_id'];
				}
			}
			$goods[$k] = $ordergoods;
			$addrs[$k] = $addr;
		}
		$this -> assign('addr', $addrs[0]);
		$this -> assign('goods', $goods);
		$this -> assign('good', D('Goods') -> itemsByIds($good_ids));
		$this -> assign('detail', $detail);
		$this -> assign('message', $message);
		$this -> assign('paytype', D('Payment') -> getPayments());
		$this -> display('goods');
	}

	public function booking($order_id) {
		$Bookingorder = D('Bookingorder');
		$Bookingyuyue = D('Bookingyuyue');
		$Bookingmenu = D('Bookingmenu');
		if (!$order = $Bookingorder -> where('order_id = ' . $order_id) -> find()) {
			$this -> baoError('该订单不存在');
		} else if (!$yuyue = $Bookingyuyue  -> where('ding_id = ' . $order['ding_id']) -> find()) {
			$this -> baoError('该订单不存在');
		} else if ($yuyue['user_id'] != $this -> uid) {
			$this -> error('非法操作');
		} else {
			$arr = $Bookingorder -> get_detail($this -> shop_id, $order, $yuyue);
			$menu = $Bookingmenu -> shop_menu($this -> shop_id);
			$this -> assign('yuyue', $yuyue);
			$this -> assign('order', $order);
			$this -> assign('order_id', $order_id);
			$this -> assign('arr', $arr);
			$this -> assign('menu', $menu);
			$this -> display();
		}
	}

	protected function booking_success($message, $detail) {
		$Bookingorder = D('Bookingorder');
		$Bookingyuyue = D('Bookingyuyue');
		$Bookingmenu = D('Bookingmenu');

		if (!$order = $Bookingorder -> where('order_id = ' . $detail['order_id']) -> find()) {
			$this -> error('该订单不存在');
		} else if (!$yuyue = $Bookingyuyue -> where('ding_id = ' . $order['ding_id']) -> find()) {
			$this -> error('该订单不存在');
		} else if ($yuyue['user_id'] != $this -> shop_id) {
			$this -> error('非法操作');
		} else {
			$arr = $Bookingorder -> get_detail($yuyue['shop_id'], $order, $yuyue);
			$menu = $Bookingmenu -> shop_menu($yuyue['shop_id']);
			$this -> assign('yuyue', $yuyue);
			$this -> assign('order', $order);
			$this -> assign('order_id', $detail['order_id']);
			$this -> assign('arr', $arr);
			$this -> assign('menu', $menu);
			$this -> assign('message', $message);
			$this -> assign('paytype', D('Payment') -> getPayments());
			$this -> display('ding');
		}
	}

	protected function other_success($message, $detail) {
		$tuanorder = D('Tuanorder') -> find($detail['order_id']);
		if (!empty($tuanorder['branch_id'])) {
			$branch = D('Shopbranch') -> find($tuanorder['branch_id']);
			$addr = $branch['addr'];
		} else {
			$shop = D('Shop') -> find($tuanorder['shop_id']);
			$addr = $shop['addr'];
		}

		$this -> assign('addr', $addr);
		$tuans = D('Tuan') -> find($tuanorder['tuan_id']);
		$this -> assign('tuans', $tuans);
		$this -> assign('tuanorder', $tuanorder);
		$this -> assign('message', $message);
		$this -> assign('detail', $detail);
		$this -> assign('paytype', D('Payment') -> getPayments());
		$this -> display('other');
	}
	
	//家政支付成功
	 protected function appoint_success($message, $detail){
        $order_id = (int) $detail['order_id'];
        $order = D('Appointorder')->find($order_id);
        $Appoint = D('Appoint')->find($order['appoint_id']);//获取众筹商品
        $this->assign('order', $order);
        $this->assign('appoint', $Appoint);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('appoint');
    }
	
	//跑腿支付成功
	 protected function running_success($message, $detail){
        $running_id = (int) $detail['order_id'];
        $running = D('Running')->find($running_id);
        $this->assign('running', $running);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('running');
    }

     //云购支付成功
	 protected function cloud_success($message, $detail){
        $log_id = (int) $detail['order_id'];
        $cloudlogs = D('Cloudlogs')->find($log_id);
        $cloudgoods = D('Cloudgoods')->find($cloudlogs['goods_id']);//获取商品
        $this->assign('cloudlogs', $cloudlogs);
        $this->assign('cloudgoods', $cloudgoods);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('cloud');
    }
	//五折卡支付成功
	 protected function zhe_success($message, $detail){
        $order_id = (int) $detail['order_id'];
        $Zheorder = D('Zheorder')->find($order_id);
        $Zhe = D('Zhe')->find($Zheorder['zhe_id']);
        $this->assign('zheorder', $Zheorder);
        $this->assign('zhe', $Zhe);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('zhe');
    }
	
	//课程支付成功
	 protected function edu_success($message, $detail){
        $order_id = (int) $detail['order_id'];
        $EduOrder = D('EduOrder')->find($order_id);
		$course = D('Educourse')->find($EduOrder['course_id']);
        $edu = D('Edu')->where(array('edu_id'=>$EduOrder['edu_id']))->find();
        $this->assign('eduorder', $EduOrder);
		$this->assign('course', $course);
        $this->assign('edu', $edu);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('edu');
    }
	protected function pintuan_success($message, $detail) {
		$tuan = D('Porder') -> find($detail['order_id']);
		if ($tuan['tstatus'] == 0) {
			D('Porder') -> where(array('id' => $detail['order_id'])) -> save(array('order_status' => 3));
			header("Location:" . U('user/pintuan/groups'));
		} elseif ($tuan['tstatus'] == 1 || $tuan['tstatus'] == 2) {
			D('Weixintmpl')->weixin_notice_pintuan_user($detail['order_id'],$tuan['tuan_id'],$detail);//商城微信通知
			header("Location:" . U('user/pintuan/groups',array('aready'=>3)));//如果以拼团人数足够不改变订单状态联系我们处理
		}
	}

	public function respond() {
		$code = $this -> _get('code');
		if (empty($code)) {
			$this -> error('没有该支付方式！');
			die ;
		}
		$ret = D('Payment') -> respond($code);
		if ($ret == false) {
			$this -> error('支付验证失败！');
			die ;
		}
		if ($this -> isPost()) {
			echo 'SUCESS';
			die ;
		}
		$type = D('Payment') -> getType();
		$log_id = D('Payment') -> getLogId();
		$detail = D('Paymentlogs') -> find($log_id);
		if (!empty($detail)) {
			if ($detail['type'] == 'ele') {

				$this -> ele_success('恭喜您支付成功啦！', $detail);

			} elseif ($detail['type'] == 'booking') {
				$this -> booking_success('恭喜您支付成功啦！', $detail);
			} elseif ($detail['type'] == 'goods') {
				if (empty($detail['order_id'])) {
					$this -> success('合并付款成功', U('user/goods/index'));
				} else {
					$this -> goods_success('恭喜您支付成功啦！', $detail);
				}

			} elseif ($detail['type'] == 'gold' || $detail['type'] == 'money'|| $detail['type'] == 'fzmoney'|| $detail['type'] == 'breaks') {
				$this -> success('恭喜您充值成功', U('user/member/index'));
			} elseif ($detail['type'] == 'pintuan') {
				$this -> pintuan_success('恭喜您支付成功啦！', $detail);
			} elseif ($detail['type'] == 'appoint') {//家政
				$this -> appoint_success('恭喜您家政支付成功啦！', $detail);
			} elseif ($detail['type'] == 'running') {
				$this -> running_success('恭喜您跑腿支付成功啦！', $detail);
			} elseif ($detail['type'] == 'cloud') {
				$this->cloud_success('恭喜您云购支付成功啦！', $detail);
			} elseif ($detail['type'] == 'zhe') {
				$this->zhe_success('恭喜您五折卡支付成功啦！', $detail);
			} elseif ($detail['type'] == 'edu') {
				$this->zhe_success('恭喜您课程支付成功啦！', $detail);
			} else {
				$this -> other_success('恭喜您支付成功啦！', $detail);
			}
		} else {
			$this -> success('支付成功！', U('user/member/index'));
		}

	}

	public function yes($log_id) {
		$log_id = (int)$log_id;
		$logs = D('Paymentlogs') -> find($log_id);
		switch ($logs['type']) {
			case 'ele' :
				$this -> ele_success('恭喜您支付成功啦！', $logs);
				break;
			case 'goods' :
				if (empty($logs['order_id'])) {
					$this -> success('合并付款成功', U('user/goods/index'));
				} else {
					$this -> goods_success('恭喜您支付成功啦！', $logs);
				}
				break;
			case 'booking' :
				$this -> booking_success('恭喜您支付成功啦！', $logs);
				break;
			case 'appoint' :
				$this -> appoint_success('恭喜您家政支付成功啦！', $logs);//家政
				break;
			case 'running' :
				$this -> running_success('恭喜您跑腿支付成功啦！', $logs);
				break;
			case 'cloud' :
				$this -> cloud_success('恭喜您云购支付成功啦！', $logs);
				break;
			case 'zhe' :
				$this -> zhe_success('恭喜您购买五折卡支付成功啦！', $logs);
				break;
			case 'edu' :
				$this -> edu_success('恭喜您购买课程支付成功啦！', $logs);
				break;
			case 'tuan' :
				$this -> other_success('恭喜您支付成功啦！', $logs);
				break;
			case 'pintuan' :
				$this -> pintuan_success('恭喜您支付成功啦！', $logs);
				break;
			default :
				$this -> success('恭喜您充值成功', U('user/member/index'));
				break;
		}
	}

	public function payment($log_id) {
		if (empty($this -> uid)) {
			header("Location:" . U('passport/login'));
			die ;
		}
		$log_id = (int)$log_id;

		$logs = D('Paymentlogs') -> find($log_id);
		if (empty($logs) || $logs['user_id'] != $this -> uid || $logs['is_paid'] == 1) {
			$this -> error('没有有效的支付记录！');
			die ;
		}
		$this -> assign('button', D('Payment') -> getCode($logs));
		$this -> assign('types', D('Payment') -> getTypes());
		$this -> assign('logs', $logs);
		$this -> assign('paytype', D('Payment') -> getPayments());
		$this->assign('paytypes',$paytype =  D('PaymentLogs')->getcode($logs['code']));
		$this->assign('user_pay_password',$check_pay_password = D('Users')->check_pay_password($this->uid));
		$this -> display();
	}
	
	 //检测支付密码
	 public function check_pay_password() {
		$user_id = I('user_id', 0, 'trim,intval');
		$pay_password = I('pay_password', 0, 'trim');
        $Users = D('Users')->find($user_id);
		if (empty($pay_password)){
           $this->ajaxReturn(array('status' => 'error', 'msg' => '支付密码不能为空'));
        }
		if ($Users['pay_password'] != md5(md5($pay_password)) ) {
           $this->ajaxReturn(array('status' => 'error', 'msg' => '支付密码错误'));
        }else{
           session('session_pay_password', $session_pay_password = rand_string(32, 1));
		   $this->ajaxReturn(array('status' => 'success', 'msg' => '密码正确，点击支付按钮支付'));
		}
    }
	
	//设置支付密码
	 public function set_pay_password() {
		$user_id = I('user_id', 0, 'trim,intval');
		$pay_password = I('pay_password', 0, 'trim');
        $Users = D('Users')->find($user_id);
		if (empty($pay_password)){
           $this->ajaxReturn(array('status' => 'error', 'msg' => '支付密码不能为空'));
        }
		if (strlen($pay_password) < 6 || strlen($pay_password) >= 20){
           $this->ajaxReturn(array('status' => 'error', 'msg' => '支付密码大于等于6位，或者大于20位数，请重新设置'));
        }

		if (D('Passport')->set_pay_password($Users['account'], $pay_password)) {
			 $this->ajaxReturn(array('status' => 'success', 'msg' => '成功'));
		}else{
			 $this->ajaxReturn(array('status' => 'error', 'msg' => '操作失败，请重试'));
		}
    }

}
