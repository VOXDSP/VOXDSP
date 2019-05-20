<?php

    //<iframe src="https://app.voxdsp.com/bleed.php?e={cid}&d={src}&u={idfa}" width="1" height="1" style="display:none;"></iframe>

    if(!isset($_GET['e']) || !isset($_GET['d']) || !isset($_GET['u']))
		exit;

	//Load Campaigns
	$coredir = '/usr/share/nginx/rtb.voxdsp.com/html/core/';
	require_once $coredir."compiled.php";

	//Get frequency capping hours
	$uhours = 24; //if we failed lets assume 24 hours bro
	foreach($campaigns as $c)
		if($c['id'] == $_GET['e'])
			$uhours = $c['uhours'];


    //Ok we're recording the bid, fireup redis	
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

	if($redis->get($_GET['e'].'-dbu-'.$_GET['u']) != 'n')
	{
    	$redis->incr($_GET['e'].'-dbc-'.$_GET['d'], 1);
		$redis->set($_GET['e'].'-dbu-'.$_GET['u'], "n");
		$redis->setTimeout($_GET['e'].'-dbu-'.$_GET['u'], $uhours*3600);
	}

?>

