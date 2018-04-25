<?php
class  UserguidelogsModel extends CommonModel{
     protected $pk   = 'log_id';
     protected $tableName =  'user_guide_logs';
	 
	 public function AddMoney($shop_id, $price, $order_id,$type){
	  //找到分成会员ID，分成，循环写入日志
	  $list = D('Shopguide')->where(array('shop_id'=>$shop_id))->select();
	  if(!empty($list)){
		  foreach ($list as $key => $var) {
			  if(!empty($var['user_id']) && $var['rate'] > 0){
				  $money = ($price * $var['rate'])/1000;
				  $intro = '会员推荐商家分成结算';
				  if ($money > 0) {
					$this->add(array(
						'shop_id' => $shop_id, 
						'user_id' => $var['user_id'], 
						'guide_id' => $var['guide_id'], 
						'order_id' => $order_id, 
						'type' => $type, 
						'money' => $money, 
						'create_time' => NOW_TIME, 
						'create_ip' => get_client_ip(), 
						'intro' => $intro
					));
					 D('Users')->addMoney($var['user_id'], $money,$intro);  //写入会员余额
				  }
			  }
		  }
	  }else{
		  return true;
     }
	  return true;
	}
}