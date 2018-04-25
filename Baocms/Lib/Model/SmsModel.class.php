<?php
class SmsModel extends CommonModel{
    protected $pk = 'sms_id';
    protected $tableName = 'sms';
    protected $token = 'bao_sms';
    public function sendSms($code, $shop_id,$mobile, $data){
        $tmpl = $this->fetchAll();
        if (!empty($tmpl[$code]['is_open'])) {
            $content = $tmpl[$code]['sms_tmpl'];
            $config = D('Setting')->fetchAll();
            $data['sitename'] = $config['site']['sitename'];
            $data['tel'] = $config['site']['tel'];
            foreach ($data as $k => $val) {
                $val = str_replace('【', '', $val);
                $val = str_replace('】', '', $val);
                $content = str_replace('{' . $k . '}', $val, $content);
            }
            if (is_array($mobile)) {
                $mobile = join(',', $mobile);
            }
            if ($config['sms']['charset']) {
                $content = auto_charset($content, 'UTF8', 'gbk');
            }
			
			$sms_id = $this->sms_bao_add($mobile,$shop_id, $content);//添加数据
            $local = array('mobile' => $mobile, 'content' => $content);
			
			if($shop_id){
				$Smsshop = D('Smsshop')->where(array('type'=>'shop','status'=>'0','shop_id'=>$shop_id))->find();
				if($Smsshop['num'] <= 0){
					D('Smsbao')->ToUpdate($sms_id,$shop_id,$res = '-1');//更新状态未-1
					return true;
				}
			}
            $http = tmplToStr($config['sms']['url'], $local);
            $res = file_get_contents($http);
			D('Smsbao')->ToUpdate($sms_id,$shop_id,$res);//更新短信宝状态
            return true;
        }
        return false;
    }
	
	//大鱼发送接口
    public function DySms($sign, $code,$shop_id, $mobile, $data){
        $config = D('Setting')->fetchAll();
        $dycode = D('Dayu')->where(array("dayu_local='{$code}'"))->find();
        if (!empty($dycode['is_open'])) {
            $sms_id = $this->sms_dayu_add($sign, $code,$shop_id, $mobile, $data, $dycode['dayu_note']);
            import('ORG.Util.Dayu');
            $obj = new AliSms($config['sms']['dykey'], $config['sms']['dysecret']);
            if ($res=$obj->sign($sign)->data($data)->sms_id($sms_id)->code($dycode['dayu_tag'])->send($mobile)) {
              	if($res['status']==0){
                	return false;
                }else{
                	return true;
                }
                
            }
        }
        return false;
    }
    public function sms_dayu_add($sign, $code, $shop_id,$mobile, $data, $dayu_note){
        foreach ($data as $k => $val) {
            $content = str_replace('${' . $k . '}', $val, $dayu_note);
            $dayu_note = $content;
          	$yzm=$val;
        }
        $sms_data = array();
        $sms_data['sign'] = $sign . '-' . time();
        $sms_data['code'] = $code;
		$sms_data['shop_id'] = $shop_id;
        $sms_data['mobile'] = $mobile;
        $sms_data['content'] = $content;
      	 $sms_data['yzm'] = $yzm;
        $sms_data['create_time'] = time();
      	$sms_data['expiry_time'] = time()+600;
      	$sms_data['type'] = 1;
        $sms_data['create_ip'] = get_client_ip();
        if ($sms_id = D('Dayusms')->add($sms_data)) {
            return $sms_id;
        }
        return true;
    }
	//短信宝添加
    public function sms_bao_add($mobile,$shop_id, $content){
        $sms_data = array();
        $sms_data['mobile'] = $mobile;
		$sms_data['shop_id'] = $shop_id;
        $sms_data['content'] = $content;
        $sms_data['create_time'] = time();
        $sms_data['create_ip'] = get_client_ip();
        if ($sms_id = D('Smsbao')->add($sms_data)) {
            return $sms_id;
        }
        return true;
    }
	//商城订单通知商家
    public function mallTZshop($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $order_id = array($order_id);
        }
        $config = D('Setting')->fetchAll();
        $orders = D('Order')->itemsByIds($order_id);
        $shop = array();
        foreach ($orders as $val) {
            $shop[$val['shop_id']] = $val['shop_id'];
        }
        $shops = D('Shop')->itemsByIds($shop);
        foreach ($shops as $val) {
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'sms_mall_tz_shop', $val['shop_id'], $val['mobile'], array(
				'sitename' => $config['site']['sitename']));
            } else {
                $this->sendSms('sms_mall_tz_shop',$val['shop_id'],$val['mobile'], array());
            }
        }
        return true;
    }
    //验证码
    public function sms_yzm($mobile, $randstring){
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $res=D('Sms')->DySms($config['site']['sitename'], 'sms_yzm',$shop_id = '0', $mobile, array(
			'sitename' => $config['site']['sitename'], 
			'code' => $randstring));
          	//dump($res);
          	if(!$res){
            	return false;
            }
          	return true;
        } else {
            D('Sms')->sendSms('sms_yzm',$shop_id = '0', $mobile, array('code' => $randstring));
          	
        }
        
    }
			
	 //用户重置新密码
    public function sms_user_newpwd($mobile, $password){
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $a = D('Sms')->DySms($config['site']['sitename'], 'sms_user_newpwd',$shop_id = '0', $mobile, array(
				'sitename' => $config['site']['sitename'], 
				'newpwd' => $password
			));
            
        } else {
            D('Sms')->sendSms('sms_user_newpwd',$shop_id = '0', $mobile, array(
				'newpwd' => $password
			));
        }
        return true;
    }
    //用户下载优惠劵通知用户手机
    public function coupon_download_user($download_id, $uid){
        $Coupondownload = D('Coupondownload')->find($download_id);
        $Coupon = D('Coupon')->find($Coupondownload['coupon_id']);
        $user = D('Users')->find($uid);
        $config = D('Setting')->fetchAll();
        //如果有手机号
        if (!empty($user['mobile'])) {
            if ($config['sms']['dxapi'] == 'dy') {
                D('Sms')->DySms($config['site']['sitename'], 'coupon_download_user',$Coupondownload['shop_id'], $user['mobile'], array(
					'coupon_title' => $Coupon['title'], 
					'code' => $Coupondownload['code'], 
					'expire_date' => $Coupon['expire_date']
				));
            } else {
                D('Sms')->sendSms('coupon_download_user',$Coupondownload['shop_id'], $user['mobile'], array(
					'coupon_title' => $Coupon['title'], 
					'code' => $Coupondownload['code'], 
					'expire_date' => $Coupon['expire_date']
				));
            }
        } else {
            return false;
        }
        return true;
    }
    //商城退款短信通知
    public function goods_refund_user($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $order = D('Order')->find($order_id);
            $config = D('Setting')->fetchAll();
            $user = D('Users')->find($order['user_id']);
            $t = time();
            $date = date('Y-m-d H:i:s ', $t);
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'goods_refund_user',$order['shop_id'], $user['mobile'], array(
					'need_pay' => round($order['need_pay'] / 100, 2), 
					'order_id' => $order['order_id']
				));
            } else {
                $this->sendSms('goods_refund_user', $order['shop_id'],$user['mobile'], array(
					'need_pay' => round($order['need_pay'] / 100, 2), 
					'order_id' => $order['order_id']
				));
            }
        }
        return true;
    }
    //外卖退款短信通知用户
    public function eleorder_refund_user($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $ele_order = D('Eleorder')->find($order_id);
            $config = D('Setting')->fetchAll();
            $user = D('Users')->find($ele_order['user_id']);
            $t = time();
            $date = date('Y-m-d H:i:s ', $t);
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'eleorder_refund_user',$ele_order['shop_id'], $user['mobile'], array(
					'need_pay' => round($ele_order['need_pay'] / 100, 2), 
					'order_id' => $order_id
				));
            } else {
                $this->sendSms('eleorder_refund_user',$ele_order['shop_id'], $user['mobile'], array(
					'need_pay' => round($ele_order['need_pay'] / 100, 2), 
					'order_id' => $order_id
				));
            }
        }
        return true;
    }
    //抢购劵退款短信通知
    public function tuancode_refund_user($code_id){
        $code_id = (int) $code_id;
        $tuancode = D('Tuancode')->find($code_id);
        $config = D('Setting')->fetchAll();
        $user = D('Users')->find($Tuancode['user_id']);
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'tuancode_refund_user', $tuancode['shop_id'],$user['mobile'], array(
				'real_money' => round($tuancode['real_money'] / 100, 2), 
				'order_id' => $code_id
			));
        } else {
            $this->sendSms('tuancode_refund_user', $tuancode['shop_id'], $user['mobile'], array(
				'real_money' => round($tuancode['real_money'] / 100, 2), 
				'order_id' => $code_id
			));
        }
        return true;
    }
    //优惠劵万能通知接口1,1是用户下载优惠劵，2代表用户会员中心再次请求优惠劵
    public function sms_coupon_user($download_id, $type){
        $Coupondownload = D('Coupondownload')->find($download_id);
        $users = D('Users')->find($Coupondownload['user_id']);
        $Coupon = D('Coupon')->find($Coupondownload['coupon_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_coupon_user',$Coupondownload['shop_id'], $users['mobile'], array(
				'user_name' => $users['nickname'], 
				'title' =>$Coupon['title'],
				'code' =>$Coupondownload['code'],
				'expire_date'=>$Coupon['expire_date']
			));
        } else {
            D('Sms')->sendSms('sms_coupon_user',$Coupondownload['shop_id'], $users['mobile'], array(
				'user_name' => $users['nickname'], 
				'title' =>$Coupon['title'],
				'code' =>$Coupondownload['code'],
				'expire_date'=>$Coupon['expire_date']
			));
        }
        return true;
    }
    //优惠劵赠送万能接口，分有会员账户跟没有会员账户，这个已不行了，大于规则修改了
    public function register_account_give_coupon($download_id, $give_user_id){
        $Coupondownload = D('Coupondownload')->find($download_id);
        $users = D('Users')->find($uid);//新用户账户
        $give_user = D('Users')->find($give_user_id);//原始账户
        $Coupon = D('Coupon')->find($Coupondownload['coupon_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'register_account_give_coupon', $shop_id = '0',$users['mobile'], array(
				'sitename' => $config['site']['sitename'], 
				'user_name' => niuMsubstr($users['nickname'], 0, 8, false), //接收人
				'give_user_name' => niuMsubstr($give_user['nickname'], 0, 8, false), //赠送人
			));
        } else {
            D('Sms')->sendSms('register_account_give_coupon',$shop_id = '0', $users['mobile'], array(
				'sitename' => $config['site']['sitename'], 
				'user_name' => niuMsubstr($users['nickname'], 0, 8, false), //接收人
				'give_user_name' => niuMsubstr($give_user['nickname'], 0, 8, false), //赠送人
			));
        }
        return true;
    }
	//新订单外卖通知商家
    public function eleTZshop($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $order = D('Eleorder')->find($order_id);
            $config = D('Setting')->fetchAll();
            $shop = D('Shop')->find($order['shop_id']);
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'sms_ele_tz_shop', $order['shop_id'],$shop['mobile'], array(
					'sitename' => $config['site']['sitename'], 
				));
            } else {
                $this->sendSms('sms_ele_tz_shop',$order['shop_id'], $shop['mobile'], array());
            }
        }
        return true;
    }
    //外卖催单通知商家
    public function sms_ele_reminder_shop($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $Eleorder = D('Eleorder')->find($order_id);
            $config = D('Setting')->fetchAll();
            $Users = D('Users')->find($Eleorder['user_id']);
            $Shop = D('Shop')->find($Eleorder['shop_id']);
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'sms_ele_reminder_shop',$Eleorder['shop_id'], $Shop['mobile'], array(
					'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
					'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
					'order_id' => $order_id
				));
            } else {
                $this->sendSms('sms_ele_reminder_shop',$Eleorder['shop_id'], $Shop['mobile'], array(
					'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
					'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
					'order_id' => $order_id
				));
            }
        }
        return true;
    }
    public function breaksTZshop($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $order = D('Breaksorder')->find($order_id);
            $config = D('Setting')->fetchAll();
            $shop = D('Shop')->find($order['shop_id']);
            $users = D('Users')->find($order['user_id']);
            if (!empty($users['nickname'])) {
                $user_name = $users['nickname'];
            } else {
                $user_name = $users['account'];
            }
            if (!empty($shop['mobile'])) {
                if ($config['sms']['dxapi'] == 'dy') {
                    $this->DySms($config['site']['sitename'], 'sms_breaks_tz_shop',$order['shop_id'], $shop['mobile'], array(
						'shop_name' => $shop['shop_name'], 
						'user_name' => $user_name, 
						'amount' => $order['amount'], 
						'money' => $order['need_pay']
					));
                } else {
                    $this->sendSms('sms_breaks_tz_shop',$order['shop_id'], $shop['mobile'], array(
						'shop_name' => $shop['shop_name'], 
						'user_name' => $user_name, 
						'amount' => $order['amount'], 
						'money' => $order['need_pay']
					));
                }
            }
        }
        return true;
    }
    public function breaksTZuser($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $order = D('Breaksorder')->find($order_id);
            $config = D('Setting')->fetchAll();
            $users = D('Users')->find($order['user_id']);
            if (!empty($users['nickname'])) {
                $user_name = $users['nickname'];
            } else {
                $user_name = $users['account'];
            }
            $shop = D('Shop')->find($order['shop_id']);
            $t = time();
            $date = date('Y-m-d H:i:s ', $t);
            if (!empty($users['mobile'])) {
                if ($config['sms']['dxapi'] == 'dy') {
                    $this->DySms($config['site']['sitename'], 'sms_breaks_tz_user',$order['shop_id'], $users['mobile'], array(
						'user_name' => $user_name, 
						'shop_name' => $shop['shop_name'], 
						'money' => $order['need_pay'], 
						'data' => $date
					));
                } else {
                    $this->sendSms('sms_breaks_tz_user',$order['shop_id'], $user['mobile'], array(
						'user_name' => $user_name, 
						'shop_name' => $shop['shop_name'], 
						'money' => $order['need_pay'], 
						'data' => $date
					));
                }
            }
        }
        return true;
    }
    //商家抢购劵验证成功后发送消息到用户手机
    public function tuan_TZ_user($code_id){
        if (is_numeric($code_id) && ($code_id = (int) $code_id)) {
            $tuancode = D('Tuancode')->find($code_id);
            $config = D('Setting')->fetchAll();
            $user = D('Users')->find($tuancode['user_id']);
            //用户手机号
            $tuan = D('Tuan')->find($tuancode['tuan_id']);
            $t = time();
            $date = date('Y-m-d H:i:s ', $t);
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'tuan_TZ_user',$tuancode['shop_id'], $user['mobile'], array(
					'name' => $tuan['title'], 
					'data' => $date, 
					'tel' => $config['site']['tel']
				));
            } else {
                $this->sendSms('tuan_TZ_user',$tuancode['shop_id'], $user['mobile'], array(
					'name' => $tuan['title'], 
					'data' => $date, 
					'tel' => $config['site']['tel']
				
				));
            }
        }
        return true;
    }
    //发送团购劵到用户手机
    public function sms_tuan_user($uid, $order_id){
        $user = D('Users')->find($uid);
        $config = D('Setting')->fetchAll();
        $order = D('Tuancode')->where(array('order_id' => $order_id))->select();
        foreach ($order as $v) {
            $code[] = $v['code'];
        }
        $tuan_id = $order[0]['tuan_id'];
        $count = $order = D('Tuancode')->where(array('order_id' => $order_id))->count();
        //统计
        if ($count == 1) {
            $tuan = D('Tuan')->where(array('tuan_id' => $tuan_id))->find();
            $tuan_title = $tuan['title'];
        } else {
            $tuan_title = '抢购列表';
        }
        $codestr = join(',', $code);
        //发送团购劵
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_tuan_user',$order[0]['shop_id'], $user['mobile'], array(
				'code' => $codestr, 
				'user' => $user['nickname'], 
				'shop_name' => $tuan_title
			));
        } else {
            D('Sms')->sendSms('sms_tuan_user', $order[0]['shop_id'],$user['mobile'], array(
				'code' => $codestr, 
				'user' => $user['nickname'], 
				'shop_name' => $tuan_title
			));
        }
        return true;
    }
    //团购通知商家
    public function tuanTZshop($shop_id){
        $shop_id = (int) $shop_id;
        $shop = D('Shop')->find($shop_id);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_tuan_tz_shop',$shop_id, $shop['mobile'], array('sitename' => $config['site']['sitename']));
        } else {
            $this->sendSms('sms_tuan_tz_shop',$shop_id, $shop['mobile'], array());
        }
        return true;
    }
    //酒店通知用户
    public function sms_hotel_user($order_id){
        $order = D('Hotelorder')->find($order_id);
        $room = D('Hotelroom')->find($order['room_id']);
        $hotel = D('Hotel')->find($order['hotel_id']);
        $shop = D('Shop')->find($hotel['shop_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_hotel_user', $hotel['shop_id'],$order['mobile'], array(
				'hotel_name' => $hotel['hotel_name'], 
				'tel' => $hotel['tel'], 
				'stime' => $order['stime']
			));
        } else {
            $this->sendSms('sms_hotel_user',$hotel['shop_id'],$order['mobile'], array(
			'hotel_name' => $hotel['hotel_name'], 
			'tel' => $hotel['tel'], 
			'stime' => $order['stime']
			));
        }
        return true;
    }
    //酒店通知商家
    public function sms_hotel_shop($order_id){
        $order = D('Hotelorder')->find($order_id);
        $room = D('Hotelroom')->find($order['room_id']);
        $hotel = D('Hotel')->find($order['hotel_id']);
        $shop = D('Shop')->find($hotel['shop_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_hotel_shop', $hotel['shop_id'],$shop['mobile'], array(
				'shop_name' => $shop['hotel_name'], 
				'title' => $room['title']
			));
        } else {
            $this->sendSms('sms_hotel_shop', $hotel['shop_id'],$shop['mobile'], array(
				'shop_name' => $shop['hotel_name'], 
				'title' => $room['title']
			));
        }
        return true;
    }
    //预订通知会员
    public function sms_booking_user($order_id) {
        $order = D('Bookingorder')->find($logs['order_id']);
        $booking = D('Booking')->find($order['shop_id']);
        //这里是预订里面填写的手机
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_booking_user',$order['shop_id'], $order['mobile'], array('booking_name' => $booking['shop_name']));
        } else {
            $this->sendSms('sms_booking_user',$order['shop_id'], $order['mobile'], array('booking_name' => $booking['shop_name']));
        }
        return true;
    }
    //预订通知商家
    public function sms_booking_shop($order_id){
        $order = D('Bookingorder')->find($logs['order_id']);
        $booking = D('Booking')->find($order['shop_id']);
        //这里是预订里面填写的手机
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_booking_shop',$order['shop_id'], $booking['mobile'], array('booking_name' => $booking['shop_name']));
        } else {
            $this->sendSms('sms_booking_shop',$order['shop_id'], $booking['mobile'], array('booking_name' => $booking['shop_name']));
        }
        return true;
    }
    //众筹通知用户
    public function sms_crowd_user($order_id){
        $order = D('Crowdorder')->find($logs['order_id']);
        $Crowd = D('Crowd')->find($order['goods_id']);
        $users = D('Users')->find($order['user_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_crowd_user', $order['shop_id'],$users['mobile'], array('user_name' => $users['nickname'], 'title' => $Crowd['title']));
        } else {
            $this->sendSms('sms_crowd_user',$order['shop_id'], $users['mobile'], array('user_name' => $users['nickname'], 'title' => $Crowd['title']));
        }
        return true;
    }
    //众筹通知发起人
    public function sms_crowd_uid($order_id){
        $order = D('Crowdorder')->find($logs['order_id']);
        $Crowd = D('Crowd')->find($order['goods_id']);
        $users = D('Users')->find($order['uid']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_crowd_uid',$order['shop_id'], $users['mobile'], array('user_name' => $users['nickname'], 'title' => $Crowd['title']));
        } else {
            $this->sendSms('sms_crowd_uid',$order['shop_id'], $users['mobile'], array('user_name' => $users['nickname'], 'title' => $Crowd['title']));
        }
        return true;
    }
    //家政预约成功再通知用户
    public function sms_appoint_TZ_user($order_id){
        $order = D('Appointorder')->find($order_id);
        $Appoint = D('Appoint')->find($order['appoint_id']);
        $users = D('Users')->find($order['user_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_appoint_TZ_user', $order['shop_id'],$users['mobile'], array(
				'sitename' => $config['site']['sitename'], 
				'appoint_name' => $Appoint['title'], 
				'time' => $order['svctime'], 
				'addr' => $order['addr']
			));
        } else {
            D('Sms')->sendSms('sms_appoint_TZ_user', $order['shop_id'],$users['mobile'], array(
				'appoint_name' => $Appoint['title'], 
				'time' => $order['svctime'], 
				'addr' => $order['addr']
			));
        }
        return true;
    }
    //家政预约成功再通知商家
    public function sms_appoint_TZ_shop($order_id){
        $order = D('Appointorder')->find($order_id);
        $appoint = D('Appoint')->find($order['appoint_id']);
        $shop = D('Shop')->find($order['shop_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_appoint_TZ_shop', $order['shop_id'],$shop['mobile'], array(
				'shop_name' => $shop['shop_name'], 
				'appoint_name' => $appoint['title'], 
				'time' => $order['svctime'], 
				'addr' => $order['addr']
			));
        } else {
            D('Sms')->sendSms('sms_appoint_TZ_shop',$order['shop_id'], $shop['mobile'], array(
				'shop_name' => $shop['shop_name'], 
				'appoint_name' => $appoint['title'], 
				'time' => $order['svctime'], 
				'addr' => $order['addr']
			));
        }
        return true;
    }
    //家政退款通知用户手机
    public function sms_appoint_refund_user($order_id){
        $order = D('Appointorder')->find($order_id);
        $Appoint = D('Appoint')->find($order['appoint_id']);
        //众筹类目
        $users = D('Users')->find($order['user_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_appoint_refund_user',$order['shop_id'], $users['mobile'], array(
				'user_name' => $users['nickname'], 
				'refund_money' => round($order['need_pay'] / 100, 2), 
				'order_id' => $order['order_id']
			));
        } else {
            D('Sms')->sendSms('sms_appoint_refund_user',$order['shop_id'], $users['mobile'], array(
				'user_name' => $users['nickname'], 
				'refund_money' => round($order['need_pay'] / 100, 2), 
				'order_id' => $order['order_id']
			));
        }
        return true;
    }
    //跑腿发布成功后通知用户
    public function sms_running_user($running_id){
        $running = D('Running')->find($running_id);
        $users = D('Users')->find($running['user_id']);
        $config = D('Setting')->fetchAll();
        $t = time();
        $date = date('Y-m-d H:i:s ', $t);
        if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_running_user', $users['mobile'], array(
				'sitename' => $config['site']['sitename'], 
				'user_name' => $users['nickname'], 
				'need_pay' => round($running['need_pay'] / 100, 2), 
				'running_id' => $running_id, 
				'time' => $date
			));
        } else {
            D('Sms')->sendSms('sms_running_user', $users['mobile'], array(
				'user_name' => $users['nickname'], 
				'need_pay' => round($running['need_pay'] / 100, 2), 
				'running_id' => $running_id, 
				'time' => $date
			));
        }
        return true;
    }
    //配送员接单通知用户
    public function sms_Running_Delivery_User($running_id){
        $running = D('Running')->find($running_id);
        $users = D('Users')->find($running['user_id']);
        $delivery = D('Delivery')->find($running['cid']);
        $config = D('Setting')->fetchAll();
        if (!empty($running)) {
            if ($running['status'] == 2) {
                $info = '您的跑腿订单ID：' . $running_id . '已被配送员' . $delivery['name'] . '接单，手机：' . $delivery['mobile'];
            } elseif ($running['status'] == 3) {
                $info = '您的跑腿订单ID：' . $running_id . '已完成配送';
            } else {
                return true;
            }
        } else {
            return false;
        }
        if (!empty($delivery)) {
            if ($config['sms']['dxapi'] == 'dy') {
                D('Sms')->DySms($config['site']['sitename'], 'sms_running_delivery_user', $users['mobile'], array('sitename' => $config['site']['sitename'], 'user_name' => $users['nickname'], 'info' => $info));
            } else {
                D('Sms')->sendSms('sms_running_delivery_user', $users['mobile'], array('user_name' => $users['nickname'], 'info' => $info));
            }
        } else {
            return true;
            //发短信暂时忽略错误return false;
        }
        return true;
    }
    //批量推送给配送员
    public function sms_delivery_user($order_id, $type){
        $type = (int) $type;
        //0是商城，1是外卖，2跑腿
        if ($type == 0) {
            $obj = D('Order');
            $info = '商城订单';
        } elseif ($type == 1) {
            $obj = D('Eleorder');
            $info = '外卖订单';
        } else {
            $obj = D('Running');
            $info = '跑腿';
        }
        $t = time();
        $date = date('m-d H:i', $t);
        $Delivery = D('Delivery')->where(array('is_sms' => 1))->field('mobile')->select();
        $config = D('Setting')->fetchAll();
        foreach ($Delivery as $value) {
            if ($config['sms']['dxapi'] == 'dy') {
                D('Sms')->DySms($config['site']['sitename'], 'sms_delivery_user',$shop_id = '0', $value['mobile'], array(
					'info' => $info, 
					'data' => $date
				));
            } else {
                D('Sms')->sendSms('sms_delivery_user',$shop_id = '0', $value['mobile'], array('info' => $info, 'data' => $date));
            }
        }
        return true;
    }
    //云购中奖通知用户
    public function sms_cloud_win_user($goods_id, $user_id, $number){
        $Cloudgoods = D('Cloudgoods')->find($goods_id);
        $Users = D('Users')->find($user_id);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_cloud_win_user',$Cloudgoods['shop_id'], $Users['mobile'], array(
				'title' => $Cloudgoods['title'], 
				'user_name' => $Users['nickname'], 
				'number' => $number
			));
        } else {
            $this->sendSms('sms_cloud_win_user', $Cloudgoods['shop_id'],$Users['mobile'], array(
				'title' => $Cloudgoods['title'], 
				'user_name' => $Users['nickname'], 
				'number' => $number
			));
        }
        return true;
    }
    //云购中奖通知商家
    public function sms_cloud_win_shop($goods_id, $number){
        $Cloudgoods = D('Cloudgoods')->find($goods_id);
        $Shop = D('Shop')->find($Cloudgoods['shop_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_cloud_win_shop', $Cloudgoods['shop_id'],$Shop['mobile'], array(
				'title' => $Cloudgoods['title'], 
				'shop_name' => $Shop['shop_name'], 
				'number' => $number
			));
        } else {
            $this->sendSms('sms_cloud_win_shop',$Cloudgoods['shop_id'], $Shop['mobile'], array(
				'title' => $Cloudgoods['title'], 
				'shop_name' => $Shop['shop_name'], 
				'number' => $number
			));
        }
        return true;
    }
    //五折卡购买成功通知
    public function sms_zhe_notice_user($order_id){
        $Zheorder = D('Zheorder')->find($order_id);
        $Users = D('Users')->find($Zheorder['user_id']);
        if ($Zheorder['type'] == 1) {
            $type = '周卡';
        } else {
            $type = '年卡';
        }
        $end_time = date("Y-m-d ", $Zheorder['end_time']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_zhe_notice_user', $shop_id = '0',$Users['mobile'], array(
				'user_name' => $Users['nickname'], 
				'type' => $type, 
				'number' => $Zheorder['number'], 
				'end_time' => $end_time
			));
        } else {
            $this->sendSms('sms_zhe_notice_user', $shop_id = '0',$Users['mobile'], array(
				'user_name' => $Users['nickname'], 
				'type' => $type, 
				'number' => $Zheorder['number'], 
				'end_time' => $end_time
			));
        }
        return true;
    }
    //课程购买成功通知买家
    public function sms_edu_notice_user($order_id){
        $EduOrder = D('EduOrder')->find($order_id);
        $Educourse = D('Educourse')->find($EduOrder['course_id']);
        $Users = D('Users')->find($EduOrder['user_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_edu_notice_user',$EduOrder['shop_id'], $Users['mobile'], array(
				'user_name' => $Users['nickname'], 
				'title' => $Educourse['title'], 
				'code' => $EduOrder['code'], 
				'need_pay' => round($EduOrder['need_pay'] / 100, 2)
			));
        } else {
            $this->sendSms('sms_edu_notice_user',$EduOrder['shop_id'], $Users['mobile'], array(
				'user_name' => $Users['nickname'], 
				'title' => $Educourse['title'], 
				'code' => $EduOrder['code'], 
				'need_pay' => round($EduOrder['need_pay'] / 100, 2)
			));
        }
        return true;
    }
    //课程购买成功通知商家
    public function sms_edu_notice_shop($order_id){
        $EduOrder = D('EduOrder')->find($order_id);
        $Educourse = D('Educourse')->find($EduOrder['course_id']);
        $Shop = D('Shop')->find($EduOrder['shop_id']);
        $Users = D('Users')->find($EduOrder['user_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_edu_notice_shop', $EduOrder['shop_id'],$Shop['mobile'], array(
				'shop_name' => $Shop['shop_name'], 
				'title' => $Educourse['title'], 
				'need_pay' => round($EduOrder['need_pay'] / 100, 2)
			));
        } else {
            $this->sendSms('sms_edu_notice_shop',$EduOrder['shop_id'], $Shop['mobile'], array(
				'shop_name' => $Shop['shop_name'], 
				'title' => $Educourse['title'], 
				'need_pay' => round($EduOrder['need_pay'] / 100, 2)
			));
        }
        return true;
    }
    //五折卡预约通知用户
    public function sms_zhe_yuyue_user($yuyue_id){
        $Zheyuyue = D('Zheyuyue')->find($yuyue_id);
        $Zhe = D('Zhe')->find($Zheyuyue['zhe_id']);
        $Shop = D('Shop')->find($Zhe['shop_id']);
        $Users = D('Users')->find($Zheyuyue['user_id']);
        $date = date('m-d H:i', time());
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_zhe_yuyue_user',$Zhe['shop_id'], $Users['mobile'], array(
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'code' => $Zheyuyue['code'], 
				'date' => $date
			));
        } else {
            $this->sendSms('sms_zhe_yuyue_user',$Zhe['shop_id'], $Users['mobile'], array(
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'code' => $Zheyuyue['code'], 
				'date' => $date
			));
        }
        return true;
    }
    //五折卡预约通知商家
    public function sms_zhe_yuyue_shop($yuyue_id){
        $Zheyuyue = D('Zheyuyue')->find($yuyue_id);
        $Zhe = D('Zhe')->find($Zheyuyue['zhe_id']);
        $Users = D('Users')->find($Zheyuyue['user_id']);
        $Shop = D('Shop')->find($Zhe['shop_id']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_zhe_yuyue_shop',$Zhe['shop_id'], $Shop['mobile'], array(
				'shop_name' => $Shop['shop_name'], 
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'user_mobile' => $Users['mobile']
			));
        } else {
            $this->sendSms('sms_zhe_yuyue_shop',$Zhe['shop_id'], $Shop['mobile'], array(
				'shop_name' => $Shop['shop_name'], 
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'user_mobile' => $Users['mobile']
			));
        }
        return true;
    }
    //五折卡预约验证通知买家
    public function sms_zhe_yuyue_is_used_user($yuyue_id){
        $Zheyuyue = D('Zheyuyue')->find($yuyue_id);
        $Zhe = D('Zhe')->find($Zheyuyue['zhe_id']);
        $Shop = D('Shop')->find($Zhe['shop_id']);
        $Users = D('Users')->find($Zheyuyue['user_id']);
        $used_time = date('m-d H:i', $Zheyuyue['used_time']);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_zhe_yuyue_is_used_user',$Zhe['shop_id'], $Users['mobile'], array(
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'used_time' => $used_time
			));
        } else {
            $this->sendSms('sms_zhe_yuyue_is_used_user',$Zhe['shop_id'], $Users['mobile'], array(
				'zhe_name' => niuMsubstr($Zhe['zhe_name'], 0, 6, false), 
				'user_name' => niuMsubstr($Users['nickname'], 0, 4, false), 
				'used_time' => $used_time
			));
        }
        return true;
    }
	
	//股权购买成功通知买家
	public function sms_stock_user($order_id){
        	$Stockorder = D('Stockorder')->find($order_id);
			$Stock = D('Stock')->find($Stockorder['stock_id']);
			$Users = D('Users')->find($Stockorder['user_id']);
			$config = D('Setting')->fetchAll();
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'sms_stock_user',$Stockorder['shop_id'],$Users['mobile'], array(
				        'user_name' => $Users['nickname'],
					    'title' => $Stock['title'],
						'stock_number' => $Stockorder['stock_number'],
                        'need_pay_price' => round($Stockorder['need_pay_price']/100,2),
 	
					));
            } else {
                $this->sendSms('sms_stock_user',$Stockorder['shop_id'], $Users['mobile'], array(
					   'user_name' => $Users['nickname'],
					   'title' => $Stock['title'],
					   'stock_number' => $Stockorder['stock_number'],
                       'need_pay_price' => round($Stockorder['need_pay_price']/100,2),
				));
            }
        return true;
    }	
	
	//股权购买成功通知商家
	public function sms_stock_shop($order_id){
        	$Stockorder = D('Stockorder')->find($order_id);
			$Stock = D('Stock')->find($Stockorder['stock_id']);
			$Users = D('Users')->find($Stockorder['user_id']);
			$Shop = D('Shop')->find($Stockorder['shop_id']);
			$config = D('Setting')->fetchAll();
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'sms_stock_shop',$Stockorder['shop_id'],$Shop['mobile'], array(
				        'shop_name' => $Shop['shop_name'],
					    'title' => $Stock ['title'],
                        'need_pay_price' => round($Stockorder['need_pay_price']/100,2),
 	
					));
            } else {
                $this->sendSms('sms_stock_shopp',$Stockorder['shop_id'], $Shop['mobile'], array(
					  'shop_name' => $Shop['shop_name'],
					  'title' => $Stock ['title'],
                      'need_pay_price' => round($Stockorder['need_pay_price']/100,2),
				));
            }
        return true;
    }
	
	//代理商申请城市审核通过会员
	public function is_open_user($city_id){
        	$city_id = (int) $city_id;
            $City = D('City')->find($city_id);
			$Users= D('Users')->where(array('user_id'=>$City['user_id']))->find();
            $config = D('Setting')->fetchAll();
            if ($config['sms']['dxapi'] == 'dy') {
                $this->DySms($config['site']['sitename'], 'is_open_user',$shop_id = '0',$Users['mobile'], array(
					'city_name' => $City['name'],//城市名称
					'user_name' => $Users['nickname'],
				));
            } else {
                $this->sendSms('is_open_user',$shop_id = '0', $Users['mobile'], array(
					'city_name' => $City['name'],//城市名称
					'user_name' => $Users['nickname'],
				));
            }
        return true;
    }	
	
	
	//KTV购买成功通知买家
    public function sms_ktv_notice_user($order_id){
        $KtvOrder = D('KtvOrder')->find($order_id);
        $Ktv = D('Ktv')->find($KtvOrder['ktv_id']);
        $Users = D('Users')->find($KtvOrder['user_id']);
        $config = D('Setting')->fetchAll();
		if($Users['mobile']){
			$mobile = $Users['mobile'];
		}else{
			$mobile = $KtvOrder['tel'];
		}
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_ktv_notice_user',$KtvOrder['shop_id'], $mobile, array(
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'title' => $Ktv['title'], 
				'code' => $KtvOrder['code'], 
				'price' => round($KtvOrder['price']/100,2)
			));
        } else {
            $this->sendSms('sms_ktv_notice_user',$KtvOrder['shop_id'], $mobile, array(
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'title' => $Ktv['title'], 
				'code' => $KtvOrder['code'], 
				'price' => round($KtvOrder['price']/100,2)			
			));
        }
        return true;
    }
	
	//KTV购买成功通知商家
    public function sms_ktv_notice_shop($order_id){
        $KtvOrder = D('KtvOrder')->find($order_id);
        $Ktv = D('Ktv')->find($KtvOrder['ktv_id']);
		$Shop = D('Shop')->find($KtvOrder['shop_id']);
        $config = D('Setting')->fetchAll();
		if($Shop['mobile']){
			$mobile = $Shop['mobile'];
		}else{
			$mobile = $Ktv['tel'];
		}
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_ktv_notice_shop',$KtvOrder['shop_id'], $mobile, array(
				'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
				'title' => $Ktv['title'], 
				'name' => $KtvOrder['name'],
				'mobile' => $KtvOrder['mobile'], 
				'price' => round($KtvOrder['price']/100,2)
			));
        } else {
            $this->sendSms('sms_ktv_notice_shop', $KtvOrder['shop_id'],$mobile, array(
				'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
				'title' => $Ktv['title'], 
				'name' => $KtvOrder['name'],
				'mobile' => $KtvOrder['mobile'], 
				'price' => round($KtvOrder['price']/100,2)	
			));
        }
        return true;
    }
	
	//KTV用户申请退款通知商家
    public function sms_ktv_refund_shop($order_id){
        $KtvOrder = D('KtvOrder')->find($order_id);
        $Ktv = D('Ktv')->find($KtvOrder['ktv_id']);
		$Shop = D('Shop')->find($KtvOrder['shop_id']);
        $config = D('Setting')->fetchAll();
		if($Shop['mobile']){
			$mobile = $Shop['mobile'];
		}else{
			$mobile = $Ktv['tel'];
		}
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_ktv_refund_shop',$KtvOrder['shop_id'], $mobile, array(
				'order_id' => $order_id, 
				'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
				'price' => round($KtvOrder['price']/100,2)
			));
        } else {
            $this->sendSms('sms_ktv_refund_shop',$KtvOrder['shop_id'], $mobile, array(
				'order_id' => $order_id, 
				'shop_name' => niuMsubstr($Shop['shop_name'], 0, 8, false), 
				'price' => round($KtvOrder['price']/100,2)		
			));
        }
        return true;
    }
	
	//KTV用户申请退款成功通知买家
    public function sms_ktv_refund_user($order_id){
        $KtvOrder = D('KtvOrder')->find($order_id);
        $Ktv = D('Ktv')->find($KtvOrder['ktv_id']);
        $Users = D('Users')->find($KtvOrder['user_id']);
        $config = D('Setting')->fetchAll();
		if($Users['mobile']){
			$mobile = $Users['mobile'];
		}else{
			$mobile = $KtvOrder['tel'];
		}
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_ktv_refund_user',$KtvOrder['shop_id'], $mobile, array(
				'order_id' => $order_id, 
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'price' => round($KtvOrder['price']/100,2)
			));
        } else {
            $this->sendSms('sms_ktv_refund_user',$KtvOrder['shop_id'], $mobile, array(
				'order_id' => $order_id, 
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'price' => round($KtvOrder['price']/100,2)			
			));
        }
        return true;
    }
	
  ////KTV处理过期订单通知买家
    public function sms_ktv_gotime_expired_user($order_id){
        $KtvOrder = D('KtvOrder')->find($order_id);
        $Ktv = D('Ktv')->find($KtvOrder['ktv_id']);
        $Users = D('Users')->find($KtvOrder['user_id']);
        $config = D('Setting')->fetchAll();
		if($Users['mobile']){
			$mobile = $Users['mobile'];
		}else{
			$mobile = $KtvOrder['tel'];
		}
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_ktv_gotime_expired_user', $KtvOrder['shop_id'],$mobile, array(
				'order_id' => $order_id, 
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'ktv_title' => niuMsubstr($Ktv['title'], 0, 8, false),
			));
        } else {
            $this->sendSms('sms_ktv_gotime_expired_user', $KtvOrder['shop_id'],$mobile, array(
				'order_id' => $order_id, 
				'user_name' => niuMsubstr($Users['nickname'], 0, 8, false), 
				'ktv_title' => niuMsubstr($Ktv['title'], 0, 8, false),
			));
        }
        return true;
    }
	
	 //用户预约商家通知买家
    public function sms_yuyue_notice_user($detail = array(),$mobile,$code){
        $config = D('Setting')->fetchAll();
		if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_yuyue_notice_shop', $shop_id = '0',$mobile, array(
				'shop_name' => niuMsubstr($detail['shop_name'], 0, 8, false), 
				'shop_tel' => $detail['tel'], 
				'shop_addr' => $detail['addr'], 
				'code' => $code
			));
       } else {
            D('Sms')->sendSms('sms_yuyue_notice_shop',$shop_id = '0', $mobile, array(
				'shop_name' => niuMsubstr($detail['shop_name'], 0, 8, false), 
				'shop_tel' => $detail['tel'], 
				'shop_addr' => $detail['addr'], 
				'code' => $code
			));
        }
        return true;
    }
	
	
	 //用户预约商家通知商家
    public function sms_yuyue_notice_shop($data = array(),$mobile){
        $config = D('Setting')->fetchAll();
		if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_yuyue_notice_shop',$shop_id = '0', $mobile, array(
				'name' => $data['name'], 
				'yuyue_time' => $data['yuyue_time'], 
				'mobile' => $data['mobile'], 
				'number' => $data['number'], 
				'yuyue_date' => $data['yuyue_date']
			));
       } else {
          D('Sms')->sendSms('sms_yuyue_notice_shop',$shop_id = '0', $mobile, array(
				'name' => $data['name'], 
				'yuyue_time' => $data['yuyue_time'], 
				'mobile' => $data['mobile'], 
				'number' => $data['number'], 
				'yuyue_date' => $data['yuyue_date']
			));
       }
        return true;
    }
	
	
	 //会员认领商家通知管理员
    public function sms_shop_recognition_admin($mobile,$shop_name,$name){ 
        $config = D('Setting')->fetchAll();
		if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_shop_recognition_admin',$shop_id = '0', $mobile, array(
				'shop_name' => niuMsubstr($shop_name, 0, 8, false),  
				'name' => $name
			));
       } else {
            D('Sms')->sendSms('sms_shop_recognition_admin',$shop_id = '0', $mobile, array(
				'shop_name' => niuMsubstr($shop_name, 0, 8, false),  
				'name' => $name
			));
        }
        return true;
    }
	
	
	
	 //认领商家通过审核给会员发送短信
    public function sms_shop_recognition_user($mobile,$user_name,$shop_name){ 
        $config = D('Setting')->fetchAll();
		if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_shop_recognition_user', $shop_id = '0',$mobile, array(
				'shop_name' => niuMsubstr($shop_name, 0, 8, false),  
				'user_name' => niuMsubstr($user_name, 0, 8, false),  
			));
       } else {
            D('Sms')->sendSms('sms_shop_recognition_user',$shop_id = '0', $mobile, array(
				'shop_name' => niuMsubstr($shop_name, 0, 8, false),  
				'user_name' => niuMsubstr($user_name, 0, 8, false),  
			));
        }
        return true;
    }
	
	 //后台账户异地登录通知管理员
    public function sms_admin_login_admin($mobile,$user_name,$time){ 
        $config = D('Setting')->fetchAll();
		if ($config['sms']['dxapi'] == 'dy') {
            D('Sms')->DySms($config['site']['sitename'], 'sms_admin_login_admin',$shop_id = '0', $mobile, array(
				'user_name' => niuMsubstr($user_name, 0, 8, false),  
				'time' => $time  
			));
       } else {
            D('Sms')->sendSms('sms_admin_login_admin', $shop_id = '0',$mobile, array(
				'user_name' => niuMsubstr($user_name, 0, 8, false),  
				'time' => $time 
			));
        }
        return true;
    }
	
	
	//新用户注册短信通知接口，支持扣除商家短信
    public function register($user_id,$mobile,$account,$password,$shop_id){
		$Shop = D('Shop')->find($shop_id);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'register',$shop_id, $mobile, array(
				'user_id' => $user_id, 
				'user_account' => niuMsubstr($account, 0, 8, false), 
				'user_password' => $password,
				'shop_name' =>niuMsubstr($Shop['shop_name'],0, 8, false),
			));
        } else {
            $this->sendSms('register',$shop_id, $mobile, array(
				'user_id' => $user_id, 
				'user_account' => niuMsubstr($account, 0, 8, false), 
				'user_password' => $password,
				'shop_name' =>niuMsubstr($Shop['shop_name'],0, 8, false),	
			));
        }
        return true;
    }
	
	
	//商家新闻，短信批量推送给会员
    public function sms_shop_news_push($detail,$mobile){
		$Shop = D('Shop')->find($shop_id);
        $config = D('Setting')->fetchAll();
        if ($config['sms']['dxapi'] == 'dy') {
            $this->DySms($config['site']['sitename'], 'sms_shop_news_push',$detail['shop_id'], $mobile, array(
				'news_title' => niuMsubstr($detail['title'],0, 8, false), //标题
				'news_source' => niuMsubstr($detail['source'], 0, 8, false), //作者
			));
        } else {
            $this->sendSms('sms_shop_news_push',$detail['shop_id'], $mobile, array(
				'news_title' => niuMsubstr($detail['title'],0, 8, false), //标题
				'news_source' => niuMsubstr($detail['source'], 0, 8, false), //作者
			));
        }
        return true;
    }
	
				
    public function fetchAll(){
        $cache = cache(array('type' => 'File', 'expire' => $this->cacheTime));
        if (!($data = $cache->get($this->token))) {
            $result = $this->order($this->orderby)->select();
            $data = array();
            foreach ($result as $row) {
                $data[$row['sms_key']] = $row;
            }
            $cache->set($this->token, $data);
        }
        return $data;
    }
}
