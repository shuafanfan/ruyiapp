<?php
class RunningmoneyModel extends CommonModel{
    protected $pk   = 'money_id';
    protected $tableName =  'running_money';
	
	//写入外卖配送费
	public function add_delivery_logistics($order_id,$logistics,$type){
		if($type ==1){
			$map = (array('type_order_id'=>$order_id,'type'=>1));
		}else{
			$map = (array('type_order_id'=>$order_id,'type'=>0));
		}
		$detail = D('DeliveryOrder')->where($map)->find();	
		$Shop = D('Shop')->find($detail['shop_id']);
		$info = '外卖订单ID'.$order_id.'结算给配送员运费'.round($detail['logistics_price']/100,2).'元';
		if(!empty($detail) && !empty($Shop)){
			if ($detail['logistics_price'] > 0) {
                    $this->add(array(
						   'running_id' => $order_id, 
						   'delivery_id' => $detail['delivery_id'], 
						   'user_id' => $detail['user_id'], 
						   'money' => $detail['logistics_price'], 
						   'type' => ele, 
						   'create_time' => NOW_TIME, 
						   'create_ip' => get_client_ip(), 
						   'intro' => $info
					));
                    D('Users')->addMoney($detail['delivery_id'], $detail['logistics_price'],$info);  //写入配送员余额
               }
             return true;
		}else{
			return true;
		}
        return true;
    }
	
	
	//写入商城配送费
	public function add_express_price($order_id,$express_price,$type){
		$detail = D('Order')->find($order_id);	
		$Shop = D('Shop')->find($detail['shop_id']);
		$do = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type'=>0))->find();	
		$info = '商城订单ID'.$order_id.'结算给配送员运费'.round($do['logistics_price']/100,2).'元';
		if($detail){
			if ($do['logistics_price'] > 0) {
                    $this->add(array(
						   'running_id' => $order_id, 
						   'delivery_id' => $do['delivery_id'], 
						   'user_id' => $detail['user_id'], 
						   'money' => $do['logistics_price'], 
						   'type' => 'goods', 
						   'create_time' => NOW_TIME, 
						   'create_ip' => get_client_ip(), 
						   'intro' => $info
					));
                    D('Users')->addMoney($do['delivery_id'], $do['logistics_price'],$info);  //写入配送员余额
               }
             return true;
		}else{
			return true;
		}
        return true;
    }

}