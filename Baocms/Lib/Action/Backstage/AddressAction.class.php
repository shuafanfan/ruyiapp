<?php
class AddressAction extends CommonAction {
   public function index(){
        $obj = D('Paddress');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
		$keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword) {
            $map['xm|tel|area_str|info'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        } 
		if ($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
   		foreach($list as $k => $v){
            $user = D('Users')->where(array('user_id'=>$v['user_id']))->find();
            $list[$k]['user'] = $user;
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
  
	//删除
    public function delete($id){
        $id = (int) $id;
        if(empty($id)) {
            $this->baoError('收货地址不存在');
        }
        if (!($detail = D('Paddress')->find($id))) {
            $this->baoError('收货地址不存在');
        }
		if(D('Paddress')->save(array('id' => $id, 'closed' => 1))){
			$this->baoSuccess('删除成功！', U('address/index'));
		}else{
			$this->baoError('删除失败');
		}
    }
  
}
