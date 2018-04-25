<?php

class WeixinAction extends CommonAction {
	public function _initialize() {
        parent::_initialize();
		if($this->workers['tuan'] != 1){
          $this->error('对不起，您无权限，请联系掌柜开通');
        }
		
    }


    public function tuan() {
		$json = $_POST["snstr"];
		$jsonarr = explode('/',$json);
		if(!empty($json)){
			$code_id = $jsonarr['7'];
		}else{
			$code_id = (int) $this->_param('code_id');
		}
		$user_id = D('Users')->where(array('user_id'=>$this->uid))->getField('user_id');
		$worker = D('Shopworker')->where(array('user_id'=>$user_id))->find();
		if(empty($worker)){
			$this->error('您不属于任何一个店铺的授权员工，无权进行管理！', U('index/index'));
		}
		if(empty($worker['status']) || $worker['status'] !=1 ){
			$this->error('您的员工信息还处于待通过状态，无权进行操作！', U('worker/index/index'));
		}
		if(empty($data)){
			$this->error('没有找到对应的团购券信息！', U('worker/index/index'));
		}
		if($data['shop_id'] != $worker['shop_id'] || $worker['tuan']!=1){
			$this->error('您不属于该公司的授权员工，无法进行管理！', U('worker/index/index'));
		}
		$obj = D('Tuancode');
		if($detail = $obj->find(array('where' => array('code' => $code_id)))){
		 	$shop = D('Shop')->find(array('where' => array('shop_id' => $detail['shop_id'])));
            if (!empty($detail) && $detail['shop_id'] == $this->shop_id && (int) $detail['is_used'] == 0 && (int) $detail['status'] == 0) {
				$data = array();
				$data['is_used'] = 1;
				$data['worker_id'] = $this->uid;
				$data['used_time'] = NOW_TIME;
				$data['used_ip'] = get_client_ip();
             	if($obj->where(array('code_id'=>$detail['code_id']))->save($data)){
					 $res = $obj->saveShopMoney($detail,$shop);//统一更新
                     if($res == 1){
						$return[$var] = $var;
                        $this->success('团购券'.$code_id.'消费成功！',U('worker/index/index'));
                     } else {
                         $this->success('到店付团购券'.$code_id.'消费成功！',U('worker/index/index'));
                     }
                } else {
					$this->error('该抢购券无效');
               }
		}else{
			$this->error('未知错误');
		}
     }
  }
	
    public function coupon() {
       
       
		$user_id = D('Users')->where(array('user_id'=>$this->uid))->getField('user_id');
		$worker = D('Shopworker')->where(array('user_id'=>$user_id))->find();
		
		
		
		if(empty($worker)){
			$this->error('您不属于任何一个店铺的授权员工，无权进行管理！', U('worker/index/index'));
		}
		if(empty($worker['status']) || $worker['status'] !=1 ){
			$this->error('您的员工信息还处于待通过状态，无权进行操作2！', U('worker/index/index',array('worker_id'=>$worker['worker_id'])));
		}
		
		$download_id = (int) $this->_param('download_id');
		$obj = D('Coupondownload');
		$data = $obj->find($download_id);

		if(empty($data)){
			$this->error('没有找到对应的优惠券信息！', U('worker/index/index'));
		}

		if($data['shop_id'] != $worker['shop_id'] || $worker['coupon']!=1){
			$this->error('您不属于该公司的授权员工，无法进行管理！', U('worker/index/index'));
		}

		if ((int) $data['is_used'] == 0 ) {
			$ip = get_client_ip();
			$result = $obj->save(array('download_id' => $data['download_id'], 'is_used' => 1, 'used_time' => time(), 'used_ip' => $ip));
			if($result){
				$this->success('优惠劵'.$code_id.'验证成功！',U('worker/index/index'));
			}else{
				$this->error('该优惠券验证失败！3',U('worker/index/index'));
			}
			p($result);die;
		}else{
			$this->error('该优惠券已经使用过了，验证失败！',U('worker/index/index'));
		}


	}
	

	
	

}
