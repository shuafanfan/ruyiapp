<?php
class UserprofitlogsModel extends CommonModel {
    protected $pk = 'log_id';
    protected $tableName = 'user_profit_logs';
	
	protected $Type = array(
        'goods' => '商城',
		'appoint' => '家政',
		'tuan' => '抢购',
		'ele' => '外卖',
		'booking'  => '订座',
		'breaks'=>'优惠买单',
		'hotel' =>'酒店',
		'farm'=>'农家乐', 
    );
	
	protected $separate = array(
        1 => '已分成',
        2 => '已取消',
    );

    public function getType() {
        return $this->Type;
    }

    public function getSeparate() {
        return $this->separate;
    }
	
	//反转数组
	public function get_money_type($type) {
		$types = $this->getType();
		$result = array_flip($types);//反转数组
		$types = array_search($type, $result);
		if(!empty($types)){
			return $types;
		}else{
			return false;
		}
        return false;
	}
	
	
	protected $_type = array(
		'tuan' => '抢购', 
		'farm' => '农家乐', 
		'goods' => '商城', 
		'booking' => '订座', 
		'hotel' => '酒店',
		'Appoint' => '家政',
	);
	
	 //新版N级分销，二开QQ  120+585+022
	public function profitUsers($order_id,$id,$shop_id, $jiesuan_price, $type){
		//p($order_id.'----'.$id.'----'.$shop_id.'----'.$jiesuan_price.'----'. $type);die;
		$config = D('Setting')->fetchAll();
		$Shop = D('Shop')->where(array('shop_id'=>$shop_id))->find();
		if($Shop['is_profit']){
			list($user_id,$money)= $this->getModelMoneyUser($order_id,$id,$jiesuan_price, $type);
			$obj = D('Users');
			$Users = $obj->find($user_id);
			if($money > 0){
				if($Users['fuid1']){
					$money1 = round($config['profit']['profit_rate1'] * $money / 100);
					if($money1 > 0){
						$info1 = $this->_type[$type]. '订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2);
						$fuser1 = $obj->find($goods['fuid1']);
						if ($fuser1){
							$obj->addMoney($Users['fuid1'], $money1, $info1);
							$obj->addProfit($Users['fuid1'], $order_type = 0, $type, $order_id, $shop_id,$money1, 1);
						}
					}
				}
				
				if($Users['fuid2']){
					$money2 = round($config['profit']['profit_rate2'] * $money / 100);
					if($money2 > 0){
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2 / 100, 2);
						$fuser2 = $obj->find($goods['fuid2']);
						if ($fuser2){
							$obj->addMoney($Users['fuid2'], $money2, $info2);
							$obj->addProfit($Users['fuid2'], $order_type = 0,$type, $order_id,$shop_id, $money2, 1);
						}
					}
				}
				
				if($Users['fuid3']){
					$money3 = round($config['profit']['profit_rate3'] * $money / 100);
						if($money2 > 0){
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3 / 100, 2);
						$fuser3 = $obj->find($goods['fuid3']);
						if ($fuser3){
							$obj->addMoney($Users['fuid3'], $money3, $info3);
							$obj->addProfit($Users['fuid3'], $order_type = 0,$type, $order_id,$shop_id, $money3, 1);
						}
					}
				}
				
			}
		}
		
	}
   //获取会员ID，金额，模型
   public function getModelMoneyUser($order_id,$id,$jiesuan_price, $type){
	    $config = D('Setting')->fetchAll();
		if($type == 'ele'){
			if($config['profit']['profit_is_ele']){
				$order = D('Eleorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['need_pay'] - $order['settlement_price'];
				}else{
					$money = 0;
				}
				D('Eleorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			}
		}elseif($type == 'farm'){
			if($config['profit']['profit_is_farm']){
				$order = D('FarmOrder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount']*100;
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['jiesuan_amount']*100;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = ($order['amount'] - $order['jiesuan_amount'])*100;
				}else{
					$money = 0;
				}
				D('FarmOrder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			 }
		}elseif($type == 'goods'){
			if($config['profit']['profit_is_goods']){
				$Order = D('Order')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $Order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $jiesuan_price;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $Order['need_pay']- $jiesuan_price;
				}else{
					$money = 0;
				}
				return array($Order['user_id'],$money);
			}
		}elseif($type == 'tuan'){
			if($config['profit']['profit_is_tuan']){
				$Tuancode = D('Tuancode')->find($id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $Tuancode['real_money'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $Tuancode['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $Tuancode['real_money']-$Tuancode['settlement_price'];
				}else{
					$money = 0;
				}
				D('Tuancode')->save(array('code_id' => $id, 'is_profit' => 1));	
				return array($Tuancode['user_id'],$money);
			}
		}elseif($type == 'booking'){
			if($config['profit']['profit_is_booking']){
				$order = D('Bookingorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['amount'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['amount'];
				}else{
					$money = 0;
				}
				D('Bookingorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			}
		}elseif($type == 'hotel'){
			if($config['profit']['profit_is_hotel']){
				$order = D('Hotelorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount']*100;
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['jiesuan_amount']*100;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = ($order['amount'] - $order['jiesuan_amount'])*100;
				}else{
					$money = 0;
				}
				D('Hotelorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			 }
		}
		
	
   }
   

}
