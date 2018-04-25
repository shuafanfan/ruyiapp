<?php
class UserrankAction extends CommonAction{
    private $create_fields = array('rank_name', 'number', 'discount', 'reward', 'icon', 'icon1','integral', 'prestige', 'rebate', 'photo');
    private $edit_fields = array('rank_name', 'number', 'discount', 'reward', 'icon', 'icon1','integral', 'prestige', 'rebate', 'photo');
    public function index(){
        $obj = D('Userrank');
        import('ORG.Util.Page');
        $map = array();
        $count = $obj->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('rank_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function create(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Userrank');
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->baoSuccess('添加成功', U('userrank/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['rank_name'] = htmlspecialchars($data['rank_name']);
        if (empty($data['rank_name'])) {
            $this->baoError('等级名称不能为空');
        }
        $data['number'] = htmlspecialchars($data['number']);
        if (empty($data['number'])) {
            $this->baoError('升级需要人数不能为空');
        }
		$data['discount'] = htmlspecialchars($data['discount']);
        if(empty($data['discount'])) {
            $this->baoError('购买折扣不能为空');
        }
		if($data['discount'] >= 100) {
            $this->baoError('折扣设置非法');
        }
		$data['reward'] = htmlspecialchars($data['reward']);
        if(empty($data['reward'])) {
            $this->baoError('下级推广奖励不能为空');
        }
		if($data['reward'] >= 100) {
            $this->baoError('推广奖励设置非法');
        }
        $data['integral'] = (int) $data['integral'];
		$data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        } 
        return $data;
    }
    public function edit($rank_id = 0){
        if ($rank_id = (int) $rank_id) {
            $obj = D('Userrank');
            if (!($detail = $obj->find($rank_id))) {
                $this->baoError('请选择要编辑的会员等级');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['rank_id'] = $rank_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->baoSuccess('操作成功', U('userrank/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的会员等级');
        }
    }
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['rank_name'] = htmlspecialchars($data['rank_name']);
        if (empty($data['rank_name'])) {
            $this->baoError('等级名称不能为空');
        }
        $data['number'] = htmlspecialchars($data['number']);
        if (empty($data['number'])) {
            $this->baoError('升级需要人数不能为空');
        }
		$data['discount'] = htmlspecialchars($data['discount']);
        if(empty($data['discount'])) {
            $this->baoError('购买折扣不能为空');
        }
		if($data['discount'] >= 100) {
            $this->baoError('折扣设置非法');
        }
		$data['reward'] = htmlspecialchars($data['reward']);
        if(empty($data['reward'])) {
            $this->baoError('下级推广奖励不能为空');
        }
		if($data['reward'] >= 100) {
            $this->baoError('推广奖励设置非法');
        }
        $data['integral'] = (int) $data['integral'];
		$data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        } 
        return $data;
    }
    public function delete($rank_id = 0){
		$rank_id = (int) $rank_id;
        if ($rank_id) {
            $obj = D('Userrank');
			$count = $obj->where(array('rank_id'=>$rank_id))->count();
			if($count > 0){
				$this->baoError('该等级还有会员使用');
			}
			if($obj->delete($rank_id)){
				$obj->cleanCache();
            	$this->baoSuccess('删除成功！', U('userrank/index'));
			}else{
				$this->baoError('操作失败');
			}
        } else {
            $this->baoError('请选择要删除的会员等级');
        }
    }
}