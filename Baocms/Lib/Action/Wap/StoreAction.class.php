<?php

class StoreAction extends CommonAction{

    public function _initialize(){

        parent::_initialize();

        $this->lifecate = D('Lifecate')->fetchAll();

        $this->lifechannel = D('Lifecate')->getChannelMeans();

        $this->assign('lifecate', $this->lifecate);

        $this->assign('channel', $this->lifechannel);

        //统计商家分类数量代码开始

        $shopcates = D('Shopcate')->fetchAll();

        foreach ($shopcates as $key => $v) {

            if ($v['cate_id']) {

                $catids = D('Shopcate')->getChildren($v['cate_id']);

                if (!empty($catids)) {

                    $count = D('Shop')->where(array('cate_id' => array('IN', $catids), 'closed' => 0, 'audit' => 1, 'city_id' => $this->city_id))->count();

                } else {

                    $count = D('Shop')->where(array('cate_id' => $cat, 'closed' => 0, 'audit' => 1, 'city_id' => $this->city_id))->count();

                }

            }

            $shopcates[$key]['count'] = $count;

        }

        $this->assign('shopcates', $shopcates);

        //结束

    }


 
	
	public function getorder(){
      	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	if(empty($_POST['x'])||empty($_POST['y'])){
        	$data['status']=1;
          	$data['msg']="请传入xy坐标";
          	$this->ajaxReturn($data);
        }
      	
      	$to[0]=$_POST['x'];
      	$to[1]=$_POST['y'];
    	$info=D('users')->where(['token'=>$_POST['token']])->find();
    	$community_id=$info['community_id'];
      	$list=D('shop_order')
          	->join("bao_users on bao_users.user_id= bao_shop_order.do_user_id")
          	->join("bao_paddress on bao_paddress.id= bao_shop_order.address")
         	-> where(['bao_shop_order.community_id'=>$community_id,'bao_shop_order.status'=>1,'bao_shop_order.is_pay'=>1])
          	->field('bao_users.face,bao_shop_order.order_id,bao_shop_order.money,bao_shop_order.firstcate,bao_shop_order.secondcate,bao_shop_order.detail,bao_shop_order.x,bao_shop_order.y,bao_paddress.name,bao_paddress.tel,bao_paddress.area_str,bao_paddress.info')
          	->select();
     	//dump($list);die;
      	if(!empty($list)){
          	
            foreach($list as $k=>$v){
                $from[0]=$v['x'];
                $from[1]=$v['y'];
                $to[0]=$_POST['x'];
                $to[1]=$_POST['y'];
                $list[$k]['distance']=$this->get_distance($from,$to,$km=true,$decimal=2);
            }
          $data['status']=0;
          $data['list']=$list;
          $this->ajaxReturn($data);
        }else{
          $data['status']=1;
          $data['msg']="当前暂无订单";
          $this->ajaxReturn($data);
        }
    }
	

  
    public function get_distance($from,$to,$km=true,$decimal=2){ 
      	//$from=[30.3072442662,120.2561759949];
      	//$to=[30.3134684987,120.3737640381];
        //sort($from);  
        //sort($to);  
        $EARTH_RADIUS = 6370.996; // 地球半径系数  

        $distance = $EARTH_RADIUS*2*asin(sqrt(pow(sin( ($from[0]*pi()/180-$to[0]*pi()/180)/2),2)+cos($from[0]*pi()/180)*cos($to[0]*pi()/180)* pow(sin( ($from[1]*pi()/180-$to[1]*pi()/180)/2),2)))*1000;  

        if($km){  
            $distance = $distance / 1000;  
        }  
    	$a= round($distance, $decimal);
      	return $a;
	}
  	
  	public function sure_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单号";
          	$this->ajaxReturn($data);
        }
      	$list=D('shop_order')->where(['order_id'=>$_POST['order_id']])->find();
      	if($list['status']==1){    	
            $res=D('shop_order')->where(['order_id'=>$_POST['order_id']])->save(['status'=>2,'do_user_id'=>$user_id]);
            if(!empty($res)){

                $data['status']=0;
                $data['msg']='接单成功';
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='接单失败';
                $this->ajaxReturn($data);
            }	
        }elseif($list['status']==2){  	
                $data['status']=1;
                $data['msg']='接单已被抢';
                $this->ajaxReturn($data); 
        }else{
        		$data['status']=1;
                $data['msg']='接单失败';
                $this->ajaxReturn($data);
        }
      	
    }
  	
  	  public function finish_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单号";
          	$this->ajaxReturn($data);
        }    
        	//dump($user_id);die;
            $res=D('shop_order')->where(['order_id'=>$_POST['order_id'],'do_user_id'=>$user_id])->save(['status'=>3]);
            if(!empty($res)){
				$order=D('shop_order')->where(['order_id'=>$_POST['order_id']])->find();
              	$money=$order['money'];
              	$community_id=$order['community_id'];     	
              	$shop=D('users')->where(['user_id'=>$order['do_user_id']])->setInc('money',$money*0.9);
              	$customer=D('users')->where(['user_id'=>$order['user_id']])->setInc('money',$money*0.1*0.15);
              	$ditui=D('users')->where(['community_id'=>$community_id,'rank_id'=>3])->setInc('money',$money*0.1*0.15);
              	//$area=D('users')->where(['user_idcommunity_id'=>$community_id,'rank_id'=>4])->setInc('money',$money*0.1*0.15);
                $data['status']=0;
                $data['msg']='确认成功';
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='确认失败';
                $this->ajaxReturn($data);
            }	
     
      	
    }
   
  public function cancle_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单号";
          	$this->ajaxReturn($data);
        }    
        	//dump($user_id);die;
            $res=D('shop_order')->where(['order_id'=>$_POST['order_id'],'do_user_id'=>$user_id])->save(['status'=>4]);
            if(!empty($res)){
			
                $data['status']=0;
                $data['msg']='取消成功';
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='取消失败';
                $this->ajaxReturn($data);
            }	
     
      	
    }
  public function today_detail(){

           
        $filepath = 'count.txt';    
        $data['count']=file_get_contents($filepath);
      	$today=strtotime(date("Y-m-d"));
      	$tomorrow=strtotime(date("Y-m-d",strtotime("+1 day")));
      	$where['create_time']  = array('between',[$today,$tomorrow]);
      	$where['status'] = 5;
    	$data['num']=D('shop_order')->where('create_time>'.$today)->count();
      	
      	$this->ajaxReturn($data);
    }
  	
  	public function accept_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	$list=D('shop_order')->where(['do_user_id'=>$user_id])->select();
      	$list=D('shop_order')
          	->join("bao_users on bao_users.user_id= bao_shop_order.do_user_id")
          	->join("bao_paddress on bao_paddress.id= bao_shop_order.address")
         	-> where(['do_user_id'=>$user_id,'status'=>2])
          	->field('bao_users.face,bao_shop_order.order_id,bao_shop_order.money,bao_shop_order.firstcate,bao_shop_order.secondcate,bao_shop_order.detail,bao_paddress.name,bao_paddress.tel,bao_paddress.area_str,bao_paddress.info')
          	->select();
      	if(!empty($list)){

                $data['status']=0;
                $data['list']=$list;
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='暂无已接订单';
                $this->ajaxReturn($data);
         }
    }
  
  	public function allfinish_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	$list=D('shop_order')->where(['do_user_id'=>$user_id])->select();
      	$list=D('shop_order')
          	->join("bao_users on bao_users.user_id= bao_shop_order.do_user_id")
          	->join("bao_paddress on bao_paddress.id= bao_shop_order.address")
         	-> where(['do_user_id'=>$user_id,'status'=>3,'shop_closed'=>0])
          	->field('bao_users.face,bao_shop_order.order_id,bao_shop_order.money,bao_shop_order.firstcate,bao_shop_order.secondcate,bao_shop_order.detail,bao_paddress.name,bao_paddress.tel,bao_paddress.area_str,bao_paddress.info')
          	->select();
      	if(!empty($list)){

                $data['status']=0;
                $data['list']=$list;
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='暂无已接订单';
                $this->ajaxReturn($data);
         }
    }
  	
  	  public function del_order(){
    	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	$user_id=$this->getuid($_POST['token']);
      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单号";
          	$this->ajaxReturn($data);
        }    
        	//dump($user_id);die;
            $res=D('shop_order')->where(['order_id'=>$_POST['order_id'],'do_user_id'=>$user_id])->save(['shop_closed'=>1]);
            if(!empty($res)){
                $data['status']=0;
                $data['msg']='删除成功';
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='删除失败';
                $this->ajaxReturn($data);
            }	
     
      	
    }
  	
  	public function message(){
      	if(!$_POST['token']){
        	$return['status']=1;
     		$return['msg']='请传入用户token';
        	$this->ajaxReturn($return);
        }
      	$user=D('users')->where(['token'=>$_POST['token']])->find();
    	$msg=D('msg')->where(['user_id'=>$user['user_id']])->select();
      	if($msg){
        	$return['status']=0;
     		$return['msg']=$msg;
        	$this->ajaxReturn($return);
        }else{
        	$return['status']=2;
     		$return['msg']="该用户暂无消息";
        	$this->ajaxReturn($return);
        }
      	$return['status']=0;
     	$return['msg']=$msg;
        $this->ajaxReturn($return);
    }
  
  	public function del_msg(){
      	//$this->ajaxReturn($_POST);
    	if(!$_POST['token']){
        	$return['status']=1;
     		$return['msg']='请传入用户token';
        	$this->ajaxReturn($return);
        }
      	if(!$_POST['msg_id']){
        	$return['status']=1;
     		$return['msg']='请传入消息ID';
        	$this->ajaxReturn($return);
        }
      	
      	$arr=$_POST['msg_id'];
      	$arr=ltrim($arr,'[');
      	$arr=rtrim($arr,']');
      	$arr=explode(',',$arr);
      	//$arr['status']=0;
      	//$this->ajaxReturn($arr);
      	foreach($arr as $k=>$v){
        	$res=D('msg')->where(['msg_id'=>$v])->delete();
        }
      	//dump($res);die;
      	if($res){
        	$return['status']=0;
     		$return['msg']=$msg;
        	$this->ajaxReturn($return);
        }else{
        	$return['status']=1;
     		$return['msg']="删除失败";
        	$this->ajaxReturn($return);
        }
      
    
    }
  	
  	public function dianping(){
    	 if(!$_POST['token']){
        	$return['status']=1;
     		$return['msg']='请传入用户token';
        	$this->ajaxReturn($return);
        }
      	$shop_id=$this->getuid($_POST['token']);
      	$res=D('shop_dianping')->where(['shop_id'=>$shop_id])->select();
      	if($res){
        	$return['status']=0;
     		$return['dianping']=$res;
        	$this->ajaxReturn($return);
        }else{
        	$return['status']=1;
     		$return['dianping']="未找到点评";
        	$this->ajaxReturn($return);
        }
    }
  
  	public function get_order_address(){
    	  if(!$_POST['token']){
        	$return['status']=1;
     		$return['msg']='请传入用户token';
        	$this->ajaxReturn($return);
        }
      	      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单号";
          	$this->ajaxReturn($data);
        }
      	$order=D('shop_order')->where(['order_id'=>$_POST['order_id']])->find();
      	//dump($order);die;
      	$res=D('paddress')->where(['id'=>$order['address']])->find();
      	if($res){
        	$return['status']=0;
     		$return['dianping']=$res;
        	$this->ajaxReturn($return);
        }else{
        	$return['status']=1;
     		$return['dianping']="未找到地址";
        	$this->ajaxReturn($return);
        }
      	
      
    }
  	public function test(){
      	$data['status']=1;
    	$res=D('shop_order')->where(['closed'=>0])->save($data);
      	dump($res);
    }
}