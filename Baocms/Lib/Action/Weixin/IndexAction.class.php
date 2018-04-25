<?php
class IndexAction extends CommonAction{
    public function index(){
        $data = $this->weixin->request();
        switch ($data['MsgType']) {
            case 'event':
                if ($data['Event'] == 'subscribe') {
                    if (isset($data['EventKey']) && !empty($data['EventKey'])) {
                        $this->events();
                    } else {
                        $this->event();
                    }
                }
                if ($data['Event'] == 'SCAN') {
                    $this->scan();
                }
                break;
            case 'location':
                $this->location($data);
                break;
            default:
                $this->keyword($data);
                break;
        }
    }
    private function location($data){
        $lat = addcslashes($data['Location_X']);
        $lng = addcslashes($data['Location_Y']);
        $list = D('Shop')->where(array('audit' => 1, 'closed' => 0))->order(" (ABS(lng - '{$lng}') +  ABS(lat - '" . $lat . '\') )  asc ')->limit(0, 10)->select();
        if (!empty($list)) {
            $content = array();
            foreach ($list as $item) {
                $content[] = array($item['shop_name'], $item['addr'], $this->getImage($item['photo']), __HOST__ . '/wap/shop/detail/shop_id/' . $item['shop_id'] . '.html');
            }
            $this->weixin->response($content, 'news');
        } else {
            $this->weixin->response('很抱歉没有合适的商家推荐给您', 'text');
        }
    }
    private function keyword($data){
        if (empty($data['Content'])) {
            return;
        }
        if ($this->shop_id == 0) {
            $key = explode(' ', $data['Content']);
            $keyword = D('Weixinkeyword')->checkKeyword($key[0]);
            if ($keyword) {
			 switch ($keyword['type']) {
                    case 'text':
                        $this->weixin->response($keyword['contents'], 'text');
                        break;
                    case 'news':
                        $content = array();
                        $content[] = array(
                            $keyword['title'],
                            $keyword['contents'],
                            $this->getImage($keyword['photo']),
                            $keyword['url'],
                        );
                        $this->weixin->response($content, 'news');
                        break;
                }
			
            } else {
                // 没有特定关键词则查询POIS信息
                $openid = $data['FromUserName'];
                $con = D('Connect')->getConnectByOpenid('weixin', $openid);
                $usr = D('Users')->where(array('user_id' => $con['uid']))->find();
                $map = array();
                $map['name|tag'] = array('LIKE', array('%' . $key[0] . '%', '%' . $key[0], $key[0] . '%', 'OR'));
                $lat = $usr['lat'];
                $lng = $usr['lng'];
                if (empty($lat) || empty($lng)) {
                    $lat = $this->_CONFIG['site']['lat'];
                    $lng = $this->_CONFIG['site']['lng'];
                }
                $squares = returnSquarePoint($lng, $lat, 2);
                $map['lat'] > $squares['right-bottom']['lat'];
                $map['lat'] < $squares['left-top']['lat'];
                $map['lng'] > $squares['left-top']['lng'];
                $map['lng'] > $squares['right-bottom']['lng'];
                $orderby = 'orderby asc';
                //查询包年固顶
                $word = D('Nearword')->where(array('text' => $key[0]))->find();
                $word_pois = $word['pois_id'];
                if ($word_pois) {
                    $ding = D('Near')->find($word_pois);
                }
                if ($ding) {
                    $map['pois_id'] != $word_pois;
                    if ($ding['shop_id']) {
                        $url = $this->_CONFIG['site']['host'] . '/wap/shop/detail/shop_id/' . $ding['shop_id'] . '.html';
                    } else {
                        $url = $this->_CONFIG['site']['host'] . '/wap/biz/detail/pois_id/' . $ding['pois_id'] . '.html';
                    }
                    $text = '<a href="' . $url . '">' . $ding['name'] . '</a> ★★★★★ /:strong
' . $ding['address'] . '
' . $ding['telephone'] . '

';
                }
                $list = D('Near')->where($map)->order($orderby)->limit(0, 9)->select();
                //判断是否从POIS中获取到信息
                if (count($list) > 0) {
                    foreach ($list as $val) {
                        if (intval($val['pois_id']) != intval($word_pois)) {
                            if (intval($val['shop_id']) > 0) {
                                $url = $this->_CONFIG['site']['host'] . '/wap/shop/detail/shop_id/' . $val['shop_id'] . '.html';
                            } else {
                                $url = $this->_CONFIG['site']['host'] . '/wap/biz/detail/pois_id/' . $val['pois_id'] . '.html';
                            }
                            $distance = getDistanceCN($val['lat'], $val['lng'], $lat, $lng);
                            if (!empty($val['telephone'])) {
                                $text .= '<a href="' . $url . '">' . $val['name'] . '</a>
' . $val['address'] . ' (' . $distance . ')
' . $val['telephone'] . '

';
                            } else {
                                $text .= '<a href="' . $url . '">' . $val['name'] . '</a>
' . $val['address'] . ' (' . $distance . ')

';
                            }
                        }
                    }
                }
                if (empty($ding) && count($list) == 0) {
                    $text = '回禀圣上，臣翻阅了整个新华字典也没找到你要的东东。依臣所见，还是点击下面菜单试试吧！';
                }
                //发送信息到客户
                $this->weixin->response($text, 'text');
            }
        } else {
           $keyword = D('Shopweixinkeyword')->checkKeyword($this->shop_id, $data['Content']);
            if ($keyword) {
                switch ($keyword['type']) {
                    case 'text':
                        $this->weixin->response($keyword['contents'], 'text');
                        break;
                    case 'news':
                        $content = array();
                        $content[] = array(
                            $keyword['title'],
                            $keyword['contents'],
                            $this->getImage($keyword['photo']),
                            $keyword['url'],
                        );
                        $this->weixin->response($content, 'news');
                        break;
                }
            } else {
                $this->event();
            }
        }
    }
	

	//自动发送账户
	public function PostMeg($condata){
		 $token = $this->getAccessToken($this->_CONFIG['weixin']["appid"], $this->_CONFIG['weixin']["appsecret"]);
		 $Contentbody = '恭喜【'.$condata['nickname'].'】成为'.$condata['uid']."位会员！\r\n会员昵称：".$condata['nickname']."\r\n会员ID：".$condata['uid'].'\r\n关注时间：'.date('Y-m-d H:i:s',time());
		  $post_msg = '{
		   "touser":"'.$condata[openid].'",
		   "msgtype":"text",
		   "text":
			   {
				   "content":"'.$Contentbody.'"
			   }
		   }';	 
				 
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$token;
		$ret_json = $this->curl_grab_page($url, $post_msg);
	}
	//curl提交
	public function curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($proxystatus == 'true') {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		if(!empty($ref_url)){
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
			curl_setopt($ch, CURLOPT_REFERER, $ref_url);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		ob_start();
		return curl_exec ($ch);
		ob_end_clean();
		curl_close ($ch);
		unset($ch);
	}
	
	
	//获取全局授权
	public function getAccessToken($appId,$appSecret) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
		$data = json_decode(file_get_contents(BASE_PATH."/access_token.json"));
		if ($data->expire_time < time()) {
		  $res = json_decode($this->httpGet($url));
		  $access_token = $res->access_token;
		  if ($access_token) {
			$data->expire_time = time() + 7000;
			$data->access_token = $access_token;
			$fp = fopen(BASE_PATH."/access_token.json", "w");
			fwrite($fp, json_encode($data));
			fclose($fp);
		  }
		} else {
		  $access_token = $data->access_token;
		}
		return $access_token;

    }
	//请求
   public function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		$res = curl_exec($curl);
		curl_close($curl);
		return $res;
	  }
  
    //微信关注自动注册为用户  fid 为推荐人id
    private function wxAutoadd($data,$fid=''){  
    	$data['type'] = 'weixin';
		$data['open_id'] = $data['openid'];
        $connect = D('Connect')->getConnectByOpenid('weixin', $data['openid']);
		//file_put_contents('tuio.txt', var_export($data, true));
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
            if (!D('Passport')->register($user,$fid)) {
               
            }
            // 注册第三方接口
            $token = D('Passport')->getToken();
            $connect['uid'] = $token['uid'];
            D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));
            //发送帐号逻辑
			$this->PostMeg($connect); 
			file_put_contents('b.txt', var_export($connect, true));
        } else {
          //  file_put_contents('u.txt', var_export($result, true));
        }
        
    }
	
    //响应用户的事件
    private function event(){
        if ($this->shop_id == 0) {
			//相应用户事件开始
			if ($this->_CONFIG['weixin']['user_auto'] == 1 && $this->_CONFIG['weixin']['user_add'] == 1) {
				$data = $this->weixin->request();
				$token = $this->getAccessToken($this->_CONFIG['weixin']["appid"], $this->_CONFIG['weixin']["appsecret"]);				  
				$result = D('Connect')->wx_user_autoinfo($data['FromUserName'],$token);					  
				$this->wxAutoadd($result); //自动发送账户并入库
			}
			if ($this->_CONFIG['weixin']['type'] == 1) {
				$this->weixin->response($this->_CONFIG['weixin']['description'], 'text');//发送文字回复
			}else{
				$content[] = array(
					$this->_CONFIG['weixin']['title'], 
					$this->_CONFIG['weixin']['description'], 
					$this->getImage($this->_CONFIG['weixin']['photo']), 
					$this->_CONFIG['weixin']['linkurl']
				);
				$this->weixin->response($content, 'news');//发送图文回复
			}
			//相应用户事件结束
        } else {
            $data['get'] = $_GET;
            $data['post'] = $_POST;
            $data['data'] = $this->weixin->request();
            $weixin_msg = unserialize($this->shopdetails['weixin_msg']);
            if ($weixin_msg['type'] == 1) {
                $this->weixin->response($weixin_msg['description'], 'text');
            } else {
                $content[] = array(
					$weixin_msg['title'], 
					$weixin_msg['description'], 
					$this->getImage($weixin_msg['photo']), 
					$this->_CONFIG['weixin']['linkurl']
				);
                $this->weixin->response($content, 'news');//发送商家图片文字简介
            }
        }
    }
	//扫码相应事件
    private function events(){
        $data['get'] = $_GET;
        $data['post'] = $_POST;
        $data['data'] = $this->weixin->request();
        if (!empty($data['data'])) {
            $datas = explode('_', $data['data']['EventKey']);
            $id = $datas[1];
			
			//优先查询用户分享关注下级
			$uDate = D('Users')->find($id);
			if($uDate){
 				$token = $this->getAccessToken($this->_CONFIG['weixin']["appid"], $this->_CONFIG['weixin']["appsecret"]);	
				$result = D('Connect')->wx_user_autoinfo($data['data']['FromUserName'],$token);					  
				$this->wxAutoadd($result,$id); //自动入库
				$this->weixin->response($this->_CONFIG['weixin']['description'], 'text');//发送文字回复
			}else {		
			//二开结束
				if (!($detail = D('Weixinqrcode')->find($id))) {
					die;
				}
				$type = $detail['type'];
				if ($type == 1) {
					$shop_id = $detail['soure_id'];
					$shop = D('Shop')->find($shop_id);
					$content[] = array(
						$shop['shop_name'], 
						$shop['addr'], 
						$this->getImage($shop['photo']), __HOST__ . '/shop/detail/shop_id/' . $shop_id . '.html');
					$result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
					if (!empty($result)) {
						$user_id = $result['uid'];
						$ymd = date('Y-m-d', NOW_TIME);
						$ymdarr = explode('-', $ymd);
						if (!($de = D('Census')->where(array('user_id' => $user_id))->find())) {
							$datac = array('user_id' => $user_id, 'year' => $ymdarr[0], 'month' => $ymdarr[1], 'day' => $ymdarr[2]);
							D('Census')->add($datac);
						}
						if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $shop_id))->find())) {
							$dataf = array('user_id' => $user_id, 'shop_id' => $shop_id, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
							D('Shopfavorites')->add($dataf);
							D('Shop')->updateCount($shop_id, 'fans_num');
						} else {
							if ($fans['closed'] == 1) {
								D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
							}
						}
					}
					$this->weixin->response($content, 'news');
				} elseif ($type == 2) {
					$tuan_id = $detail['soure_id'];
					$tuan = D('Tuan')->find($tuan_id);
					$content[] = array($tuan['title'], $tuan['intro'], $this->getImage($tuan['photo']), __HOST__ . '/wap/tuan/detail/tuan_id/' . $tuan_id . '.html');
					$result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
					if (!empty($result)) {
						$user_id = $result['uid'];
						$ymd = date('Y-m-d', NOW_TIME);
						$ymdarr = explode('-', $ymd);
						if (!($de = D('Census')->where(array('user_id' => $user_id))->find())) {
							$datac = array('user_id' => $user_id, 'year' => $ymdarr[0], 'month' => $ymdarr[1], 'day' => $ymdarr[2]);
							D('Census')->add($datac);
						}
						if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $tuan['shop_id']))->find())) {
							$dataf = array('user_id' => $user_id, 'shop_id' => $tuan['shop_id'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
							D('Shopfavorites')->add($dataf);
							D('Shop')->updateCount($tuan['shop_id'], 'fans_num');
						} else {
							if ($fans['closed'] == 1) {
								D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
							}
						}
					}
					$this->weixin->response($content, 'news');
				} elseif ($type == 3) {
					//购物
					$goods_id = $detail['soure_id'];
					$goods = D('Goods')->find($goods_id);
					$shops = D('Shop')->find($goods['shop_id']);
					$content[] = array($goods['title'], $shops['shop_name'], $this->getImage($goods['photo']), __HOST__ . '/wap/mall/detail/goods_id/' . $goods_id . '.html');
					$result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
					if (!empty($result)) {
						$user_id = $result['uid'];
						$ymd = date('Y-m-d', NOW_TIME);
						$ymdarr = explode('-', $ymd);
						if (!($de = D('Census')->where(array('user_id' => $user_id))->find())) {
							$datac = array('user_id' => $user_id, 'year' => $ymdarr[0], 'month' => $ymdarr[1], 'day' => $ymdarr[2]);
							D('Census')->add($datac);
						}
						if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $goods['shop_id']))->find())) {
							$dataf = array('user_id' => $user_id, 'shop_id' => $goods['shop_id'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
							D('Shopfavorites')->add($dataf);
							D('Shop')->updateCount($goods['shop_id'], 'fans_num');
						} else {
							if ($fans['closed'] == 1) {
								D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
							}
						}
					}
					$this->weixin->response($content, 'news');
				}
			}
		}
    }
    public function scan(){
        $data['data'] = $this->weixin->request();
        if (!empty($data['data'])) {
            $id = $data['data']['EventKey'];
            if (!($detail = D('Weixinqrcode')->find($id))) {
                die;
            }
            $type = $detail['type'];
            if ($type == 1) {
                $shop_id = $detail['soure_id'];
                $shop = D('Shop')->find($shop_id);
                $content[] = array($shop['shop_name'], $shop['addr'], $this->getImage($shop['photo']), __HOST__ . '/wap/shop/detail/shop_id/' . $shop_id . '.html');
                $result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
                if (!empty($result)) {
                    $user_id = $result['uid'];
                    $ymd = date('Y-m-d', NOW_TIME);
                    $ymdarr = explode('-', $ymd);
                    if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $shop_id))->find())) {
                        $dataf = array('user_id' => $user_id, 'shop_id' => $shop_id, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
                        D('Shopfavorites')->add($dataf);
                        D('Shop')->updateCount($shop_id, 'fans_num');
                    } else {
                        if ($fans['closed'] == 1) {
                            D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
                        }
                    }
                }
                $this->weixin->response($content, 'news');
            } elseif ($type == 2) {
                //抢购
                $tuan_id = $detail['soure_id'];
                $tuan = D('Tuan')->find($tuan_id);
                $content[] = array($tuan['title'], $tuan['intro'], $this->getImage($tuan['photo']), __HOST__ . '/wap/tuan/detail/tuan_id/' . $tuan_id . '.html');
                $result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
                if (!empty($result)) {
                    $user_id = $result['uid'];
                    if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $tuan['shop_id']))->find())) {
                        $dataf = array('user_id' => $user_id, 'shop_id' => $tuan['shop_id'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
                        D('Shopfavorites')->add($dataf);
                        D('Shop')->updateCount($tuan['shop_id'], 'fans_num');
                    } else {
                        if ($fans['closed'] == 1) {
                            D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
                        }
                    }
                }
                $this->weixin->response($content, 'news');
            } elseif ($type == 3) {
                //购物
                $goods_id = $detail['soure_id'];
                $goods = D('Goods')->find($goods_id);
                $shops = D('Shop')->find($goods['shop_id']);
                $content[] = array($goods['title'], $shops['shop_name'], $this->getImage($goods['photo']), __HOST__ . '/wap/mall/detail/goods_id/' . $goods_id . '.html');
                $result = D('Connect')->getConnectByOpenid('weixin', $data['data']['FromUserName']);
                if (!empty($result)) {
                    $user_id = $result['uid'];
                    if (!($fans = D('Shopfavorites')->where(array('user_id' => $user_id, 'shop_id' => $goods['shop_id']))->find())) {
                        $dataf = array('user_id' => $user_id, 'shop_id' => $goods['shop_id'], 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
                        D('Shopfavorites')->add($dataf);
                        D('Shop')->updateCount($goods['shop_id'], 'fans_num');
                    } else {
                        if ($fans['closed'] == 1) {
                            D('Shopfavorites')->save(array('favorites_id' => $fans['favorites_id'], 'closed' => 0));
                        }
                    }
                }
                $this->weixin->response($content, 'news');
            }
        }
    }
    private function getImage($img){
		return config_weixin_img($img);
    }
}