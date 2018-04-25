<?php

class EleorderModel extends CommonModel {
    protected $pk = 'order_id';
    protected $tableName = 'ele_order';
    protected $cfg = array(
        0 => '等待付款',
        1 => '等待配送',
        2 => '正在配送',
		3 => '等待退款',
		4 => '退款完成',		
        8 => '已完成',
    );
	public function getError() {
        return $this->error;
    }
    public function checkIsNew($uid, $shop_id) {
        $uid = (int) $uid;
        $shop_id = (int) $shop_id;
        return $this->where(array('user_id' => $uid, 'shop_id' => $shop_id, 'closed' => 0))->count();
    }

    public function getCfg() {
        return $this->cfg;
    }
	
	
		
	//退款逻辑封装
	public function ele_user_refund($order_id){
		$detail = $this->where('order_id =' . $order_id)->find();
		if(!$detail = $this->where('order_id =' . $order_id)->find()) {
           $this->error = '没有找到订单';
		   return false;
        }else{
			if(!$Shop = D('Shop')->find($detail['shop_id'])) {
			   $this->error = '没有找到该订单的商家信息';
			   return false;
			}else{
				if ($Shop['is_ele_pei'] == 1) {
					$DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();
					if ($DeliveryOrder['status'] != 1) {
						$this->error = '亲，当前状态不能退款啦';
						return false;
					}
					if(!$DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->setField('closed', 1)) {
						$this->error = '申请退款更新配送信息错误，请稍后再试';
						return false;
					}
			     }
				 
				if($this->where('order_id =' . $order_id)->setField('status', 3)){
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 3);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 3);
					return true;
				}else{
					$this->error = '更新退款状态失败';
					return false;
				}
			}
        }
    }
	 


    public function overOrder($order_id) {
		if($detail = D('Eleorder')->find($order_id)){
			if($detail['status'] != 2){
				return false;
			}else{
				$Ele = D('Ele')->find($detail['shop_id']);
				if (D('Eleorder')->save(array('order_id' => $order_id, 'status' => 8,'end_time' => NOW_TIME))) { //防止并发请求
					$Intro = '外卖订单结算';//获取结算说明
					
					D('Shopmoney')->insertData($order_id,$id ='0',$detail['shop_id'],$detail['settlement_price'],$type ='ele',$Intro);//结算给商家
					if($detail['settlement_price'] > 0) {
						D('Userguidelogs')->AddMoney($detail['shop_id'], $detail['settlement_price'], $order_id,$type = "ele");//推荐员分成
						D('Users')->integral_restore_user($detail['user_id'],$order_id,$id ='0',$detail['settlement_price'],$type ='ele');//外卖购物返利积分
					}
					
					$this->AddDeliveryIogistics($order_id);//结算配送费给配送员
					
					D('Eleorderproduct')->updateByOrderId($order_id);
					D('Ele')->updateCount($detail['shop_id'], 'sold_num'); //这里是订单数
					D('Ele')->updateMonth($detail['shop_id']);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 1,$status = 8);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 1,$status = 8);
					return true;
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
		
    }
	//给配送员给钱
	public function AddDeliveryIogistics($order_id){
		if($detail = D('Eleorder')->find($order_id)){
			$Ele = D('Ele')->find($detail['shop_id']);
        	$Shop = D('Shop')->find($detail['shop_id']);
			if($Shop['is_ele_pei'] == 1){
				if($detail['logistics_full_money']){//如果扣除的配送费一样分成
					D('Runningmoney')->add_delivery_logistics($order_id,$detail['logistics_full_money'],1);//配送费接口
					return true;
				}else{
					D('Runningmoney')->add_delivery_logistics($order_id,$Ele['logistics'],1);//配送费接口
					return true;
				}
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	
	public function ele_print($order_id,$addr_id) {	
			$order_id = (int) $order_id;
			$addr_id = (int) $addr_id;	
			$order = D('Eleorder')->find($order_id);
			if (empty($order))//没有找到订单返回假
            return false;
			if($order['is_daofu'] == 1){
				$fukuan = '货到付款';
			}else{
				$fukuan = '在线支付';
			}
            $member = D('Users')->find($order['user_id']);//会员信息
			if(!empty($addr_id)){
				$addr_id = $addr_id;	
			}else{
				$addr_id = $order['addr_id'];
			}
			$user_addr = D('Useraddr')->where(array('addr_id'=>$addr_id))->find();
			$shop_print = D('Shop')->where(array('shop_id'=> $order['shop_id']))->find();//商家信息
            $msg .= '@@2点菜清单__________NO:' . $order['order_id'] . '\r';
            $msg .= '店名：' . $shop_print['shop_name'] . '\r';
            $msg .= '联系人：' . $user_addr['name'] . '\r';
            $msg .= '电话：' . $user_addr['mobile'] . '\r';
            $msg .= '客户地址：' . $user_addr['addr'] . '\r';
            $msg .= '用餐时间：' . date('Y-m-d H:i:s', $order['create_time']) . '左右\r';
            $msg .= '用餐地址：' . $shop_print['addr'] . '\r';
            $msg .= '商家电话：' . $shop_print['tel'] . '\r';
            $msg .= '----------------------\r';
            $msg .= '@@2菜品明细\r';
            $products = D('Eleorderproduct')->where(array('order_id' => $order['order_id']))->select();
            foreach ($products as $key => $value) {
                $product = D('Eleproduct')->where(array('product_id' => $value['product_id']))->find();
                $msg		  .= ($key+1).'.'.$product['product_name'].'—'.($product['price']/100).'元'.'*'.$value['num'].'份\r';
            }
            $msg .= '----------------------\r';
            $msg .= '@@2支付方式：' . $fukuan . '\r';
            $msg .= '外送费用：' . $order['logistics'] / 100 . '元\r';
			
			$msg .= '菜品金额：' .'总价'. round($order['total_price'] / 100). '元-新单立减'.round($order['new_money'] / 100).'元-免配送费'.round($order['logistics_full_money'] / 100).'元-满减优惠'.round($order['full_reduce_price'] / 100).'元=应付金额'.round($order['need_pay'] / 100). '元\r';
			
            $msg .= '应付金额：' . $order['need_pay'] / 100 . '元\r';
			$msg .= '留言：'.$order['message'].'\r';
			return $msg;//返回数组
   }
   //打印接口中间件
   public function combination_ele_print($order_id,$addr_id) {	
  		    $order = D('EleOrder') -> where('order_id =' . $order_id) -> find();
			$shops = D('Shop') -> find($order['shop_id']);
			//外卖打印开始
			if($shops['is_ele_print'] ==1){
			  $msg = $this->ele_print($order['order_id'],$order['addr_id']);
			  $result = D('Print')->printOrder($msg, $shops['shop_id']);
			  $result = json_decode($result);
			  $backstate = $result -> state;
			  $ele = D('Ele') -> find($order['shop_id']);
			  if($ele['is_print_deliver'] ==1){//如果开启自动打印
				  if ($backstate == 1) {
						if($shops['is_ele_pei'] ==1){//1代表没开通配送确认发货步骤
							D('EleOrder')->where(array('order_id' =>$order_id)) -> save(array('status' => 2,'is_print'=>1,'orders_time' => NOW_TIME));
						}else{//如果是配送配送只改变打印状态
							 D('EleOrder') -> save(array('is_print'=>1), array("where" => array('order_id' => $order['order_id'])));
						}
					}	
			 }	
				
		    }
		  return true;
	  }
						
						
   public function ele_delivery_order($order_id,$wait = 0) {	
   			$order_id = (int) $order_id;
			if($wait == 0){
				$status = 1;
			}else{
				$status = 0;
			}
  			$order = D('Eleorder')->find($order_id);
			if (empty($order)){
				 return false;//没有找到订单返回假
			}
			D('Sms')->sms_delivery_user($order_id,$type=1);//短信通知配送员
			D('Weixintmpl')->delivery_tz_user($order_id,$type=1);//微信消息全局通知
			
			
			$DeliveryOrder = D('DeliveryOrder');
            $shops = D('Shop')->where(array('shop_id'=>$order['shop_id']))->find();
			
			if (!$Useraddr = D('Useraddr')->find($order['addr_id'])) {
				return false;//没有找到用户地址返回假
			}
			
			if ($ele = D('Ele')->find($order['shop_id'])) {
				if(!empty($ele['given_distribution'])){
					$is_appoint = 1;
				}else{
					$is_appoint  = 0;
				}
			}else{
				return false;//没有找到外卖商家返回假
			}
			if($order['logistics_full_money'] > 0){
				$logistics_price = $order['logistics_full_money'];
			}else{
				$logistics_price = $order['logistics'];
			}
			if ($shops['is_ele_pei'] == 1) {
				$deliveryOrder_data = array(
						'type' => 1, 
						'type_order_id' => $order['order_id'], 
						'delivery_id' => 0, 
						'shop_id' => $order['shop_id'],
						'city_id' => $shops['city_id'],
						'area_id' => $shops['area_id'], 
						'business_id' => $shops['business_id'],  
						'lat' => $shops['lat'], 
						'lng' => $shops['lng'],  
						'user_id' => $order['user_id'], 
						'shop_name' => $shops['shop_name'],
						'name' => $Useraddr['name'],
						'mobile' => $Useraddr['mobile'],
						'addr' => $Useraddr['addr'],
						'addr_id' => $order['addr_id'], 
						'address_id' => $order['address_id'], 
						'logistics_price' => $logistics_price, //订单配送费
						'intro' => $order['message'], //订单备注
						'is_appoint' => $is_appoint, //状态1位指定配送员
						'appoint_user_id' => $ele['given_distribution'], //指定配送员ID
						'create_time' => time(), 
						'update_time' => 0, 
						'status' => $status,
						'closed'=>0
					);
				$order_id = D('DeliveryOrder')->add($deliveryOrder_data);
			}
	}
	
	public function ele_month_num($order_id) {	
   	   $order_id = (int) $order_id;
       $Eleorderproduct = D('Eleorderproduct')->where('order_id =' . $order_id)->select();
       foreach ($Eleorderproduct as $k => $v) {
       	 D('Eleproduct')->updateCount($v['product_id'], 'sold_num', $v['num']);
		 D('Ele')->updateCount($v['shop_id'], 'sold_num', $v['num']);
       }
      return TRUE;
	}
	//订单导出获取订单状态
	public function get_export_ele_order_status($order_id) {	
   	   $order = D('Eleorder')->find($order_id);
       if($order['is_daofu'] ==1){
		   return '货到付款';
		}else{
			return $this->cfg[$order['status']];
		}
	}

	
	//订单导出获取订单的商品信息
	public function get_export_ele_order_product($order_id) {	
   	  $Eleorderproduct = D('Eleorderproduct')->where(array('order_id'=>$order_id))->select();
	  foreach ($Eleorderproduct as $k => $v) {
       	 $Eleorderproduct[$k]['name'] = $this->get_ele_product_name($v['product_id']);
		 $Eleorderproduct[$k]['num'] = $v['num'];
		 $Eleorderproduct[$k]['total_price'] = $v['total_price'];
      }
	  return  $Eleorderproduct[$k]['name'].'*'.$Eleorderproduct[$k]['num'].'='.$Eleorderproduct[$k]['total_price'];
	}
	
	//订单导出获取订单状态
	public function get_ele_product_name($product_id) {	
   	   $Eleproduct = D('Eleproduct')->find($product_id);
       return $Eleproduct['product_name'];
	}
	
	//获取用户等待时间
	public function get_wait_time($order_id) {	
   	   $Eleorder = D('Eleorder')->find($order_id);
       if($Eleorder){
		   $now_time = time();
		   $cha_time = $now_time-$Eleorder['pay_time'];
		   return  ele_wait_Time($cha_time);
		}else{
		   return  false;
		}
	}
	
	//获取用户等待时间分钟数
	public function get_wait_time_minutes($order_id) {	
   	   $Eleorder = D('Eleorder')->find($order_id);
       if($Eleorder){
		   $now_time = time();
		   $cha_time = $now_time-$Eleorder['pay_time'];
		   return  $cha_time/60;
		}else{
		   return  false;
		}
	}
	
	//获取当前订单是否达到免邮条件
	public function get_logistics($total_money,$shop_id){	
	   $Ele = D('Ele')->find($shop_id);
	   if($Ele['logistics_full'] > 10){
		   if($total_money >= $Ele['logistics_full']){
			   return  $Ele['logistics'];
			}else{
				return false; 
		    }
	   }else{
		  return false; 
	   }
	}
	
	//获取当前订单满减
	public function get_full_reduce_price($total_money,$shop_id){	
	   $Ele = D('Ele')->find($shop_id);
	   if($Ele['is_full'] == 1){
		   //第一种可能
		   if(!empty($Ele['order_price_full_1']) && !empty($Ele['order_price_full_2'])){
			   //中间
			   if($total_money >= $Ele['order_price_full_1'] && $total_money <= $Ele['order_price_full_2']){
				   if($Ele['order_price_reduce_1'] > 0){
					  return $Ele['order_price_reduce_1'];   
				   }
				}
				//大于第二个满减
				if($total_money >= $Ele['order_price_full_2']){
				   if($Ele['order_price_reduce_2'] > 0){
					  return $Ele['order_price_reduce_2'];   
				   }
				}
				if($total_money <= $Ele['order_price_full_1']){
				   return 0; //不返回
				}
			}
			//第二种可能
			if(!empty($Ele['order_price_full_1'])){
			   if($total_money >= $Ele['order_price_full_1']){
				   if($Ele['order_price_reduce_1'] > 0){
					  return $Ele['order_price_reduce_1'];   
				   }
				}
			   if($total_money <= $Ele['order_price_full_1']){
				   return 0; //不返回
				}
			}
			return 0; 
	   }else{
		  return 0; 
	   }
	}

	
	
}

