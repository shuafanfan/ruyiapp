<?php
class LifeModel extends CommonModel{
    protected $pk = 'life_id';
    protected $tableName = 'life';
    protected $_validate = array(array(), array(), array());
    public function randTop(){
        $lifes = $this->where(array('audit' => 1, 'top_date' => array('EGT', TODAY)))->order(array('last_time' => 'desc'))->limit(0, 45)->select();
        shuffle($lifes);
        if (empty($lifes)) {
            return array();
        }
        $num = count($lifes) > 9 ? 9 : count($lifes);
        $keys = array_rand($lifes, $num);
        $return = array();
        foreach ($lifes as $k => $val) {
            if (in_array($k, $keys)) {
                $return[] = $val;
            }
        }
        return $return;
    }
	
	 
	public function get_life_list($city_id,$channel_id){
		$Lifecate = D('Lifecate')->fetchAll();
		$cates_ids = array();
            foreach ($Lifecate as $val) {
                if ($val['channel_id'] == $channel_id) {
                    $cates_ids[] = $val['cate_id'];
                }
            }
            if (!empty($cates_ids)) {
                $map['cate_id'] = array('IN', $cates_ids);
				
            }
			$order = array('top_date' => 'desc', 'last_time' => 'desc');
			$list = D('Life')->where($map)->order($order)->limit(0, 5)->select();
			foreach ($list as $k => $val) {
				$val['cate_name'] = $Lifecate[$val['cate_id']]['cate_name'];
				$list[$k] = $val;
			}
			return $list ;
	}
	
	//余额购买分类信息
	public function buyLifeDetails($life_id,$user_id){
		if(D('LifeBuy')->where(array('life_id'=>$life_id,'user_id'=>$user_id))->find()){
			$this->error = '请不要重复购买';
			return false;
		}
        if($detail = D('Life')->find($life_id)){
			if($detail['audit'] == 0){
				$this->error = '信息没有审核';
				return false;
			}elseif($detail['closed'] == 1){
				$this->error = '信息已被删除';
				return false;				
			}elseif($detail['user_id'] == $user_id){
				$this->error = '您不能购买自己的信息';
				return false;				
			}else{
				$user = D('Users')->find($user_id);
				if($user['money'] < $detail['money']){
					$this->error = '您余额不足';
					return false;
				}else{
					$data['life_id'] = $life_id;
					$data['city_id'] = $detail['city_id'];
					$data['user_id'] = $user_id;
					$data['money'] = $detail['money'];
					$data['create_time'] = NOW_TIME;
					$data['create_ip'] = get_client_ip();
					if(D('LifeBuy')->add($data)){
						D('Users')->addMoney($user_id, -$detail['money'] ,'购买分类信息：【'.$detail['title'].'】花费金额');
						D('Users')->addMoney($detail['user_id'], $detail['money'] ,$user['nickname'].'购买您分类信息：【'.$detail['title'].'】结算金额');
						D('Life')->updateCount($life_id, 'buy_num');//增加购买量
						return true;
					}else{
						$this->error = '更新数据库失败';
						return false;
					}
				}
			}
		}else{
			$this->error = '信息不存在';
			return false;
		}	
	}
	
	//积分订阅
	public function subscribeLife($life_id,$user_id){
		$detail = D('Life')->find($life_id);
		
		if(D('LifeSubscribe')->where(array('cate_id'=>$detail['cate_id'],'user_id'=>$user_id))->find()){
			$this->error = '请不要重复订阅';
			return false;
		}
		if($detail['cate_id']){
			$data = array();
			$data['city_id'] = $detail['city_id'];
			$data['area_id'] = $detail['area_id'];
			$data['business_id'] = $detail['business_id'];
			$data['user_id'] = $user_id;
			$data['cate_id'] = $detail['cate_id'];
			$data['create_time'] = NOW_TIME;
			$data['create_ip'] = get_client_ip();
			if(D('LifeSubscribe')->add($data)){
				return true;
			}else{
				$this->error = '更新数据库失败';
				return false;
			}
		}else{
			$this->error = '您订阅的分类不存在';
			return false;
		}
	}
}