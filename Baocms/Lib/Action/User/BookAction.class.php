<?php
class BookAction extends CommonAction {
	protected function _initialize(){
       parent::_initialize();
	   $this->assign('getTypes', D('BookOrder')->getType());//订单状态
    }
  
    public function index() {
        $status = (int) $this->_param('status');
		$this->assign('status', $status);
		$this->display(); 
    }
    
    public function loaddata() {
		$obj = D('BookOrder');
		import('ORG.Util.Page'); 
		$map = array('user_id' => $this->uid,'closed'=>0); 
		$status = (int) $this->_param('status');
		if ($status == 0 || empty($status)) { 
			$map['status'] = 0;
		}elseif ($status == 1) {    
			$map['status'] = 1;
		}elseif ($status == 2) {    
			$map['status'] = 2;
		}elseif ($status == 3) {    
			$map['status'] = 3;
		}elseif ($status == 4) {    
			$map['status'] = 4;
		}elseif ($status == 8) {    
			$map['status'] = 8;
		}
		$count = $obj->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
            die('0');
		}
		$list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
            if($attr = D('Shopcateattr')->where(array('attr_id'=>$v['attr_id']))->find()){
                $list[$k]['attr'] = $attr;
            }
			if($cate = D('Shopcate')->where(array('cate_id'=>$v['cate_id']))->find()){
                $list[$k]['cate'] = $cate;
            }
        }
		$this->assign('list', $list); 
		$this->assign('page', $show); 
		$this->display(); 
	}

    
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('BookOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{
           $detail['attr'] = D('Shopcateattr')->where(array('attr_id'=>$detail['attr_id']))->find(); 
           $detail['cate'] = D('Shopcate')->where(array('cate_id'=>$detail['cate_id']))->find();
           $this->assign('detail',$detail);
		   $this->assign('thumb', D('Bookphoto')->getPics($order_id)); 
           $this->display();
        }
    }


   //删除订单
   public function delete($order_id){
	   $obj = D('BookOrder');
       if(!$order_id = (int)$order_id){
           $this->fengmiMsg('订单不存在');
       }elseif(!$detail = $obj->find($order_id)){
           $this->fengmiMsg('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->fengmiMsg('非法操作订单');
       }elseif($detail['status'] != 0){
           $this->fengmiMsg('该订单无法删除');
       }else{
		    if(false == $obj->book_delete($order_id)) {
				$this->fengmiMsg($obj->getError());
			}else{
				$this->fengmiMsg('订单删除成功', U('book/index', array('status' => 1)));
			}
       }
   }
   
   //最新封装退款
    public function refund(){
        $order_id = I('order_id', 0, 'trim,intval');
        $obj = D('BookOrder');
		if(!$detail = $obj->where('order_id =' . $order_id)->find()) {
           $this->fengmiMsg('错误！');
        }elseif($detail['user_id'] != $this->uid) {
           $this->fengmiMsg('请不要操作他人的订单');
        }elseif($detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 3) {
           $this->fengmiMsg('当前订单状态不支持退款');
        }else{
			if(false == $obj->book_user_refund($order_id)) {//更新什么什么的
				$this->fengmiMsg($obj->getError());
			}else{
				$this->fengmiMsg('申请退款成功！', U('book/index', array('status' => 3)));
			}
		}
    }
   
  
}
