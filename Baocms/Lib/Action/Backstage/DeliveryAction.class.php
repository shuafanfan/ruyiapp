<?php
class DeliveryAction extends CommonAction{
	
	private $create_fields = array('city_id', 'user_id','photo', 'name', 'mobile', 'addr');
	private $edit_fields = array('city_id', 'user_id','photo', 'name', 'mobile', 'addr');
	
    public function index(){
        $Delivery = D('Delivery');
        import('ORG.Util.Page');
		$map = array('closed' => 0);
		if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['user_id|name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Delivery->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Delivery->where($map)->order('create_time')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function create(){
		$obj = D('Delivery');
        if ($this->isPost()) {
            $data = $this->createCheck();
            if ($id = $obj->add($data)) {
                $this->baoSuccess('添加成功', U('delivery/index'));
            }
            $this->baoError('申请失败！');
        } else {
			$this->assign('user_delivery', $user_delivery);
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		$data['user_id'] = (int) $data['user_id'];
        if (empty($data['user_id'])) {
            $this->baoError('用户不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传身份证');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('姓名不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->baoError('电话不能为空');
        }
        if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->baoError('电话应该为13位手机号码');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('地址不能为空');
        } 
		$data['audit'] = 1;       
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
	
     public function edit($id = 0){
        if ($id = (int) $id) {
            $obj = D('Delivery');
            if (!($detail = $obj->find($id))) {
                $this->baoError('请选择要编辑的配送员');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('delivery/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的配送员');
        }
    }
	
	 private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['user_id'] = (int) $data['user_id'];
        if (empty($data['user_id'])) {
            $this->baoError('用户不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传身份证');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('姓名不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->baoError('电话不能为空');
        }
        if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->baoError('电话应该为13位手机号码');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('地址不能为空');
        } 
		$data['audit'] = 1;       
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
    public function lists(){
        $id = I('id', '', 'intval,trim');
        if (!$id) {
            $this->baoError('没有选择！');
        } else {
			$Delivery = D('Delivery')->where('id =' . $id)->find();
			$users = D('Users')->find($Delivery['user_id']);
            $this->assign('delivery', D('Delivery')->where('id =' . $id)->find());
            $dvo = D('DeliveryOrder');
            import('ORG.Util.Page');
			
			if ($order_id = (int) $this->_param('order_id')) {
				$map['order_id'] = $order_id;
				$this->assign('order_id', $order_id);
			}
		
		
			if (isset($_GET['st']) || isset($_POST['st'])) {
				$st = (int) $this->_param('st');
				if ($st != 999) {
					$map['status'] = $st;
				}
				$this->assign('st', $st);
			} else {
				$this->assign('st', 999);
			}
			
		
            $count = $dvo->where('delivery_id =' . $users['user_id'])->count();
            $Page = new Page($count, 25);
            $show = $Page->show();
            $list = $dvo->where('delivery_id =' . $users['user_id'])->order('order_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }
    }
	
	// 新增选择配送员
	public function select(){
        $Delivery = D('Delivery');
        import('ORG.Util.Page'); // 导入分页类
        $map = array();
        if($name = $this->_param('name','htmlspecialchars')){
            $map['name'] = array('LIKE','%'.$name.'%');
            $this->assign('name',$name);
        }
        $count = $Delivery->where($map)->count(); 
        $Page = new Page($count, 8); 
        $pager = $Page->show(); // 分页显示输出
        $list = $Delivery->where($map)->order(array('id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
        $this->assign('list', $list);
        $this->assign('page', $pager); 
        $this->display(); 
        
    }
	
	public function delete($id = 0){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Delivery');
            $obj->save(array('id' => $id,'closed'=>1));
            $this->baoSuccess('删除成功！', U('delivery/index'));
        } else {
            $id = $this->_post('id', false);
            if (is_array($id)) {
                $obj = D('Delivery');
                foreach ($id as $id) {
                    $obj->save(array('id'=>$id, 'closed'=>1));
                }
                $this->baoSuccess('删除成功！', U('delivery/index'));
            }
            $this->baoError('请选择要删除的配送员');
        }
    }
    public function audit($id = 0){
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('Delivery');
            $obj->save(array('id' => $id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('delivery/index'));
        } else {
            $id = $this->_post('id', false);
            if (is_array($id)) {
                $obj = D('Delivery');
                foreach ($id as $id) {
                    $obj->save(array('id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('delivery/index'));
            }
            $this->baoError('请选择要审核的配送员');
        }
    }
	
	public function order() {
        $DeliveryOrder = D('DeliveryOrder');
        import('ORG.Util.Page'); 
        $map = array('closed' => 0);
        if ($order_id = (int) $this->_param('order_id')) {
            $map['order_id'] = $order_id;
            $this->assign('order_id', $order_id);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
		
		if (isset($_GET['type']) || isset($_POST['type'])) {
            $type = (int) $this->_param('type');
            if ($type != 999) {
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        } else {
            $this->assign('type', 999);
        }
		
        $count = $DeliveryOrder->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $DeliveryOrder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
    }
	
	public function del_order($order_id = 0){
            $order_id = (int) $order_id;
			$obj = D('DeliveryOrder');
			if (!$detail = $obj->find($order_id)) {
                $this->baoError('没有找到该订单号');
            }
			if($detail['status'] >1 ){
				$this->baoError('当前状态不能删除该订单');
			}
            $obj->save(array('order_id' => $order_id,'closed'=>1));
            $this->baoSuccess('删除成功！', U('delivery/order'));
        
    }
	//配送员所有的费用记录
	 public function finance(){
        $Runningmoney = D('Runningmoney');
        import('ORG.Util.Page');
		if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		if (isset($_GET['type']) || isset($_POST['type'])) {
            $type = (int) $this->_param('type');
            if ($type == 1) {
                $map['type'] = goods;
            }elseif($type == 2){
				$map['type'] = ele;
			}elseif($type == 3){
				$map['type'] = running;
			}
            $this->assign('type', $type);
        } else {
            $this->assign('type', 999);
        }
		
        $count = $Runningmoney->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Runningmoney->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
}