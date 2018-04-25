<?php

class GoodsModel extends CommonModel{
    protected $pk   = 'goods_id';
    protected $tableName =  'goods';
	 protected $_validate = array(
        array( ),
        array( ),
        array( )
    );
	
	public function getError() {
        return $this->error;
    }

    public function _format($data){
        $data['save'] =  round(($data['price'] - $data['mall_price'])/100,2);
        $data['price'] = round($data['price']/100,2);
		//多属性开始
		$data['mobile_fan'] = round($data['mobile_fan']/100,2);
		//多属性结束
        $data['mall_price'] = round($data['mall_price']/100,2); 
        $data['settlement_price'] = round($data['settlement_price']/100,2); 
        $data['commission'] = round($data['commission']/100,2); 
        $data['discount'] = round($data['mall_price'] * 10 / $data['price'],1);
        return $data;
    }
	
	//计算用户下单返回多少积分传2个参数，商品id商品类型
    public function get_forecast_integral_restore($id,$type){
        $config = D('Setting')->fetchAll();
		if($config['integral']['is_restore'] == 1){
			if($type == 'goods'){
				$Goods = D('Goods')->find($id);
				if($config['integral']['is_goods_restore'] == 1){
					if($config['integral']['restore_type'] == 1){
						$integral = $Goods['mall_price'];
					}elseif($config['integral']['restore_type'] == 2){
						$integral = $Goods['settlement_price'];
					}elseif($config['integral']['restore_type'] == 3){
						$integral = $Goods['mall_price']- $Goods['settlement_price'];
					}else{
						$integral = 0;
					}
				}else{
					return false;
				}
			}elseif($type == 'tuan'){
				$Tuan = D('Tuan')->find($id);
				if($config['integral']['is_tuan_restore'] == 1){
					if($config['integral']['restore_type'] == 1){
						$integral = $Tuan['tuan_price'];
					}elseif($config['integral']['restore_type'] == 2){
						$integral = $Tuan['settlement_price'];
					}elseif($config['integral']['restore_type'] == 3){
						$integral = $Tuan['tuan_price']- $Tuan['settlement_price'];
					}else{
						$integral = 0;
					}
				}else{
					return false;
				}
			}
			if($integral > 0){
				if($config['integral']['restore_points'] > 100){
					if($config['integral']['restore_points']){
						$integral = $integral - (($integral * $config['integral']['restore_points'])/100);
						return int($integral/100);
					}else{
						return false;
					}
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
		
    }
	
	//这里暂时没有判断多属性的问题，后期再判断
	public function check_add_use_integral($use_integral,$mall_price){
        $config = D('Setting')->fetchAll();
        $integral = $config['integral']['buy'];
		if($integral == 0){
			if ($use_integral % 100 != 0) {
				$this->error = '积分必须为100的倍数';
				return false;
			}
			if ($use_integral > $mall_price) {
				$this->error = '积分兑换数量必须小于'.$mall_price.','.'并是100的倍数';
				return false;
			}
		}elseif($integral == 10){
			if ($use_integral % 10 != 0) {
				$this->error = '积分必须为10的倍数';
			}
			if ($use_integral*10 > $mall_price) {
				$this->error = '积分兑换数量必须小于'.($mall_price/10).','.'并是10的倍数';
				return false;
			}
		}elseif($integral == 100){
			if ($use_integral % 1 != 0) {
				$this->error = '积分必须为1的倍数';
				return false;
			}
			if ($use_integral*100 > $mall_price) {
				$this->error = '积分兑换数量必须小于'.($mall_price/100).','.'并是1的倍数';
				return false;
			}	
		}else{
			$this->error = '后台设置的消费抵扣积分比例不合法';
			return false;
		}
		return true;
    }

}