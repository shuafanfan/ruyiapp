<?php
    ini_set( 'date.timezone' , 'Asia/Shanghai' );
    error_reporting( E_ERROR );

    require_once "WxPay.Api.php";
    require_once 'WxPay.Notify.php';
    require_once 'WxLog.class.php';
	//初始化日志
    $logHandler = new CLogFileHandler( getcwd().'/Baocms/Lib/Payment/logs/'.date( 'Y-m-d' ) . '.weixin.log' );
    $log = WxLog::Init( $logHandler , 15 );
    class PayNotifyCallBack extends WxPayNotify{
        //查询订单
        public function Queryorder( $transaction_id ) {
            $input = new WxPayOrderQuery();
            $input->SetTransaction_id( $transaction_id );
            $result = WxPayApi::orderQuery( $input );
            foreach ( $result as $key => $value ) {
                $result[$key] = urlencode( $value );
            }
            Log::DEBUG( "query:" . urldecode( json_encode( $result ) ) );//中文转义
            if ( array_key_exists( "return_code" , $result )
                && array_key_exists( "result_code" , $result )
                && $result["return_code"] == "SUCCESS"
                && $result["result_code"] == "SUCCESS"
            ) {
                return true;
            }

            return false;
        }

        //重写回调处理函数
        public function NotifyProcess( $data , &$msg ){
            foreach ( $data as $key => $value ) {
                $data[$key] = urlencode( $value );
            }
            WxLog::DEBUG( "call back:" . urldecode( json_encode( $data ) ) );//中文转义
            if ( !array_key_exists( "transaction_id" , $data ) ) {
                $msg = "输入参数不正确";

                return false;
            }
            //查询订单，判断订单真实性
            if ( !$this->Queryorder( $data["transaction_id"] ) ) {
                $msg = "订单查询失败";

                return false;
            }

            return true;
        }
    }

