<?php
class PassportAction extends CommonAction{
    private $create_fields = array('account', 'password', 'nickname');
    public function register(){
        if ($this->isPost()) {
          //dump($_POST);die;
 
            if (isMobile(htmlspecialchars($_POST['account']))) {
              $a=D('users')->where(['account'=>$_POST['account']])->find();
              	if ($a) {
                  $return['status']=1;
                  $return['msg']='账户已存在';
                  $this->ajaxReturn($return);
                     
                }
              	$scode=$_POST['scode'];
                //if (!($scode = trim($_POST['scode']))) {
                      // $return['status']=1;
                     // $return['msg']='请获取短信验证码';
                     // $this->ajaxReturn($return);
                //}
                $info = D('dayu_sms')->where(['mobile'=>$_POST['account'],'status'=>1])->order('sms_id desc')->find();
				//dump($info);
   				$scode2=$info['yzm'];
              	$expiry_time=$info['expiry_time']-60;
              	//dump(time());
              	//dump($expiry_time);die;
              	if (empty($_POST['community_id'])) {
                   $return['status']=1;
              		$return['msg']='请传入街道Id';
              		$this->ajaxReturn($return);
                }
               if (empty($_POST['rank_id'])) {
                   $return['status']=1;
              		$return['msg']='请传入注册会员类型';
              		$this->ajaxReturn($return);
                }
                if (empty($scode2)||(time()>$expiry_time)) {
                   $return['status']=1;
              		$return['msg']='验证码无效';
              		$this->ajaxReturn($return);
                }
              	//dump($scode);die;
                if ($scode != $scode2) {
                    $return['status']=1;
                    $return['msg']='请输入正确短信验证码啊啊';
                    $this->ajaxReturn($return);
                }
            }else{
            		$return['status']=1;
              		$return['msg']='验证码错误';
              		$this->ajaxReturn($return);
            }
          	$data=$_POST;
          
            //$data = $this->createCheck();
            //$invite_id = (int) cookie('invite_id');
            //if (!empty($invite_id)) {
            //    $data['invite_id'] = $invite_id;
           // }
          	 $match='/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{6,20}$/';
             $str = $_POST['password'];
			
          	if (empty($data['rank_id'])) {
              $return['status']=1;
              $return['msg']='请输入用户类型';
              $this->ajaxReturn($return);
            }
			if (empty($data['password'])) {
              $return['status']=1;
              $return['msg']='请输入密码';
              $this->ajaxReturn($return);
            }
             if (!preg_match($match, $str)) {
                        $return['status']=1;
                              $return['msg']='请输入正确密码格式';
                              $this->ajaxReturn($return);
             }
            $wap_register_password2 = $this->_CONFIG['register']['wap_register_password2'];
          	//$this->ajaxReturn($_POST);
            if ($wap_register_password2 == 1) {
                $password2 = $this->_post('password2');
                if ($password2 !== $data['password']) {
                        $return['status']=1;
                        $return['msg']='两次密码不一样';
                        $this->ajaxReturn($return);
                }
            }
			
            //开始其他的判断了
          	 
            if (true == D('Passport')->register($data)) {
             
              $return['status']=0;
              $return['msg']='注册成功';
              $this->ajaxReturn($return);
                $this->fengmiMsg('恭喜您，注册成功！', U('index/index'));
            }
          	  $return['status']=1;
              $return['msg']='账户已存在';
              $this->ajaxReturn($return);
            $this->error(D('Passport')->getError());
        } else {
            //分销开始
            $fuid = (int) cookie('fuid');
            if ($fuid) {
                $profit_min_rank_id = (int) $this->_CONFIG['profit']['profit_min_rank_id'];
                $fuser = D('Users')->find($fuid);
                if ($fuser) {
                    $flag = false;
                    if ($profit_min_rank_id) {
                        $modelRank = D('Userrank');
                        $rank = $modelRank->find($profit_min_rank_id);
                        $userRank = $modelRank->find($fuser['rank_id']);
                        if ($rank) {
                            if ($userRank && $userRank['prestige'] >= $rank['prestige']) {
                                $flag = true;
                            } else {
                                $flag = false;
                            }
                        } else {
                            $flag = false;
                        }
                    } else {
                        $flag = true;
                    }
                    $fuser['nickname'] = empty($fuser['nickname']) ? $fuser['account'] : $fuser['nickname'];
                    if ($flag) {
                        $this->assign('fuser', $fuser);
                    }
                }
            }
            //分销结束
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['fuid1'] = htmlspecialchars($_POST['fuid1']);
        //微信自动分享
        $data['account'] = htmlspecialchars($_POST['account']);
        if (!isMobile($data['account'])) {
            session('verify', null);
            $this->fengmiMsg('只允许手机号注册，请检查~');
        }
        $data['password'] = htmlspecialchars($data['password']);
        //整合UC的时候需要
        $register_password = $this->_CONFIG['register']['register_password'];
        if (empty($data['password']) || strlen($data['password']) < $register_password) {
            session('verify', null);
            $this->fengmiMsg('请输入正确的密码!密码长度必须要在' . $register_password . '个字符以上', 2000, true);
        }
        $data['nickname'] = $data['account'];
        $data['token'] = $data['token'];
        $data['ext0'] = $data['account'];
        //兼容UCENTER
        $data['mobile'] = $data['account'];
        $data['reg_ip'] = get_client_ip();
        $data['reg_time'] = NOW_TIME;
        return $data;
    }
    public function sendsms(){
        if (!($mobile = htmlspecialchars($_POST['account']))) {
          		$return['status']=1;
              $return['msg']='请输入正确的手机号码';
              $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
        if (!isMobile($mobile)) {
          		$return['status']=1;
              $return['msg']='请输入正确的手机号码';
              $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
        if ($user = D('Users')->getUserByAccount($mobile)) {
          		$return['status']=1;
              $return['msg']='手机号码已经存在';
              $this->ajaxReturn($return);
            die('手机号码已经存在！');
        }
       
        //$randstring = session('scode');
       // if (empty($randstring)) {
        //    $randstring = rand_string(6, 1);
        //    session('scode', $randstring);
       // }
      	$randstring = rand_string(6, 1);
      	
		$res=D('Sms')->sms_yzm($mobile, $randstring);//发送短信

      	if(!$res){
        	  $return['status']=1;
              $return['msg']='发送失败，请稍后再试';
              $this->ajaxReturn($return);
        }else{
      		  $return['status']=0;
              $return['msg']='发送成功';
              $this->ajaxReturn($return);
        }
        die('1');
    }
  	public function test(){
    		  $return['status']=0;
              $return['msg']='12321';
              $this->ajaxReturn($return);
    }
    public function bind(){
        $this->display();
    }
    public function index(){
        $this->redirect('login');
    }
    public function login(){
      //dump($_POST);DIE;

        if ($this->isPost()) {
          
            $account = $this->_post('account');
            if (empty($account)) {
              $return['status']=1;
              $return['msg']='请输入用户名';
              $this->ajaxReturn($return);
                $this->fengmiMsg('请输入用户名!');
            }
            $password = $this->_post('password');
            if (empty($password)) {
              $return['status']=1;
              $return['msg']='请输入登录密码';
              $this->ajaxReturn($return);
                $this->fengmiMsg('请输入登录密码!');
            }
            $backurl = $this->_post('backurl', 'htmlspecialchars');
            if (empty($backurl)) {
                $backurl = U('user/member/index');
            }
            if (true == D('Passport')->login($account, $password)) {
              $return['status']=0;
              $return['msg']='登录成功';
              $token=D('users')->where(['account'=>$_POST['account']])->field('user_id,account,token,community_id,face,nickname,money,mobile')->find();
              $return['token']=$token['token'];
              $return['userinfo']=$token;
              setcookie("token",$token['token'], time()+3600);
              //dump($this);die;
              $this->ajaxReturn($return);
               //$this->fengmiMsg('恭喜您登录成功！', $backurl);
            } else {
              $return['status']=1;
              $return['msg']='用户名或密码不正确';
              $this->ajaxReturn($return);
              $this->fengmiMsg(D('Passport')->getError());
            }
        } else {
            if (!empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            } else {
                $backurl = U('user/member/index');
            }
          	$return['status']=1;
             $return['msg']='请提交数据过来';
             $this->ajaxReturn($return);
            $this->assign('backurl', $backurl);
            $this->display();
        }
    }
   public function login2(){
      //dump($_POST);DIE;
      header("Access-Control-Allow-Origin: *");
        if ($this->isPost()) {
          
            $account = $this->_post('account');
            if (empty($account)) {
              $return['status']=1;
              $return['msg']='请输入用户名';
              $this->ajaxReturn($return);
                
            }
         	
          	$res=D('dayu_sms')->where(['mobile'=>$account])->order('sms_id desc')->find();
          //dump($res['yzm']);die;
            if ($res['yzm']==$_POST['yzm']) {
              $return['status']=0;
              $return['msg']='登录成功';
               
              $token=D('users')->where(['account'=>$_POST['account']])->find();
              $return['token']=$token['token'];
              setcookie("token",$token['token'], time()+3600);
              //dump($this);die;
              $this->ajaxReturn($return);
               //$this->fengmiMsg('恭喜您登录成功！', $backurl);
            } else {
              $return['status']=1;
              $return['msg']='验证码不正确';
              $this->ajaxReturn($return);
            }
        } else {
            if (!empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            } else {
                $backurl = U('user/member/index');
            }
          	$return['status']=1;
             $return['msg']='请提交数据过来';
             $this->ajaxReturn($return);
            $this->assign('backurl', $backurl);
            $this->display();
        }
    } 
  	public function sendsms2(){
        if (!($mobile = htmlspecialchars($_POST['account']))) {
          		$return['status']=1;
              $return['msg']='请输入正确的手机号码';
              $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
        if (!isMobile($mobile)) {
          		$return['status']=1;
              $return['msg']='请输入正确的手机号码';
              $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
       
        //$randstring = session('scode');
       // if (empty($randstring)) {
        //    $randstring = rand_string(6, 1);
        //    session('scode', $randstring);
       // }
      	$randstring = rand_string(6, 1);
      	
		$res=D('Sms')->sms_yzm($mobile, $randstring);//发送短信

      	if(!$res){
        	  $return['status']=1;
              $return['msg']='发送失败，请稍后再试';
              $this->ajaxReturn($return);
        }else{
      		  $return['status']=0;
              $return['msg']='发送成功';
              $this->ajaxReturn($return);
        }
    }

	//微博登录
    public function wblogin(){
        $login_url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $this->_CONFIG['connect']['wb_app_id'] . '&response_type=code&redirect_uri=' . urlencode(__HOST__ . U('passport/wbcallback'));
        header("Location:{$login_url}");
        die;
    }
	//微博注册
	public function wbcallback(){
        import('@/Net.Curl');
        $curl = new Curl();
        $params = array('grant_type' => 'authorization_code', 'code' => $_REQUEST['code'], 'client_id' => $this->_CONFIG['connect']['wb_app_id'], 'client_secret' => $this->_CONFIG['connect']['wb_app_key'], 'redirect_uri' => __HOST__ . U('passport/wbcallback'));
        $url = 'https://api.weibo.com/oauth2/access_token';
        $response = $curl->post($url, http_build_query($params));
        $params = json_decode($response, true);
        if (isset($params['error'])) {
            echo '<h3>error:</h3>' . $params['error'];
            echo '<h3>msg  :</h3>' . $params['error_code'];
            die;
        }
        $url = 'https://api.weibo.com/2/account/get_uid.json?source=' . $this->_CONFIG['connect']['wb_app_key'] . '&access_token=' . $params['access_token'];
        $result = $curl->get($url);
        $user = json_decode($result, true);
      	
        if (isset($user['error'])) {
            echo '<h3>error:</h3>' . $user['error'];
            echo '<h3>msg  :</h3>' . $user['error_code'];
            die;
        }
        $data = array(
			'type' => 'weibo', 
			'open_id' => $user['uid'], 
			'token' => $params['access_token'], 
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip()
		);
        $this->thirdlogin($data);
    }
	
    public function qqlogin(){
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);
        $login_url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=' . $this->_CONFIG['connect']['qq_app_id'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/qqcallback')) . '&state=' . $state . '&scope=';
        header("Location:{$login_url}");
        die;
    }
	 public function qqcallback(){
        import('@/Net.Curl');
        $curl = new Curl();
        if ($_REQUEST['state'] == session('state')) {
            $token_url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&' . 'client_id=' . $this->_CONFIG['connect']['qq_app_id'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/qqcallback')) . '&client_secret=' . $this->_CONFIG['connect']['qq_app_key'] . '&code=' . $_REQUEST['code'];
            $response = $curl->get($token_url);
            if (strpos($response, 'callback') !== false) {
                $lpos = strpos($response, '(');
                $rpos = strrpos($response, ')');
                $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
                $msg = json_decode($response);
                echo '<h3>error:</h3>' . $msg->error;
                echo '<h3>msg  :</h3>' . $msg->error_description;
                die;
            }
            $params = array();
            parse_str($response, $params);
            if (empty($params)) {
                die;
            }
            $graph_url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $params['access_token'];
            $str = $curl->get($graph_url);
            if (strpos($str, 'callback') !== false) {
                $lpos = strpos($str, '(');
                $rpos = strrpos($str, ')');
                $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
            }
            $user = json_decode($str, true);
            if (isset($user['error'])) {
                echo '<h3>error:</h3>' . $user['error'];
                echo '<h3>msg  :</h3>' . $user['error_description'];
                die;
            }
            if (empty($user['openid'])) {
                die;
            }
			//如果是qq登录则获取头像昵称
            $user_info = D('Connect')->user_info($user['client_id'], $user['openid'], $params['access_token']);
            $face = $user_info['figureurl_qq_2'] == '' ? $user_info['figureurl_qq_1'] : $user_info['figureurl_qq_2'];
			
            $data = array(
				'type' => 'qq', 
				'client_id' => $user['client_id'], 
				'open_id' => $user['openid'], 
				'token' => $params['access_token'], 
				'nickname' => $user_info['nickname'], 
				'headimgurl' => $face, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			);
            $this->thirdlogin($data);
        }
    }
	//分销专用，关注注册
	public function wxauto(){
          file_put_contents('2.txt', var_export($url, true));
    }
	//分销专用，微信自动注册用户
	public function wxstart(){
        if ($_REQUEST['state'] == session('state')) {
            import('@/Net.Curl');
            $curl = new Curl();
            if (empty($_REQUEST['code'])) {
                $this->error('授权后才能登陆！', U('passport/login'));
            }
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->_CONFIG['weixin']['appid'] . '&secret=' . $this->_CONFIG['weixin']['appsecret'] . '&code=' . $_REQUEST['code'] . '&grant_type=authorization_code';
            $str = $curl->get($token_url);
            $params = json_decode($str, true);
            if (!empty($params['errcode'])) {
                echo '<h3>error:</h3>' . $params['errcode'];
                echo '<h3>msg  :</h3>' . $params['errmsg'];
                die;
            }
            if (empty($params['openid'])) {
                $this->error('登录失败', U('passport/login'));
            }
            $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $params['access_token'] . '&openid=' . $params['openid'] . '&lang=zh_CN';
            $info = $curl->get($info_url);
            $info = json_decode($info, true);
            $data = array(
				'type' => 'weixin', 
				'open_id' => $params['openid'], 
				'token' => $params['refresh_token'], 
				'nickname' => $info['nickname'], 
				'headimgurl' => $info['headimgurl']
			);
            $this->wxconn($data);
        }
    }
	
	 //分销专用微信自动注册为用户
    private function wxconn($data) {
        $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
        if (empty($connect)) {
            $connect = $data;
            $connect['connect_id'] = D('Connect')->add($data);
        }  
        if (empty($connect['uid'])) {
            session('connect', $connect['connect_id']);
            // 用户数据整理
            $host = explode('.', $this->_CONFIG['site']['host']);
            $account = uniqid() . '@' . $host[1] . '.' . $host[2];
            if ($data['nickname'] == '') {
                $nickname = $data['type'] . $connect['connect_id'];
            } else {
                $nickname = $data['nickname'];
            }
            $user = array(
				'account' => $account, 
				'password' => rand(10000000, 999999999), 
				'nickname' => $nickname, 
				'ext0' => $account, 
				'face' => $data['headimgurl'], 
				'token' => $data['token'], 
				'reg_time' => NOW_TIME, 
				'reg_ip' => get_client_ip()
			);
            //注册用户资料
            if (!D('Passport')->register($user)) {
                $this->error('创建帐号失败');
            }
            // 注册第三方接口
            $token = D('Passport')->getToken();
            $connect['uid'] = $token['uid'];
            D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));// 注册成功智能跳转
            $backurl = session('backurl');
			header('Location:' . U('user/member/index'));
        } else {
            setuid($connect['uid']);
            session('access', $connect['connect_id']);
			header('Location:' . U('user/member/index'));
        }
        die;
    }
	
	
	//微信登录
    public function wxlogin(){
        $state = md5(uniqid(rand(), TRUE));
        cookie('wx_back_url', $_SERVER['HTTP_REFERER']);
        session('state', $state);
        $login_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->_CONFIG['weixin']['appid'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/wxcallback')) . '&response_type=code&scope=snsapi_base&state=' . $state . '#wechat_redirect';
        header("Location:{$login_url}");
        die;
    }
 	 //微信注册
    public function wxcallback(){
        if ($_REQUEST['state'] == session('state')) {
            import('@/Net.Curl');
            $curl = new Curl();
            if (empty($_REQUEST['code'])) {
                $this->error('授权后才能登陆！', U('passport/login'));
            }
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->_CONFIG['weixin']['appid'] . '&secret=' . $this->_CONFIG['weixin']['appsecret'] . '&code=' . $_REQUEST['code'] . '&grant_type=authorization_code';
            $str = $curl->get($token_url);
            $params = json_decode($str, true);
            if (!empty($params['errcode'])) {
                echo '<h3>error:</h3>' . $params['errcode'];
                echo '<h3>msg  :</h3>' . $params['errmsg'];
                die;
            }
            if (empty($params['openid'])) {
                $this->error('登录失败', U('passport/login'));
            }
            $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $params['access_token'] . '&openid=' . $params['openid'] . '&lang=zh_CN';
            $info = $curl->get($info_url);
            $info = json_decode($info, true);
            $data = array(
				'type' => 'weixin', 
				'open_id' => $params['openid'], 
				'token' => $params['refresh_token'], 
				'nickname' => $info['nickname'], 
				'headimgurl' => $info['headimgurl'], 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			);
            $this->thirdlogin($data);
        }
    }
   
    //统一注册方法
    private function thirdlogin($data){ 
        if ($this->_CONFIG['connect']['debug']) {
            $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
          	
            if (empty($connect)) {
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add($data);
            } else {
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));//
            }
			
			
            //如果是qq登录则获取
            if ($data['type'] == 'qq') {
                $user_info = D('Connect')->user_info($data['client_id'], $data['open_id'], $data['token']);
                $nickname = $user_info['nickname'];
                $face = $user_info['figureurl_qq_2'] == '' ? $user_info['figureurl_qq_1'] : $user_info['figureurl_qq_2'];
            }
            //结束
            if (empty($connect['uid'])) {
                $account = $data['type'] . rand(100000, 999999) . '@qq.com';
                $user = array(
					'account' => $account, 
					'password' => rand(100000, 999999), 
					'nickname' => $nickname, 
					'face' => $face, 
					'ext0' => $account, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip()
				);
                if (!D('Passport')->register($user)) {
                    $this->error('创建帐号失败');
                }
              	
                $token = D('Passport')->getToken();
                $connect['uid'] = $token['uid'];
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));
            }
            setuid($connect['uid']);
            session('access', $connect['connect_id']);
          
            header('Location:' . U('wap/index/index'));
            die;
        } else {
            $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
         
            if (empty($connect)) {
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add($data);
            } else {
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));
            }
            if (empty($connect['uid'])) {
                session('connect', $connect['connect_id']);
                header('Location: ' . U('passport/bind'));
            } else {
                setuid($connect['uid']);
                session('access', $connect['connect_id']);
              $return['status']=0;
              $return['msg']='登录成功';
              $token=D('connect')->where(['uid'=>$connect['uid']])->find();
              setcookie("token",$token['token'], time()+3600);
              $this->ajaxReturn($return);
               // header('Location:' . U('user/member/index'));
            }
            die;
        }
    }
    public function logout(){
        cookie('BAOCMS_TOKEN', 0);
        cookie('goods', null);
        D('Passport')->logout();
        $this->success('退出登录成功！', U('Wap/passport/login'));
    }
    public function weixincheck(){
        $state = $this->_param('state');
        if (empty($state)) {
            $this->error('非法访问', U('wap/index/index'));
        }
        if ($this->uid) {
            $wxconn = D('Weixinconn')->where(array('state' => $state))->find();
            $data = array();
            $data['conn_id'] = $wxconn['conn_id'];
            $data['status'] = 1;
            $data['user_id'] = $this->uid;
            D('Weixinconn')->save($data);
            $this->success('扫描成功，请等待电脑端响应！', U('user/member/index'));
        }
    }
    public function forget(){
        $way = (int) $this->_param('way');
        $this->assign('way', $way);
        $this->display();
    }
	//找回密码
    public function newpwd() {
        $mobile = htmlspecialchars($_POST['mobile']);
		$count = D('Users')->where(array('mobile'=>$mobile))->count();
		if($count > 1){
			session('scode', null);
			$this->fengmiMsg('参数错误，请联系管理员解决！');
		}
		if(!$Users = D('Users')->where(array('mobile'=>$mobile))->find()){
			session('scode', null);
            $this->fengmiMsg('手机号码输入错误！');
        }
        if(!($scode = trim($_POST['scode']))) {
          $this->fengmiMsg('请输入短信验证码！');
        }
        $scode2 = session('scode');
        if (empty($scode2)) {
           $this->fengmiMsg('请获取短信验证码！');
        }
        if($scode != $scode2) {
            $this->fengmiMsg('请输入正确的短信验证码！');
        }
		$password = rand_string(6, 1);
		if(D('Passport')->uppwd($Users['account'], '', $password)){
			D('Sms')->sms_user_newpwd($mobile, $password);//发送短信
			session('scode', null);
			$this->fengmiMsg('恭喜您更新密码成功，请查收手机短信', U('passport/login'));
		}else{
			session('scode', null);
			$this->fengmiMsg('更新密码失败');
		}
    }
    public function findsms(){
        if (!($mobile = htmlspecialchars($_POST['mobile']))) {
          			 $return['status']=1;
                  $return['msg']='请输入正确的手机号码';
                  $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
        if (!isMobile($mobile)) {
          		$return['status']=1;
                  $return['msg']='请输入正确的手机号码';
                  $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }

        //if ($user = D('Users')->getUserByAccount($account)) {
            //if (empty($user['mobile'])) {
                //die('你还未绑定手机号，请选择其他方式！');
            //} else {
               // if ($user['mobile'] != $mobile) {
                    //die('请填写您的绑定手机号！');
               // }
           // }
        //}
      	$user=D('users')->where(['account'=>$_POST['mobile']])->find();
      	//dump($user);die;
      	if (empty($user)) {
          		$return['status']=1;
                  $return['msg']='当前用户未注册';
                  $this->ajaxReturn($return);
            die('请输入正确的手机号码');
        }
      	$randstring = rand_string(6, 1);
      	$res=D('Sms')->sms_yzm($mobile, $randstring);//发送短信
		
      	if(!$res){
        	  $return['status']=1;
              $return['msg']='发送失败，请稍后再试';
              $this->ajaxReturn($return);
        }else{
      		  $return['status']=0;
              $return['msg']='发送成功';
              $this->ajaxReturn($return);
        }
        //$randstring = session('scode');
        //if (empty($randstring)) {
           // $randstring = rand_string(6, 1);
           // session('scode', $randstring);
       // }
		//D('Sms')->sms_yzm($mobile, $randstring);//发送短信
       // die('1');
    }
   
  	public function findpwd(){
    	if (!$_POST['mobile']) {
          		$return['status']=1;
                  $return['msg']='请输入正确的手机号码';
                  $this->ajaxReturn($return);
        }
      	$mobile=$_POST['mobile'];
      	if (!($scode = trim($_POST['scode']))) {
                       $return['status']=1;
                      $return['msg']='请获取短信验证码';
                      $this->ajaxReturn($return);
                }
                $info = D('dayu_sms')->where(['mobile'=>$mobile])->order('sms_id desc')->find();
				//dump($info);
   				$scode2=$info['yzm'];
              	$expiry_time=$info['expiry_time']-60;
              	//dump(time());
              	//dump($expiry_time);die;
                if (empty($scode2)||(time()>$expiry_time)) {
                   $return['status']=1;
              		$return['msg']='验证码无效';
              		$this->ajaxReturn($return);
                }
                if ($scode != $scode2) {
              $return['status']=1;
              $return['msg']='请输入正确短信验证码';
              $this->ajaxReturn($return);
              }
                	 $match='/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{6,20}$/';
             $str = $_POST['password'];

			if (empty($_POST['password'])) {
              $return['status']=1;
              $return['msg']='请输入密码';
              $this->ajaxReturn($return);
            }
             if (!preg_match($match, $str)) {
                        $return['status']=1;
                              $return['msg']='请输入正确密码格式';
                              $this->ajaxReturn($return);
             }
            $wap_register_password2 = $this->_CONFIG['register']['wap_register_password2'];
            if ($wap_register_password2 == 1) {
                $password2 = $this->_post('password2');
                if ($password2 !== $_POST['password']) {
                        $return['status']=1;
                        $return['msg']='两次密码不一样';
                        $this->ajaxReturn($return);
                }
            }
			
            //开始其他的判断了
      		
            if (1 == D('users')->where(['account'=>$mobile])->save(['password'=>md5($_POST['password'])])) {
              $return['status']=0;
              $return['msg']='修改成功';
              $this->ajaxReturn($return);
            }else{
              $return['status']=1;
              $return['msg']='密码未做修改';
              $this->ajaxReturn($return);
            }
    }
  
  			public function userinfo(){
            	$token=$_POST['token'];
              	$info=D('users')->where(['token'=>$token])->find();
              if ($info) {
                  $return['status']=0;
                  $return['msg']=$info;
                  $this->ajaxReturn($return);
                }else{
                  $return['status']=1;
                  $return['msg']='未找到该用户信息';
                
              $this->ajaxReturn($return);
            }
              	$this->ajaxReturn($data);
            }
  
  			public function take_money_detail(){
            	$token=$_POST['token'];
              	$uid=$this->getuid($token);
              	$data['detail']=D('users_cash')->where(['user_id'=>$uid,'status'=>1])->field('money,rest_money,payment_time')->select();
              	$this->ajaxReturn($data);
            }
    		
  
          public function upload_face()
            {
            //echo 1;
                $upload = new \Think\Upload();// 实例化上传类
            echo 1;die;
                $upload->maxSize   =     '31457280' ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->autoSub  = true;
                $upload->subName  = array('date','Ymd');
                $upload->rootPath = "./Public/";//文件上传保存的根路径，下面的Upload文件夹放在这里面，./Public/Upload
                $upload->savePath  =      './Upload/'; // 设置附件上传目录，文件上传上来以后放在了这个文件件里面。
                $info   =   $upload->upload();
            
                if(!$info) // 上传错误提示错误信息
                {
                    $this->error($upload->getError());
                }
                else// 上传成功 获取上传文件信息
                {
                    foreach($info as $file){        
                    echo $file['savepath'].$file['savename'];    
                    }
                }
            }
  
  			public function setface(){
              //$this->ajaxReturn($_POST);die;
            	if(!$_POST['token']){
                  $data['status']=1;
                  $data['msg']='请传入token';
                  $this->ajaxReturn($data);
       			 }
              	$user_id=$this->getuid($_POST['token']);
              	if(!$_POST['face']){
                  $data['status']=1;
                  $data['msg']='请传入图片';
                  $this->ajaxReturn($data);
       			 }
              $type = $_FILES['face']['type'];
              $size = $_FILES['face']['size'];
              print_r($type);
             // $this->ajaxReturn($type);
              //$this->ajaxReturn($size);
             
              
              die;
              		$imgurl=$_POST['face'];
                	$path = "./Public/upload/";
              		$path2 = "/Public/upload/";
              		//$this->ajaxReturn($_POST);
                	$prefix='tx_';
              	  	$base_img = str_replace('data:image/png;base64,','',$imgurl);
             		//$this->ajaxReturn($base_img);
                    $output_file = $prefix.time().rand(100,999).'.png';
                    $path =$path.$output_file;
                    $ifp = fopen( $path, "w+" );
                    fwrite( $ifp, base64_decode($base_img) );
                    fclose( $ifp );
              		//dump($ifp);die; 
                    //return $path;
              		//$path3 ="http://".$_SERVER['SERVER_NAME'].$path2.$output_file;
              		$path3 ="http://cs.rytlan.com".$path2.$output_file;
              		$data['face']=$path3;
              		//$this->ajaxReturn($data);
              	
              	$res=D('users')->where(['user_id'=>$user_id])->save($data);
              	//dump($user_id);die;
               if ($res) {
                      $return['status']=0;
                      $return['msg']='上传成功';
                      $this->ajaxReturn($return);
                    }else{
                      $return['status']=1;
                      $return['msg']='上传失败';

                  $this->ajaxReturn($return);
                }
            }
  	
  		public function check_token(){
        	if(!$_POST['token']){
                  $data['status']=1;
                  $data['msg']='请传入token';
                  $this->ajaxReturn($data);
       		}
          	if(!$_POST['mobile']){
                  $data['status']=1;
                  $data['msg']='请传入mobile';
                  $this->ajaxReturn($data);
       		}
          	$res=D('users')->where(['mobile'=>$_POST['mobile'],'token'=>$_POST['token']])->find();
          	//dump($res);die;
          	     if ($res) {
                      $return['status']=0;
                      $return['msg']='token匹配成功';
                      $this->ajaxReturn($return);
                    }else{
                      $return['status']=1;
                      $return['msg']='token匹配失败';

                  $this->ajaxReturn($return);
                }
        }
}