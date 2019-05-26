<?php

	if(!isset($_GET['e']))
		exit;
	
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

	function add_ustor($idfa, $expire_seconds)
	{
	    if($expire_seconds == 0){$expire_seconds = 86400;}
	    if($idfa == ''){$idfa = "u";}
	    $fp = stream_socket_client("udp://127.0.0.1:7810", $errno, $errstr, 1);
	    if($fp)
	    {
		stream_socket_sendto($fp, "$" . $idfa . "|" . $expire_seconds);
		fclose($fp);
	    }
	}

	//Get variable
	if(isset($_GET['uid']))
		add_ustor($_GET['uid'], $_GET['uh']*3600);

	//Start a redis pipe
	$pipe = $redis->multi(Redis::PIPELINE);

	//Give a bid id 30 second block after it arrives the first time, encase of re-sends.
	$umk = $_GET['aid'] . "bi-" . $_GET['bid'];
	if($pipe->set($umk, "n") != FALSE)
		$pipe->setTimeout($umk, 30);
	else
		exit; //Don't process this bid again !
	
	//Set the cost
	$cost = 0;
	if(isset($_GET['c']))
		$cost = floatval($_GET['c']);

	//Skim the cost
	$c = $cost;
	$a = $_GET['aid'];
	if($a != 1 && $a != 2) //Don't skim admin campaigns
		$c *= 1.2; //Do the skim (all accounts apart from admin) (20%)
	else
	if($a == 2)
		$c *= 1.3; //Do the skim on account-id 2 (30%)
	
	//Update redis for a fork to mysql later
	$pipe->incr('mb-'.$_GET['e'].'-wins', 1);
	$pipe->incrByFloat('mb-'.$_GET['e'].'-cost', $c);
	$pipe->incrByFloat('mb-'.$_GET['aid'].'-spent', $c);

	//Update Redis (exchange wins and cost per account)
	$pipe->incr($_GET['aid'].'-'.$_GET['exc'].'-wins', 1);
	$pipe->incrByFloat($_GET['aid'].'-'.$_GET['exc'].'-cost', $c);

	//Log cost per campaign (this is for the daily capping of cost, lame)
	$pipe->incrByFloat($_GET['e'].'-cost', $c);
	
	//Bleed detection
	if(isset($_GET['src']))
		$pipe->incr($_GET['e'].'-dbl-'.$_GET['src'], 1);

	//Write Bid JSON to file
	if(isset($_GET['jbid']))
	{
		$bid = substr($_GET['jbid'], 1);
		//$bid = '{"winprice":' . $cost . ',"aid":"'.$_GET['aid'].'","cid":"'.$_GET['e'].'",' . $bid;
		$bid = '{"wincpm":' . $c . ',' . $bid;
		
		if(!file_exists('/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$_GET['aid']))
			mkdir('/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$_GET['aid'], 0777);

		if(!file_exists('/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$_GET['aid'].'/'.$_GET['e']))
			mkdir('/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$_GET['aid'].'/'.$_GET['e'], 0777);

		$ret = FALSE;
		while($ret == FALSE)
			$ret = file_put_contents('/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$_GET['aid'].'/'.$_GET['e'].'/'.date("Y-m-d-G").'.txt', $bid."\n", FILE_APPEND | LOCK_EX);
	}

	//Done !
	$pipe->exec();

?>
