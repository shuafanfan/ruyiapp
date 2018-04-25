<?php

class ShopAction extends CommonAction{

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


public function push(){

        $obj = D('Shop');

        import('ORG.Util.Page');

        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id);

        $count = $obj->where($map)->count();

        $Page = new Page($count, 3);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $shop = $obj->order("orderby asc")->where($map)->limit(0,30)->select();

        $this->assign('shop', $shop);

        $this->assign('page', $show);

        $this->display();

    }
	
	
    public function index(){

        $cat = (int) $this->_param('cat');

        $this->assign('cat', $cat);

        $order = (int) $this->_param('order');

        $this->assign('order', $order);

        $keyword = $this->_param('keyword', 'htmlspecialchars');

        $this->assign('keyword', $keyword);

        $areas = D('Area')->fetchAll();

        $area = (int) $this->_param('area');

        $this->assign('area_id', $area);

        $biz = D('Business')->fetchAll();

        $business = (int) $this->_param('business');

        $this->assign('business_id', $business);

        $this->assign('areas', $areas);

        $this->assign('biz', $biz);

        $this->assign('nextpage', LinkTo('shop/loaddata', array('cat' => $cat, 'area' => $area, 'business' => $business, 'order' => $order, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));

        $this->display();

        // 输出模板

    }

    //二维码名片开始

    public function qrcode($shop_id){

        $shop_id = (int) $shop_id;

        if (empty($shop_id)) {

            $this->error('该商家不存在');

        }

        $shop = D('Shop')->find($shop_id);

        $file = D('Weixin')->getCode($shop_id, 1);

        $this->assign('file', $file);

        $this->assign('shop', $shop);

        $this->display();

    }

    public function gps($shop_id)

    {

        $shop_id = (int) $shop_id;

        if (empty($shop_id)) {

            $this->error('该商家不存在');

        }

        $shop = D('Shop')->find($shop_id);

        $this->assign('shop', $shop);

        $this->display();

    }

   

    public function loaddata(){

        $Shop = D('Shop');

        import('ORG.Util.Page');

        $map = array('closed' => 0, 'audit' => 1, 'city_id' => $this->city_id);

        $cat = (int) $this->_param('cat');

        if ($cat) {

            $catids = D('Shopcate')->getChildren($cat);

            if (!empty($catids)) {

                $map['cate_id'] = array('IN', $catids);

            } else {

                $map['cate_id'] = $cat;

            }

        }

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {

            $map['shop_name|addr'] = array('LIKE', '%' . $keyword . '%');

        }

        $area = (int) $this->_param('area');

        if ($area) {

            $map['area_id'] = $area;

        }

        $business = (int) $this->_param('business');

        if ($business) {

            $map['business_id'] = $business;

        }

        $order = (int) $this->_param('order');

        $lat = addslashes(cookie('lat'));

        $lng = addslashes(cookie('lng'));

        if (empty($lat) || empty($lng)) {

            $lat = $this->city['lat'];

            $lng = $this->city['lng'];

        }

        switch ($order) {

            case 2:

                $orderby = array('orderby' => 'asc', 'ranking' => 'desc');

                break;

            default:

                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";

                break;

        }

        $count = $Shop->where($map)->count();

        $Page = new Page($count, 8);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $Shop->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $val) {

            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);

        }

        $shop_ids = array();

        foreach ($list as $key => $v) {

            $shop_ids[$v['shop_id']] = $v['shop_id'];

        }

        $shopdetails = D('Shopdetails')->itemsByIds($shop_ids);

        foreach ($list as $k => $val) {

            $list[$k]['price'] = $shopdetails[$val['shop_id']]['price'];

        }

        $this->assign('list', $list);

        $this->assign('page', $show);

        $this->display();

    }

    public function detail(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        $Shopdianping = D('Shopdianping');

        import('ORG.Util.Page');

        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));

        $count = $Shopdianping->where($map)->count();

        $Page = new Page($count, 4);

        $show = $Page->show();

        $list = $Shopdianping->where($map)->order(array('dianping_id' => 'desc'))->limit(0, 4)->select();

        $all_ping = $Shopdianping->where('shop_id =' . $shop_id)->count();

        $this->assign('all_ping', $all_ping);

        $user_ids = $dianping_ids = array();

        foreach ($list as $k => $val) {

            $list[$k] = $val;

            $user_ids[$val['user_id']] = $val['user_id'];

            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];

        }

        if (!empty($user_ids)) {

            $this->assign('users', D('Users')->itemsByIds($user_ids));

        }

        if (!empty($dianping_ids)) {

            $this->assign('pics', D('Shopdianpingpics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());

        }

        $this->assign('totalnum', $count);

        $this->assign('list', $list);

        $this->assign('page', $show);

        $this->assign('favnum', D('Shopfavorites')->where(array('shop_id' => $shop_id))->count());

        $this->assign('detail', $detail);

        $this->seodatas['title'] = $detail['shop_name'];

        $this->assign('ex', D('Shopdetails')->find($shop_id));

        $this->assign('cates', D('Shopcate')->fetchAll());

        $shop_tuan = D('Shop')->where(array('cate_id' => array('neq', $detail['cate_id'])))->order(array('shop_id' => 'desc'))->select();

        $shop_ids = array();

        foreach ($shop_tuan as $k => $val) {

            $list[$k] = $val;

            $shop_ids[$val['shop_id']] = $val['shop_id'];

        }

        $map_tuan['shop_id'] = array('IN', $shop_ids);

        $map_tuan['closed'] = array('eq', '0');

        $map_tuan['bg_date'] = array('ELT', TODAY);

        $map_tuan['end_date'] = array('EGT', TODAY);

        $tuans = D('Tuan')->where($map_tuan)->order(array('top_date' => 'desc', 'create_time' => 'desc'))->limit(0, 6)->select();

        foreach ($tuans as $k => $val) {

            $tuans[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);

        }

        $this->assign('tuans', $tuans);

        $work = D('work')->order('work_id desc ')->where(array('shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'closed' => 0, 'expire_date' => array('EGT', TODAY)))->select();

        $this->assign('work', $work);

        $weidian = D('WeidianDetails')->where(array('audit' => 1, 'city_id' => $this->city_id, 'closed' => 0))->order('id desc')->limit(0, 1)->select();

        $this->assign('weidian', $weidian);

        $goods = D('Goods')->where(array('shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'closed' => 0, 'end_date' => array('EGT', TODAY)))->order('goods_id desc')->select();

        $this->assign('goods', $goods);

        $coupon = D('Coupon')->order('coupon_id desc ')->where(array('shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'closed' => 0, 'expire_date' => array('EGT', TODAY)))->select();

        $this->assign('coupon', $coupon);

        $huodong = D('Activity')->order('activity_id desc ')->where(array('shop_id' => $shop_id, 'city_id' => $this->city_id, 'audit' => 1, 'closed' => 0, 'end_date' => array('EGT', TODAY), 'bg_date' => array('ELT', TODAY)))->select();

        $this->assign('huodong', $huodong);

        $ele_menu = D('ele_product')->order('product_id desc ')->where(array('shop_id' => $shop_id, 'city_id' => $this->city_id))->select();

        $this->assign('ele_menu', $ele_menu);

        $ding_menu = D('shop_ding_menu')->order('menu_id desc ')->where(array('shop_id' => $shop_id, 'city_id' => $this->city_id))->select();

        $this->assign('ding_menu', $ding_menu);

        D('Shop')->updateCount($shop_id, 'view');

        $Weidian = D('Weidian_details');

        $weidianid = $Weidian->where('shop_id=' . $shop_id . ' ')->find();

        $this->assign('weidian_id', $weidianid['id']);

        $this->assign('pic', $pic = D('Shoppic')->where(array('shop_id' => $shop_id))->order(array('pic_id' => 'desc'))->count());

        $shopyouhui = D('Shopyouhui')->where(array('shop_id' => $shop_id, 'is_open' => 1, 'audit' => 1))->find();

        $this->assign('shopyouhui', $shopyouhui);

		$this->assign('pics', $pics = D('Shoppic')->order('orderby desc')->where(array('shop_id' => $shop_id))->select());

        $this->assign('news', $news = D('Shopnews')->order('create_time desc')->where(array('shop_id' => $shop_id,'audit'=>1))->find());

		$this->assign('goodsshopcates', $goodsshopcates = D('Goodsshopcate')->order('orderby desc')->where(array('shop_id' => $shop_id))->select());

        $this->display();

    }

	

	//二维码名片开始

    public function nav($shop_id){

        $shop_id = (int) $shop_id;

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

        }

		$nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find();

        $this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('detail', $detail);

        $this->display();

    }

    public function favorites(){

        if (empty($this->uid)) {

            header("Location:" . U('passport/login'));

            die;

        }

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

        }

        if (D('Shopfavorites')->check($shop_id, $this->uid)) {

            $this->error('您已经收藏过了！');

        }

        $data = array('shop_id' => $shop_id, 'user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());

        if (D('Shopfavorites')->add($data)) {

			D('Shop')->updateCount($shop_id, 'fans_num');

            $this->success('恭喜您收藏成功！', U('shop/detail', array('shop_id' => $shop_id)));

        }

        $this->error('收藏失败！');

    }

    //点评

    public function dianping(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('detail', $detail);

        $this->display();

    }

    public function dianpingloading(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            die('0');

        }

        if ($detail['closed']) {

            die('0');

        }

        $Shopdianping = D('Shopdianping');

        import('ORG.Util.Page');

        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));

        $count = $Shopdianping->where($map)->count();

        $Page = new Page($count, 5);

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $show = $Page->show();

        $list = $Shopdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $user_ids = $dianping_ids = array();

        foreach ($list as $k => $val) {

            $list[$k] = $val;

            $user_ids[$val['user_id']] = $val['user_id'];

            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];

        }

        if (!empty($user_ids)) {

            $this->assign('users', D('Users')->itemsByIds($user_ids));

        }

        if (!empty($dianping_ids)) {

            $this->assign('pics', D('Shopdianpingpics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());

        }

        $this->assign('totalnum', $count);

        $this->assign('list', $list);

        $this->assign('detail', $detail);

        $this->display();

    }

	

	

	//点评详情

    public function img(){

        $dianping_id = (int) $this->_get('dianping_id');

        if (!($detail = D('Shopdianping')->where(array('dianping_id'=>$dianping_id))->find())){

            $this->error('没有该点评');

            die;

        }

        if ($detail['closed']) {

            $this->error('该点评已经被删除');

            die;

        }

        $list =  D('Shopdianpingpics')->where(array('dianping_id' =>$detail['dianping_id']))->select();

        $this->assign('list', $list);

        $this->assign('detail', $detail);

        $this->display();

    }

	

    //新添加预约商家开始

    public function book($shop_id){

        if (empty($this->uid)) {

            $this->error('登录状态失效!', U('passport/login'));

        }

        $shop_id = (int) $shop_id;

        $detail = D('Shop')->find($shop_id);

        if (empty($detail)) {

            $this->error('商家不存在');

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        $Users = D('Users')->find($detail['user_id']);

        if ($this->isPost()) {

            $data = $this->checkBook($shop_id);

            $obj = D('Shopyuyue');

            $data['shop_id'] = (int) $shop_id;

            $data['type'] = 0;

            $data['code'] = $obj->getCode();

            if ($yuyue_id = $obj->add($data)) {

				

				D('Sms')->sms_yuyue_notice_user($detail,$data['mobile'],$data['code']);//短信通知会员

				D('Sms')->sms_yuyue_notice_shop($data,$Users['mobile']);//短信通知商家            

                //预约通知商家功能结束

                D('Weixintmpl')->weixin_yuyue_notice($yuyue_id,1);//预约后微信通知预约人

				D('Weixintmpl')->weixin_yuyue_notice($yuyue_id,2);//预约后微信通知商家

                D('Shop')->updateCount($shop_id, 'yuyue_total');

                $this->fengmiMsg('预约成功！', U('user/yuyue/index'));

            }

            $this->fengmiMsg('操作失败！');

        } else {

            $this->assign('shop_id', $shop_id);

            $this->assign('detail', $detail);

            $this->display();

        }

    }

    public function checkBook(){

        $data = $this->checkFields($this->_post('data', false), array('name', 'mobile', 'type', 'content', 'yuyue_date', 'yuyue_time', 'number'));

        $data['user_id'] = (int) $this->uid;

        $data['name'] = htmlspecialchars($data['name']);

        if (empty($data['name'])) {

            $this->fengmiMsg('称呼不能为空');

        }

        $data['content'] = htmlspecialchars($data['content']);

        if (empty($data['content'])) {

            $this->fengmiMsg('留言不能为空');

        }

        $data['mobile'] = htmlspecialchars($data['mobile']);

        if (empty($data['mobile'])) {

            $this->fengmiMsg('手机不能为空');

        }

        if (!isMobile($data['mobile'])) {

            $this->fengmiMsg('手机格式不正确');

        }

        $data['yuyue_date'] = htmlspecialchars($data['yuyue_date']);

        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);

        if (empty($data['yuyue_date']) || empty($data['yuyue_time'])) {

            $this->fengmiMsg('预定日期不能为空');

        }

        if (!isDate($data['yuyue_date'])) {

            $this->fengmiMsg('预定日期格式错误！');

        }

        $data['number'] = (int) $data['number'];

        $data['create_time'] = NOW_TIME;

        $data['create_ip'] = get_client_ip();

        return $data;

    }

    //预约商家结束

    public function branch(){

        $shop_id = I('shop_id', 0, 'intval,trim');

        $this->assign('shop_id', $shop_id);

        $keyword = $this->_param('keyword', 'htmlspecialchars');

        $this->assign('keyword', $keyword);

        $this->assign('nextpage', LinkTo('shop/branchload', array('keyword' => $keyword, 'shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));

        $this->display();

        // 输出模板

    }

    public function branchload(){

        $shop_id = I('shop_id', 0, 'intval,trim');

        $branch_id = (int) $this->_get('branch_id');

        $shopbranch = D('Shopbranch');

        import('ORG.Util.Page');

        $map = array('shop_id' => $shop_id, 'closed' => 0, 'audit' => 1);

        $count = $shopbranch->where($map)->count();

        $Page = new Page($count, 8);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $shopbranch->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $val) {

            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);

        }

        $this->assign('page', $show);

        $this->assign('list', $list);

        $this->display();

    }

    //分店重写

    public function branches(){

        $shop_id = (int) $this->_get('shop_id');

        $branch_id = (int) $this->_get('branch_id');

        import('ORG.Util.Page');

        $detail = D('Shopbranch')->find($branch_id);

        if (empty($detail) || $detail['shop_id'] != $shop_id) {

            $this->error('该分店不存在');

        }

        if ($detail['closed'] != 0 || $detail['audit'] != 1) {

            $this->error('该分店不存在');

            die;

        }

        //调用分店数据

        $this->assign('goods', $goods = D('Goods')->where(array('shop_id' => $shop_id, 'branch_id' => $branch_id, 'city_id' => $this->city_id, 'audit' => 1, 'closed' => 0, 'end_date' => array('EGT', TODAY)))->order('goods_id desc')->limit(0, 12)->select());

        $this->assign('tuan', $tuan = D('Tuan')->where(array('shop_id' => $shop_id, 'branch_id' => $branch_id, 'city_id' => $this->city_id, 'audit' => 1, 'closed' => 0, 'end_date' => array('EGT', TODAY)))->order('tuan_id desc ')->limit(0, 10)->select());

        //调用总店数据

        $list = D('Shopbranch')->where(array('shop_id' => $shop_id, 'closed' => 0, 'audit' => 1))->select();

        $shopdetail = D('Shop')->find($shop_id);

        array_unshift($list, $shopdetail);

        foreach ($list as $k => $val) {

            if ($val['branch_id'] == $branch_id) {

                unset($list[$k]);

            }

        }

        D('Shopbranch')->updateCount($branch_id, 'view');

        $this->assign('detail', $detail);

        $this->display();

    }

    //增加团购

    public function tuan(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('detail', $detail);

        $this->assign('nextpage', LinkTo('shop/tuanload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));

        $this->display();

        // 输出模板

    }

    public function tuanload(){

        $shop_id = (int) $this->_get('shop_id');

        $tuanload = D('Tuan');

        import('ORG.Util.Page');

        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));

        $count = $tuanload->where($map)->count();

        $Page = new Page($count, 5);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $tuanload->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->display();

        // 输出模板

    }

	

		

	 //增加商城

    public function goods() {

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

		$map = array('shop_id' => $shop_id);

		$Goodsshopcate = D('Goodsshopcate')->where($map)->select();

		$this->assign('goodsshopcate', $Goodsshopcate); 

        $this->assign('detail', $detail);

		

		//商品

		$Goods = D('Goods');

        $goods_map = array('shop_id' => $shop_id,'closed' => 0,'audit' => 1, 'end_date' => array('EGT', TODAY));

        $count = $Goods->where($goods_map)->count();

        $list = $Goods->where($goods_map)->order(array('create_time' => 'desc'))->select();

        $this->assign('list', $list);

		

        $this->display();

    }

    //增加优惠劵

    public function coupon()

    {

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('detail', $detail);

        $this->assign('nextpage', LinkTo('shop/couponload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));

        $this->display();

        // 输出模板

    }

    public function couponload(){

        $shop_id = (int) $this->_get('shop_id');

        $couponload = D('Coupon');

        import('ORG.Util.Page');

        $map = array('audit' => 1,'closed' => 0, 'shop_id' => $shop_id, 'expire_date' => array('EGT', TODAY));

        $count = $couponload->where($map)->count();

        $Page = new Page($count, 5);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $couponload->where($map)->order(array('coupon_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->display();

    }

	 //积分兑换

	public function jifen(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        $this->assign('nextpage', LinkTo('shop/jifenloaddata', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));

        $this->assign('detail', $detail);

        $this->display();

    }

    public function jifenloaddata(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        $obj = D('Integralgoods');

        import('ORG.Util.Page');

        $map = array('closed' => 0,'audit' => 1, 'shop_id' => $detail['shop_id']);

        $count = $obj->where($map)->count();

        $Page = new Page($count, 25);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $obj->where($map)->order(array('exchange_num' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->assign('page', $show);

        $this->display();

    }



    //团购图文详情

    public function pic(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		

		$list = D('Shoppic')->get_shop_pic_array($shop_id );//获取商家全部图片结合

		$this->assign('list', $list);

        $this->assign('detail', $detail);

        $this->display();

    }

    public function life() {

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('nextpage', LinkTo('shop/lifeload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));

        $this->assign('detail', $detail);

        $this->display();

    }

    public function lifeload(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        $Life = D('Life');

        import('ORG.Util.Page');

        $map = array('audit' => 1, 'city_id' => $this->city_id, 'user_id' => $detail['user_id']);

        $count = $Life->where($map)->count();

        $Page = new Page($count, 25);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $Life->where($map)->order(array('top_date' => 'desc', 'last_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->assign('page', $show);

        $this->display();

    }

    public function news(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

		$this->assign('nav', $nav = D('Shopnav')->where(array('shop_id' => $shop_id))->find());

        $this->assign('nextpage', LinkTo('shop/newsload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));

        $this->assign('detail', $detail);

        $this->display();

    }

    public function newsload(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        $Shopnews = D('Shopnews');

        import('ORG.Util.Page');

        $map = array('audit' => 1, 'city_id' => $this->city_id, 'shop_id' => $shop_id);

        $count = $Shopnews->where($map)->count();

        $Page = new Page($count, 10);

        $show = $Page->show();

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $Shopnews->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->assign('page', $show);

        $this->display();

    }

	public function news_detail($news_id = 0) {

        if ($news_id = (int) $news_id) {

            $obj = D('Shopnews');

            if (!$detail = $obj->find($news_id)) {

                $this->error('没有该文章');

            }

			if ($detail['audit'] != 1 ) {

            	$this->error('该文章不存在');

            }	

			$obj->updateCount($news_id, 'views');

            $this->assign('detail', $detail);

            $this->display();

        } else {

            $this->error('没有该文章');

        }

    }

     //增加商城商品

    public function mall(){

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        $this->assign('detail', $detail);

        $this->assign('nextpage', LinkTo('mallonload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));

		$this->assign('goodsshopcates', $goodsshopcates = D('Goodsshopcate')->order('orderby desc')->where(array('shop_id' => $shop_id))->select());

        $this->display();

    }

    public function mallonload(){

        $shop_id = (int) $this->_get('shop_id');

        $Goods = D('Goods');

        import('ORG.Util.Page');

        $map = array('closed' => 0, 'audit' => 1, 'shop_id' => $shop_id, 'end_date' => array('ELT', TODAY));

		

		$shopcate_id = (int) $this->_param('shopcate_id');

        if ($shopcate_id) {

            $map['shopcate_id'] = $shopcate_id;

        }

		

        $count = $Goods->where($map)->count();

        $Page = new Page($count, 5);

        $show = $Page->show();

		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';

        $p = $_GET[$var];

        if ($Page->totalPages < $p) {

            die('0');

        }

        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);

        $this->display();

    }

    public function recognition() {

        $shop_id = (int) $this->_get('shop_id');

        if (!($detail = D('Shop')->find($shop_id))) {

            $this->error('没有该商家');

            die;

        }

        if ($detail['closed']) {

            $this->error('该商家已经被删除');

            die;

        }

        if ($this->isPost()) {

            $data = $this->checkFields($this->_post('data', false), array('name', 'mobile', 'content'));

            if (D('Shop')->find(array('where' => array('user_id' => $this->uid)))) {

                $this->fengmiMsg('您已经拥有一家店铺了！不能认领了！', U('distributors/index/index'));

            }

            if (D('Shoprecognition')->where(array('user_id' => $this->uid))->find()) {

                $this->fengmiMsg('您已经认领过一家商铺了，不能认领了哦！');

            }

            $data['user_id'] = (int) $this->uid;

            $data['shop_id'] = (int) $shop_id;

            $data['name'] = htmlspecialchars($data['name']);

            if (empty($data['name'])) {

                $this->fengmiMsg('称呼不能为空');

            }

            $data['content'] = htmlspecialchars($data['content']);

            if (empty($data['content'])) {

                $this->fengmiMsg('留言不能为空');

            }

            $data['mobile'] = htmlspecialchars($data['mobile']);

            if (empty($data['mobile'])) {

                $this->fengmiMsg('手机不能为空');

            }

            if (!isMobile($data['mobile'])) {

                $this->fengmiMsg('手机格式不正确');

            }

            $data['create_time'] = NOW_TIME;

            $data['create_ip'] = get_client_ip();

            $obj = D('Shoprecognition');

            $data['code'] = $obj->getCode();

            //保证唯一性

            if ($obj->add($data)) {

                D('Sms')->sms_shop_recognition_admin($this->_CONFIG['site']['config_mobile'],$detail['shop_name'],$data['name']);//认领商家通知管理员

            }

            $this->fengmiMsg('恭喜，认领成功，等待管理员审核', U('Wap/shop/index'));

        } else {

            $this->assign('shop_id', $shop_id);

            $this->assign('detail', $detail);

            $this->display();

        }

    }

	//点餐页面

	public function ele(){

        $shop_id = (int) $this->_param('shop_id');

        if (!($detail = D('Ele')->find($shop_id))) {

            $this->error('该餐厅不存在');

        }

        if (!($shop = D('Shop')->find($shop_id))) {

            $this->error('该餐厅不存在');

        }

        $Eleproduct = D('Eleproduct');

        $map = array('closed' => 0, 'audit' => 1, 'shop_id' => $shop_id);

        $list = $Eleproduct->where($map)->order(array('sold_num' => 'desc', 'price' => 'asc'))->select();

        foreach ($list as $k => $val) {

            $list[$k]['cart_num'] = $this->cart[$val['product_id']]['cart_num'];

        }

        $this->assign('list', $list);

        $this->assign('detail', $detail);

        $this->assign('cates', D('Elecate')->where(array('shop_id' => $shop_id, 'closed' => 0))->select());

        $this->assign('shop', $shop);

        $this->display();

    }

	

	//订座

	public function booking($shop_id = 0){

		$shop_id = (int) $this->_param('shop_id');

		$Booking = D('Booking');

        if(!$shop_id = (int)$shop_id){

            $this->error('该商家不存在');

        }elseif(!$detail = $Booking->find($shop_id)){

			$this->error('该商家不存在');

        }elseif($detail['audit'] !=1||$detail['closed']!=0){

            $this->error('该商家已删除或未审核');

        }else{

            $lat = addslashes(cookie('lat'));

            $lng = addslashes(cookie('lng'));

            if (empty($lat) || empty($lng)) {

                $lat = $this->city['lat'];

                $lng = $this->city['lng'];

            }

            $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);

			$pics = D('Shopdingpics')->where(array('shop_id'=>$shop_id))->select();

            $pics[] = array('photo'=>$detail['photo']);

            $this->assign('photos',$pics);

            $dianping = D('Shopdingdianping');

            import('ORG.Util.Page'); 

            $map = array('closed' => 0, 'shop_id' => $shop_id);

            $list = $dianping->where($map)->order(array('order_id' => 'desc'))->limit(2)->select();

            $user_ids = $order_ids = array();

            foreach ($list as $k => $val) {

                $user_ids[$val['user_id']] = $val['user_id'];

                $order_ids[$val['order_id']] = $val['order_id'];

            }

            if (!empty($user_ids)) {

                $this->assign('users', D('Users')->itemsByIds($user_ids));

            }

            if (!empty($order_ids)) {

                $this->assign('pics', D('Bookingdianpingpic')->where(array('order_id' => array('IN', $order_ids)))->select());

            }

            $coupon_list = D('Coupon')->where(array('shop_id'=>$detail['shop_id']))->limit(2)->select();

            $this->assign('coupon_list',$coupon_list);

            $menus = D('Bookingmenu')->where(array('shop_id'=>$shop_id,'is_tuijian'=>1))->limit(8)->select();

            $this->assign('menus',$menus);

            $less_count = $Booking->where(array('audit'=>1,'closed'=>0,'score'=>array('ELT',$detail['score'])))->count();

            $total_count = $Booking->where(array('audit'=>1,'closed'=>0))->count();

            $high_to = round(($less_count/$total_count)*100,2);

            $this->assign('high_to',$high_to);

            $filter = array('audit'=>1,'closed'=>0,'city_id'=>$this->city_id,'shop_id'=>array('NEQ',$shop_id));

            $more_list = $Booking->where($filter)->limit(2)->select();

            foreach ($more_list as $k => $val) {

                $more_list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);

            }

            $this->assign('more_list',$more_list);

            $this->assign('list', $list); 

            $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 

            $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 

            $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 

            $this->assign('ding_type',htmlspecialchars($_COOKIE['ding_type'])); 

			$this->assign('detail',$detail);

			$this->assign('shop_id',$shop_id);

            $this->display();

		}

		

	}

	//酒店

	 public function hotel($shop_id =0){

		$shop_id = (int) $this->_param('shop_id');

        $obj = D('Hotel');

		$Hotel = $obj->where(array('shop_id'=>$shop_id))->find();

        if(!($detail = $obj->find($Hotel['hotel_id']))) {

            $this->error('该酒店不存在');

        } elseif ($detail['closed'] == 1 || $detail['audit'] == 0) {

            $this->error('该酒店已删除或未通过审核');

        } else {

            $lat = addslashes(cookie('lat'));

            $lng = addslashes(cookie('lng'));

            if (empty($lat) || empty($lng)) {

                $lat = $this->city['lat'];

                $lng = $this->city['lng'];

            }

            $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);

            $pics = D('Hotelpics')->where(array('hotel_id' => $hotel_id))->select();

            $pics[] = array('photo' => $detail['photo']);

            $into_time = htmlspecialchars($_COOKIE['into_time']);

            $out_time = htmlspecialchars($_COOKIE['out_time']);

            $room_list = D('Hotelroom')->where(array('hotel_id' => $hotel_id))->select();

            $room_count = D('Hotelroom')->where(array('hotel_id' => $hotel_id))->count();

            $this->assign('room_list', $room_list);

            $this->assign('room_count', $room_count);

            $tuan_list = D('Tuan')->where(array('audit' => 1, 'closed' => 0, 'bg_date' => array('ELT', NOW), 'shop_id' => $detail['shop_id']))->limit(3)->select();

            $this->assign('tuan_list', $tuan_list);

            $this->assign('into_time', $into_time);

            $this->assign('out_time', $out_time);

            $this->assign('detail', $detail);

            $this->assign('pics', $pics);

			$this->assign('shop_id',$shop_id);

            $this->display();

        }

    }

	

    public function breaks($shop_id){

        //优惠买单

        if (!$this->uid) {

            $this->error('请登录', U('passport/login'));

        }

        $shop_id = (int) $shop_id;

        if (!$shop_id) {

            $this->error('该商家没有设置买单优惠');

        } elseif (!($detail = D('Shopyouhui')->where(array('shop_id' => $shop_id, 'is_open' => 1))->find())) {

            $this->error('该商家没有设置买单优惠或已关闭');

        }

        if ($detail['audit'] == 0) {

            $this->error('商家优惠未通过审核');

        }

        $breaksorder = D('Breaksorder')->where(array('user_id' => $this->uid))->order(array('create_time' => 'desc'))->find();

        $breaksorder_time = NOW_TIME;

        $cha = $breaksorder_time - $breaksorder['create_time'];

        if ($cha < 30) {

            $this->success('提交太频繁！', U('shop/detail', array('shop_id' => $shop_id)));

        }

        if ($this->isPost()) {

            $amount = floatval($_POST['amount']);

            if (empty($amount)) {

                $this->fengmiMsg('消费金额不能为空');

            }

            $exception = floatval($_POST['exception']);

            $need_pay = D('Shopyouhui')->get_amount($shop_id, $amount, $exception);

            $data = array('shop_id' => $shop_id, 'user_id' => $this->uid, 'amount' => $amount, 'exception' => $exception, 'need_pay' => $need_pay, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());

            if ($order_id = D('Breaksorder')->add($data)) {

                $this->fengmiMsg('创建订单成功！', U('shop/breakspay', array('order_id' => $order_id)), U('shop/breakspay', array('order_id' => $order_id)));

            } else {

                $this->fengmiMsg('创建订单失败！');

            }

        } else {

            $this->assign('detail', $detail);

            $this->mobile_title = '优惠买单';

            $this->display();

        }

    }

    public function breakspay(){

        if (empty($this->uid)) {

            $this->error('请登录', U('passport/login'));

        }

        $order_id = (int) $this->_get('order_id');

        $order = D('Breaksorder')->find($order_id);

        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {

            $this->fengmiMsg('该订单不存在');

        }

        $shop = D('Shop')->find($order['shop_id']);

        $this->assign('payment', D('Payment')->getPayments(true));

        $this->assign('shop', $shop);

        $this->assign('order', $order);

        $this->display();

    }

    public function breakspay2(){

        if (empty($this->uid)) {

            $this->error('请登录', U('passport/login'));

        }

        $order_id = (int) $this->_get('order_id');

        $order = D('Breaksorder')->find($order_id);

        if (empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid) {

            $this->fengmiMsg('该订单不存在');

        }

        if (!($code = $this->_post('code'))) {

            $this->fengmiMsg('请选择支付方式！');

        }

        $logs = D('Paymentlogs')->getLogsByOrderId('breaks', $order_id);

        if (empty($logs)) {

            $logs = array('type' => 'breaks', 'user_id' => $this->uid, 'order_id' => $order_id, 'code' => $code, 'need_pay' => $order['need_pay'] * 100, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip(), 'is_paid' => 0);

            $logs['log_id'] = D('Paymentlogs')->add($logs);

        } else {

            $logs['need_pay'] = $order['need_pay'] * 100;

            $logs['code'] = $code;

            D('Paymentlogs')->save($logs);

        }

        $this->fengmiMsg('买单订单设置完毕，即将进入付款。', U('payment/payment', array('log_id' => $logs['log_id'])));

    }
  
  	public function callorder(){
    	$order=$_POST;
      	//$this->ajaxReturn($order);
      	if(!$_POST['token']){
        	$data['status']=1;
            $data['msg']='请发送用户token';
          	$this->ajaxReturn($data);
        }
        if(!$_POST['small_type']||!$_POST['big_type']){
        	$data['status']=1;
            $data['msg']='请选择类型';
          	$this->ajaxReturn($data);
        }
      	if(!$_POST['money']){
        	$data['status']=1;
            $data['msg']='请输入金额';
          	$this->ajaxReturn($data);
        }
      	if(!$_POST['detail']){
        	$data['status']=1;
            $data['msg']='请输入订单详情';
          	$this->ajaxReturn($data);
        }      	
      	if(!$_POST['address']){
        	$data['status']=1;
            $data['msg']='请输入地址';
          	$this->ajaxReturn($data);
        }
  
      	      	if(empty($_POST['x'])||empty($_POST['y'])){
        	$data['status']=1;
          	$data['msg']="请传入xy坐标";
          	$this->ajaxReturn($data);
        }
      	$user=D('users')->where(['token'=>$_POST['token']])->find();
      	if(!$user['token']){
        	$data['status']=1;
            $data['msg']='未找到用户';
          	$this->ajaxReturn($data);
        }
      	$order['user_id']=$user['user_id'];
      //订单编号

      	$order_sn = date('ymdHis').substr(microtime(),2,4);
      	$address=D('paddress')->where(['id'=>$_POST['address']])->find();
      	$order['order_id'] = $order_sn;
      	$order['money'] = $_POST['money']*100;
      	$order['community_id'] = $address['community_id'];
      	$order['expiry_time']=time()+3600;
      	$order['pay_time']=time()+600;
        $order['create_time']=time();
     	$a=D('shop_cate')->where(['cate_id'=>$_POST['big_type']])->find();
      	$order['firstcate']=$a['cate_name'];
      	$b=D('shop_cate')->where(['cate_id'=>$_POST['small_type']])->find();
      	$order['secondcate']=$b['cate_name'];
      	$res=M('shop_order')->add($order); 
      	if($res){
        	$data['status']=0;
          	$data['order']['id']=$order_sn;
          	$data['order']['money']=$_POST['money'];
          	$data['order']['detail']=$_POST['detail'];
            $data['msg']='订单生成成功';
          	$this->ajaxReturn($data);
        }else{
        	$data['status']=1;
            $data['msg']='订单生成失败';
          	$this->ajaxReturn($data);
        }
    }
  
  	public function getorder(){
      	 if(!$_POST['token']){
        	$data['status']=1;
            $data['msg']='未找到该用户';
          	$this->ajaxReturn($data);
        }
      	 //if(!isset($_POST['type'])){
        	//$data['status']=1;
           // $data['msg']='请传入订单类型';
          	//$this->ajaxReturn($data);
        //}
      	   // $type="";
          //	$is_pay="";
          //	$is_ready="";
          	//$is_comment="";
		
 		if(isset($_POST['is_pay'])){
          	$where['bao_shop_order.is_pay']=$_POST['is_pay'];
        }
 		if(isset($_POST['is_ready'])){
          	$where['bao_shop_order.is_ready']=$_POST['is_ready'];
        }
        if(isset($_POST['is_comment'])){
          	$where['bao_shop_order.is_comment']=$_POST['is_comment'];
        }
      	if(isset($_POST['type']) and ($_POST['type']!=0)){
          	$where['bao_shop_order.status']=$_POST['type'];
        }
      	
      	$user_id=$this->getuid($_POST['token']);
      	$where['bao_shop_order.user_id']=$user_id;
    	$res=M('shop_order')

          				->where($where)
          				
          				->select();
      	//echo M('shop_order')->getLastSql();die;
		//$this->ajaxReturn($res);
      	foreach($res as $k=>$list){
          
 					$data[$k]['detail']['id']=$list['order_id'];
          			$data[$k]['detail']['dls']=$list['detail'];
              		$userinfo=M('users')
          					->where(['user_id'=>$list['user_id']])
          					->find();
          			$data[$k]['detail']['users']['avatar']=$userinfo['face'];
              		$data[$k]['detail']['users']['name']=$userinfo['nickname'];
              		$data[$k]['detail']['users']['tel']=$userinfo['mobile'];
              		$add=M('paddress')
          					->where(['id'=>$list['address']])
          					->find();
              		$data[$k]['detail']['users']['address']['info']=$add['area_str'];
              		$data[$k]['detail']['users']['address']['dls']=$add['info'];
              		$data[$k]['detail']['service']['first']['id']=$list['big_type'];
              		$data[$k]['detail']['service']['first']['name']=$list['firstcate'];
              		$data[$k]['detail']['service']['second']['id']=$list['small_type'];
              		$data[$k]['detail']['service']['second']['name']=$list['secondcate'];
              		$data[$k]['detail']['service']['money']=$list['money'];
              $data[$k]['detail']['time']['play']=$list['ready_time'];
              $data[$k]['detail']['time']['pay']=$list['pay_time'];
              $data[$k]['detail']['time']['create']=$list['create_time'];
              $data[$k]['detail']['time']['arrive']=0;
              $data[$k]['detail']['money']['actual']=0;
              $data[$k]['detail']['money']['discount']=0;
              $data[$k]['detail']['money']['red']=0;
              $userinfo2=M('users')
          					->where(['user_id'=>$list['do_user_id']])
          					->find();
                    $data[$k]['detail']['bus']['avatar']=$userinfo2['face'];
              		$data[$k]['detail']['bus']['name']=$userinfo2['nickname'];
              		$data[$k]['detail']['bus']['tel']=$userinfo2['mobile'];
              		$data[$k]['detail']['bus']['tel']=0;
              		$data[$k]['detail']['state']['status']=$list['status'];
					$data[$k]['detail']['state']['ready']=$list['is_ready'];
              		$data[$k]['detail']['state']['comment']=$list['is_comment'];
          	
        }
      	$order['order']=$data;
      	if(empty($order['order'])){
          	$order['order']=[];
         	$order['status']=1;
            $order['msg']='未查询到订单';
          	$this->ajaxReturn($order); 
        }
      	$order['status']=0;
      	$this->ajaxReturn($order);
    }
  
  	//根据一级类查询店铺
	public function firstshop(){
      	if(!$_GET['channel_id']){
        	$data['code']=1;
            $data['msg']='输入类别';
          	$this->ajaxReturn($data);
        }
     	$cate=D('shop_cate')->where(['cate_id'=>$_GET['channel_id']])->field('cate_id')->select();
      	foreach($cate as $v){
        	$list[]=$v['cate_id'];
        }
        $where['cate_id'] = array('in',$list);
        $shoplist=D('shop')->where($where)->select();
      	if(empty($shoplist)){
        	$data['code']=1;
            $data['msg']='未搜索到';
          	$this->ajaxReturn($data);
        }else{
          	$data['code']=0;
            $data['shoplist']=$shoplist;
        	$this->ajaxReturn($data);
        }
      	
    }
  
  	public function firstcate(){
    	$data['firstcate']=D('shop_cate')->where(['channel_id'=>1])->field('cate_id,cate_name')->order('cate_id asc')->select();
      	$data['status']=0;
      	$this->ajaxReturn($data);
    }
  
  	public function secondcate(){
    	$data['secondcate']=D('shop_cate')->where(['parent_id'=>$_GET['cate_id']])->field('parent_id,cate_id,cate_name')->order('cate_id asc')->select();
      	$data['status']=0;
      	$this->ajaxReturn($data);
    }
    	//根据二级类查询店铺
	public function secondshop(){
      	if(!$_GET['cate_id']){
        	$data['code']=1;
            $data['msg']='输入类别';
          	$this->ajaxReturn($data);
        }
     	//$cate=D('Lifecate')->where(['channel_id'=>$_GET['channel_id']])->field('cate_id')->select();
      	//foreach($cate as $v){
        //	$list[]=$v['cate_id'];
        //}
        //$where['cate_id'] = array('in',$list);
        $shoplist=D('shop')->where(['cate_id'=>$_GET['cate_id']])->select();
      	if(empty($shoplist)){
        	$data['code']=1;
            $data['msg']='未搜索到';
          	$this->ajaxReturn($data);
        }else{
          	$data['code']=0;
            $data['shoplist']=$shoplist;
        	$this->ajaxReturn($data);
        }
      	
    }
  
  	public function shopdetail(){
    	$data['detail']=D('shop')->where(['shop_id'=>$_GET['shop_id']])->find();
      	$data['status']=1;
      	$this-ajaxReturn($data);
    }
  
  	public function allcate(){
    	$firstcate=D('shop_cate')->where(['channel_id'=>1])->field('cate_id,cate_name,url,photo')->order('cate_id asc')->select();
      	$data['firstcate']=$firstcate;
      	foreach ($firstcate as $k=>$v) {
            $data['firstcate'][$k]['second']=D('shop_cate')->where(['parent_id'=>$v['cate_id']])->field('cate_id,cate_name,url')->order('cate_id asc')->select();
        }
      	
		$data['all']['cate_id']=0;
      	$data['all']['cate_name']=全部;
      	$data['all']['url']="http://tk.qmlan.com/2017-12-20_5a3a215c65b99.png";
      	$data['all']['photo']="http://tk.qmlan.com/2017-12-20_5a3a215c65b99.png ";
     	 $data['all']['second']=D('shop_cate')->where(['channel_id'=>0])->field('cate_id,cate_name,url')->order('cate_id asc')->select();
      	$data['status']=0;
      	$this->ajaxReturn($data);
    }
  
    public function get_distance($from,$to,$km=true,$decimal=2){ 
      	//$from=[30.3072442662,120.2561759949];
      	//$to=[30.3134684987,120.3737640381];
        sort($from);  
        sort($to);  
        $EARTH_RADIUS = 6370.996; // 地球半径系数  

        $distance = $EARTH_RADIUS*2*asin(sqrt(pow(sin( ($from[0]*pi()/180-$to[0]*pi()/180)/2),2)+cos($from[0]*pi()/180)*cos($to[0]*pi()/180)* pow(sin( ($from[1]*pi()/180-$to[1]*pi()/180)/2),2)))*1000;  

        if($km){  
            $distance = $distance / 1000;  
        }  
    	$a= round($distance, $decimal);
      	return $a;
	}
  	
  	public function getorderinfo(){
          if(empty($_POST['token'])){
        	$data['status']=1;
          	$data['msg']="请传入用户token";
          	$this->ajaxReturn($data);
        }
      	if(empty($_POST['order_id'])){
        	$data['status']=1;
          	$data['msg']="请传入订单ID";
          	$this->ajaxReturn($data);
        }else{
          $id = $_POST['order_id'];
          //$id =1;
          $order_id=$id;
          $user_id=$this->getuid($_POST['token']);
          
    		$list=M('shop_order')
          				->where(['order_id'=>$order_id])
          				->select();
          $list=$list[0];
          //dump($list);

            if(!empty($list)){
				    $data['detail']['id']=$list['order_id'];
          			$data['detail']['dls']=$list['detail'];
              		$userinfo=M('users')
          					->where(['user_id'=>$list['user_id']])
          					->find();
          			$data['detail']['users']['avatar']=$userinfo['face'];
              		$data['detail']['users']['name']=$userinfo['nickname'];
              		$data['detail']['users']['tel']=$userinfo['mobile'];
              		$add=M('paddress')
          					->where(['id'=>$list['address']])
          					->find();
              		$data['detail']['users']['address']['info']=$add['area_str'];
              		$data['detail']['users']['address']['dls']=$add['info'];
              		$data['detail']['service']['first']['id']=$list['big_type'];
              		$data['detail']['service']['first']['name']=$list['firstcate'];
              		$data['detail']['service']['second']['id']=$list['small_type'];
              		$data['detail']['service']['second']['name']=$list['secondcate'];
              		$data['detail']['service']['money']=$list['money'];
              $data['detail']['time']['play']=$list['ready_time'];
              $data['detail']['time']['pay']=$list['pay_time'];
              $data['detail']['time']['create']=$list['create_time'];
              $data['detail']['time']['arrive']=0;
              $data['detail']['money']['actual']=0;
              $data['detail']['money']['discount']=0;
              $data['detail']['money']['red']=0;
              $userinfo2=M('users')
          					->where(['user_id'=>$list['do_user_id']])
          					->find();
                    $data['detail']['bus']['avatar']=$userinfo2['face'];
              		$data['detail']['bus']['name']=$userinfo2['nickname'];
              		$data['detail']['bus']['tel']=$userinfo2['mobile'];
              		$data['detail']['bus']['tel']=0;
              		$data['detail']['state']['status']=$list['status'];
					$data['detail']['state']['ready']=$list['is_ready'];
              		$data['detail']['state']['comment']=$list['is_comment'];
               		$data['status']=0;
           
                //$data['list']=$list;
                $this->ajaxReturn($data); 
            }else{
                $data['status']=1;
                $data['msg']='暂无此订单详情';
                $this->ajaxReturn($data);
         }
          
        }     
    }
  
  	public function get_city(){
    	 $getIp=$_SERVER["REMOTE_ADDR"];
        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=YWNt8VcHK7Goj1yljLlMVHnWl6ZWS26t&ip={$getIp}&coor=bd09ll");
        $data['city'] = json_decode($content);
      	$this->ajaxReturn($data);
    }

}