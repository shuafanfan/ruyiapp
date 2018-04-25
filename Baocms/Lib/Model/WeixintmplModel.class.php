<?php
class WeixintmplModel extends CommonModel{
	protected $pk   = 'tmpl_id';
    protected $tableName =  'weixin_tmpl';
	protected $_validate = array(
		array('title','2,20','模板标题2至10个字符！',Model::MUST_VALIDATE, 'length', Model::MODEL_BOTH),
		array('serial','/^\w{3,}$/','请输入正确的模板库编号！',Model::MUST_VALIDATE, 'regex', Model::MODEL_BOTH),
		array('status','0,1','状态值不合法,必须0或1！',Model::MUST_VALIDATE, 'in', Model::MODEL_BOTH),
		array('sort','/^\d{1,4}$/','排序值不合法！',Model::MUST_VALIDATE, 'regex', Model::MODEL_BOTH),
	);
	
	//抢单配送批量发送微信模板消息
	public function delivery_tz_user($order_id,$type){
		$order_id = array($order_id);
		$type = (int) $type;//0是商城，1是外卖，2是快递
		if($type == 0){
			$obj = D('Order');
			$info = '有新的商城订单啦~';
		}elseif($type == 1){
			$obj = D('Eleorder');
			$info = '有新的外卖订单啦~';
		}else{
			$obj = D('Express');
			$info = '有新的快递订单啦~';
		}
		$detail = $obj->find($order_id);
		$time = date("Y-m-d H:i:s",$detail['create_time']); //订单时间
		$delivery  = D('Delivery')->where(array('is_weixin'=>1))->select();
		
		$config = D('Setting')->fetchAll();
		
        foreach ($delivery as $v=>$val)  { 
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $_delivery_tz_user = array(//整体变更
                'url'       =>  $config['site']['host']."/delivery/lists/scraped.html",
                'topcolor'  =>  '#F55555',
                'first'     =>  $val['name'].'订单生成日期：'.$time .'',
                'remark'    =>  '更多信息,请登录'.$config['site']['sitename'].',将为您提供更多信息服务！',
                'nickname'  =>  $val['name'],
                'title'     =>  $info

         );
         $delivery_tz_user_data = Wxmesg::delivery_tz_user($_delivery_tz_user);
         $return = Wxmesg::net($val['user_id'], 'OPENTM400045127', $delivery_tz_user_data);//结束
       } 
        return true;
    }
	
	//配送员抢单通知用户
	public function delivery_qiang_tz_user($order_id,$delivery_id,$type,$status){
		$order_id = (int) ($order_id);
		$type = (int) $type;//0是商城，1是外卖，2是快递
		$status = (int) $status;//0是商城，1是外卖，2是快递
		if($status == 0){
			$status = '您的订单已被抢单了' ;
		}else{
			$status = '您的订单已被配送员设置为已完成' ;
		}
		$config_site_url = 'http://' . $_SERVER['HTTP_HOST'] . '/user/';
		$DeliveryOrder  = D('DeliveryOrder')->where(array('order_id'=>$order_id))->find();//配送订单信息
		$delivery  = D('Delivery')->where(array('user_id'=>$delivery_id))->find();//筛选配送员信息
		if($type == 0){
			$url = $config_site_url.'goods/detail/order_id/'.$DeliveryOrder['type_order_id'].'/';
			$order_name = $this->get_mall_order_goods_name($DeliveryOrder['type_order_id']);
			$order_id = $DeliveryOrder['type_order_id'];
		}elseif($type == 1){
			$url = $config_site_url.'eleorder/detail/order_id/'.$DeliveryOrder['type_order_id'].'/';
			$order_name = $this->get_ele_order_product_name($DeliveryOrder['type_order_id']);
			$order_id = $DeliveryOrder['type_order_id'];
		}else{
			//跑腿的不写
		}
		$config = D('Setting')->fetchAll();
        include_once "Baocms/Lib/Net/Wxmesg.class.php";
        $_delivery_qiang_tz_user = array(//整体变更
                'url'       =>  $url,
                'topcolor'  =>  '#F55555',
                'first'     =>  $status,
                'remark'    =>  '更多信息,请登录'.$config['site']['sitename'].',将为您提供更多信息服务！',
                'order_name' => $order_name, //商品名称
				'order_id' => $order_id, //订单ID
				'delivery_user_name' => $delivery['name'],//配送员姓名
				'delivery_user_mobile' => $delivery['mobile'], //配送员电话
         );
         $delivery_qiang_tz_user_data = Wxmesg::delivery_qiang_tz_user($_delivery_qiang_tz_user);
         $return = Wxmesg::net($DeliveryOrder['user_id'], 'OPENTM406590003', $delivery_qiang_tz_user_data);//结束
        return true;
    }
	
	
	
	//抢购下单微信通知
    public function weixin_notice_tuan_user($order_id,$user_id,$type){
            $Tuanorder = D('Tuanorder')->where(array('order_id'=>$order_id))->find();
		    $Tuan = D('Tuan')->find($order['tuan_id']);
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $notice_data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/user/tuan/detail/order_id/' . $order_id . '.html', 
				'first' => '亲,您的订单创建成功!', 
				'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'], 
				'order_id' => $order_id, 
				'title' => $Tuan['title'], 
				'num' => $Tuanorder['num'],
				'price' => round($Tuanorder['need_pay'] / 100, 2) . '元', 
				'pay_type' => $pay_type 
			);
			
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id, 'OPENTM202297555', $notice_data);
			return true;
    }
	
	//商城下单微信通知
    public function weixin_notice_goods_user($order_id,$user_id,$type){
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
			$Order = D('Order')->find($order_id);
			$num = D('Ordergoods')->where(array('order_id'=>$order_id))->sum('num');
			$goods_name = $this->get_mall_order_goods_name($order_id);//获取商城订单名称
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $notice_data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/user/goods/index/aready/' . $order_id . '.html', 
				'first' => '亲,您的订单创建成功!', 
				'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'], 
				'order_id' => $order_id, 
				'title' => $goods_name, 
				'num' => $num,
				'price' => round($Order['need_pay'] / 100, 2) . '元', 
				'pay_type' => $pay_type 
			);
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id,'OPENTM202297555', $notice_data);
			return true;
    }
	
	//订座下单微信通知
    public function weixin_notice_booking_user($order_id,$user_id,$type){
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
			$Bookingorder = D('Bookingorder')->find($order_id);
			$Booking = D('Booking')->find($Bookingorder['shop_id']);
			
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $notice_data = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/user/booking/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
				'order_id' => $order_id, 
				'title' => $Booking['shop_name'], 
				'num' => '1',
				'price' => round($Bookingorder['amount'] / 100, 2) . '元', 
				'pay_type' => $pay_type 
            );
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id, 'OPENTM202297555', $notice_data);
			return true;
    }
	
	//酒店下单微信通知
    public function weixin_notice_hotel_user($order_id,$user_id,$type){
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
			$Hotelorder = D('Hotelorder')->find($order_id);
			$Hotel = D('Hotel')->find($Hotelorder['hotel_id']);
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $notice_data = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/user/hotels/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
				'order_id' => $order_id, 
				'title' => $Hotel['hotel_name'], 
				'num' => '1',
				'price' => $Hotelorder['amount']. '元', 
				'pay_type' => $pay_type 
            );
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id, 'OPENTM202297555', $notice_data);
			return true;
    }
	
	//农家乐下单微信通知
    public function weixin_notice_farm_user($order_id,$user_id,$type){
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
			$Farmgorder = D('Farmorder')->find($order_id);
			$Farm = D('Farm')->find($Farmorder['farm_id']);
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $notice_data = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/user/farm/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
				'order_id' => $order_id, 
				'title' => $Farm['farm_name'], 
				'num' => '1',
				'price' => $Farmorder['amount'] . '元', 
				'pay_type' => $pay_type 
            );
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id, 'OPENTM202297555', $notice_data);
			return true;
    }
	
	//外卖下单微信通知
    public function weixin_notice_ele_user($order_id,$user_id,$type){
			if($type == 0){
				$pay_type = '货到付款' ;
			}else{
				$pay_type = '在线支付' ;
			}
			$order = D('Eleorder')->find($order_id);
            $product_name = $this->get_ele_order_product_name($order_id);
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $notice_data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/user/eleorder/detail/order_id/' . $order_id . '.html', 
				'first' => '亲,您的订单创建成功!', 
				'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'], 
				'order_id' => $order_id, 
				'title' => $product_name, 
				'num' => $order['num'],
				'price' => round($order['need_pay'] / 100, 2) . '元', 
				'pay_type' => $pay_type 
			);
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id,'OPENTM202297555', $notice_data);
			return true;
    }
	
	
	
	//根据订单ID获取外卖订单名称
	public function get_ele_order_product_name($order_id){
		    $order = D('Eleorder')->find($order_id);
            $product_ids = D('Eleorderproduct')->where('order_id=' . $order_id)->getField('product_id', true);
            $product_ids = implode(',', $product_ids);
            $map = array('product_id' => array('in', $product_ids));
            $product_name = D('Eleproduct')->where($map)->getField('product_name', true);
            $product_name = implode(',', $product_name);
			return $product_name;
		 
    }
	//根据订单ID获取商城订单名称
	public function get_mall_order_goods_name($order_id){
		    $Order = D('Order')->find($order_id);
			$goods_ids = D('Ordergoods')->where("order_id={$order_id}")->getField('goods_id', true);
			$goods_ids = implode(',', $goods_ids);
			$map = array('goods_id' => array('in', $goods_ids));
			$goods_name = D('Goods')->where($map)->getField('title', true);
			$goods_name = implode(',', $goods_name);
			return $goods_name;
		 
    }
	
	
	//会员提现，审核，拒绝，通知会员自己
 	 public function weixin_cash_user($user_id,$tpye){
		if($tpye ==1){
			$tpye_name = '您已经成功申请提现'; 
		}elseif($tpye ==2){
			$tpye_name = '您的提现已通过审核'; 
		}elseif($tpye ==3){
			$tpye_name = '您的提现被拒绝，请关注您的账户'; 
		}
		$Users = D('Users')->find($user_id);
		$t = time(); 
        include_once "Baocms/Lib/Net/Wxmesg.class.php";
        $_cash_data = array(
             'url'       =>  "http://".$_SERVER['HTTP_HOST']."/user/",
             'first'   => $tpye_name,
             'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
             'balance'  => '您的余额：'.round($Users['money']/100,2).'元',
             'time'   => '操作时间：'.$t,
          );
         $cash_data = Wxmesg::cash($_cash_data);
	      Wxmesg::net($user_id, 'OPENTM206909003', $cash_data);
		return true;
	}
	
	
	//后台自动审核微信提现，1会员申请，2商家申请
 	 public function weixin_cash_user_refund($user_id,$pay_price,$cash_id,$tpye){
		if($tpye ==1){
			$tpye_name = '尊敬的会员您已成功申请提现'; 
		}elseif($tpye ==2){
			$tpye_name = '尊敬的商家您已成功申请提现'; 
		}elseif($tpye ==3){
			$tpye_name = '您的提现被拒绝，请关注您的账户'; 
		}
		$Users = D('Users')->find($user_id);
		include_once "Baocms/Lib/Net/Wxmesg.class.php";
			$_data_cttuikuan = array(//整体变更
				'url' => "http://" . $_SERVER['HTTP_HOST'] . "/user/", 
				'topcolor' => '#F55555', 
				'first' => $tpye_name , 
				'payprice' => round($pay_price / 100, 2) . '元', 
				'orderno' => $cash_id, 
				'remark' => '微信系统需要审核，预计最迟5个工作日内会退回您支付的帐号，感谢您的支持！', 
			);
		$cttuikuan_data = Wxmesg::cttuikuan($_data_cttuikuan);
		$return = Wxmesg::net($user_id, 'TM00004', $cttuikuan_data);
		return true;
	}
	
	
	//积分兑换通知买家
    public function weixin_notice_jifen_user($exchange_id,$user_id){
            $detail = D('Integralexchange')->find($exchange_id);
			$goods = D('Integralgoods')->find($detail['goods_id']);
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $notice_data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/user/exchange/index.html', 
				'first' => '亲,您成功兑换积分商品!', 
				'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'], 
				'order_id' => $exchange_id, 
				'title' => $goods['title'], 
				'num' => '1',
				'price' => round($goods['price'] / 100, 2) . '分', 
				'pay_type' => '积分兑换' 
			);
            $notice_data = Wxmesg::place_an_order($notice_data);
            Wxmesg::net($user_id, 'OPENTM202297555', $notice_data);
			return true;
    }
	
	//积分兑换通知商家
    public function weixin_notice_jifen_shop($exchange_id,$user_id){
           $detail = D('Integralexchange')->find($exchange_id);
		   $Shop = D('Shop')->find($detail['shop_id']);
		   $goods = D('Integralgoods')->find($detail['goods_id']);
           include_once "Baocms/Lib/Net/Wxmesg.class.php";
           $_data_order_notice = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/', 
				'topcolor' => '#F55555', 
				'first' => '积分兑换商品通知', 
				'remark' => '尊敬的【'.$Shop['shop_name'].'】，您有一笔新兑换订单！', 
				'order_id' => $exchange_id, 
				'order_goods' => $goods['title'], 
				'order_price' => round($goods['price'] / 100, 2) . '分', 
				'order_ways' => '积分兑换', 
				'order_user_information' => '兑换人信息登录管理中心查看'
			);
            $order_notice = Wxmesg::order_notice_shop($_data_order_notice);
            $return = Wxmesg::net($Shop['user_id'], 'OPENTM401973756', $order_notice);
			return true;
    }
	
	//客户预约商家微信通知OPENTM206305152，1通知客户，2通知商家
    public function weixin_yuyue_notice($yuyue_id,$type){
            $detail = D('Shopyuyue')->find($yuyue_id);
			$Shop = D('Shop')->find($detail['shop_id']);
			if($type == 1){
				$user_id = $detail['user_id'];
				$first = '恭喜您成功预约';
				$url = 'http://' . $_SERVER['HTTP_HOST'] . '/user/yuyue/index.html';
			}else{
				$user_id = $Shop['user_id'];
				$first = '您有新的预约订单';
				$url = 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/yuyue/index.html';
			}
			
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $yuyue_notice_data = array(
				'url' => $url, 
				'first' => $first, 
				'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'], 
				'name' => $detail['name'], 
				'mobile' => $detail['mobile'], 
				'date' => $detail['yuyue_date'].'---'.$detail['yuyue_time'],
				'content' => $detail['content']
			);
            $_yuyue_notice_data = Wxmesg::yuyue($yuyue_notice_data);
            Wxmesg::net($user_id, 'OPENTM206305152', $_yuyue_notice_data);
			return true;
    }

	

}