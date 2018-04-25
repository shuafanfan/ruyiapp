<?php


class BreaksorderModel extends CommonModel{
    protected $pk   = 'order_id';
    protected $tableName =  'breaks_order';
	
    //更新优惠买单销售接口
    public function settlement($order_id) {
        $order_id = (int) $order_id;
		$logs = D('Paymentlogs')->where(array('type'=>breaks,'order_id'=>$order_id))->find();//支付日志
		$order = D('Breaksorder')->find($order_id );//查询订单信息
		$shopyouhui = D('Shopyouhui')->find($order['shop_id']);//商家优惠信息
		$shop = D('Shop')->find($order['shop_id']);//商家信息
		
		$deduction = $this->get_deduction($shop['shop_id'],$order['amount'],$order['exception']);//网站扣除金额，暂时写到购买的会员余额
		
		$info = '优惠买单，支付记录ID：' . $logs['log_id'];
		$ip = get_client_ip();//IP
		
		
		
		if($shopyouhui['type_id'] == 0){//打折
			if(!empty($shopyouhui['deduction'])){
				$money = round(($order['need_pay'] - $deduction)*100,2);//商户实际到账
			}else{
				$money = round($order['need_pay']*100,2);	
			}
		}else{//满减
			if(!empty($shopyouhui['vacuum'])){
				$money = round(($order['need_pay'] - $deduction)*100,2);//商户实际到账
			}else{
				$money = round($order['need_pay']*100,2);	
			}	
		}

		
		//会员买单实际支付日志
		D('Usermoneylogs')->add(array(
          'user_id' => $order['user_id'],
          'money' => $logs['need_pay'],
          'create_time' => NOW_TIME,
          'create_ip' => $ip,
          'intro' => $info
        ));	
		
				
		D('Shopmoney')->insertData($logs['order_id'],$id ='0',$order['shop_id'],$money,$type ='breaks',$info);//结算给商家 
        return TRUE;
    }
	
	
    public function get_deduction($shop_id,$amount,$exception){
        $shopyouhui = D('Shopyouhui')->where(array('shop_id'=>$shop_id,'is_open'=>1,'audit'=>1))->find();
        $need = $amount - $exception;//应该计算的金额=消费总额-参与优惠
        if($shopyouhui['type_id'] == 0){
            $result_deduction = round($need *$shopyouhui['deduction']/10,2); //减去金额=总金额-不参与优惠金额*点数
        }else{
            $t = (int)$need/$shopyouhui['vacuum'];//$T是应付款除以网站抽成金额，比如100元，网站抽3元，这里的t就是百分之3
            $result_deduction = round($t*$need/10,2);//实际付款金额*百分比
        }
        return $result_deduction;//返回网站扣除金额
    }

					
}