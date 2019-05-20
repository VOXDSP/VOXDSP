<?php

	//Filter chaff
    if(!isset($_GET['a']))
        exit;

    function b64en($in)
	{
		return base64_encode($in);
	}

	function b64un($in)
	{
		return base64_decode($in);
	}

	$rbid = '';
	if(isset($_POST['bid']))
		$rbid = $_POST['bid'];
	else if(isset($_GET['bid']))
		$rbid = $_GET['bid'];

	//fireup redis	
	$redis_address = '127.0.0.1';
	$redis_port = 6379;
	if(!isset($redis))
	{
		try
		{
			$redis = new Redis();
			$redis->pconnect($redis_address, $redis_port);
			//$redis->auth($redis_auth);
		}
		catch(Exception $e){}
	}
	$redis->incr($_GET['a'].'-clicks', 1);
	//echo $redis->get($_GET['a'].'-clicks');

	if($rbid != '')
	{
		//append click time
		date_default_timezone_set('UTC');
		//$bid = '{"clicktime":"' . date('Y-m-d H:i:s') . '","bidid":"' . $rbid . '"}';
		$bid = substr(b64un($rbid), 1);
		$bid = '{"clicktime":"' . date('Y-m-d H:i:s') . '",' . $bid;

		//Write to logs
		$ret = FALSE;
		do
		{
			$ret = file_put_contents('/usr/share/nginx/rtb.voxdsp.com/html/logs/clicklogs/'.$_GET['a'].'.txt', $bid . "\n", FILE_APPEND | LOCK_EX);
		}
		while($ret == FALSE);
	}

	//Redirect to landing page url
	if(isset($_GET['u']))
    	header('Location: '.$_GET['u']);
    
	exit;

?>