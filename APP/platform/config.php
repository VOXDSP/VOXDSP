<?php

	//This really means bidder UI root folder name	
	$biddername = "platform";

	$paypalemail = 'james@voxdsp.com';

	$fronthn = "app.voxdsp.com";
	$backhn = "rtb.voxdsp.com";
	
	$rootdir = '/usr/share/nginx/app.voxdsp.com/html/platform/';
	$coredir = '/usr/share/nginx/rtb.voxdsp.com/html/core/';
	$logsdir = '/usr/share/nginx/rtb.voxdsp.com/html/logs/';
	$endpointsdir = '/usr/share/nginx/rtb.voxdsp.com/html/';

	function getExchanges()
	{
		$ra = array();
		$ar = glob('/usr/share/nginx/rtb.voxdsp.com/html/*.php');
		foreach($ar as $a)
			array_push($ra, str_replace('.php', '', str_replace('/usr/share/nginx/rtb.voxdsp.com/html/', '', $a)));
		return $ra;
	}

	function removeEOL($in, $r='')
	{
		return trim(str_replace(PHP_EOL, $r, $in));
	}

	function ZeroNan($in)
	{
		if($in == 'nan')
			return 0;
		return $in;
	}

	function jsstr($in)
	{
		return str_replace("'", '\'', $in);
	}

	function divide($v1, $v2)
	{
		if($v1 == 0 || $v2 == 0)
			return 0;
		return $v1 / $v2;
	}

	function fprice($in)
	{
		$r = number_format($in/1000, 2);
		if($r == "0.00" || $r == "nan")
			return "0";
		return $r;
	}
	
	function atz($in)
	{
		return preg_replace("/[^a-zA-Z0-9]+/", "", $in);
	}

	function b64en($in)
	{
		return base64_encode(xor_string($in, 'bfn438BUd3W'.strlen($in)));
	}

	function b64un($in)
	{
		return xor_string(base64_decode($in), 'bfn438BUd3W'.strlen($in));
	}

	function xor_string($string, $key)
	{
		$str_len = strlen($string);
		$key_len = strlen($key);
	
		for($i = 0; $i < $str_len; $i++)
			$string[$i] = $string[$i] ^ $key[$i % $key_len];
	
		return $string;
	}

	$database_username = 'root';
	$database_password = 'mysq2';
	$database_address = '127.0.0.1';
	$database = $biddername;
	$mysql = new mysqli($database_address, $database_username, $database_password, $database) or die(mysql_error());

	$redis_address = '127.0.0.1';
	$redis_port = 6379;
	$redis_auth = 'hdgd64skk';
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

	date_default_timezone_set('UTC');

	function secureAC($id)
	{
		GLOBAL $mysql;
		$accountid = $_SESSION['auth'];
		$r = $mysql->query("SELECT aid FROM `campaigns` WHERE id=".$mysql->real_escape_string($id));
		if($r && $r->num_rows > 0)
		{
			$ro = $r->fetch_assoc();
			if($ro['aid'] != $accountid && $accountid != 1)
			{
				header('Location: campaigns.php');
				exit;
			}
		}
		return $accountid;
	}

?>
