<?php
   //微信NATIVE--原生扫码支付，www.hatudou.com二次开发
    require_once "weixin/WxPay.Api.php";
    require_once "weixin/WxPay.NativePay.php";
    require_once 'weixin/WxPay.Notify.php';
    require_once 'weixin/notify.php';
    class native{

		public function init($payment) {
        define('WEIXIN_APPID', $payment['appid']);
        define('WEIXIN_MCHID', $payment['mchid']);
        define('WEIXIN_APPSECRET', $payment['appsecret']);
        define('WEIXIN_KEY',$payment['appkey']);
        //=======【证书路径设置】=====================================
        /**
         * TODO：设置商户证书路径
         * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
         * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
         * @var path
         */
        define('WEIXIN_SSLCERT_PATH', '../cert/apiclient_cert.pem');
        define('WEIXIN_SSLKEY_PATH', '../cert/apiclient_key.pem');
        //=======【curl代理设置】===================================
        /**
         * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
         * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
         * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
         * @var unknown_type
         */
        define('WEIXIN_CURL_PROXY_HOST', "0.0.0.0"); //"10.152.18.220";
        define('WEIXIN_CURL_PROXY_PORT', 0); //8080;
        //=======【上报信息配置】===================================
        /**
         * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
         * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
         * 开启错误上报。
         * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
         * @var int
         */
        define('WEIXIN_REPORT_LEVENL', 1);
        require_once "weixin/WxPay.Api.php";
        require_once "weixin/WxPay.JsApiPay.php";
        //require_once "weixin/WxPay.Notify.php";

    }
        public function getCode( $logs , $payment ){
			$this->init($payment);
            $notify = new NativePay();
            $url1 = $notify->GetPrePayUrl( $logs['logs_id'] );
            $url1 = urlencode( $url1 );
            $input = new WxPayUnifiedOrder();
            $input->SetBody( $logs['subject'] );//是 商品或支付单简要描述
            $input->SetAttach( $logs['subject'] );//否 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetDetail( $logs['subject'] );//否 商品名称明细列表
            $input->SetOut_trade_no( $logs['logs_id'] );//商户系统内部的订单号,32个字符内、可包含字母,
            $logs['logs_amount'] = $logs['logs_amount'] * 100;
            $input->SetTotal_fee( $logs['logs_amount'] );//订单总金额，单位为分
            $input->SetTime_start( date( "YmdHis" ) );//否 订单生成时间
            $input->SetTime_expire( date( "YmdHis" , time() + 600 ) );// 否 注意：最短失效时间间隔必须大于5分钟
            $input->SetGoods_tag( $logs['subject'] ); // 否 商品标记，代金券或立减优惠功能的参数
            $input->SetNotify_url( __HOST__ . U( 'Home/payment/respond' , array( 'code' => 'native' ) ) );
            $input->SetTrade_type( "NATIVE" );
            $input->SetProduct_id( $logs['logs_id'] );//trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义
            $result = $notify->GetPayUrl( $input );//weixin://wxpay/bizpayurl?pr=OINmxCp
            $url2 = $result["code_url"];
            $url2 = urlencode( $url2 );
            $questurl = 'http://paysdk.weixin.qq.com/example/qrcode.php?data=';
            $img = '<img src=' . '\'' . $questurl . $url2 . '\'' . ' style="width:260px;height:260px;"/>';
            return $img;

        }
        public function respond(){
            WxLog::DEBUG( "扫码支付日志" );
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            if (!empty($xml)){
                $res = new WxPayResults();
				$data = $res->FromXml($xml);
                if (true){
                    WxLog::DEBUG("结果：" );
                    if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS'){
                        WxLog::DEBUG( "扫码支付成功" );
                        $map['log_id'] = $data['out_trade_no'];
                        $logs = D('Paymentlogs')->where( $map)->find();
                        if (!$logs) {
                            D('Payment')->logsPaid( $data['out_trade_no'] );
                            WxLog::DEBUG( "恭喜支付成功，已更新付款状态，支付ID".$data['out_trade_no']);
                            return true;
                        } else {
                            if (!$logs['is_paid']) {
                                WxLog::DEBUG("支付成功未更新付款状态" );
                                $result = D( 'Payment' )->checkMoney( $data['out_trade_no'] , $data['total_fee']);
                                if ($result) {
                                    WxLog::DEBUG( "native money right!" );
                                    D('Payment')->logsPaid($data['out_trade_no']);
                                    WxLog::DEBUG( "支付成功已更新付款状态".$data['out_trade_no']);
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
            return false;
        }
        public function b2cQuery($logs ,$payment ){
            $input = new WxPayOrderQuery();
            $out_trade_no = $logs["log_id"];
            $input->SetOut_trade_no( $out_trade_no );
            $output = WxPayApi::orderQuery( $input );
            return $output;
        }
        public function b2cRefund($logs , $payment){
            $out_trade_no = $logs["log_id"]; //商户订单号：商户侧传给微信的订单号
            $total_fee = $logs["need_pay"];  //订单总金额(分)订单总金额，单位为分，只能为整数，Int
            $refund_fee = $logs["need_pay"]; //退款金额(分)：退款总金额，订单总金额，单位为分，只能为整数，详见
            $input = new WxPayRefund();
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(WxPayConfig::MCHID . date("YmdHis"));
            $input->SetOp_user_id(WxPayConfig::MCHID);
            $output = WxPayApi::refund($input);
            return $output;
        }
    }

