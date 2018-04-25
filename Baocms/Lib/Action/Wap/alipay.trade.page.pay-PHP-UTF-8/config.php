<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "2088621930434608",

		//商户私钥
		'merchant_private_key' => "MIIEowIBAAKCAQEAvl2CBQ1nc2Xv6lhjGo91ZbIwT5vIc1Ukce29Gbs7p3qR0A5wNVZ1V1z8zoE4i4LSvWwMssTtvSPJWG5dmh5YAOqFRCvWEQcvRQIkEyp0B/9Dc6N5V1+IXiR3js2eLJx6Kt1S4KtvNwkxCzpcpDh2pASxBWM1SYGjIQttK2mK6lCz/UTVjwGS+Vk9CSsll2jJXB7AhA7ohjWV3nSujwtXT4u2RjEoo7oMLULs9uebHYQ/YmeJ4zzHQB7WNTBaY4Fn0CUbfhy9LnBpfm4C31JYY31nRdGT1+guQf2FxGtInwNSeVb3omYpMRb+QJ21LDzJvbc5TLBSafVbKXLaBIKnDQIDAQABAoIBABsajy+O+AK7KcyQ5xNaB5oCI9TB1mltXvIFql3mhZjT37ziwWEmvTBCIhB434clikHEB47QcRTz6m/3zsXpuhfvTCgnoaPtBPLrWh2MdtbIpl7pkJY7GNxmjss7RWEOqmo99tXcMsqAOYZiudv72hCCRn4A4Q5lMce7q8B5l3jC48O5ITFPwqaIZp5vplLMIEEvkBFgPzacU1Xk2i2k7RxiHRc3nFvr4dBlpgKd581ZINoTG/QJoxwzi/KkKedLGUuRZumJf1AcvtcMPSfQzcsSRnIptm7o9D0ifmXYFLtbp27jdJr8baiqOcBQEfYstmsAyn9cqU9rDd/6DfB5B8ECgYEA7ecP9uw0GlUxyWFdPL7SdChWqf+4ctzZNkgAgQnfgTnP0T2SwjmwbeA63yBJsGgNm9SwpXxaX5IlZG6ahK3vV+sa69Dgg5/8BgzjI+prjFel55VSwcfiUacAabsVmMKrmX3xNTs9b/KTAWV3Mi0lAAQ3OQKUCJLSwwxM9CvdhTECgYEAzNiy/h6lvuw5vz6cCd20kLN/n8Aqb+BosYD+gVloqtaSSh/ITjqfXkJKoPCZvDLmbson3tscs4P66/7TKlzKGLXp/cX4hK91NnuG3BoT4lrMkLb+ROrraO9qNKMDzcfdgOOd",
		
		//异步通知地址
		'notify_url' => "http://cs.rytlan.com/alipay.trade.page.pay-PHP-UTF-8/notify_url.php",
		
		//同步跳转
		'return_url' => "http://cs.rytlan.com/alipay.trade.page.pay-PHP-UTF-8/return_url.php",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsyJMfU/nErb4/CA7lfL4UV/26U0Hkv42bGP4G45Goy2sFNdRpZK6et/sbmH/reLafsrwu6AfH62N0mmO8/UoReCMjakan0zd+yST8MRU8//YXCpcYSuucdcFS8jSSwpgUNfTKOwA3Or+EEUJAe9ISRn+WuafDGJ8BgW0t8GpEp1xDdAgDEq1Dl6OF4LcusELziN89fkp4eFc8pp0BeDChCGjhSiIZO5pexLMyiJV0Q965hxfqvcdizTPHIrXgcPTEf3EVxzCosFDK92t61pIiJ534NOrdMmiiVALfv6WN5/hi//nU6uzndml+AsmO+xz6v8dzQy/ZrC+LT7/brvaHQIDAQAB
",
);