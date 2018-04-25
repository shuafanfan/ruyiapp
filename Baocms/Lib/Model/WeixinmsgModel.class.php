<?php
class WeixinmsgModel extends CommonModel{
    protected $pk = 'msg_id';
    protected $tableName = 'weixin_msg';
	protected $_type = array(
		'1' => '外卖', 
		'2' => '商城', 
		'3' => '家政', 
		'4' => '抢购', 
		'5' => '农家乐',
		'6' => '酒店',
		'7' => '订座',
		'8' => 'KTV',
	);
	protected $_status = array(
		'0' => '下单成功', 
		'1' => '订单已支付', 
		'2' => '订单已发货', 
		'3' => '订单申请退款', 
		'4' => '订单已退款', 
		'8' => '订单已完成',
		'11' => '订单已取消',
	);
	
	protected $_Capital_Type = array(
		'1' => '余额可提现收入', 
		'2' => '积分', 
		'3' => '商户资金可提现收入', 
		'4' => '威望', 
	);
	
	//订单消息提醒OPENTM205109409
	//$order_id订单ID
	//$cate  1通知会员，2通知商家
	//$type  1外卖，2商城，3家政，4团购，5农家乐，6酒店，7订座，8KTV
	//$status 0取消订单，2订单已发货，3申请退款，4已同意退款，8订单已完成
    public function weixinTmplOrderMessage($order_id,$cate,$type,$status){
		    $remark = $this->_type[$type].''.$this->_status[$status].'详情请登录：http://' . $_SERVER['HTTP_HOST'];
			if($type == 1){
				$Eleorder = D('Eleorder')->where(array('order_id'=>$order_id))->find();
				$user_id = $this->getUserId($cate,$Eleorder['user_id'],$Eleorder['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $this->get_ele_order_product_name($order_id);
				$price = round($Eleorder['need_pay']/100,2).'元';
				$status =  $this->_status[$status];
			}elseif($type == 2){
				$Order = D('Order')->find($order_id);
				$user_id = $this->getUserId($cate,$Order['user_id'],$Order['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $this->get_mall_order_goods_name($order_id);
				$price = round($Order['need_pay']/100,2).'元';
				$status =  $this->_status[$status];
			}elseif($type == 3){
				$Appointorder = D('Appointorder')->find($order_id);
				$Appoint= D('Appoint')->find($Appointorder['appoint_id']);
				$user_id = $this->getUserId($cate,$Appointorder['user_id'],$Appointorder['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $Appoint['title'];
				$price = round($Appointorder['need_pay']/100,2).'元';
				$status =  $this->_status[$status];
			}elseif($type == 4){
				$Tuanorder = D('Tuanorder')->find($order_id);
			    $Tuan = D('Tuan')->find($Tuanorder['tuan_id']);
				$user_id = $this->getUserId($cate,$Tuanorder['user_id'],$Tuanorder['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $Tuan['title'];
				$price = round($Tuanorder['need_pay']/100,2).'元';
				$status =  $this->_status[$status];
			}elseif($type == 5){
				$Farmgorder = D('Farmorder')->find($order_id);
			    $Farm = D('Farm')->find($Farmorder['farm_id']);
				$user_id = $this->getUserId($cate,$Farmgorder['user_id'],$Farm['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $Farm['farm_name'];
				$price = $Farmgorder['amount'].'元';
				$status =  $this->_status[$status];
			}elseif($type == 6){
				$Hotelorder = D('Hotelorder')->where(array('order_id'=>$order_id))->find();
			    $Hotel = D('Hotel')->find($Hotelorder['hotel_id']);
				$Hotelroom = D('Hotelroom')->find($Hotelorder['room_id']);
				$user_id = $this->getUserId($cate,$Hotelorder['user_id'],$Hotel['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $Hotelroom['title'];
				$price = $Hotelorder['amount'].'元';
				$status =  $this->_status[$status];
			}elseif($type == 7){
				$Bookingorder = D('Bookingorder')->find($order_id);
			    $Booking = D('Booking')->find($Bookingorder['shop_id']); 
			    $Bookingroom = D('Bookingroom')->find($Bookingorder['room_id']); 
				$user_id = $this->getUserId($cate,$Bookingorder['user_id'],$Bookingorder['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $Bookingroom['name'];
				$price = round($Bookingorder['amount']/100,2).'元';
				$status =  $this->_status[$status];
			}elseif($type == 8){
				$KtvOrder = D('KtvOrder')->find($order_id);
			    $Ktv = D('Ktv')->find($KtvOrder['shop_id']); 
			    $KtvRoom = D('KtvRoom')->find($KtvOrder['room_id']); 
				$user_id = $this->getUserId($cate,$KtvOrder['user_id'],$KtvOrder['shop_id']);
				$first = $this->_type[$type].''.$this->_status[$status];
				$url = $this->getUrl($order_id,$cate,$type);
				$titie = $KtvRoom['title'];
				$price = round($KtvOrder['price']/100,2).'元';
				$status =  $this->_status[$status];
			}
            include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $data = array(
				'url' => $url, 
				'first' => $first, 
				'remark' => $remark, 
				'title' => $title, //商品名称
				'price' => $price , //价格
				'status' => $status,//状态
			);
            $_data = Wxmesg::order_message($data);
            Wxmesg::net($user_id,'OPENTM205109409', $_data);
			return true;
    }
	//获取发送微信模板消息的主体
	public function getUserId($cate,$user_id,$shop_id){
		if($cate == 1){
			return $user_id;
		}elseif($cate == 2){
			$detail = D('Shop')->where(array('shop_id'=>$shop_id))->find();
			return $detail['user_id'];
		}else{
			return 1;
		}
	}
	//获取订单支付信息的URL
	public function getUrl($order_id,$cate,$type){
		if($type == 1){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/eleorder/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/ele/eleorder';
			}
		}elseif($type == 2){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/goods/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/mart/order.html';
			}
		}elseif($type == 3){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/appoint/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/';
			}
		}elseif($type == 4){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/tuan/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/tuan/order';
			}
		}elseif($type == 5){//农家乐
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/farm/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/';
			}
		}elseif($type == 6){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/hotels/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/';
			}
		}elseif($type == 7){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/booking/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/';
			}
		}elseif($type == 8){
			if($cate == 1){
				return 'http://' . $_SERVER['HTTP_HOST'] . '/user/ktv/detail/order_id/' . $order_id . '.html';
			}else{
				return 'http://' . $_SERVER['HTTP_HOST'] . '/distributors/ktv/index';
			}
		}
		
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
	
	//会员Capital账户变动通知
	//$type  1余额，2积分，3商户资金，4威望
    public function weixinTmplCapital($type,$user_id,$capital,$intro){
			$first = $this->_Capital_Type[$type].'变动通知';
			$remark = $intro.'详情请登录：http://' . $_SERVER['HTTP_HOST'].'/user';
			$types = $this->_Capital_Type[$type];
			$time = date('Y-m-d H:i:s ', time());
			$url = 'http://' . $_SERVER['HTTP_HOST'] . '/user';
			if($type == 1){
				$capital = round($capital/100,2).'元';
			}elseif($type == 2){
				$capital = $capital.'分';
			}elseif($type == 3){
				$capital = round($capital/100,2).'元';
			}else{
				$capital = $capital.'威望';
			}
			include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $data = array(
				'url' => $url, 
				'first' => $first, 
				'remark' => $remark, 
				'types' =>  $types, //资金类型
				'capital' => $capital , //金额
				'time' => $time,//状态
			);
            $_data = Wxmesg::capital($data);
            Wxmesg::net($user_id,'OPENTM200465417', $_data);
			return true;
    }
	
	
	
	//分类信息推送
    public function weixin_tmpl_life_subscribe($detail,$user_id){
			$Users = D('Users')->find($user_id);
			$cates = D('Lifecate')->fetchAll();
			include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/wap/life/detail/'.$detail['life_id'].'/', 
				'first' => '分类信息推送', 
				'user_demand' => $cates[$detail['cate_id']]['cate_name'], //客户要求
				'user_name' => $Users['nickname'] , //客户名称
				'time' => date('Y-m-d H:i:s ', $detail['create_time']),//提出时间
				'remark' => '根据您的需求匹配：信息标题'.$detail['title'].'请点击下面的连接查看', 
			);
            $_data = Wxmesg::subscribe($data);
            Wxmesg::net($user_id,'OPENTM207467627', $_data);
			return true;
    }
	
	//分类信息推送
    public function weixin_shop_news_push($detail,$user_id){
			$Users = D('Users')->find($user_id);
			include_once 'Baocms/Lib/Net/Wxmesg.class.php';
            $data = array(
				'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/wap/shop/news_detail/'.$detail['news_id'].'/', 
				'first' => '商家新闻推送', 
				'user_demand' => $detail['title'], //客户要求
				'user_name' => $Users['nickname'] , //客户名称
				'time' => date('Y-m-d H:i:s ', $detail['create_time']),//提出时间
				'remark' => '作者'.$detail['source'].'给你推荐的新文章，标题'.$detail['title'].'请点击下面的连接查看', 
			);
            $_data = Wxmesg::subscribe($data);
            Wxmesg::net($user_id,'OPENTM207467627', $_data);
			return true;
    }
	
}