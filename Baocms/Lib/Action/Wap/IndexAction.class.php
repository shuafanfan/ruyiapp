<?php





class IndexAction extends CommonAction {



      public function index() {

           function counter($f_value)
          {
           
           $fp = fopen($f_value,'r') or die('打开文件时出错。');
           $countNum = fgets($fp,1024);
           fclose($fp);
           $countNum++;
           $fpw = fopen($f_value,'w');
           fwrite($fpw,$countNum);
           fclose($fpw);
          }		
            $filepath = 'count.txt';
    		if (!file_exists($filepath))
             {

             $fp = fopen($filepath,'w');

             fwrite($fp,0);
              fclose($fp);
              counter($filepath);
             }else{
              counter($filepath);
             }



		

        
		$data['ad']=D('ad')->where(['site_id'=>57,'closed'=>0])->select();
        $data['ad2']=D('ad')->where(['site_id'=>62,'closed'=>0])->select();
        //$this->assign('lifecate', D('Lifecate')->fetchAll());
		//$data['lifecate']=D('Lifecate')->fetchAll();
        $data['channel']=D('shop_cate')->where(['channel_id'=>1])->select();

        //$data['news'] = D('Article')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order(array('create_time' => 'desc'))->limit(0, 10)->select();



		 //$data['ele'] = D('Ele')->where(array('city_id'=>$this->city_id, 'audit' => 1))->order(array('orderby' => 'desc'))->limit(0, 10)->select();



		 //$data['life'] = D('Life')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order(array('create_time' => 'desc'))->limit(0, 10)->select();
        //当前经纬度获取
        $getIp=$_SERVER["REMOTE_ADDR"];

        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=YWNt8VcHK7Goj1yljLlMVHnWl6ZWS26t&ip={$getIp}&coor=bd09ll");
//http://api.map.baidu.com/highacciploc/v1?qcip={$getIp}&qterm=pc&ak=YWNt8VcHK7Goj1yljLlMVHnWl6ZWS26t&coord=bd09ll
        //http://api.map.baidu.com/location/ip?ak=YWNt8VcHK7Goj1yljLlMVHnWl6ZWS26t&ip={$getIp}&coor=bd09ll
        $json = json_decode($content);

        $lng = $json->{'content'}->{'point'}->{'x'};//按层级关系提取经度数据
        $lat = $json->{'content'}->{'point'}->{'y'};//按层级关系提取纬度数据
		$data['location']=$json;
		//dump($json);die;
	    $shoplist = D('Shop')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order($orderby)->limit(0, 10)->select();
		foreach ($shoplist as $k => $val) {
            $shoplist[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }

        
        $data['shoplist']=$shoplist;



		$maps = array('status' => 2,'closed'=>0);

		$nav = D('Navigation') ->where($maps)->order(array('orderby' => 'asc'))->select();

		$bg_time = strtotime(TODAY);

		//$data['sign_day'] = (int) D('Usersign')->where(array('user_id' => $this->uid, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();

        //$this->display();
		$this->ajaxReturn($data);
    }





    public function search() {

        $keys = D('Keyword')->fetchAll();

        $keytype = D('Keyword')->getKeyType();

        $this->assign('keys',$keys);

        $this->display();

    }



	 public function dingwei() {

        $lat = $this->_get('lat', 'htmlspecialchars');

        $lng = $this->_get('lng', 'htmlspecialchars');

        cookie('lat', $lat);

        cookie('lng', $lng);

        echo NOW_TIME;

    }



	public function more() {

		$maps = array('status' => 2,'closed'=>0);

		$this->assign('nav',$nav = D('Navigation') ->where($maps)->order(array('orderby' => 'asc'))->select());

		$this->display();

	}
	
  	public function shoplist(){
    	$data['status']=1;
      	$data['msg']="暂无数据";
    	$this->ajaxReturn($data);
    }

	public function cate(){
    	$list=D('shop_cate')->select();
      	foreach($list as $k=>$v){
        	if($v['channel_id']==1){
            	$data[$k]['first']['id']=$v['cate_id'];
             	$data[$k]['first']['name']=$v['cate_name'];
              	$data[$k]['first']['banner']['url']=$v['url'];
              	$data[$k]['first']['banner']['img']=$v['photo'];
              	$data[$k]['first']['banner']['title']=$v['title'];  	
              	//$data[$k]['first']['second']=$v;
            }
        }
      	foreach($list as $k=>$v){
          	//dump($v['parent_id']);
          	//dump($data[$k+2]['first']['id']);die;
          	foreach($data as $m=>$n){
              	//dump($n['first']['id']);
            	if($v['parent_id']==$n['first']['id']){
            		$data[$m]['first']['second'][$k]['id']=$v['cate_id'];
                  	$data[$m]['first']['second'][$k]['name']=$v['cate_name'];
                  	$data[$m]['first']['second'][$k]['icon']=$v['photo'];
            	}
              
            }
        	
        }
      			$data[0]['first']['id']=0;
             	$data[0]['first']['name']="全部";
              	$data[0]['first']['banner']['url']="";
              	$data[0]['first']['banner']['img']="";
              	$data[0]['first']['banner']['title']="";
      			$data2['data']=$data;
      	$this->ajaxReturn($data2);
    }


}

