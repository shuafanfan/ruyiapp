<?php
class UsersBackersRewardLogModel extends CommonModel{
    protected $pk   = 'log_id';
    protected $tableName =  'user_backers_reward_log';
	//下级会员购买后给上级分成
	public function UsersBackersReward($order_id){
		$Order = D('Order')->find($order_id);
		$Users = D('Users')->find($Order['user_id']);
		$Userrank = D('Userrank')->where(array('rank_id'=>$Users['rank_id']))->find();
		
		$money = (int)(($Order['need_pay']*$Userrank['reward'])/100);
		$intro = '下级购买商品订单分成';
		if($money > 0){
			$data = array();
			$data['user_id'] = $Order['user_id'];
			$data['shop_id'] = $Order['shop_id'];
			$data['order_id'] = $order_id;
			$data['goods_name'] = D('Ordergoods')->get_mall_order_goods_name($order_id);
			$data['money'] = $money;
			$data['intro'] = $intro;
			$data['create_time'] = NOW_TIME;
			$data['create_ip'] = get_client_ip();
			$this->add($data);
			if($Users['fuid1']){
				D('Users')->addMoney($Users['fuid1'],$money,$intro);
			}
		}
	}
}