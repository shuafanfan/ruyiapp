<?php
class UsersBackersDiscountLogModel extends CommonModel{
    protected $pk   = 'log_id';
    protected $tableName =  'user_backers_discount_log';
	
	//推手会员等级折扣返利
	public function UsersBackersDiscount($order_id){
		$Order = D('Order')->find($order_id);
		$Users = D('Users')->find($Order['user_id']);
		$Userrank = D('Userrank')->where(array('rank_id'=>$Users['rank_id']))->find();
		$money = $Order['need_pay'] - (($Order['need_pay']*$Userrank['discount'])/100);
		$intro = '推手购物返利';
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
			$this->add($data);//写入数据库
			D('Users')->addMoney($Order['user_id'],$money,$intro);
		}
	}
	
}