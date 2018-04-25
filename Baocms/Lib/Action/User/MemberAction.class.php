<?php
class MemberAction extends CommonAction{
 public function pay(){
        $logs_id = (int) $this->_get('logs_id');
        if (empty($logs_id)) {
            $this->error('没有有效的支付');
        }
        if (!($detail = D('Paymentlogs')->find($logs_id))) {
            $this->error('没有有效的支付');
        }
        if ($detail['code'] != 'money') {
            $this->error('没有有效的支付');
        }
        $member = D('Users')->find($this->uid);
        if ($detail['is_paid']) {
            $this->error('没有有效的支付');
        }
        if ($member['money'] < $detail['need_pay']) {
            $this->error('很抱歉您的账户余额不足', U('user/money/index'));
        }
        $member['money'] -= $detail['need_pay'];
        if (D('Users')->save(array('user_id' => $this->uid, 'money' => $member['money']))) {
            D('Usermoneylogs')->add(array(
				'user_id' => $this->uid, 
				'money' => -$detail['need_pay'], 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip(), 
				'intro' => '余额支付' . $logs_id
			));
            D('Payment')->logsPaid($logs_id);
        }
        if ($detail['type'] == 'ele') {
            $this->ele_success('恭喜您支付成功啦！', $detail);
        } elseif ($detail['type'] == 'booking') {
            $this->booking_success('恭喜您支付成功啦！', $detail);
        } elseif ($detail['type'] == 'farm') {
            $this->farm_success('恭喜您支付成功啦！', $detail);
        } elseif ($detail['type'] == 'appoint') {
            $this->appoint_success('恭喜您家政支付成功啦！', $detail);
        } elseif ($detail['type'] == 'running') {
            $this->running_success('恭喜您家政跑腿成功啦！', $detail);
        } elseif ($detail['type'] == 'goods') {
            $this->goods_success('恭喜您支付成功啦！', $detail);
        } elseif ($detail['type'] == 'cloud') {
            $this->cloud_success('恭喜您云购支付成功啦！', $detail);
        } elseif ($detail['type'] == 'pintuan') {
            $this->pintuan_success('恭喜您拼团支付成功啦！', $detail);
        } elseif ($detail['type'] == 'zhe') {
            $this->zhe_success('恭喜您购买五折卡支付成功啦！', $detail);
        } elseif ($detail['type'] == 'edu') {
            $this->edu_success('恭喜您购买课程支付成功啦！', $detail);
        } elseif ($detail['type'] == 'stock') {
            $this->stock_success('恭喜您购买股权支付成功啦！', $detail);
        } elseif ($detail['type'] == 'money') {
            $this->success('恭喜您充值成功', U('member/index'));
            die; 
	    }elseif ($detail['type'] == 'book'){
            $this->success('恭喜您付款成功', U('book/detail',array('order_id'=>$detail['order_id'])));
            die; 
	    }  elseif($detail['type'] == 'breaks' || $detail['type'] == 'hotel' || $detail['type'] == 'ktv'){
            $this->success('恭喜您付款成功', U('member/index'));die;
        } else {
            $this->other_success('恭喜您支付成功啦！', $detail);
        }
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
	
	//股权支付成功
	 protected function stock_success($message, $detail){
        $order_id = (int) $detail['order_id'];
        $order = D('Stockorder')->find($order_id);
		$stock = D('Stock')->find($order['stock_id']);
        $this->assign('order', $order);
		$this->assign('stock', $stock);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('stock');
    }
	
	//拼团支付成功
	 protected function pintuan_success($message, $detail) {
		$tuan = D('Porder') -> find($detail['order_id']);
		if ($tuan['tstatus'] == 0) {
			D('Porder') -> where(array('id' => $detail['order_id'])) -> save(array('order_status' => 3));
			header("Location:" . U('user/pintuan/groups'));
		} elseif ($tuan['tstatus'] == 1 || $tuan['tstatus'] == 2) {
			D('Weixintmpl')->weixin_notice_pintuan_user($detail['order_id'],$tuan['tuan_id'],$detail);//商城微信通知
			header("Location:" . U('user/pintuan/groups',array('aready'=>3)));
		}
	}
	//跑腿支付成功
	 protected function running_success($message, $detail){
        $running_id = (int) $detail['order_id'];
        $running = D('Running')->find($running_id);
        $this->assign('running', $running);
        $this->assign('message', $message);
		$this->assign('detail', $detail);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('running');
    }
	
	protected function ele_success($message, $detail){
        $order_id = $detail['order_id'];
        $eleorder = D('Eleorder')->find($order_id);
        $detail['single_time'] = $eleorder['create_time'];
        $detail['settlement_price'] = $eleorder['settlement_price'];
        $detail['new_money'] = $eleorder['new_money'];
        $detail['fan_money'] = $eleorder['fan_money'];
        $addr_id = $eleorder['addr_id'];
        $product_ids = array();
        $ele_goods = D('Eleorderproduct')->where(array('order_id' => $order_id))->select();
        foreach ($ele_goods as $k => $val) {
            if (!empty($val['product_id'])) {
                $product_ids[$val['product_id']] = $val['product_id'];
            }
        }
        $addr = D('Useraddr')->find($addr_id);
        $this->assign('addr', $addr);
        $this->assign('ele_goods', $ele_goods);
        $this->assign('products', D('Eleproduct')->itemsByIds($product_ids));
        $this->assign('message', $message);
        $this->assign('detail', $detail);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('ele');
    }
    protected function goods_success($message, $detail){
        $order_ids = array();
        if (!empty($detail['order_id'])) {
            $order_ids[] = $detail['order_id'];
        } else {
            $order_ids = explode(',', $detail['order_ids']);
        }
        $goods = $good_ids = $paddress = array();
        foreach ($order_ids as $k => $val) {
            if (!empty($val)) {
                $order = D('Order')->find($val);
                $paddress = D('Paddress')->find($order['address_id']);
                $ordergoods = D('Ordergoods')->where(array('order_id' => $val))->select();
                foreach ($ordergoods as $a => $v) {
                    $good_ids[$v['goods_id']] = $v['goods_id'];
                }
            }
            $goods[$k] = $ordergoods;
            $paddress[$k] = $paddress;
        }
        $this->assign('paddress', $paddress[0]);
        $this->assign('goods', $goods);
        $this->assign('good', D('Goods')->itemsByIds($good_ids));
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('goods');
    }
	
	protected function booking_success($message, $detail) {
        $order_id = (int)$detail['order_id'];
        $order = D('Bookingorder')->find($order_id);
        $dingmenu = D('Bookingordermenu')->where(array('order_id'=>$order_id))->select();
        $menu_ids = array();
        foreach($dingmenu as $k=>$val){
            $menu_ids[$val['menu_id']] = $val['menu_id'];
        }
        $this->assign('menus',D('Bookingmenu')->itemsByIds($menu_ids));
        $this->assign('shop',D('Booking')->find($order['shop_id']));
        $this->assign('dingmenu',$dingmenu);
        $this->assign('order',$order);
        $this->assign('message', $message);
        $this->assign('detail', $detail);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->mobile_title = '完成支付';
        $this->display('booking');
    }

    protected function other_success($message, $detail){
        $tuanorder = D('Tuanorder')->find($detail['order_id']);
        if (!empty($tuanorder['branch_id'])) {
            $branch = D('Shopbranch')->find($tuanorder['branch_id']);
            $addr = $branch['addr'];
        } else {
            $shop = D('Shop')->find($tuanorder['shop_id']);
            $addr = $shop['addr'];
        }
        $this->assign('addr', $addr);
        $tuans = D('Tuan')->find($tuanorder['tuan_id']);
        $this->assign('tuans', $tuans);
        $this->assign('tuanorder', $tuanorder);
        $this->assign('message', $message);
        $this->assign('detail', $detail);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('other');
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
	
    public function detail($order_id) {
        $Bookingorder = D('Bookingorder');
        $Bookingyuyue = D('Bookingyuyue');
        $Bookingmenu = D('Bookingmenu');
        if (!$order = $Bookingorder->where('order_id = ' . $order_id)->find()) {
            $this->baoError('该订单不存在');
        } else if (!$yuyue = $Bookingyuyue->where('ding_id = ' . $order['ding_id'])->find()) {
            $this->baoError('该订单不存在');
        } else if ($yuyue['user_id'] != $this->uid) {
            $this->error('非法操作');
        } else {
            $arr = $Bookingorder->get_detail($this->shop_id, $order, $yuyue);
            $menu = $Bookingmenu->shop_menu($this->shop_id);
            $this->assign('yuyue', $yuyue);
            $this->assign('order', $order);
            $this->assign('order_id', $order_id);
            $this->assign('arr', $arr);
            $this->assign('menu', $menu);
            $this->display();
        }
    }
	
	protected function farm_success($message, $detail) {
        $order_id = (int)$detail['order_id'];
        $order = D('FarmOrder')->find($order_id);
        $f = D('FarmPackage')->find($order['pid']);
        $shop = D('Shop')->find($farm['shop_id']);
        $farm = D('Farm')->where(array('farm_id'=>$f['farm_id']))->find();
        
        $this->assign('farm',$farm);
        $this->assign('order',$order);
        $this->assign('f',$f);
        $this->assign('shop', $shop);
        $this->assign('detail', $detail);
        $this->assign('message', $message);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->display('farm');
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
	
   //不知道是什么
   function diffBetweenTwoDays ($day1, $day2){
          $second1 = strtotime($day1);
          $second2 = strtotime($day2);
          if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
          }
          return ($second1 - $second2) / 86400;
    }
    public function index(){
        if (empty($this->uid)) {
            header('Location: ' . U('Wap/passport/login'));
            die;
        }
        $this->assign('order', D('Tuanorder')->where(array('user_id' => $this->uid))->count());
        $this->assign('code', D('Tuancode')->where(array('user_id' => $this->uid, 'is_used' => 0, 'status' => 0,'closed'=>0))->count());
        $this->assign('goods_order', D('Order')->where(array('user_id' => $this->uid))->count());
        $this->assign('ele_order', D('Eleorder')->where(array('user_id' => $this->uid))->count());
        $this->assign('coupon', D('Coupondownload')->where(array('user_id' => $this->uid, 'is_used' => 0))->count());
        $this->assign('hd', D('Huodong')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());
        $this->assign('xiaoqu', D('Community')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());
        $this->assign('tieba', D('Post')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());
        $this->assign('lipin', D('Integralexchange')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());
        $this->assign('tongzhi', D('Msg')->where(array('user_id' => $this->uid))->count());
        $this->assign('yuehui', D('Usermessage')->where(array('user_id' => $this->uid))->count());
        //统计同城信息
        $this->assign('life', D('Life')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());
        $this->assign('shop_yuyue', D('Shopyuyue')->where(array('user_id' => $this->uid, 'closed' => 0, 'used' => 0))->count());
        $is_shop = D('Shop')->find(array('where' => array('user_id' => $this->uid)));
        $is_shop_name = $is_shop['shop_name'];
        $this->assign('is_shop_name', $is_shop_name);
        $this->assign('is_shop', $is_shop);//统计今日新的约会数量
        $counts = array();
        $bg_time = strtotime(TODAY);
        //今日时间，需要统计其他的下面写。
        $counts['yuhui'] = (int) D('Huodong')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $counts['tieba'] = (int) D('Post')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $this->assign('counts', $counts);
        $this->assign('user_id', $this->uid);
        $sf = D('ShopFavorites');
        $rsf = $sf->where('user_id =' . $this->uid)->count();
        $this->assign('rsf', $rsf);
        $this->display();
    }
    public function password(){
        if ($this->isPost()) {
            $oldpwd = $this->_post('oldpwd', 'htmlspecialchars');
            if (empty($oldpwd)) {
                $this->error('旧密码不能为空！');
            }
            $newpwd = $this->_post('newpwd', 'htmlspecialchars');
            if (empty($newpwd)) {
                $this->error('请输入新密码');
            }
            $pwd2 = $this->_post('pwd2', 'htmlspecialchars');
            if (empty($pwd2) || $newpwd != $pwd2) {
                $this->error('两次密码输入不一致！');
            }
            if ($this->member['password'] != md5($oldpwd)) {
                $this->error('原密码不正确');
            }
            if (D('Passport')->uppwd($this->member['account'], $oldpwd, $newpwd)) {
                session('uid', null);
                $this->success('更改密码成功！', U('passport/login'));
            }
            $this->error('修改密码失败！');
        } else {
            $this->display();
        }
    }
	public function zhe(){
		$obj = D('Zheorder');
		$map = array('user_id'=>$this->uid,'closed'=>0,'status'=>1,'end_time' => array('EGT', NOW_TIME));
		$count = $obj->where($map)->count();
		if($count > 1){
			$this->error('五折卡状态不正确，请联系管理员处理');
		}
        $detail = $obj->where($map)->find();
		$detail['user'] = D('Users')->find($detail['user_id']);
		$this->assign('detail',$detail);
        $this->display('zhe_detail');
    }
	
	public function zhe_yuyue(){
		$aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }
	
	public function zhe_yuyue_loaddata(){
        $Zheyuyue = D('Zheyuyue');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);
		$bg_time = strtotime(TODAY);
		$NOW_TIME = strtotime(TODAY) + 86000;//今天晚上的时间
        $aready = (int) $this->_param('aready');
        if ($aready == -1) {//已过期
            $map['is_used'] = -1;
        } elseif ($aready == 1) {
            $map['is_used'] = 1;
        } else {//待消费未过期
            $aready == null;
			$map['is_used'] = 0;
        }
		$this->assign('aready', $aready);
        $count = $Zheyuyue->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Zheyuyue->where($map)->order(array('yuyue_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            if($zhe = D('Zhe')->where(array('zhe_id'=>$v['zhe_id']))->find()){
               $list[$k]['zhe'] = $zhe;
            }
			if($shop = D('Shop')->where(array('shop_id'=>$v['shop_id']))->find()){
               $list[$k]['shop'] = $shop;
            }
			if($users = D('Users')->where(array('user_id'=>$v['user_id']))->find()){
               $list[$k]['users'] = $users;
            }
			if(($v['create_time'] < $bg_time) && ($v['is_used'] == 0)){ //如果超过了今天
			    $Zheyuyue->save(array('yuyue_id'=>$v['yuyue_id'],'is_used'=> -1));
				$list[$k]['is_used'] = -1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display(); 
    }
	//五折卡详情
	public function zhe_yuyue_detail($yuyue_id){
        if(!$yuyue_id = (int)$yuyue_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Zheyuyue')->find($yuyue_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $detail['zhe'] = D('Zhe')->where(array('zhe_id'=>$detail['zhe_id']))->find(); 
           $detail['shop'] = D('Shop')->where(array('shop_id'=>$detail['shop_id']))->find();
		   $detail['users'] = D('Users')->where(array('user_id'=>$detail['user_id']))->find();
           $this->assign('detail',$detail);
           $this->display();
        }
    }
	
	//五折卡详情
	public function zhe_yuyue_qrcode($yuyue_id){
        if(!$yuyue_id = (int)$yuyue_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Zheyuyue')->find($yuyue_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $url = U('distributors/zhe/used', array('yuyue_id' => $yuyue_id, 't' => NOW_TIME, 'sign' => md5($yuyue_id . C('AUTH_KEY') . NOW_TIME)));
		   $token = 'zhe_yuyue_id_' . $yuyue_id;
		   $file = baoQrCode($token, $url);
		   $this->assign('file', $file);
           $this->assign('detail',$detail);
           $this->display();
        }
    }
  
    public function mobile(){
        if (!empty($this->member['mobile'])) {
            $this->success('恭喜您！您的手机已经绑定，可以正常购物！');
        }
        if ($this->isPost()) {
            $mobile = $this->_post('mobile');
            $yzm = $this->_post('yzm');
            if (empty($mobile) || empty($yzm)) {
                $this->error('请填写正确的手机及手机收到的验证码！');
            }
            $s_mobile = session('mobile');
            $s_code = session('code');
            if ($mobile != $s_mobile) {
                $this->error('手机号码和收取验证码的手机号不一致！');
            }
            if ($yzm != $s_code) {
                $this->error('验证码不正确');
            }
            $data = array('user_id' => $this->uid, 'mobile' => $mobile);
            if (D('Users')->save($data)) {
                D('Users')->integral($this->uid, 'mobile');
                $this->success('恭喜您通过手机认证', U('member/mobile'));
            }
            $this->error('更新数据失败！');
        } else {
            $this->display();
        }
    }
    public function sendsms(){
        $mobile = $this->_post('mobile');
        if (isMobile($mobile)) {
            session('mobile', $mobile);
            $randstring = session('code');
            if (empty($randstring)) {
                $randstring = rand_string(6, 1);
                session('code', $randstring);
            }
			//如果开启大鱼，用大鱼
			if($this->_CONFIG['sms']['dxapi'] == 'dy'){
                D('Sms')->DySms($this->_CONFIG['site']['sitename'], 'sms_yzm', $mobile, array(
                    'sitename' => $this->_CONFIG['site']['sitename'],
                    'code' => $randstring
                ));
            }else{
                D('Sms')->sendSms('sms_code', $mobile, array('code' => $randstring));//短信宝
            }
			
			
        }
    }
   
    public function money(){
        $this->assign('payment', D('Payment')->getPayments());
        $this->display();
    }
    public function moneypay(){
        //后期优化
        $money = (int) ($this->_post('money') * 100);
        $code = $this->_post('code', 'htmlspecialchars');
        if ($money <= 0) {
            $this->error('请填写正确的充值金额！');
            die;
        }
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
            die;
        }
        $logs = array('user_id' => $this->uid, 'type' => 'money', 'code' => $code, 'order_id' => 0, 'need_pay' => $money, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('money', $money);
        $this->display();
    }
	
	public function fabu(){
        $this->display();
    }
   
  
    public function xiaoxizhongxin(){
        $msg = D('Msg');//用户收到的总通知
        $msg_common = $msg->where(array('is_used' => 0,'is_fenzhan'=>0))->count();
        $msg_qita = $msg->where(array('user_id' => $this->uid, 'is_used' => 0,'is_fenzhan'=>0))->count();
        $this->assign('msg_common', $msg_common);
        $this->assign('msg_qita', $msg_qita);
        $message = D('Message');
        $message = $message->where('user_id =' . $this->uid)->count();
        $this->assign('message', $message);
        $counts = array();
        $bg_time = strtotime(TODAY);//今日时间，需要统计其他的下面写。
        $counts['message_xiaoqu'] = (int) D('Message')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $counts['mesg'] = (int) D('Msg')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $this->assign('counts', $counts);
        $this->display();
    }
    public function zijinguanli(){
        $this->display();
    }
    public function xiaoqu(){
        $this->assign('community', D('Community')->where(array('user_id' => $this->uid, 'closed' => 0, 'audit' => 1))->count());//加入的小区
        $this->assign('feedback', D('Feedback')->where(array('user_id' => $this->uid, 'closed' => 0))->count());//报修数量
        $this->assign('communityorder', D('Communityorder')->where(array('user_id' => $this->uid))->count()); //账单
        $this->assign('tieba', D('Communityposts')->where(array('user_id' => $this->uid))->count());//账单//统计今日新的数量
        $counts = array();
        $bg_time = strtotime(TODAY);//今日时间，需要统计其他的下面写。
        $counts['feedback_today'] = (int) D('Feedback')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $counts['communityorder_today'] = (int) D('Communityorder')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $counts['tieba_today'] = (int) D('Communityposts')->where(array('user_id' => $this->user_id, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
        $this->assign('counts', $counts);
        $this->display();
    }
}