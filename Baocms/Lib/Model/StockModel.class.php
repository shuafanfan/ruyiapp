<?php
class StockModel extends CommonModel{
    protected $pk = 'stock_id';
    protected $tableName = 'stock';
    protected $cfg = array(
        0 => '未付款',
        1 => '已付款',
    );
	public function getCfg() {
        return $this->cfg;
    }
	public function getError() {
        return $this->error;
    }
	
	//智能付款返回订单ID
    public function get_order_id($stock_id, $user_id, $num){
		if(!$detail = $this->find($stock_id)){
			$this->error = '你所购买的股权商品不存在';
			return false;
		}elseif($detail['closed'] == 1){
			$this->error = '你所购买的股权商品被删除';
			return false;
		}elseif($detail['num'] <= 0){
			$this->error = '你所购买的股权商品已售空';
			return false;
		}elseif($num >= $detail['num']){
			$this->error = '少买一点吧，当前剩余库存'.$detail['num'];
			return false;
		}else{
			$aux = D('Usersaux')->find($user_id);
			if($aux['audit'] ==1){
				$stock_number = $this->get_stock_number($user_id, $num);
				$order_id = D('Stockorder')->add(array(
					'stock_id' => $stock_id, 
					'user_id' => $user_id, 
					'shop_id' => $detail['shop_id'], 
					'num' => $num, 
					'price' =>$detail['price'], 
					'need_pay_price' => $num * $detail['price'], 
					'status' => 0, 
					'stock_number' => $stock_number,
					'name' => $aux['name'],
					'mobile' => $aux['mobile'],
					'card_id' => $aux['card_id'],
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
				));
				if($order_id){
					return $order_id;
				}else{
					$this->error = '获取订单编号失败';
					return false;
				}
			
			}else{
				$this->error = '对不起，你还未通过实名认证，请认证购购买';
				return false;
			}
			$this->error = '未知错误，请稍后在测试';
			return false;
		}
    }
	//获取编号
	public function get_stock_number($user_id, $num){
        $code = D('Tuancode')->getCode();
		$stock_number = $code;
        return $stock_number;
    }
	
	//股权扣费直接扣费
    public function pay_stock_status($stock_id, $user_id, $num,$order_id){
        $detail = $this->find($stock_id);
		$order = D('Stockorder')->find($order_id);
        if (false !== D('Users')->addMoney($user_id, -$num * 100, '股权商品' . $detail['title'] . '购买，扣费成功')) {
			if($order_id){
				if (D('Stockorder')->save(array('order_id' => $order_id, 'status' => '1'))) {
					$this->save_stock_num($order['stock_id'],$order['num']);
					return true; 
				 }else{
					return false;	 
				}
			}else{
				return false;
			}
        } else {
            return false;
        }
    }
	 //股权在线付款回调
    public function save_stock_logs_status($order_id){
		$order = D('Stockorder')->find($order_id);
        if (!empty($order_id)) {
			if (D('Stockorder')->save(array('order_id' => $order_id, 'status' => '1'))) {
				$this->save_stock_num($order['stock_id'],$order['num']);
				return true; 
			}
        }else{
			return true; 
           
        }
    }
	 //付款后减去库存
    public function save_stock_num($stock_id,$num){
        $detail = $this->find($stock_id);
        if (!empty($detail)) {
			$this->where(array('stock_id'=>$stock_id))->setInc('sold_num',$num);
			$this->where(array('stock_id'=>$stock_id))->setDec('num',$num);
			D('Sms')->sms_stock_user($order_id);//会员购买通知买家
			D('Sms')->sms_stock_shop($order_id);//会员购买通知商家
			return true; 
        }else{
			return true; 
        }
    }
 
}