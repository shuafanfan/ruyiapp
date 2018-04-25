<?php
class AddressAction extends CommonAction{
	//public function _initialize(){
        //parent::_initialize();
       // if(empty($this->uid)){
        //    $this->error('检测不到登录信息');              
        //}
    //}
   public function addlist() {
		$user_id=$this->getuid($_POST['token']);
     	$data['list']=D('paddress')->where(['user_id'=>$user_id])->select();
     	$data['status']=0;
     	$this->ajaxReturn($data);
	}
	//众筹商城选择地址
	 public function choice() {
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$log_id = isset($_GET['log_id']) ? intval($_GET['log_id']) : 0;
		$address_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if($type == crowd && (!empty($address_id))){//众筹换地址
			if (false == D('Crowdorder') -> replace_crowd_pay_addr($order_id,$address_id,$this->uid)) {//更换众筹地址
				$this->fengmiMsg('更新地址出错');
			}else{
				$this->fengmiMsg('更换地址成功，正在跳转', U('crowd/pay', array('type' => $type,'order_id' => $order_id,'address_id'=>$address_id)));
			}
		}elseif($type == goods && (!empty($log_id))){//商城合并付款
			D('Ordergoods')->merge_update_express_price($this->uid,$type,$log_id,$address_id);//传4个参数
			$this->fengmiMsg('选择商城收货地址操作成功', U('mall/paycode', array('type' => $type,'log_id' => $log_id,'address_id'=>$id)));
		}elseif($type == goods && (!empty($order_id))){//商城单个付款
			D('Ordergoods')->update_express_price($this->uid,$type, $order_id,$address_id );
			$this->fengmiMsg('更换商城地址成功', U('mall/pay', array('type' => $type,'order_id' => $order_id,'address_id'=>$address_id)));
		} else {
			$this->fengmiMsg('操作失败');
		}
	}

	public function addrcat() {
		
      	$token=$_POST['token'];
      	$user_id=$this->getuid($token);
	    if ($this->isPost()) {
			$data = $_POST;
			
          	if (empty($user_id)) {
              	$return['status']=1;
              	$return['msg']="未找到该用户";
              	$this->ajaxReturn($return);
			}
          	if (empty($data['name'])) {
              	$return['status']=1;
              	$return['msg']="收货人姓名不能为空";
              	$this->ajaxReturn($return);
			}
			if (empty($data['tel'])) {
              	$return['status']=1;
              	$return['msg']="联系电话不能为空";
              	$this->ajaxReturn($return);
			}
			if (!isPhone($data['tel']) && !isMobile($data['tel'])) {
              	$return['status']=1;
              	$return['msg']="联系电话格式不正确";
              	$this->ajaxReturn($return);
			}
			$data['province'] = (int) $data['province'];
          	if (empty($data['default'])) {
              	$return['status']=1;
              	$return['msg']="设置是否默认地址";
              	$this->ajaxReturn($return);
			}
			if (empty($data['province'])) {
              	$return['status']=1;
              	$return['msg']="省份不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('省份不能为空');
			}
			$data['city'] = (int) $data['city'];
			if (empty($data['city'])) {
              	$return['status']=1;
              	$return['msg']="城市不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('城市不能为空');
			}
			$data['area'] = (int) $data['area'];
			if (empty($data['area'])) {
              	$return['status']=1;
              	$return['msg']="区县不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('地区不能为空');
			}
          	if (empty($data['community'])) {
              	$return['status']=1;
              	$return['msg']="小区不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('地区不能为空');
			}
			$data['addinfo'] = htmlspecialchars($data['addinfo']);
			if (empty($data['addinfo'])) {
              	$return['status']=1;
              	$return['msg']="详细地址不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('详细地址不能为空');
			}
			if(empty($_POST['x'])||empty($_POST['y'])){
        	$return['status']=1;
          	$return['msg']="请传入xy坐标";
          	$this->ajaxReturn($return);
        	}
			$provinfo = D('Paddlist') -> find($data['province']);
			$cityinfo = D('Paddlist') -> find($data['city']);
			$areasinfo = D('Paddlist') -> find($data['area']);
          	$cominfo = D('Paddlist') -> find($data['community']);
			$areainfo = D('Paddlist') -> find($data['addinfo']);
          	if($_POST['type']=="true"){
            		$type=1;
            	}else{
            		$type=0;
            }
			$newadd = array(
				'user_id' => $user_id,
              	'name'=>$data['name'],
				'default' => $type, 
				'xm' => $data['addxm'], 
				'tel' => $data['tel'], 
				'province_id' => $data['province'], 
				'city_id' => $data['city'], 
				'area_id' => $data['area'],
              	'community_id'=>$data['community'],
				'area_str' => $provinfo['name'] . " " . $cityinfo['name'] . " " . $areasinfo['name'] . " " .$cominfo['name'] . " ". $areainfo['name'], 
				'info' => $data['addinfo'],
              	'x'=>$data['x'],
              	'y'=>$data['y']
              
			);
			
			$Paddress= D('Paddress');
			if ($type == 1) {
				$update_default_user_address = $Paddress -> where('user_id =' . $user_id) -> setField('default', 0);
			}
			if ($id = $Paddress->add($newadd)) {
					if($id){
                    	$return['status']=0;
              			$return['msg']="地址添加成功";
              			$this->ajaxReturn($return);			
                    }	
				
			} else {
						$return['status']=1;
              			$return['msg']="地址添加失败";
              			$this->ajaxReturn($return);	
			}
		}
		
	}
	//修改收货地址重写
	public function addedit() {
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = (int)$this -> _get('order_id');
		$goods_id = (int)$this -> _get('goods_id');
		$log_id = (int)$this -> _get('log_id');
		$address_id = (int)$this -> _get('address_id');
		$detail = D('Paddress') -> where(array('user_id' => $this -> uid, 'id' => $address_id)) -> find();
		if(empty($detail) || empty($address_id) || empty($order_id)){
			$this->error('存在异常，请找稍后再试');
		}
	    if ($this->isPost()) {
			$data = $this->checkFields($this->_post('data', false), array('type','defaults','addxm', 'addtel', 'province', 'city', 'areas', 'addinfo'));
			$data['type'] = htmlspecialchars($data['type']);
			$data['defaults'] = (int) $data['defaults'];
			$data['addxm'] = htmlspecialchars($data['addxm']);
			$data['addtel'] = htmlspecialchars($data['addtel']);
			if (empty($data['addtel'])) {
				$this->fengmiMsg('联系电话不能为空');
			}
			if (!isPhone($data['addtel']) && !isMobile($data['addtel'])) {
				$this->fengmiMsg('联系电话格式不正确');
			}
			$data['province'] = (int) $data['province'];
			if (empty($data['province'])) {
				$this->fengmiMsg('省份不能为空');
			}
			$data['city'] = (int) $data['city'];
			if (empty($data['city'])) {
				$this->fengmiMsg('城市不能为空');
			}
			$data['areas'] = (int) $data['areas'];
			if (empty($data['areas'])) {
				$this->fengmiMsg('地区不能为空');
			}
			$data['addinfo'] = htmlspecialchars($data['addinfo']);
			if (empty($data['addinfo'])) {
				$this->fengmiMsg('详细地址不能为空');
			}
			$provinfo = D('Paddlist') -> find($data['province']);
			$cityinfo = D('Paddlist') -> find($data['city']);
			$areasinfo = D('Paddlist') -> find($data['areas']);
			$areainfo = D('Paddlist') -> find($data['addinfo']);
			
			$newadd = array(
				'id' => $address_id,
				'user_id' => $this ->uid, 
				'default' => $data['defaults'], 
				'xm' => $data['addxm'], 
				'tel' => $data['addtel'], 
				'province_id' => $data['province'], 
				'city_id' => $data['city'], 
				'area_id' => $data['areas'], 
				'area_str' => $provinfo['name'] . " " . $cityinfo['name'] . " " . $areasinfo['name'] . "  " . $areainfo['name'], 
				'info' => $data['addinfo']
			);
			
			$Paddress= D('Paddress');
			if ($data['defaults'] == 1) {
				$update_default_user_address = $Paddress -> where('user_id =' . $this -> uid) -> setField('default', 0);
			}
			if ($Paddress->save($newadd)) {
				if($data['type'] == goods && (!empty($address_id))){//如果是商城
					D('Ordergoods')->update_express_price($this->uid,$data['type'], $order_id,$address_id);//去更新订单运费
					$this->fengmiMsg('修改成功', U('mall/pay', array('type' => $data['type'],'order_id' => $order_id,'address_id'=>$address_id)));
				}elseif($data['type'] == goods && (!empty($data['log_id']))){
					D('Ordergoods')->merge_update_express_price($this->uid,$data['type'],$log_id,$address_id);//传4个参数，合并付款封装修改运费
					$this->fengmiMsg('修改合并付款地址成功', U('mall/paycode', array('type' => $data['type'],'log_id' =>$log_id,'address_id'=>$address_id)));
				}elseif($data['type'] == crowd && (!empty($address_id))){//如果是众筹
					if (false == D('Crowdorder')->replace_crowd_pay_addr($order_id,$address_id,$this->uid)) {//更换众筹地址
						$this->fengmiMsg('更新地址出错');
					}else{
						$this->fengmiMsg('编辑地址成功，正在跳转', U('crowd/pay', array('type' =>$data['type'],'order_id' =>$order_id,'address_id'=>$address_id)));
					}
				}
				
			} else {
				$this->fengmiMsg('修改失败，请重试');
			}
			
		}else{
			$provinceList = D('Paddlist') -> where(array('level' => 1)) -> select();
			$this -> assign('provinceList', $provinceList);
			$cityList = D('Paddlist') -> where(array('upid' => $detail['province_id'])) -> select();
			$areaList = D('Paddlist') -> where(array('upid' => $detail['city_id'])) -> select();
			$this -> assign('cityList', $cityList);
			$this -> assign('areaList', $areaList);
			$this -> assign('detail', $detail);
			$this -> assign('type', $type);
			$this -> assign('order_id', $order_id);
			$this -> assign('goods_id', $goods_id);
			$this -> assign('log_id', $log_id);
			$this -> assign('address_id', $address_id);
			$this -> display();
		}
		
	}
	
	//更新收货地址
	public function edit() {
		$address_id = (int)$this -> _get('address_id');
		$detail = D('Paddress') -> where(array('user_id' => $this -> uid, 'id' => $address_id)) -> find();
		if(empty($address_id)){
			$this->error('您选择的地址不存在');
		}
	    if ($this->isPost()) {
			$data = $this->checkFields($this->_post('data', false), array('type','defaults','addxm', 'addtel', 'province', 'city', 'areas', 'addinfo'));
			$data['addxm'] = htmlspecialchars($data['addxm']);
			$data['addtel'] = htmlspecialchars($data['addtel']);
			if (empty($data['addtel'])) {
				$this->fengmiMsg('联系电话不能为空');
			}
			if (!isPhone($data['addtel']) && !isMobile($data['addtel'])) {
				$this->fengmiMsg('联系电话格式不正确');
			}
			$data['province'] = (int) $data['province'];
			if (empty($data['province'])) {
				$this->fengmiMsg('省份不能为空');
			}
			$data['city'] = (int) $data['city'];
			if (empty($data['city'])) {
				$this->fengmiMsg('城市不能为空');
			}
			$data['areas'] = (int) $data['areas'];
			if (empty($data['areas'])) {
				$this->fengmiMsg('地区不能为空');
			}
			$data['addinfo'] = htmlspecialchars($data['addinfo']);
			if (empty($data['addinfo'])) {
				$this->fengmiMsg('详细地址不能为空');
			}
			$provinfo = D('Paddlist') -> find($data['province']);
			$cityinfo = D('Paddlist') -> find($data['city']);
			$areasinfo = D('Paddlist') -> find($data['areas']);
			$areainfo = D('Paddlist') -> find($data['addinfo']);
			$newadd = array(
				'id' => $address_id,
				'user_id' => $this ->uid, 
				'default' => $data['defaults'], 
				'xm' => $data['addxm'], 
				'tel' => $data['addtel'], 
				'province_id' => $data['province'], 
				'city_id' => $data['city'], 
				'area_id' => $data['areas'], 
				'area_str' => $provinfo['name'] . " " . $cityinfo['name'] . " " . $areasinfo['name'] . "  " . $areainfo['name'], 
				'info' => $data['addinfo']
			);
			$Paddress= D('Paddress');
			if ($data['defaults'] == 1) {
				$update_default_user_address = $Paddress -> where('user_id =' . $this -> uid) -> setField('default', 0);
			}
			if ($Paddress->save($newadd)) {
				$this->fengmiMsg('编辑地址成功，正在跳转', U('address/addlist'));
			} else {
				$this->fengmiMsg('修改失败，请重试');
			}
			
		}else{
			$provinceList = D('Paddlist') -> where(array('level' => 1)) -> select();
			$this -> assign('provinceList', $provinceList);
			$cityList = D('Paddlist') -> where(array('upid' => $detail['province_id'])) -> select();
			$areaList = D('Paddlist') -> where(array('upid' => $detail['city_id'])) -> select();
			$this -> assign('cityList', $cityList);
			$this -> assign('areaList', $areaList);
			$this -> assign('detail', $detail);
			$this -> assign('address_id', $address_id);
			$this -> display();
		}
		
	}
	//删除地址
	public function deladdress() {
      	if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	if(empty($_POST['address_id'])){
        	$data['status']=1;
          	$data['msg']="请传入地址id";
          	$this->ajaxReturn($data);
        }
      	$id=$_POST['address_id'];
 
		//$type = I('type', '', 'trim,htmlspecialchars');
		//$address_id = (int)$this -> _get('address_id');
		//$tuan_id = isset($_GET['tuan_id']) ? intval($_GET['tuan_id']) : 0;
		//$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$obj = D('Paddress') -> where(['id'=>$id])->delete();
	
		if ($obj) {
			 $return['status']=0;
             $return['msg']="地址删除成功";
             $this->ajaxReturn($return);			
                     	

		}else{
       			$return['status']=1;
              $return['msg']="地址删除失败";
              $this->ajaxReturn($return);			
                     	
        }
	
	}
	//删除地址会员中心直接删除的时候
	public function delete() {
		$address_id = (int)$this -> _get('address_id');
		$obj = D('Paddress');
		if ($obj->save(array('id' => $address_id, 'closed' => 1))) {
			$this -> error('删除成功！', U('address/addlist'));

		}
	}
	//获取城市ID
	public function city() {
		$upid = isset($_POST['upid']) ? intval($_POST['upid']) : 0;
		$callback = $_GET['callback'];
		$outArr = array();
		$cityList = D('Paddlist') -> where(array('upid' => $upid)) -> select();
      	//dump($cityList);die;
		if (is_array($cityList) && !empty($cityList)) {
			foreach ($cityList as $key => $value) {
				$outArr[$key]['id'] = $value['id'];
				$outArr[$key]['name'] = $value['name'];
			}
		}
		//$outStr = '';
		//$outStr = json_encode($outArr);
      	$data['data']=$outArr;
      	$this->ajaxReturn($data);
		if ($callback) {
			$outStr = $callback . "(" . $outStr . ")";
		}
      	$data['data']=$outStr;
      	$this->ajaxReturn($data);
		echo $outStr;
		die();
	}
  	
  	public function getaddress(){
    	 if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	if(empty($_POST['address_id'])){
        	$data['status']=1;
          	$data['msg']="请传入地址id";
          	$this->ajaxReturn($data);
        }
      	$id=$_POST['address_id'];
      	$res=D('paddress')->where(['id'=>$id])->find();
      	if($res){
        	$data['status']=0;
          	$data['info']=$res;
          	$this->ajaxReturn($data);
        }else{
        	$data['status']=1;
          	$data['msg']="未查询到该地址";
          	$this->ajaxReturn($data);
        }
    }
  	
  	public function editaddress() {
		
      	$token=$_POST['token'];
      	$user_id=$this->getuid($token);
	    if ($this->isPost()) {
			$data = $_POST;
			
          	if (empty($user_id)) {
              	$return['status']=1;
              	$return['msg']="未找到该用户";
              	$this->ajaxReturn($return);
			}
          	if (empty($data['address_id'])) {
              	$return['status']=1;
              	$return['msg']="请传入地址id";
              	$this->ajaxReturn($return);
			}
          	
          	if (empty($data['name'])) {
              	$return['status']=1;
              	$return['msg']="收货人姓名不能为空";
              	$this->ajaxReturn($return);
			}
			if (empty($data['tel'])) {
              	$return['status']=1;
              	$return['msg']="联系电话不能为空";
              	$this->ajaxReturn($return);
			}
			if (!isPhone($data['tel']) && !isMobile($data['tel'])) {
              	$return['status']=1;
              	$return['msg']="联系电话格式不正确";
              	$this->ajaxReturn($return);
			}
			$data['province'] = (int) $data['province'];
          	if (empty($data['type'])) {
              	$return['status']=1;
              	$return['msg']="设置是否默认地址";
              	$this->ajaxReturn($return);
			}
			if (empty($data['province'])) {
              	$return['status']=1;
              	$return['msg']="省份不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('省份不能为空');
			}
			$data['city'] = (int) $data['city'];
			if (empty($data['city'])) {
              	$return['status']=1;
              	$return['msg']="城市不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('城市不能为空');
			}
			$data['area'] = (int) $data['area'];
			if (empty($data['area'])) {
              	$return['status']=1;
              	$return['msg']="区县不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('地区不能为空');
			}
          	if (empty($data['community'])) {
              	$return['status']=1;
              	$return['msg']="小区不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('地区不能为空');
			}
			$data['addinfo'] = htmlspecialchars($data['addinfo']);
			if (empty($data['addinfo'])) {
              	$return['status']=1;
              	$return['msg']="详细地址不能为空";
              	$this->ajaxReturn($return);
				$this->fengmiMsg('详细地址不能为空');
			}
			if(empty($_POST['x'])||empty($_POST['y'])){
        	$return['status']=1;
          	$return['msg']="请传入xy坐标";
          	$this->ajaxReturn($return);
        	}
			$provinfo = D('Paddlist') -> find($data['province']);
			$cityinfo = D('Paddlist') -> find($data['city']);
			$areasinfo = D('Paddlist') -> find($data['area']);
          	$cominfo = D('Paddlist') -> find($data['community']);
			$areainfo = D('Paddlist') -> find($data['addinfo']);
          	if($_POST['type']=="true"){
            		$type=1;
            	}else{
            		$type=0;
            }
			$newadd = array(
				'user_id' => $user_id,
              	'name'=>$data['name'],
				'default' => $type, 
				'xm' => $data['addxm'], 
				'tel' => $data['tel'], 
				'province_id' => $data['province'], 
				'city_id' => $data['city'], 
				'area_id' => $data['area'],
              	'community_id'=>$data['community'],
				'area_str' => $provinfo['name'] . " " . $cityinfo['name'] . " " . $areasinfo['name'] . " " .$cominfo['name'] . " ". $areainfo['name'], 
				'info' => $data['addinfo'],
              	'x'=>$data['x'],
              	'y'=>$data['y']
			);
			
			$Paddress= D('Paddress');
			if ($type == 1) {
				$update_default_user_address = $Paddress -> where('user_id =' . $user_id) -> setField('default', 0);
			}
          	$res= $Paddress->where(['id'=>$data['address_id']])->save($newadd);
          	//dump($res);die;
			if (1 == $Paddress->where(['id'=>$data['address_id']])->save($newadd)) {
              
					
                    	$return['status']=0;
              			$return['msg']="地址修改成功";
              			$this->ajaxReturn($return);			
                     	
				
			} else {
              
						$return['status']=1;
              			$return['msg']="地址未修改";
              			$this->ajaxReturn($return);	
			}
		}
		
	}
  public function all_address(){
  	$uid=$this->getuid($_POST['token']);
    $list=D('paddress')->where(['user_id'=>$uid])->select();
    			if ($list) {
              
					
                    	$return['status']=0;
                  foreach($list as $k=>$v){
                  		$return2[$k]['id']=$v['id'];
                  		$return2[$k]['info']['name']=$v['name'];
                    	$return2[$k]['info']['gender']='先生';
                    	$return2[$k]['info']['tel']=$v['tel'];
                    	$return2[$k]['x']=$v['x'];
                    	$return2[$k]['y']=$v['y'];
                    	$return2[$k]['info']['address']=$v['area_str'].$v['info'];
                  }
              			$return['data']=$return2;
              			$this->ajaxReturn($return);			
                     	
				
			} else {
              
						$return['status']=1;
              			$return['addressList']="未查询到地址";
              			$this->ajaxReturn($return);	
			}
  }
}