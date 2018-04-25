<?php
class ShopAction extends CommonAction{
    public function index(){
        $this->display();
    }
    public function logo(){
        if ($this->isPost()) {
            $logo = $this->_post('logo', 'htmlspecialchars');
            if (empty($logo)) {
                $this->baoError('请上传商铺LOGO');
            }
            if (!isImage($logo)) {
                $this->baoError('商铺LOGO格式不正确');
            }
            $data = array('shop_id' => $this->shop_id, 'logo' => $logo);
            if (D('Shop')->save($data)) {
                $this->baoSuccess('上传LOGO成功！', U('shop/logo'));
            }
            $this->baoError('更新LOGO失败');
        } else {
            $this->display();
        }
    }
    public function image(){
        if ($this->isPost()) {
            $photo = $this->_post('photo', 'htmlspecialchars');
            if (empty($photo)) {
                $this->baoError('请上传商铺形象照');
            }
            if (!isImage($photo)) {
                $this->baoError('商铺形象照格式不正确');
            }
			
			$logo = $this->_post('logo', 'htmlspecialchars');
            if (empty($logo)) {
                $this->baoError('请上传商铺LOGO');
            }
            if (!isImage($logo)) {
                $this->baoError('LOGO格式不正确');
            }
			
            $data = array('shop_id' => $this->shop_id, 'photo' => $photo, 'logo' => $logo);
            if (false !== D('Shop')->save($data)) {
                $this->baoSuccess('上传成功', U('shop/image'));
            }
            $this->baoError('更新形象照失败');
        } else {
            $this->display();
        }
    }
    public function about(){
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('addr', 'contact','tel','mobile', 'qq', 'business_time','express_price', 'delivery_time'));
            $data['addr'] = htmlspecialchars($data['addr']);
            if (empty($data['addr'])) {
                $this->baoError('店铺地址不能为空');
            }
            $data['contact'] = htmlspecialchars($data['contact']);
			$data['tel'] = htmlspecialchars($data['tel']);
			$data['mobile'] = htmlspecialchars($data['mobile']);
            if (empty($data['mobile'])) {
                $this->baoError('手机不能为空');
            }
            if (!isMobile($data['mobile'])) {
                $this->baoError('手机格式不正确');
            }
            $data['qq'] = htmlspecialchars($data['qq']);
            $data['business_time'] = htmlspecialchars($data['business_time']);
            $data['shop_id'] = $this->shop_id;
            $data['delivery_time'] = (int) $data['delivery_time'];
			$data['express_price'] = (int) ($data['express_price']*100);
			if (empty($data['express_price'])) {
                $this->baoError('配送费必须设置');
            }
			if ($data['express_price'] < 300) {
				$this->baoError('配送费必须大于3元');
			}
            $details = $this->_post('details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('商家介绍含有敏感词：' . $words);
            }
            $ex = array('details' => $details, 'near' => $data['near'], 'business_time' => $data['business_time'], 'delivery_time' => $data['delivery_time']);
            unset($data['business_time'], $data['near'], $data['delivery_time']);
            if (false !== D('Shop')->save($data)) {
                D('Shopdetails')->upDetails($this->shop_id, $ex);
                $this->baoSuccess('操作成功', U('shop/about'));
            }
            $this->baoError('操作失败');
        } else {
            $this->assign('ex', D('Shopdetails')->find($this->shop_id));
            $this->display();
        }
    }
    //其他设置
    public function service(){
        $obj = D('Shop');
        if (!($detail = $obj->find($this->shop_id))) {
            $this->baoError('请选择要编辑的商家');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->baoError('请不要非法操作');
        }
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('is_ele_print','is_tuan_print','is_goods_print','is_booking_print','is_appoint_print','panorama_url','apiKey', 'mKey', 'partner', 'machine_code', 'service'));
			$data['is_ele_print'] = (int) $_POST['is_ele_print'];
			$data['is_tuan_print'] = (int) $_POST['is_tuan_print'];
			$data['is_goods_print'] = (int) $_POST['is_goods_print'];
			$data['is_booking_print'] = (int) $_POST['is_booking_print'];
			$data['is_appoint_print'] = (int) $_POST['is_appoint_print'];
			$data['panorama_url'] = htmlspecialchars($data['panorama_url']);
            $data['apiKey'] = htmlspecialchars($data['apiKey']);
            $data['mKey'] = htmlspecialchars($data['mKey']);
            $data['partner'] = htmlspecialchars($data['partner']);
            $data['machine_code'] = htmlspecialchars($data['machine_code']);
            $data['service'] = $data['service'];
            $data['shop_id'] = $this->shop_id;

            if (false !== $obj->save($data)) {
                $this->baoSuccess('更新成功', U('shop/service'));
            }
            $this->baoError('操作失败');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    //购买短信
    public function sms() {
        $sms_shop_money = $this->_CONFIG['sms_shop']['sms_shop_money']; //单价
        $sms_shop_small = $this->_CONFIG['sms_shop']['sms_shop_small'];//最少购买多少条
        $sms_shop_big = $this->_CONFIG['sms_shop']['sms_shop_big'];//最大购买多少条
        $nums = D('Smsshop')->where(array('type' => shop, 'shop_id' => $this->shop_id))->find();
        if(IS_POST){
            $num = (int) $_POST['num'];
            if($num <= 0) {
                $this->baoError('购买数量不合法');
            }
			if(false == D('Smsshop')->buy($num,$this->uid,$this->shop_id)){
				$this->baoError(D('Smsshop')->getError());
			}else{
				$this->baoSuccess('购买短信成功', U('shop/sms'));
			}
        } else {
            $this->assign('sms_shop_money', $sms_shop_money);
            $this->assign('sms_shop_small', $sms_shop_small);
            $this->assign('sms_shop_big', $sms_shop_big);
            $this->assign('nums', $nums);
            $this->display();
        }
    }
	
	//商家等级权限 
	public function grade(){
        $Shopgrade = D('Shopgrade');
        import('ORG.Util.Page');
        $map = array('closed'=>0);
        $count = $Shopgrade->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgrade->where($map)->order(array('orderby' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach ($list as $k => $val) {
            $list[$k]['shop_count'] = $Shopgrade->get_shop_count($val['grade_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	//商家等级权限 
	public function permission($grade_id = 0){
        $grade_id = (int) $grade_id;
        $obj = D('Shopgrade');
        if (!($detail = $obj->find($grade_id))) {
            $this->baoError('请选择要查看的商家等级');
        }
        $this->assign('detail', $detail);
        $this->display();
    }
	
	//购买等级权限
	public function pay_permission(){
        $grade_id = (int) $this->_param('grade_id');
		$shop_id = (int) $this->_param('shop_id');
        if (!$obj = D('Shopgradeorder')->shop_pay_grade($grade_id,$shop_id)) {
			$this->baoError(D('Shopgradeorder')->getError(), 3000, true);	
        }else{
			 $this->baoSuccess('恭喜您购买等级成功', U('shop/grade'));
		}
        $this->display();
    }
	//商家导航
	
	private $create_fields = array('shop_id','nav1_name','nav2_name','nav3_name','nav4_name','nav5_name','nav6_name','nav7_name', 'nav8_name', 'nav1_is_open', 'nav2_is_open', 'nav3_is_open', 'nav4_is_open', 'nav5_is_open', 'nav6_is_open', 'nav7_is_open', 'nav8_is_open', 'nav1_is_orderby', 'nav2_is_orderby', 'nav3_is_orderby', 'nav4_is_orderby', 'nav5_is_orderby', 'nav6_is_orderby', 'nav7_is_orderby', 'nav8_is_orderby');
	
	
	 public function nav() {
		$obj = D('Shopnav');
		$detail = $obj->find($this->shop_id);
        if ($this->isPost()) {
            $data = $this->createCheck();
			$data['shop_id'] = $this->shop_id;
            if($detail){
				if (false !== $obj->save($data)) {
					
					$this->baoSuccess('修改导航成功', U('shop/nav'));
				}else{
					$this->baoError('操作失败');
				}
			}else{
				if ($obj->add($data)) {
					$this->baoSuccess('初次配置导航成功', U('shop/nav'));
				}else{
					$this->baoError('新增导航配置操作失败');
				}
			}
        } else {
            $this->assign('detail', $detail);
            $this->display('shop_nav');
        }
    }

	private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		$data['nav1_name'] = htmlspecialchars($data['nav1_name']);
		$data['nav2_name'] = htmlspecialchars($data['nav2_name']);
		$data['nav3_name'] = htmlspecialchars($data['nav3_name']);
		$data['nav4_name'] = htmlspecialchars($data['nav4_name']);
		$data['nav5_name'] = htmlspecialchars($data['nav5_name']);
		$data['nav6_name'] = htmlspecialchars($data['nav6_name']);
		$data['nav7_name'] = htmlspecialchars($data['nav7_name']);
		$data['nav8_name'] = htmlspecialchars($data['nav8_name']);
		$data['nav1_is_open'] = $_POST['nav1_is_open'];
		$data['nav2_is_open'] = $_POST['nav2_is_open'];
		$data['nav3_is_open'] = $_POST['nav3_is_open'];
		$data['nav4_is_open'] = $_POST['nav4_is_open'];
		$data['nav5_is_open'] = $_POST['nav5_is_open'];
		$data['nav6_is_open'] = $_POST['nav6_is_open'];
		$data['nav7_is_open'] = $_POST['nav7_is_open'];
		$data['nav8_is_open'] = $_POST['nav8_is_open'];
		$data['nav1_is_orderby'] = $_POST['nav1_is_orderby'];
		$data['nav2_is_orderby'] = $_POST['nav2_is_orderby'];
		$data['nav3_is_orderby'] = $_POST['nav3_is_orderby'];
		$data['nav4_is_orderby'] = $_POST['nav4_is_orderby'];
		$data['nav5_is_orderby'] = $_POST['nav5_is_orderby'];
		$data['nav6_is_orderby'] = $_POST['nav6_is_orderby'];
		$data['nav7_is_orderby'] = $_POST['nav7_is_orderby'];
		$data['nav8_is_orderby'] = $_POST['nav8_is_orderby'];
		$data['update_time'] = NOW_TIME;
        $data['update_ip'] = get_client_ip();
		$and = $data['nav1_is_open']+$data['nav2_is_open']+$data['nav3_is_open']+$data['nav4_is_open']+$data['nav5_is_open']+$data['nav6_is_open']+$data['nav7_is_open']+$data['nav8_is_open'];
		if($and != 4){
			$this->baoError('您必须配置4个导航，不能多不能少，刚好勾选4个显示，请重新选择需要显示的模块');
		}			
        return $data;
    }
}