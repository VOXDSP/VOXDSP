<?php

	//GeoIP
	require 'geoip2.phar';

	function check_ustor($idfa)
	{
		if($idfa == ''){$idfa = "u";}
		$fp = stream_socket_client("udp://127.0.0.1:7811", $errno, $errstr, 1);
		if($fp)
		{
			$r = fwrite($fp, $idfa);
			if($r == FALSE)
			{
				fclose($fp);
				return TRUE;
			}
			//stream_set_timeout($fp, 1);
			$r = fread($fp, 1);
			if($r != FALSE && $r[0] == 'n')
			{
				fclose($fp);
				return FALSE; //The only time we can bid, is if the server 'says-so'
			}
			fclose($fp);
			return TRUE;
		}
		return TRUE;
	}

	function dtc($id)
	{
		if($id == 0){return "Unknown";}
		if($id == 1){return "Ethernet";}
		if($id == 2){return "WIFI";}
		if($id == 3){return "Cellular Network";}
		if($id == 4){return "Cellular Network – 2G";}
		if($id == 5){return "Cellular Network – 3G";}
		if($id == 6){return "Cellular Network – 4G";}

		return $id;
	}
		
	function mISP($ip)
	{
		try
		{
			$reader = new GeoIp2\Database\Reader("/usr/share/GeoIP/GeoIP2-ISP.mmdb");
			$record = $reader->isp($ip);
			return strtolower($record->isp);
		}
		catch (Exception $e){}
		return 'Unknown';
	}

	function mCont($ip)
	{
		try
		{
			$reader = new GeoIp2\Database\Reader("/usr/share/GeoIP/GeoIP2-Connection-Type.mmdb");
			$record = $reader->connectionType($ip);
			return strtolower($record->connectionType);
		}
		catch (Exception $e){}
		return 'Unknown';
	}

	//Load Campaigns
	require_once "/usr/share/nginx/rtb.voxdsp.com/html/core/compiled.php";
	date_default_timezone_set('UTC');

	$geo = "";
	$exchange = "";

	//Get exchange name
	function getExchange()
	{
		return explode('.', str_replace('/', '', strtolower($_SERVER['REQUEST_URI'])), 2)[0];
	}


	//Generate user fingerprnt
	function makeFingerprint($d)
	{
		$r = "";

		if(isset($d->{'device'}->{'ip'}))
			$r .= $d->{'device'}->{'ip'}."-";

		if(isset($d->{'device'}->{'ipv6'}))
			$r .= $d->{'device'}->{'ipv6'}."-";
		
		if(isset($data->{'user'}->{'id'}))
			$r .= $d->{'user'}->{'id'}."-";
		
		if(isset($d->{'ext'}->{'atuid'}))
			$r .= $d->{'ext'}->{'atuid'}."-";
		
		if(isset($d->{'device'}->{'ifa'}))
			$r .= $d->{'device'}->{'ifa'}."-";
		
		if(isset($d->{'device'}->{'idfa'}))
			$r .= $d->{'device'}->{'idfa'}."-";

		if(isset($d->{'ext'}->{'androididmd5'}))
			$r .= $d->{'ext'}->{'androididmd5'}."-";
		
		if(isset($d->{'device'}->{'dpidsha1'}))
			$r .= $d->{'device'}->{'dpidsha1'}."-";
		
		if(isset($d->{'device'}->{'dpidmd5'}))
			$r .= $d->{'device'}->{'dpidmd5'}."-";
		
		if(isset($d->{'ext'}->{'idfa'}))
			$r .= $d->{'ext'}->{'idfa'}."-";
		
		if(isset($d->{'ext'}->{'uid'}->{'idfa'}))
			$r .= $d->{'ext'}->{'uid'}->{'idfa'}."-";
		
		if(isset($d->{'ext'}->{'idfatracking'}))
			$r .= $d->{'ext'}->{'idfatracking'}."-";
		
		if(isset($d->{'ext'}->{'macmd5'}))
			$r .= $d->{'ext'}->{'macmd5'}."-";
		
		if(isset($d->{'ext'}->{'odin'}))
			$r .= $d->{'ext'}->{'odin'}."-";
		
		if(isset($d->{'ext'}->{'openudid'}))
			$r .= $d->{'ext'}->{'openudid'}."-";
		
		if(isset($d->{'ext'}->{'udidmd5'}))
			$r .= $d->{'ext'}->{'udidmd5'}."-";
		
		if(isset($d->{'ext'}->{'udidsha1'}))
			$r .= $d->{'ext'}->{'udidsha1'}."-";
		
		if(isset($d->{'device'}->{'model'}))
			$r .= $d->{'device'}->{'model'}."-";
		
		if(isset($d->{'device'}->{'os'}))
			$r .= $d->{'device'}->{'os'}."-";
		
		if(isset($d->{'device'}->{'osv'}))
			$r .= $d->{'device'}->{'osv'}."-";
		
		if(isset($d->{'device'}->{'language'}))
			$r .= $d->{'device'}->{'language'}."-";
		
		if(isset($d->{'device'}->{'carrier'}))
			$r .= $d->{'device'}->{'carrier'}."-";
		
		if(isset($d->{'device'}->{'ua'}))
			$r .= $d->{'device'}->{'ua'}."-";
		
		if(isset($d->{'device'}->{'geo'}->{'country'}))
			$r .= $d->{'device'}->{'geo'}->{'country'}."-";
		
		if(isset($d->{'device'}->{'geo'}->{'city'}))
			$r .= $d->{'device'}->{'geo'}->{'city'}."-";
		
		if(isset($d->{'device'}->{'geo'}->{'region'}))
			$r .= $d->{'device'}->{'geo'}->{'region'}."-";
		
		if(isset($d->{'device'}->{'geo'}->{'type'}))
			$r .= $d->{'device'}->{'geo'}->{'type'}."-";
		
		if(isset($d->{'device'}->{'geo'}->{'zip'}))
			$r .= $d->{'device'}->{'geo'}->{'zip'}."-";
		
		if(isset($d->{'device'}->{'connectiontype'}))
			$r .= $d->{'device'}->{'connectiontype'}."-";
		
		if($r != "")
			return sha1($r);
	}


	//Fuck you smaato, why!? WHY
	function smaato_javaAd($script, $width, $height)
	{
		return '<ad xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://standards.smaato.com/ad/smaato_ad_v0.9.xsd" modelVersion="0.9"><richmediaAd><content><![CDATA[ '.$script.' ]]></content><width>'.$width.'</width><height>'.$height.'</height></richmediaAd></ad>';
	}

	//Custom Encode
	function jsonEncode($in)
	{
		return str_replace('"', '\"', str_replace('/', '\/', $in));
	}
		
	//Bid Construction
	$rsp = "";
	$bic = 0;
	$nocur = 0;
	function start_bidresponse($bidid)
	{
		GLOBAL $rsp, $bic;
		$rsp = '{"id":"'.$bidid.'","seatbid":[';
		$bic = 0;
	}
	/*function add_popbid($id, $impid, $price, $adcode, $winurl) //exo
	{
		GLOBAL $rsp;
		GLOBAL $nocur;
		$nocur = 1;
		$rsp .= '{"bid": [{"id":"'.$id.'","impid":"'.$impid.'","price":'.$price.',"adm":"'.jsonEncode($adcode).'","nurl":"'.$winurl.'"}]}';
	}*/
	function add_seatbid($id, $impid, $price, $adid, $winurl, $adcode, $addomain, $iurl, $creativeid, $accountid, $seatid, $width, $height, $categories, $imptracker)
	{
		GLOBAL $rsp, $bic;

		if($bic != 0)
			$rsp .= ',';
		else
			$bic++;

		$wah = '';
		if($width != '' && $height != '')
		{
			$wah = ',"w":'.$width.',"h":'.$height;
		}
		/*else
		{
			//If no w/h assume pop bid
			add_popbid($id, $impid, $price, $adcode, $winurl);
			return;
		}*/

		if($imptracker != '')
			$imptracker = ',"ext":{"imptrackers":["'.$imptracker.'"]}';
		
		if($categories != "")
			$categories = ",\"cat\":[\"" . $categories . "\"]";

		$iur = '';
		if($iurl != '')
			$iur = ',"iurl":"'.$iurl.'"';

		$adom = '';
		if($addomain != '')
			$adom .= ',"adomain":["'.$addomain.'"]';

		$rsp .= '{"seat":"'. $seatid .'","bid":[{"id":"'.$id.'","impid":"'.$impid.'","cid":"' . $accountid . '","crid":"' . $creativeid . '","price":'.$price.',"nurl":"'.$winurl.'","adid":"'.$adid.'","adm":"' . jsonEncode($adcode) .'"'.$wah.$iur.$adom.$imptracker.$categories.'}]}';
	}
	function closebid()
	{
		GLOBAL $rsp;
		GLOBAL $nocur;
		if($nocur == 0)
			$rsp .= '],"cur":"USD"}';
		else
			$rsp .= ']}';
	}
	function noBid()
	{
		http_response_code(204);
		exit;
	}
	function random_str()
	{
		$keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return $keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)].$keyspace[mt_rand(0, 61)];
	}

	//Bid Decode Functions
	//$imp_floor = 0;
	$impid = -1;
	$imp_w = 0;
	$imp_h = 0;

	function findImp($imps, $width, $height, $minbid, $maxbid)
	{
		GLOBAL $impid, $jsonbid;
		$impid = -1; //NEED TO RESET IT BEFORE EACH CALL

		if(isset($jsonbid))
		{
			foreach($jsonbid->{'imp'} as $imp)
			{
				$imp_w = 0;
				$imp_h = 0;

				//Get banner size		
				if(isset($imp->{'banner'}->{'w'}))
				{
					$imp_w = $imp->{'banner'}->{'w'};
					$imp_h = $imp->{'banner'}->{'h'};
				}
				else if(isset($imp->{'w'}))
				{
					$imp_w = $imp->{'w'};
					$imp_h = $imp->{'h'};
				}

				//Is this the size we want?
				if($imp_w == $width && $imp_h == $height)
				{
					//Can we afford this impression?
					$imp_floor = $imp->{'bidfloor'};
					if($imp_floor < $minbid || $imp_floor > $maxbid)
						continue;

					$impid = $imp->{'id'};
					break;
				}
			}
		}
	}

	function findImpAny($imps)
	{
		GLOBAL $jsonbid, $impid, $imp_w, $imp_h;
		$imp_floor = 999999; //Because we're trying to find the lowest cost bid!
		$impid = -1; //NEED TO RESET IT BEFORE EACH CALL

		if(isset($jsonbid))
		{
			foreach($jsonbid->{'imp'} as $imp)
			{
				if(isset($imp->{'id'}))
					$impid = $imp->{'id'};
				if(isset($imp->{'bidfloor'}) && $imp->{'bidfloor'} < $imp_floor)
				{
					$imp_floor = $imp->{'bidfloor'};
					
					//Get banner size		
					if(isset($imp->{'banner'}->{'w'}))
					{
						$imp_w = $imp->{'banner'}->{'w'};
						$imp_h = $imp->{'banner'}->{'h'};
					}
					else if(isset($imp->{'w'}))
					{
						$imp_w = $imp->{'w'};
						$imp_h = $imp->{'h'};
					}
				}
			}
		}
	}

	//Get Bid Request
	//header("Connection: Keep-alive");
	$pi = file_get_contents("php://input");
	$bid = strtolower($pi); //Lower it for string searches
	$bid = str_replace(': ', ':', $bid); //wont fuck up decode
	$jsonbid = json_decode($pi);
	$bid = str_replace('\/', '/', $bid); //would fuckup decode

	//If the bid is timestamped, check how old the bid is, in seconds (SSPHWY)
	/*if(isset($jsonbid->{'ext'}->{'timestamp'}))
	{
		$dms = time() - $jsonbid->{'ext'}->{'timestamp'};
		if($dms > 330)
			noBid();
	}*/

	//Dont bid if no bidid or no user agent.
	if(!isset($jsonbid->{'device'}->{'ua'}) || !isset($jsonbid->{'id'}))
		noBid();

	//Get bid id
	$bidid = $jsonbid->{'id'};

	//Get Exchange
	$exchange = getExchange();

	//Open surgery on bid request to append to beginning
	$bid = substr($bid, 1);

	//Get OSV
	$osv = '';
	if(!isset($jsonbid->{'device'}->{'osv'}))
	{
		$sosv = $jsonbid->{'device'}->{'ua'};
		$p = stristr($sosv, 'OS ');
		if($p)
		{
			$p = substr($p, 3);
			$osv = explode(' ', $p, 2)[0];
			$osv = str_replace(')', '', $osv);
          	$osv = str_replace(';', '', $osv);
		}
		else
		{
			$p = stristr($sosv, 'Android ');
			if($p)
			{
				$p = substr($p, 8);
				$osv = explode(' ', $p, 2)[0];
				$osv = str_replace(')', '', $osv);
          		$osv = str_replace(';', '', $osv);
			}
		}
	}
	else
	{
		$osv = $jsonbid->{'device'}->{'osv'};
	}
	if($osv != '')
		$bid = '"osv":"'.str_replace('_', '.', $osv).'",' . $bid;

	//Only GEOIP exoclick
	//if($exchange == 'exoclick' || $exchange == 'exopmp')
	//{
		//Pre-Calculate Carrier if not supplied & append to beginning
		$geoisp = '';
		//if(!isset($jsonbid->{'device'}->{'carrier'}) || $jsonbid->{'device'}->{'carrier'} == '' || ctype_alpha($jsonbid->{'device'}->{'carrier'}[0]) == FALSE || stripos($jsonbid->{'device'}->{'carrier'}, "unknown") !== false)
		//{
			if(isset($jsonbid->{'device'}->{'ip'}))
				$geoisp = mISP($jsonbid->{'device'}->{'ip'});
		//}
		if($geoisp != '')
			$bid = '"maxmind":"'.$geoisp.'",' . $bid;

		$geocont = '';
		if(isset($jsonbid->{'device'}->{'ip'}))
			$geocont = mCont($jsonbid->{'device'}->{'ip'});
		if($geocont != '')
			$bid = '"contype":"'.$geocont.'",' . $bid;
	//}
	//else
	//{
		//if(isset($jsonbid->{'device'}->{'connectiontype'}))
			//$bid = '"contype":"'.dtc($jsonbid->{'device'}->{'connectiontype'}).'",' . $bid;
	//}

	//Append exchange to beginning & timestamp
	$timestamp = date('Y-m-d H:i:s');
	$bid = '{"timestamp":"'.$timestamp.'","exchange":"'.$exchange.'",' . $bid; //Make sure capitals are never used as exchange name endpoint due to string searches

	//Request Dump
	/*if($exchange == "ero")
	{
		$fn = "logs/ero/erolog-".date("Y-m-d-G").".txt";
		if(filesize($fn) < 3000000) //3MB
			file_put_contents($fn, $bid . "\n", FILE_APPEND | LOCK_EX);
	}
	else
	{*/
		$fn = "/usr/share/nginx/rtb.voxdsp.com/html/logs/bidlogs/bidlog-".date("Y-m-d-G").".txt";
		if(@filesize($fn) < 11000000) //11MB
			file_put_contents($fn, $bid . "\n", FILE_APPEND | LOCK_EX);
	//}
	
	//Check anti-chaff filters
	/*if(strpos($bid, '"js":0') !== FALSE)
		noBid();
	if(strpos($bid, 'gecko') === FALSE && strpos($bid, 'mozilla') === FALSE && strpos($bid, 'webkit') === FALSE)
		noBid();*/
	
	//No campaigns? Do nothing.
	if(!is_array($campaigns) || $campaigns == '')
		noBid();
	
	//Get Impressions
	$imps = 0;
	$cid = 0;
	$aid = 0;

	//Start bid response
	start_bidresponse($bidid);

	//Loop through campaigns
	$uid = "";
	foreach($campaigns as $c)
	{
		//Globalise Variables
		$cid = $c['id'];
		$aid = $c['aid'];

		//Check if this campaign can bid on any of the provided impressions
		if($c['w'] == '0' || $c['h'] == '0')
			findImpAny($imps);
		else
			findImp($imps, $c['w'], $c['h'], $c['mincpm'], $c['maxcpm']);
		
		//Was there an impression we can bid on?
		if($impid != -1)
		{
			//Check Blocking keywords (obviously blocking will always be faster to check than targeting)
			if($c['blocking'][0] != '')
			{
				foreach($c['blocking'] as $b)
				{
					if(stripos($bid, $b) !== FALSE)
						continue 2;
				}
			}
			
			//Check Targeting Keywords
			if($c['targeting'][0] != '')
			{
				foreach($c['targeting'] as $ta)
				{					
					$p = 0;
					foreach($ta as $t)
					{
						if($t == '')
							continue;
						
						if(stripos($bid, $t) !== FALSE) //String check
							$p++;
						
					}
					if($p == 0)
						continue 2;
				}
			}

			//Append tracking code if not exoclick
			/*if($exchange != 'exoclick')
			{
				//$c['tag'] .=  '<div id="htp"></div><img src="https://app.voxdsp.com/bleed.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />';
				$c['tag'] .=  '<script>!function(){function e(){var e=document.createElement("iframe");e.setAttribute("src","https://app.voxdsp.com/click.php?a={cid}&bid={rawb64}"),e.style.width="1px",e.style.height="1px",e.style.display="none",document.body.appendChild(e)}document.addEventListener("click",function(t){e(),t.preventDefault()},!1),document.addEventListener("touchstart",function(t){e(),t.preventDefault()},!1);var t=0,n=0,p=0,h=!0,i=!1;window.addEventListener("deviceorientation",function(e){n<13?(1!=e.isTrusted&&(h=!1),n++):1==h&&(0==i&&(document.getElementById("htp").innerHTML+=\'<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />\'),i=!0)},!0),window.onmousemove=function(e){var n;p<13?((n=Math.abs(e.movementX))>t&&(t=n),(n=Math.abs(e.movementY))>t&&(t=n),1!=e.isTrusted&&(h=!1),a=e.alpha,b=e.beta,g=e.gamma,p++):t<.33*window.screen.availWidth&&1==h&&(0==i&&(document.getElementById("htp").innerHTML+=\'<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />\'),i=!0)}}();</script>';
			}*/

			//Cache Buster
			if(stripos($c['tag'], '{rnd}') !== FALSE)
				$c['tag'] = str_replace('{rnd}', random_str(), $c['tag']);
			
			//Tell AD-TAG name of incomming endpoint
			if(stripos($c['tag'], '{epn}') !== FALSE)
				$c['tag'] = str_replace('{epn}', $exchange, $c['tag']);

			//Get variables
			$geo = $jsonbid->{'device'}->{'geo'}->{'country'};

			//Tell AD-TAG Alpha-3 of incomming GEO
			if(stripos($c['tag'], '{geo}') !== FALSE)
				$c['tag'] = str_replace('{geo}', $geo, $c['tag']);

			//Build Categories
			$cat = "";
			if($c['iab'] != "")
			{
				$cat = "IAB" . $c['iab'];
				$cat = str_replace(",", "\",\"IAB", $cat);
			}

			//Get publisher id if possible
			$pubid = 'Unknown';
			$idfa = 'Unknown';
			$src = 'Unknown';
			$dom = 'Unknown';
			$site_id = 'Unknown';
			$category_id = 'Unknown';
			$language = 'Unknown';
			$pubname = 'Unknown';

			if(isset($jsonbid))
			{
				if(isset($jsonbid->{'site'}->{'id'}))
					$site_id = $jsonbid->{'site'}->{'id'};

				if(isset($jsonbid->{'site'}->{'cat'}[0]))
					$category_id = $jsonbid->{'site'}->{'cat'}[0];

				if(isset($jsonbid->{'device'}->{'language'}))
					$language = $jsonbid->{'device'}->{'language'};

				if(isset($jsonbid->{'site'}->{'publisher'}->{'id'}))
					$pubid = $jsonbid->{'site'}->{'publisher'}->{'id'};
				else if(isset($jsonbid->{'app'}->{'publisher'}->{'id'}))
					$pubid = $jsonbid->{'app'}->{'publisher'}->{'id'};

				if(isset($jsonbid->{'site'}->{'publisher'}->{'name'}))
					$pubname = $jsonbid->{'site'}->{'publisher'}->{'name'};
				else if(isset($jsonbid->{'app'}->{'publisher'}->{'name'}))
					$pubname = $jsonbid->{'app'}->{'publisher'}->{'name'};
				else if(isset($jsonbid->{'site'}->{'name'}))
					$pubname = $jsonbid->{'site'}->{'name'};
				else if(isset($jsonbid->{'app'}->{'name'}))
					$pubname = $jsonbid->{'app'}->{'name'};

				if(isset($jsonbid->{'site'}->{'page'}))
					$src = $jsonbid->{'site'}->{'page'};
				else if(isset($jsonbid->{'site'}->{'domain'}))
					$src = $jsonbid->{'site'}->{'domain'};
				else if(isset($jsonbid->{'app'}->{'domain'}))
					$src = $jsonbid->{'app'}->{'domain'};

				if(isset($jsonbid->{'site'}->{'domain'}))
					$dom = $jsonbid->{'site'}->{'domain'};
				else if(isset($jsonbid->{'app'}->{'domain'}))
					$dom = $jsonbid->{'app'}->{'domain'};
				
				if(isset($jsonbid->{'device'}->{'ifa'}))
					$idfa = $jsonbid->{'device'}->{'ifa'};
				else if(isset($jsonbid->{'user'}->{'id'}))
					$idfa = $jsonbid->{'user'}->{'id'};
			}
			if(stripos($c['tag'], '{pub}') !== FALSE)
				$c['tag'] = str_replace('{pub}', urlencode($pubid), $c['tag']);
			if(stripos($c['tag'], '{pubname}') !== FALSE)
				$c['tag'] = str_replace('{pubname}', urlencode($pubname), $c['tag']);

			//Campaign ID
			if(stripos($c['tag'], '{cid}') !== FALSE)
				$c['tag'] = str_replace('{cid}', urlencode($c['id']), $c['tag']);

			//Replace IDFA
			if(stripos($c['tag'], '{idfa}') !== FALSE)
				$c['tag'] = str_replace('{idfa}', urlencode($idfa), $c['tag']);

			//rawbid
			if(stripos($c['tag'], '{rawbid}') !== FALSE)
				$c['tag'] = str_replace('{rawbid}', $bid, $c['tag']);

			//bidid
			if(stripos($c['tag'], '{bidid}') !== FALSE)
				$c['tag'] = str_replace('{bidid}', urlencode($bidid), $c['tag']);

			//Rawbid
			if(stripos($c['tag'], '{rawb64}') !== FALSE)
				$c['tag'] = str_replace('{rawb64}', base64_encode($bid), $c['tag']);

			//Domain url
			if(stripos($c['tag'], '{src}') !== FALSE)
				$c['tag'] = str_replace('{src}', urlencode($src), $c['tag']);

			//Domain
			if(stripos($c['tag'], '{dom}') !== FALSE)
				$c['tag'] = str_replace('{dom}', urlencode($dom), $c['tag']);

			//Bid
			if(stripos($c['tag'], '{bid}') !== FALSE)
				$c['tag'] = str_replace('{bid}', urlencode($c['maxcpm']), $c['tag']);

			//Site ID
			if(stripos($c['tag'], '{sid}') !== FALSE)
				$c['tag'] = str_replace('{sid}', urlencode($site_id), $c['tag']);

			//Zone ID
			if(stripos($c['tag'], '{zid}') !== FALSE)
				$c['tag'] = str_replace('{zid}', urlencode($impid), $c['tag']);

			//Category ID
			if(stripos($c['tag'], '{catid}') !== FALSE)
				$c['tag'] = str_replace('{catid}', urlencode($category_id), $c['tag']);
			
			//Timestamp
			if(stripos($c['tag'], '{time}') !== FALSE)
				$c['tag'] = str_replace('{time}', urlencode($timestamp), $c['tag']);

			//Language
			if(stripos($c['tag'], '{lang}') !== FALSE)
				$c['tag'] = str_replace('{lang}', urlencode($language), $c['tag']);

			//ad width
			if(stripos($c['tag'], '{adw}') !== FALSE)
				$c['tag'] = str_replace('{w}', urlencode($imp_w), $c['tag']);

			//ad height
			if(stripos($c['tag'], '{adh}') !== FALSE)
				$c['tag'] = str_replace('{adh}', urlencode($imp_h), $c['tag']);

			//If enabled redirects, append addcode
			//if(isset($c['directurl']) && $c['directurl'] != '')
				//$c['tag'] .= '<script>setTimeout(function(){top.location.href = "'.$c['directurl'].'";location.replace("'.$c['directurl'].'");}, 100);</script>';

			//Is this smaato?
			if($exchange == "smaato")
				$c['tag'] = smaato_javaAd($c['tag'], $c['w'], $c['h']);

			//Skim... (need to take the skim off the cpm before adding it to the win or people notice)
			if($aid != 1 && $aid != 2) //Don't skim admin campaigns
				$c['maxcpm'] -= (0.01 * $c['maxcpm']) * 20; // take away 20% i use a reciprical to avoid the (x / 100) * y)
			else 
			if($aid == 2) //Skim Khoi (khoi)
				$c['maxcpm'] -= (0.01 * $c['maxcpm']) * 30; // take away 30% i use a reciprical to avoid the (x / 100) * y)
			
			//Let's do it
			$adid = hash('crc32b', $c['iurl'].$c['id']); //WARNING, SLOWEST FUNC USED

			$uid = makeFingerprint($jsonbid);

			if($c['w'] != '0' || $c['h'] != '0')
			{
				$imp_w = $c['w'];
				$imp_h = $c['h'];
			}
			//add_seatbid($adid, $impid, $c['maxcpm'], $adid, "http://".$_SERVER['HTTP_HOST']."/core/win.php?e=".$c['id']."&aid=".$c['aid']."&c=\${AUCTION_PRICE}&geo=".$geo."&exc=".$exchange.'&bid='.urlencode($bidid), $c['tag'], $c['adomain'], $c['iurl'], $adid, $adid, $adid, $c['w'], $c['h'], $cat, $c['imptracker']);
			add_seatbid($adid, $impid, $c['maxcpm'], $adid, "http://".$_SERVER['HTTP_HOST']."/core/win.php?e=".$c['id']."&aid=".$c['aid']."&dom=".$dom."&uid=".$uid."&uh=".$c['uhours']."&c=\${AUCTION_PRICE}&exc=".$exchange.'&bid='.urlencode($bidid).'&src='.urlencode($dom).'&jbid='.urlencode($bid), $c['tag'], $c['adomain'], $c['iurl'], $adid, $adid, $adid, $imp_w, $imp_h, $cat, $c['imptracker']);
		}
	}
	closebid();

	//Send Bid Response if a seatbid was added
	if($bic != 0)
	{
		//Check If Unique (USTOR)
		if(check_ustor($uid) == TRUE)
			noBid();

		// > OK ITS UNIQUE < Spit out the bid
		echo $rsp;
		
		//Response Dump
		//$fn = "logs/rsplogs/rsplog-".date("Y-m-d-G").".txt";
		//if(filesize($fn) < 1000000) //1MB
			//file_put_contents($fn, $rsp . "\n", FILE_APPEND | LOCK_EX);

		//ero
		/*if($exchange == 'ero')
		{
			$fn = "logs/ero/rsplog-".date("Y-m-d-G").".txt";
			if(filesize($fn) < 1000000) //1MB
				file_put_contents($fn, $rsp . "\n", FILE_APPEND | LOCK_EX);
		}*/
		
		exit;
	}
	
	//No bid otherwise
	noBid();

?>
