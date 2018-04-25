<?php
class IndexAction extends CommonAction{
    public function index(){
		$counts = array();
        $bg_time = strtotime(TODAY);
		$str = '-1 day';
        $bg_time_yesterday = strtotime(date('Y-m-d', strtotime($str)));
        $counts['gold_day'] = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time)), 'shop_id' => $this->shop_id))->sum('money');
        $counts['gold_day_yesterday'] = (int) D('Shopmoney')->where(array('create_time' => array(array('ELT', $bg_time), array('EGT', $bg_time_yesterday)), 'shop_id' => $this->shop_id))->sum('money');
		
		$this->assign('sms', D('Smsshop')->where(array('shop_id' =>$this->shop_id,'status'=>0,'type'=>'shop'))->find());
		
		$counts['order'] =  D('BookOrder')->where(array('status'=>'1','closed'=>'0'))->count();
		$counts['order_orders'] =  D('BookOrder')->where(array('shop_id'=>$this->shop_id,'status'=>'2','closed'=>'0'))->count();
		$counts['order_complete'] =  D('BookOrder')->where(array('shop_id'=>$this->shop_id,'closed'=>'8','closed'=>'0'))->count();
		
		$this->assign('counts', $counts);
        $this->display();
    }
   
}