<?php
class PersonAction extends CommonAction{
   	public function userinfo(){
      	if(!$_POST['token']){
        	$data['status']=1;
          	$data['msg']="请传入token";
          	$this->ajaxReturn($data);
        }
    	$res=D("users")->where(['token'=>$_POST['token']])->field('nickname,money,face')->find();
      	$list['avatar']=$res['face'];
      	$list['name']=$res['nickname'];
      	$list['gender']=$res['sex'];
      	$list['tel']=$res['mobile'];
     	$list['money']=$res['money'];
      	if(!$res){
        	$data['status']=1;
          	$data['msg']="未查询到该用户信息";
          	$this->ajaxReturn($data);
        }else{
        	$data['status']=0;
          	$data['data']=$list;
          	$this->ajaxReturn($data);
        }
    }
  
  	 public function test(){
     require_cache(APP_PATH . 'Lib/Payment/alipay/aop/SignData.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/AopClient.php');
    require_cache(APP_PATH . 'Lib/Payment/alipay/aop/request/AlipayFundTransToaccountTransferRequest.php');
       require_cache(APP_PATH . 'Lib/Payment/alipay/aop/request/AlipayTradeAppPayRequest.php');
  	$aop = new \AopClient;
$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
$aop->appId = "2017091508746595";
$aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAvl2CBQ1nc2Xv6lhjGo91ZbIwT5vIc1Ukce29Gbs7p3qR0A5wNVZ1V1z8zoE4i4LSvWwMssTtvSPJWG5dmh5YAOqFRCvWEQcvRQIkEyp0B/9Dc6N5V1+IXiR3js2eLJx6Kt1S4KtvNwkxCzpcpDh2pASxBWM1SYGjIQttK2mK6lCz/UTVjwGS+Vk9CSsll2jJXB7AhA7ohjWV3nSujwtXT4u2RjEoo7oMLULs9uebHYQ/YmeJ4zzHQB7WNTBaY4Fn0CUbfhy9LnBpfm4C31JYY31nRdGT1+guQf2FxGtInwNSeVb3omYpMRb+QJ21LDzJvbc5TLBSafVbKXLaBIKnDQIDAQABAoIBABsajy+O+AK7KcyQ5xNaB5oCI9TB1mltXvIFql3mhZjT37ziwWEmvTBCIhB434clikHEB47QcRTz6m/3zsXpuhfvTCgnoaPtBPLrWh2MdtbIpl7pkJY7GNxmjss7RWEOqmo99tXcMsqAOYZiudv72hCCRn4A4Q5lMce7q8B5l3jC48O5ITFPwqaIZp5vplLMIEEvkBFgPzacU1Xk2i2k7RxiHRc3nFvr4dBlpgKd581ZINoTG/QJoxwzi/KkKedLGUuRZumJf1AcvtcMPSfQzcsSRnIptm7o9D0ifmXYFLtbp27jdJr8baiqOcBQEfYstmsAyn9cqU9rDd/6DfB5B8ECgYEA7ecP9uw0GlUxyWFdPL7SdChWqf+4ctzZNkgAgQnfgTnP0T2SwjmwbeA63yBJsGgNm9SwpXxaX5IlZG6ahK3vV+sa69Dgg5/8BgzjI+prjFel55VSwcfiUacAabsVmMKrmX3xNTs9b/KTAWV3Mi0lAAQ3OQKUCJLSwwxM9CvdhTECgYEAzNiy/h6lvuw5vz6cCd20kLN/n8Aqb+BosYD+gVloqtaSSh/ITjqfXkJKoPCZvDLmbson3tscs4P66/7TKlzKGLXp/cX4hK91NnuG3BoT4lrMkLb+ROrraO9qNKMDzcfdgOOdMV3+pbwsSuwqUVTkd6xMPoJEN0q16hcPLwCJeJ0CgYAb8qASfu8kBMwjfzen0jcBBFgiAnOVstIlqVG91v4VvUfnqdca0BIk5kmkOrPNcHvgyZyy5CftwEp6QPAQFCrg5jK0b0Zg0COYRl1Ms9ZMl2NbEFS3hRbWRssqoJrJ5lyoXH5ApaPzCckxSc017M3C8bvXC7F+TFlMJp7HtHuCkQKBgQDCkoriCeZRhTwMxcGdNXOPhpARA4zLm5KWF/qmjnB25T0rHyk59UDvXXRZjm/YbVinn2ljqyiF8zTh+LhHIr/r3M8Xd3XpQmfJime8pSCB/tEUHF1ExQc6Mz7kJHs2eUOSa6EiBTAd6LEBsXU4r7QbYBNDxKRbCKHmamTHtnDdmQKBgBW8VzYGysx5tkvV6VovCVVUV2FbXS/mJHqA8E2VimEwKQPrlOdFCxalOUCSGUnLH48iXkY1g6qAPmfgpiD/CkcCC6ip7FxswMfrX4iI2RXhFVOhf92k7WReUhzRIQSRsbw6RqNip2+04/Z5ANpMqOyIocmQMgIjv9v2Rq/IHFSD';
$aop->format = "json";
$aop->charset = "UTF-8";
$aop->signType = "RSA2";
$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx3I4IanA+18jllg3lxlboGJMHm03TMW7ljtqZ+uQZVy9/VZSL1l5/LuCInVkiy4GgM4yFViB0bMOt9pfNCp8vX5mzi/PgEYEcWQt2N5CpKC/oFZEL2B4iY7Y652P8t2WbU3hs8iRsDwyct6nKe3EgZT7xzY2chAgnoXfrnaYx54eXvZFdwBY74FKeZbzUXNIov4o11RVhOa99REgTW5y75rGxJpG9sVnmCDreYBHQbM2Q8FjW1DALz+Ghz+EeulEzcZmtmP5vftPTfIm/UoYm3Uwzhnehtc7cTodeZUMTdXmDo5e0AYKkqBzvuxvUJMQ6l3jNcu5gFw2N2OT1313JwIDAQAB';
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
$request = new AlipayTradeAppPayRequest();
    //dump($request);die;
//SDK已经封装掉了公共参数，这里只需要传入业务参数
$bizcontent = "{\"body\":\"我是测试数据\"," 
                . "\"subject\": \"App支付测试\","
                . "\"out_trade_no\": \"20170125test05\","
                . "\"timeout_express\": \"30m\"," 
                . "\"total_amount\": \"0.01\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
$request->setNotifyUrl("http://cs.rytlan.com");
$request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
$response = $aop->sdkExecute($request);
$a=strstr( $response, '&');
$a=ltrim($a,'&');
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
echo htmlspecialchars($a);//就是orderString 可以直接给客户端请求，无需再做处理。
  }
  
  
  
  public function test2(){
          $amount=100;
          //if($_SERVER['REQUEST_METHOD']=='POST'){
          //    $amount=$_POST['total'];
          //}else{
           //   $amount=$_GET['total'];
         // }
          $total = floatval($amount); 
          if(!$total){ 
              $total = 10; 
          }
          // 对签名字符串转义

          // 支付宝合作身份者ID，以2088开头的16位纯数字 
          $partner = "%2088621930434608%";  // 支付宝开通快捷支付功能后可获取
          // 支付宝账号
          $seller_id = "ryto2o@163.com"; 
          // 商品网址
          $base_path = urlencode('http://cs.rytlan.com');
          // 异步通知地址
          $notify_url = urlencode('http://cs.rytlan.com');
          // 订单标题
          $subject = '呵呵哒';
          // 订单详情
          $body = '测试用的'; 
          // 订单号，示例代码使用时间值作为唯一的订单ID号
          $out_trade_no = date('YmdHis', time());
          $parameter = array(
              'service'        => 'mobile.securitypay.pay',   // 必填，接口名称，固定值
              'partner'        => $partner,                   // 必填，合作商户号
              '_input_charset' => 'UTF-8',                    // 必填，参数编码字符集
              'out_trade_no'   => $out_trade_no,              // 必填，商户网站唯一订单号
              'subject'        => $subject,                   // 必填，商品名称
              'payment_type'   => '1',                        // 必填，支付类型
              'seller_id'      => $seller_id,                 // 必填，卖家支付宝账号
              'total_fee'      => $total,                     // 必填，总金额，取值范围为[0.01,100000000.00]
              'body'           => $body,                      // 必填，商品详情
              'it_b_pay'       => '1d',                       // 可选，未付款交易的超时时间
              'notify_url'     => $notify_url,                // 可选，服务器异步通知页面路径
              'show_url'       => $base_path                  // 可选，商品展示网站
           );
          //生成需要签名的订单
          $orderInfo = $this->createLinkstring($parameter);
          //签名
          $sign = $this->rsaSign($orderInfo);
          //生成订单
          echo $orderInfo.'&sign="'.$sign.'"&sign_type="RSA"';
      }
                                                                                public	          function createLinkstring($para) {
                                                                                            $arg  = "";
                                                                                            while (list ($key, $val) = each ($para)) {
                                                                                                $arg.=$key.'="'.$val.'"&';
                                                                                            }
                                                                                            //去掉最后一个&字符
                                                                                            $arg = substr($arg,0,count($arg)-2);
                                                                                            //如果存在转义字符，那么去掉转义
                                                                                            if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
                                                                                            return $arg;
                                                                                        }
                                                                                        // 签名生成订单信息
                                                                              public          function rsaSign($data) {
                                                                                            $priKey = "%私钥%";   // 生成密钥时获取，直接使用pem文件的字符串
                                                                                            $res = openssl_get_privatekey($priKey);
                                                                                            openssl_sign($data, $sign, $res);
                                                                                            openssl_free_key($res);
                                                                                            $sign = base64_encode($sign);
                                                                                            $sign = urlencode($sign);
                                                                                            return $sign;
                                                                                        }
}