<?php

class LifecateModel extends CommonModel

{

    protected $pk = 'cate_id';

    protected $tableName = 'life_cate';

    protected $token = 'life_cate';

    protected $orderby = array('is_hot' => 'desc', 'orderby' => 'asc');

    protected $channel = array(
		'ershou' => 1, 
		'car' => 2, 
		'qiuzhi' => 3, 
		'love' => 4, 
		'house' => 5, 
		'peixun' => 6, 
		'jobs' => 7, 
		'service' => 8, 
		'jianzhi' => 9, 
		'chongwu' => 10,
		'weixiu' => 11, 
		'yishu' => 12, 
		'shangwu' => 13, 
		'jiatingbaojie' => 14, 
		'shangmenanmo' => 15, 
		'yiwuxihu' => 16, 
		'meirongmeizhuang' => 17, 
		'baomuyuesao' => 18, 
		'shangmenyihu' => 19, 
		'jianshengyundong' => 20,
		'qitafuwu' => 21,
	);

    protected $channelMeans = array(
		1 => ['channel_id'=>1,'name'=>'二手'], 
		2 => ['channel_id'=>2,'name'=>'维修'], 
		3 => ['channel_id'=>3,'name'=>'家政'], 
		4 => ['channel_id'=>4,'name'=>'舞蹈'], 
		5 => ['channel_id'=>5,'name'=>'教育'], 
		6 => ['channel_id'=>6,'name'=>'医护'], 
		7 => ['channel_id'=>7,'name'=>'宠物'], 
		8 => ['channel_id'=>8,'name'=>'搬家'], 
		9 => ['channel_id'=>9,'name'=>'按摩'], 
		10 => ['channel_id'=>10,'name'=>'洗浴'], 

	);

    public function getChannel()

    {

        return $this->channel;

    }

    public function getChannelMeans()

    {

        return $this->channelMeans;

    }

}