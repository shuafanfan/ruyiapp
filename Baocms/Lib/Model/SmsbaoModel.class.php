<?php
class SmsbaoModel extends CommonModel{
    protected $pk   = 'sms_id';
    protected $tableName =  'sms_bao';
	//扣除短信
	public function ToUpdate($sms_id,$shop_id,$res){
		$data = array();
		$data['sms_id'] = $sms_id;
		$data['shop_id'] = $shop_id;
		$data['status'] = $res;
		$this->save($data);
		D('Smsshop')->where(array('type'=>'shop','status'=>'0','shop_id'=>$shop_id))->setDec('num');
		return true;
	}
	 
}