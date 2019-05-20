<?php

  //Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: index.php');
    exit;
  }
  $accountid = $_SESSION['auth'];

  //Lets get started
  require_once "config.php";
  $exchanges = getExchanges();

  //Don't allow access to campaigns that do not belong to the signed in account
  if(isset($_GET['cid']))
    secureAC($_GET['cid']);
  if(isset($_GET['new']))
    secureAC($_GET['new']);

  //Calculate campaign completion
  $tcost = 0;
  $tcostlimit = 0;
  $r = $mysql->query("SELECT SUM(cost) as costs, SUM(costlimit) as costlimits FROM `campaigns` WHERE aid=".$accountid);
  if($r)
  {
    $ro = $r->fetch_assoc();
    $tcost = $ro['costs'];
    $tcostlimit = $ro['costlimits'] * 1000;
  }
  $rt = divide(100, $tcostlimit) * $tcost;
  if($rt >= 100)
    $rt = 100;
  if($rt <= 0)
    $rt = 0;
  $rt = number_format($rt, 2);
  if($rt == "nan"){$rt=0;}

  //Resolve a redirecting url
  function resolveURL($url)
  {
    if($url == '')
      return $url;

    $headers = get_headers($url);
    if(!is_array($headers))
      return $url;

    $headers = array_reverse($headers);
    foreach($headers as $header)
    {
      if(substr($header, 0, 10) == 'Location: ')
      {
        $url = substr($header, 10);
        return $url;
      }
    }

    return $url;
  }

  //If edit id provided load campaign data ready for editing
  $ero = '';
  unset($ero);
  if(isset($_GET['cid']))
  {
    $r = $mysql->query("SELECT * FROM `campaigns` WHERE id=".$mysql->real_escape_string($_GET['cid']));
    if($r)
      $ero = $r->fetch_assoc();
    else
      unset($ero);
  }

  //Create new campaign
	if(isset($_GET['new']))
	{ 
		//Attempt to create database, encase it doesnt exist
		/*$r = $mysql->query("CREATE TABLE `campaigns`(`id` int(4) UNSIGNED NOT NULL,`active` tinyint(1) UNSIGNED DEFAULT NULL,`name` varchar(64) NOT NULL,`w` smallint(2) UNSIGNED NOT NULL DEFAULT '0',`uhours` tinyint(1) UNSIGNED DEFAULT 0,`h` smallint(2) UNSIGNED NOT NULL DEFAULT '0',`mincpm` double UNSIGNED NOT NULL DEFAULT '0',`maxcpm` double UNSIGNED NOT NULL DEFAULT '0',`wins` int(10) UNSIGNED NOT NULL DEFAULT '0',`cost` double UNSIGNED NOT NULL DEFAULT '0',`costlimit` double UNSIGNED NOT NULL DEFAULT '0',`dailycostlimit` double UNSIGNED NOT NULL DEFAULT '0',`blocking` text,`implimit` int(10) UNSIGNED NOT NULL DEFAULT '0',`targeting` text,`adtag` text,`adomain` varchar(64) NOT NULL,`iurl` varchar(256) NOT NULL,`iab` varchar(256) NOT NULL,`starthour` tinyint(1) UNSIGNED DEFAULT NULL,`endhour` tinyint(1) UNSIGNED DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
		if($r)
		{
			$mysql->query("ALTER TABLE `campaigns` ADD PRIMARY KEY (`id`);");
			$mysql->query("ALTER TABLE `campaigns` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;");
		}*/
		
		//Add the new Row
    $name = $mysql->real_escape_string($_POST['name']);
    $size = $_POST['size'];
    $sp = explode('x', $size, 2);
		$w = $mysql->real_escape_string($sp[0]);
		$h = $mysql->real_escape_string($sp[1]);
		$mincpm = $mysql->real_escape_string($_POST['mincpm']);
		$maxcpm = $mysql->real_escape_string($_POST['maxcpm']);
    if($maxcpm > 50.0)
      $maxcpm = 50.0;
		$costlimit = $mysql->real_escape_string($_POST['costlimit']);
    $implimit = $mysql->real_escape_string($_POST['implimit']);
		if($implimit == '')
      $implimit = 0;
    $uniquehours = $mysql->real_escape_string($_POST['uniquehours']);
    if($uniquehours == '')
      $uniquehours = 0;
    $dailycap = $mysql->real_escape_string($_POST['dailycap']);
    if($dailycap == '')
      $dailycap = 0;
    $tracker = $mysql->real_escape_string($_POST['tracker']);


    $iurl = $mysql->real_escape_string($_POST['miurl']);
    if($_POST['iurl'] != "No Banner (Only Script)")
      $iurl = 'https://' . $fronthn .'/' . $biddername . '/b/' . $accountid . '/' . $_POST['iurl'];



    $curl = $redis->get($accountid.'-'.$iurl);
    $adomain = str_ireplace('www.', '', parse_url(resolveURL($curl), PHP_URL_HOST));
    if($_POST['adomain'] != '') //Manual override
      $adomain = $_POST['adomain'];
    $adomain = $mysql->real_escape_string($adomain);

    $iab = '';
    if(isset($_POST['iab']) && is_array($_POST['iab']))
      foreach($_POST['iab'] as $v)
        $iab .= str_replace('IAB', '', $v) . ',';
    $iab = $mysql->real_escape_string(rtrim($iab, ','));

    $startdate = $mysql->real_escape_string($_POST['startdate']);
		$enddate = $mysql->real_escape_string($_POST['enddate']);

    $adscript = $mysql->real_escape_string($_POST['adscript']);

    //Generate Blocking List
    $blocking = $mysql->real_escape_string($_POST['blocking']);
    //Generate Targeting List
    $rv = '';
    if(isset($_POST['targetkeys']))
      $rv = $_POST['targetkeys'];
    $targetkeys = rtrim($rv);

    $rv = '';
    if(isset($_POST['country']))
      if(is_array($_POST['country']))
        foreach($_POST['country'] as $v)
          $rv .= '"'.$v . '",';
    $country = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['city']) && is_array($_POST['city']))
      foreach($_POST['city'] as $v)
        $rv .= '"'.$v . '",';
    $city = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['language']) && is_array($_POST['language']))
      foreach($_POST['language'] as $v)
        $rv .= '"'.$v . '",';
    $language = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['model']) && is_array($_POST['model']))
      foreach($_POST['model'] as $v)
        $rv .= '"'.$v . '",';
    $model = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['make']) && is_array($_POST['make']))
      foreach($_POST['make'] as $v)
        $rv .= '"'.$v . '",';
    $make = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['devicetype']) && is_array($_POST['devicetype']))
      foreach($_POST['devicetype'] as $v)
        $rv .= $v . ',';
    $devicetype = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['carrier']) && is_array($_POST['carrier']))
      foreach($_POST['carrier'] as $v)
        $rv .= '"'.$v . '",';
    $carrier = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['contype']) && is_array($_POST['contype']))
      foreach($_POST['contype'] as $v)
        $rv .= '"'.$v . '",';
    $contype = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['browser']) && is_array($_POST['browser']))
      foreach($_POST['browser'] as $v)
        $rv .= $v . ',';
    $browser = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['os']) && is_array($_POST['os']))
      foreach($_POST['os'] as $v)
        $rv .= '"'.$v . '",';
    $os = rtrim($rv, ',');

    $rv = '';
    if(isset($_POST['osv']) && is_array($_POST['osv']))
      if(count($_POST['osv']) == 1)
        $rv .= $_POST['osv'][0] . ',';
      else
        foreach($_POST['osv'] as $v)
          $rv .= '"' . $v . '","' . str_replace('.', '_', $v) . '",';
    $osv = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['sites']) && is_array($_POST['sites']))
      foreach($_POST['sites'] as $v)
        $rv .= '"' . $v . '",';
    $sites = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['desc']) && is_array($_POST['desc']))
      foreach($_POST['desc'] as $v)
        $rv .= '"'.$v . '",';
    $desc = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['traffictype']) && is_array($_POST['traffictype']))
      foreach($_POST['traffictype'] as $v)
        $rv .= '"'.$v . '",';
    $traffictype = rtrim($rv, ','); 

    $rv = '';
    if(isset($_POST['exchange']) && is_array($_POST['exchange']))
      foreach($_POST['exchange'] as $v)
        $rv .= '"'.$v . '",';
    $exchange = rtrim($rv, ','); 


    //Finish targeting list
    $targeting = '';
    if($targetkeys != ''){$targeting .= $targetkeys.';';}
    if($country != ''){$targeting .= $country.';';}
    if($city != ''){$targeting .= $city.';';}
    if($language != ''){$targeting .= $language.';';}
    if($model != ''){$targeting .= $model.';';}
    if($make != ''){$targeting .= $make.';';}
    if($devicetype != ''){$targeting .= $devicetype.';';}
    if($carrier != ''){$targeting .= $carrier.';';}
    if($contype != ''){$targeting .= $contype.';';}
    if($browser != ''){$targeting .= $browser.';';}
    if($os != ''){$targeting .= $os.';';}
    if($osv != ''){$targeting .= $osv.';';}
    if($sites != ''){$targeting .= $sites.';';}
    if($desc != ''){$targeting .= $desc.';';}
    if($traffictype != ''){$targeting .= $traffictype.';';}
    if($exchange != ''){$targeting .= $exchange.';';}
    $targeting = $mysql->real_escape_string(rtrim($targeting, ';'));

    //Generate AdTag
    $adtag = '';
    if($_POST['iurl'] != "No Banner (Only Script)")
    {
      if(stripos($curl, '://') === FALSE)
        $curl = 'https://'.$curl;
      //$adtag = '<a href="'.$curl.'" target="_blank"><img src="'.$iurl.'" width="'.$w.'px" height="'.$h.'px" border=0 /></a>';//.$mysql->real_escape_string($_POST['adtag']);
      $turl = 'https://'.$fronthn.'/click.php?a={cid}&u='.urlencode($curl);
      $adtag = '<meta name="viewport" content="width=device-width,initial-scale=1.0"><style>*{margin:0;padding:0;}html,body{width:100%;height:100%;overflow:hidden;}</style><form target="_blank" action="'.$turl.'" method="POST"><input name="bid" type="text" value="{rawb64}" style="display:none;"><input type="image" name="submit" src="'.$iurl.'" border="0" /></form>';
    }
    else if($adscript != '')
    {
      $adtag .= $adscript;
    }

    //If they try to set a blank tag, take them back to the config page
    if($adtag == '')
    {
      echo '<script>window.history.back();</script>';
      exit;
    }

    //Append click tracker for script tags
    //if($_POST['iurl'] == "No Banner (Only Script)")
      //$adtag .=  '<div id="htp"></div><img src="https://app.voxdsp.com/bleed.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" /><script>!function(){function e(){var e=document.createElement("iframe");e.setAttribute("src","https://app.voxdsp.com/click.php?a={cid}&bid={rawb64}"),e.style.width="1px",e.style.height="1px",e.style.display="none",document.body.appendChild(e)}document.addEventListener("click",function(t){e(),t.preventDefault()},!1),document.addEventListener("touchstart",function(t){e(),t.preventDefault()},!1);var t=0,n=0,p=0,h=!0,i=!1;window.addEventListener("deviceorientation",function(e){n<13?(1!=e.isTrusted&&(h=!1),n++):1==h&&(0==i&&(document.getElementById("htp").innerHTML+='<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />'),i=!0)},!0),window.onmousemove=function(e){var n;p<13?((n=Math.abs(e.movementX))>t&&(t=n),(n=Math.abs(e.movementY))>t&&(t=n),1!=e.isTrusted&&(h=!1),a=e.alpha,b=e.beta,g=e.gamma,p++):t<.33*window.screen.availWidth&&1==h&&(0==i&&(document.getElementById("htp").innerHTML+='<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />'),i=!0)}}();</script>';

    //Create the campaign !
		if($_GET['new'] != 1)
		{
			$mysql->query("UPDATE `campaigns` SET startdate='".$startdate."', enddate='".$enddate."', name='".$name."', imptracker='".$tracker."', uhours='".$uniquehours."', w='".$w."', h='".$h."', mincpm='0', maxcpm='".$maxcpm."', costlimit='".$costlimit."', dailycostlimit='".$dailycap."', implimit='".$implimit."', blocking='".$blocking."', targeting='".$targeting."', adtag='".$adtag."', adomain='".$adomain."', iurl='".$iurl."', iab='".$iab."' WHERE id=".$mysql->real_escape_string($_GET['new']));
      //echo "UPDATE `campaigns` SET name='".$name."', uhours='".$uniquehours."', w='".$w."', h='".$h."', mincpm='".$mincpm."', maxcpm='".$maxcpm."', costlimit='".$costlimit."', dailycostlimit='".$dailycap."', implimit='".$implimit."', blocking='".$blocking."', targeting='".$targeting."', adtag='".$adtag."', adomain='".$adomain."', iurl='".$iurl."', iab='".$iab."', starthour='".$starthour."', endhour='".$endhour."' WHERE id=".$mysql->real_escape_string($_GET['new']);
      //exit;

      //Save targeting settings to redis
      $redis->set($biddername.'-'.$_GET['new'].'-t1', str_replace('"', '', $country));
      $redis->set($biddername.'-'.$_GET['new'].'-t2', str_replace('"', '', $city));
      $redis->set($biddername.'-'.$_GET['new'].'-t3', str_replace('"', '', $language));
      $redis->set($biddername.'-'.$_GET['new'].'-t4', str_replace('"', '', $model));
      $redis->set($biddername.'-'.$_GET['new'].'-t5', str_replace('"', '', $make));
      $redis->set($biddername.'-'.$_GET['new'].'-t6', $devicetype);
      $redis->set($biddername.'-'.$_GET['new'].'-t7', str_replace('"', '', $carrier));
      $redis->set($biddername.'-'.$_GET['new'].'-t8', str_replace('"', '', $os));
      $redis->set($biddername.'-'.$_GET['new'].'-t9', str_replace('"', '', $osv));
      $redis->set($biddername.'-'.$_GET['new'].'-t10', str_replace('"', '', $sites));
      $redis->set($biddername.'-'.$_GET['new'].'-t11', str_replace('"', '', $exchange));
      $redis->set($biddername.'-'.$_GET['new'].'-t12', $_POST['blocking']);
      $redis->set($biddername.'-'.$_GET['new'].'-t13', $adscript);
      $redis->set($biddername.'-'.$_GET['new'].'-t14', str_replace('"', '', $traffictype));
      $redis->set($biddername.'-'.$_GET['new'].'-t15', $targetkeys);
      $redis->set($biddername.'-'.$_GET['new'].'-t16', str_replace('"', '', $desc));
      $redis->set($biddername.'-'.$_GET['new'].'-t17', str_replace('"', '', $contype));
      $redis->set($biddername.'-'.$_GET['new'].'-t18', $browser);
    }
		else
		{
			$mysql->query("INSERT INTO `campaigns`(active, name, uhours, w, h, mincpm, maxcpm, wins, cost, costlimit, dailycostlimit, blocking, targeting, adtag, adomain, iurl, imptracker, iab, startdate, enddate, aid) VALUES('0', '".$name."', '".$uniquehours."', '".$w."', '".$h."', '0', '".$maxcpm."', '0', '0', '".$costlimit."', '".$dailycap."', '".$blocking."', '".$targeting."', '".$adtag."', '".$adomain."', '".$iurl."', '".$tracker."', '".$iab."', '".$startdate."', '".$enddate."', '".$accountid."')");
			//echo "INSERT INTO `campaigns`(active, name, uhours, w, h, mincpm, maxcpm, wins, cost, costlimit, dailycostlimit, blocking, targeting, adtag, adomain, iurl, imptracker, iab, startdate, enddate, aid) VALUES('0', '".$name."', '".$uniquehours."', '".$w."', '".$h."', '".$mincpm."', '".$maxcpm."', '0', '0', '".$costlimit."', '".$dailycap."', '".$blocking."', '".$targeting."', '".$adtag."', '".$adomain."', '".$iurl."', '".$tracker."', '".$iab."', '".$startdate."', '".$enddate."', '".$accountid."')";
      //exit;

      //Save targeting settings to redis
      $redis->set($biddername.'-'.$mysql->insert_id.'-t1', str_replace('"', '', $country));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t2', str_replace('"', '', $city));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t3', str_replace('"', '', $language));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t4', str_replace('"', '', $model));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t5', str_replace('"', '', $make));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t6', $devicetype);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t7', str_replace('"', '', $carrier));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t8', str_replace('"', '', $os));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t9', str_replace('"', '', $osv));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t10', str_replace('"', '', $sites));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t11', str_replace('"', '', $exchange));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t12', $_POST['blocking']);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t13', $adscript);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t14', str_replace('"', '', $traffictype));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t15', $targetkeys);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t16', str_replace('"', '', $desc));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t17', str_replace('"', '', $contype));
      $redis->set($biddername.'-'.$mysql->insert_id.'-t18', $browser);

      //Create required logging directories
      mkdir($logsdir.'winlogs/'.$accountid);
		  mkdir($logsdir.'winlogs/'.$accountid.'/'.$mysql->insert_id);
    }
	 
		//Done
		$mysql->close();
		header('Location: campaigns.php');
		exit;
	}


  //Load Alpha-3 to string array : fuction a3str (converts Alpha-3 country to plain text string)
  $acd = explode(PHP_EOL, file_get_contents('countrycodes.txt'));
  function a3str($a3)
  {
    GLOBAL $acd;
    foreach($acd as $l)
      if($a3[0] == $l[0] && $a3[1] == $l[1] && $a3[2] == $l[2])
        return rtrim(substr($l, 4));
    return $a3;
  }

  //Decode json bidlog into an array of objects
  $bids = array();
  $bidlog = "";

  //Load as many files for today as we can !
  $do = strtotime('-1 days');
  $date1 = date("Y-m-d", $do);
  $date2 = date("Y-m-d");
  //$date = date_sub(date("Y-m-d"), date_interval_create_from_date_string('1 days'));
  for($i = 20; $i < 24; $i++)
    if(file_exists($logsdir.'bidlogs/bidlog-'.$date1.'-'.$i.'.txt'))
      $bidlog .= file_get_contents($logsdir.'bidlogs/bidlog-'.$date1.'-'.$i.'.txt');
  
  /*$si = date("G")-4;
  if($si < 0)
    $si = 0;
  for($i = $si; $i < 24; $i++)
    if(file_exists($logsdir.'bidlogs/bidlog-'.$date2.'-'.$i.'.txt'))
      $bidlog .= file_get_contents($logsdir.'bidlogs/bidlog-'.$date2.'-'.$i.'.txt');*/

  $bidlog = file_get_contents($logsdir.'bidlogs/bidlog-'.date("Y-m-d").'-'.date("G").'.txt');
  $bidlog .= file_get_contents($logsdir.'bidlogs/bidlog-'.date("Y-m-d", strtotime('-1 hours')).'-'.date("G").'.txt');
  $bidlog .= file_get_contents($logsdir.'bidlogs/bidlog-'.date("Y-m-d", strtotime('-2 hours')).'-'.date("G").'.txt');
  
  $lines = explode(PHP_EOL, $bidlog);
  foreach($lines as $l)
  {
    if($l == '')
      continue;
    //$o = '{'.rtrim(explode('{', $l, 2)[1]); //Strip exchange append at beginning
    array_push($bids, json_decode($l));
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Configuration</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX - Demand Side Platform (DSP)">
  <meta name="author" content="VOX">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

  <!-- Font CSS (Via CDN) -->
  <link rel='stylesheet' type='text/css' href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>

  <!-- Datatables CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/datatables/media/css/dataTables.bootstrap.css">
  
  <!-- Datatables Editor Addon CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/datatables/extensions/Editor/css/dataTables.editor.css">

  <!-- Datatables ColReorder Addon CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/datatables/extensions/ColReorder/css/dataTables.colReorder.min.css">

  <!-- Theme CSS -->
  <link rel="stylesheet" type="text/css" href="assets/skin/default_skin/css/theme.css">
  <link rel="stylesheet" type="text/css" href="vendor/plugins/select2/css/core.css">

  <!-- DateTime Picker CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/daterange/daterangepicker.css">
  <link rel="stylesheet" type="text/css" href="vendor/plugins/datepicker/css/bootstrap-datetimepicker.css">

  <!-- Admin Forms CSS -->
  <link rel="stylesheet" type="text/css" href="assets/admin-tools/admin-forms/css/admin-forms.min.css">

  <!-- Favicon -->
  <link rel="shortcut icon" href="assets/img/favicon.ico">

  <style>
    td.min
    {
      width: 1%;
      white-space: nowrap;
    }
    textarea
    {
      resize: none !important;
    }
    .tooltip
    {
      font-size: 14px !important;
      font-weight: bold !important;
    }
  </style>

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

</head>

<body class="dashboard-page">

  <!-- Start: Main -->
  <div id="main">

    <!-- Start: Header -->
    <header class="navbar navbar-fixed-top navbar-shadow">
      <div class="navbar-branding">
        <a class="navbar-brand" href="dashboard.php">
          <img src="assets/img/logos/logo_white.png" height="60%" />
        </a>
        <span id="toggle_sidemenu_l" class="ad ad-lines"></span>
      </div>
    </header>
    <!-- End: Header -->

    <!-- Start: Sidebar -->
    <aside id="sidebar_left" class="nano nano-light affix">

      <!-- Start: Sidebar Left Content -->
      <div class="sidebar-left-content nano-content">

        <!-- Start: Sidebar Menu -->
        <ul class="nav sidebar-menu">
          <li class="sidebar-label pt20">Menu</li>
          <?php $_GET['configure']=1; include "nav.php"; ?>

          <!-- sidebar progress bars -->
          <li class="sidebar-label pt25 pb10">User Stats</li>
          <li class="sidebar-stat">
            <a href="#" class="fs11">
              <span class="fa fa-table text-info"></span>
              <span class="sidebar-title text-muted">Campaign's Completion</span>
              <span class="pull-right mr20 text-muted"><?php echo $rt; ?>%</span>
              <div class="progress progress-bar-xs mh20 mb10">
                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="<?php echo $rt; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $rt; ?>%">
                  <span class="sr-only"><?php echo $rt; ?>% Complete</span>
                </div>
              </div>
            </a>
          </li>
        <!-- End: Sidebar Menu -->

<?php

if(isset($_SESSION['switcher']) && $_SESSION['switcher'] == 1)
{
  echo '<li class="sidebar-label pt20">User Account Swicher</li>';
  echo '<form action="am.php" method="POST">';
  echo '<li><center><select name="suid" style="width:80%;" onchange="this.form.submit();" class="input-sm form-control select2-single-prude">';
  $ra = $mysql->query("SELECT * FROM `account`;");
  if($ra)
  {
    while($rao = $ra->fetch_assoc())
    {
      $ch = '';
      if($accountid == $rao['id'])
        $ch = ' selected';
      echo '<option value="'.$rao['id'].'"'.$ch.'>'.explode(';', $rao['ldat'])[0].'</option>';
    }
  }

  echo '</center></select></li></form>';
}

?>

</ul>

	      <!-- Start: Sidebar Collapse Button -->
	      <div class="sidebar-toggle-mini">
	        <a href="#">
	          <span class="fa fa-sign-out"></span>
	        </a>
	      </div>
	      <!-- End: Sidebar Collapse Button -->

      </div>
      <!-- End: Sidebar Left Content -->

    </aside>
    <!-- End: Sidebar Left -->

    <!-- Start: Content-Wrapper -->
    <section id="content_wrapper">

      <!-- Begin: Content -->
      <section id="content" class="table-layout animated fadeIn">

          <div class="row">



                <!-- Validation Example -->
                <div class="col-xs-12 col-sm-6">
                <div class="admin-form theme-primary mw1000 center-block">
                  
                            <div class="panel panel-primary heading-border">
                  
                                <div class="panel-body">
                  
                                  <div class="section-divider mt20 mb40">
                                    <span> <b style="color:#000;">Campaign Configuration</b> </span>
                                  </div>
                                  <!-- .section-divider -->

<form method="post" action="configure.php?new=<?php if(isset($_GET['cid'])){echo $_GET['cid'];}else{echo "1";} ?>" id="config-form">                  

                                  <div class="section">
                                    <input name="name" type="text" class="form-control" placeholder="Name" <?php if(is_array($ero) != FALSE){echo 'value="'.$ero['name'].'"';} ?> required>
                                  </div>

                                  <div class="section">
                                  <div class="form-group">
                                  <div class="input-group">
                                  <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Banner Size</br><i>(Width & Height)</i></b>" class="fa fa-arrows-h"></i></span>
                                      <select name="size" class="select2-single-prude form-control">
                                      <option value="0x0">Target All</option>
<?php

    $o = array('300x250');
    foreach($bids as $b)
    {
      if(isset($b->{'imp'}[0]->{'banner'}->{'w'}))
        array_push($o, $b->{'imp'}[0]->{'banner'}->{'w'}.'x'.$b->{'imp'}[0]->{'banner'}->{'h'});
    }
    $o = array_unique($o);
    
    $y = '';
    if(is_array($ero) != FALSE)
      $y = $ero['w'].'x'.$ero['h'];

    foreach($o as $v)
    {
      if($y != '' && $v == $y)
        echo '<option value="'.$v.'" selected>'.$v.'</option>';
      else
        echo '<option value="'.$v.'">'.$v.'</option>';
    }

?>
                                      </select>
                                  </div>
                                  </div>
                                  </div>

                                  <div class="section">
                                  <div class="form-group">
                                  <div class="input-group">
                                  <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Banner Image (iURL)</b>" class="fa fa-image"></i></span>
                                    <select id="iurl" name="iurl" class="select2-single-prude form-control select-primary">
                                    <option>No Banner (Only Script)</option>
<?php
$y = '';
if(is_array($ero) != FALSE)
  $y = basename($ero['iurl']);

foreach(glob($rootdir.'b/'.$accountid.'/*') as $file)
{
  $curl = $redis->get($accountid.'-https://' . $fronthn .'/'.$biddername.'/b/' . $accountid . '/' . basename($file));
  if($curl == FALSE)
    continue;

  if($y != '' && basename($file) == $y)
    echo '<option selected>'.basename($file).'</option>';
  else
    echo '<option>'.basename($file).'</option>';
}

?>
                                    </select>
                                  </div>
                                  </div>
                                  </div>

                                  <div id="script" class="section" style="display:none;">
                                    <textarea id="adscript" name="adscript" class="form-control" rows="10" placeholder="Enter additional HTML code." maxlength="12000"><?php
                                      if(isset($_GET['cid']))
                                      {
                                        $r = $redis->get($biddername.'-'.$_GET['cid'].'-t13');
                                        if($r != FALSE)
                                          echo stripslashes(str_replace('\r\n', "\r\n", $r));
                                      }
                                    ?></textarea></br>
                                    

                                    <div class="input-group">
                                      <input id="ifurl" name="ifurl" type="url" class="form-control" placeholder="e.g. https://yourdoamin.com/ad.html">
                                      <span class="input-group-btn">
                                        <button type="button" onclick="javascript:genexoiframe($('#ifurl').val());" class="btn btn-system"><b>Generate Exoclick iFrame</b></button>
                                      </span>
                                    </div>

                                    <div class="input-group">
                                      <input id="ifurl2" name="ifurl" type="url" class="form-control" placeholder="e.g. https://yourdoamin.com/ad.html">
                                      <span class="input-group-btn">
                                        <button type="button" onclick="javascript:genexopopunder($('#ifurl2').val());" class="btn btn-system"><b>Generate Exoclick PopUnder</b></button>
                                      </span>
                                    </div>
                                    <br>
                                    <button type="button" onclick="javascript:genbleedtracking();" class="btn btn-system"><b>Add Bleed Tracking</b></button>
                                    <button type="button" onclick="javascript:genclicktracking();" class="btn btn-system"><b>Add Click Tracking</b></button>
                                    <br>
                                    <br>
                                    <b>Macros:</b></br>
                                    {rnd} <i>- Cache Buster Random</i></br>
                                    {bid} <i>- Bid Price (CPM)</i></br>
                                    {epn} <i>- Name of Exchange impression is sent from</i></br>
                                    {geo} <i>- Origin Country of impression</i></br>
                                    {pub} <i>- Publisher ID providing the impression</i></br>
                                    {src} <i>- Publisher Domain & Page providing the impression</i></br>
                                    {dom} <i>- Publisher Domain providing the impression</i></br>
                                    {sid} <i>- Publisher Site ID</i></br>
                                    {adw} <i>- Ad Zone Width</i></br>
                                    {adh} <i>- Ah Zone Height</i></br>
                                    {zid} <i>- Publisher Zone ID</i></br>
                                    {cid} <i>- Campaign ID</i></br>
                                    {catid} <i>- Category ID</i></br>
                                    {time} <i>- Timestamp (UTC)</i></br>
                                    {lang} <i>- Browser Language</i></br>
                                    {idfa} <i>- Impression IDFA</i></br>
                                    {pubname} <i>- Publisher Name</i></br>
                                    {rawbid} <i>- Bid Request JSON</i>
                                  </div>

                                  <div class="section">
                                    <input name="adomain" type="text" class="form-control" placeholder="(aDomain)" <?php if(is_array($ero) != FALSE){echo 'value="'.$ero['adomain'].'"';} ?> >
                                  </div>

                                  <div class="section">
                                    <input id="miurl" name="miurl" type="url" class="form-control" placeholder="(iurl)" <?php if(is_array($ero) != FALSE){echo 'value="'.$ero['iurl'].'"';} ?> >
                                  </div>

                                  <div class="section">
                                    <input name="tracker" type="url" class="form-control" placeholder="Impression Tracking URL" <?php if(is_array($ero) != FALSE){echo 'value="'.$ero['imptracker'].'"';} ?> >
                                  </div>

                                  <div class="section">
                                      <select name="iab[]" class="select2-single form-control select-primary" multiple="multiple">
<?php

            $y = 0;
            if(is_array($ero) != FALSE)
              $y = explode(',', $ero['iab']);

            $handle = fopen($rootdir . "iab.txt", "r");
            if($handle)
            {
              $out = "";
              while(($line = fgets($handle)) !== false)
              {
                $t = htmlspecialchars(rtrim($line));
                $v = explode(' ', $line);
                $v = htmlspecialchars(rtrim(str_replace('IAB', '', $v[0])));

                $ap = '';
                if($y != '')
                {
                  foreach($y as $yv)
                  {
                    if($yv == $v)
                    {
                      $ap = ' selected';
                      break;
                    }
                  }
                }

                $out .= '<option value="'.$v.'"'.$ap.'>'.$t.'</option>';
              }
              $out = rtrim($out, ',');
              echo $out;
              fclose($handle);
            }

?>
                                      </select>
                                  </div>


<div class="row">

  <div class="col-xs-6">
  <div class="form-group">
  <div class="input-group">
    <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Maximum CPM (<i>Cost Per Mile</i>)</b>" class="fa fa-usd"></i></span>
    <input id="spinner2" name="maxcpm" class="form-control ui-spinner-input" placeholder="Max CPM (USD)" <?php if(is_array($ero) != FALSE){echo 'value="'.$ero['maxcpm'].'"';} ?> required>
  </div>
  </div>
  </div>

  <div class="col-xs-6">
  <div class="form-group">
  <div class="input-group">
    <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Total Maximum Spend this Campaign can reach before turning itself off.</b>" class="fa fa-usd"></i></span>
  <input id="spinner3" name="costlimit" class="form-control ui-spinner-input" placeholder="Maximum Campaign Spend (USD)" <?php if(isset($ero) && $ero['costlimit'] != 0){echo 'value="'.$ero['costlimit'].'"';} ?> required>
  </div>
  </div>
  </div>

</div>



<div class="form-group">
<div class="input-group">
  <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Total Maximum Impressions this Campaign can receive before turning itself off.</b>" class="fa fa-bullseye"></i></span>
  <input id="spinner4" name="implimit" class="form-control ui-spinner-input" placeholder="Maximum Campaign Impressions" <?php if(isset($ero) && $ero['implimit'] != 0){echo 'value="'.$ero['implimit'].'"';} ?> >
</div>
</div>


<div class="row">
  <div class="col-xs-6">
  <div class="form-group">
  <div class="input-group">
    <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>This is how many hours must elapse between bidding on a unique impression, for example an input of 24 means unique impressions will only be be on once ever 24 hours.</b>" class="fa fa-clock-o"></i></span>
    <input id="spinner7" name="uniquehours" class="form-control ui-spinner-input" placeholder="Frequency Capping (x Hours)" <?php if(isset($ero)){echo 'value="'.$ero['uhours'].'"';}else{echo 'value="24"';} ?> >
  </div>
  </div>
  </div>


  <div class="col-xs-6">
  <div class="form-group">
  <div class="input-group">
    <span class="input-group-addon"><i data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Maximum spend a Campaign can reach within one day (USD).</b>" class="fa fa-dollar"></i></span>
    <input id="spinner8" name="dailycap" class="form-control ui-spinner-input" placeholder="Daily Max Spend" <?php if(isset($ero) && $ero['dailycostlimit'] != 0){echo 'value="'.$ero['dailycostlimit'].'"';} ?> >
  </div>
  </div>
  </div>
</div>




                  
                                </div>
                                <!-- end .form-body section -->
                                <div class="panel-footer text-right">
                                  <button type="submit" class="button btn-primary"> <b><?php if(isset($_GET['cid'])){echo 'Save Campaign';}else{echo 'Create Campaign';} ?></b> </button>
                                  <button data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>This will reset your campaign settings back to their orignal state before editing.</b>" type="reset" class="button"> Reset </button>
                                </div>
                                <!-- end .form-footer section -->
                  
                  
                            </div>
                  
                          </div>
                          </div>
                          <!-- end: .admin-form -->
                  
                          <div class="col-xs-12 col-sm-6">
                          <div class="admin-form theme-primary mw1000 center-block">
                            <div class="panel panel-primary heading-border">

                                <div class="panel-body">
                  
                                  <div class="section-divider mt20 mb40">
                                    <span> <b style="color:#000;">Targeting Configuration</b> </span>
                                  </div>
                                  <!-- .section-divider -->
                                  
                                  <div class="section">
                                    <span class="label label-primary"><b>Country</b></span></br>
                                    <select name="country[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

    if(isset($_GET['cid']))
      $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t1'));
    foreach($acd as $l)
    {
      if($l == '')
        continue;

      $cc = $l[0] . $l[1] . $l[2];
      $cn = rtrim(substr($l, 4));

      $y = '';
      if(isset($_GET['cid']))
      {
        foreach($sel as $s)
        {
          if($s == $cc)
          {
            $y = ' selected';
            break;
          }
        }
      }

      echo '<option value="'.$cc.'"'.$y.'>'.$cn.'</option>';
    }

/*
              $o = array('USA','GBR');
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'geo'}->{'country'}))
                  array_push($o, strtoupper($b->{'device'}->{'geo'}->{'country'}));
              }
              $o = array_unique($o);
              foreach($o as $v)
                echo '<option value="'.$v.'">'.a3str($v).'</option>';
*/
?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>City</b></span></br>
                                    <select name="city[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'geo'}->{'city'}))
                  array_push($o, strtolower(strval($b->{'device'}->{'geo'}->{'city'})));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t2'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.ucfirst($v).'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Language</b></span></br>
                                    <select name="language[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'language'}))
                  array_push($o, strtolower($b->{'device'}->{'language'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t3'));
              foreach($o as $v)
              {
                if($v == '--' || $v == '')
                  continue;
                
                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.strtoupper($v).'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Model</b></span></br>
                                    <select name="model[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'model'}))
                  array_push($o, strtolower(strval($b->{'device'}->{'model'})));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t4'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.ucfirst($v).'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Make</b></span></br>
                                    <select name="make[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'make'}))
                  array_push($o, strtolower($b->{'device'}->{'make'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t5'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.ucfirst($v).'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Device Type</b></span></br>
                                    <select name="devicetype[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

/*
                                    <option value="1">Mobile/Tablet</option>
                                    <option value="2">Personal Computer</option>
                                    <option value="3">Connected TV</option>
                                    <option value="4">Phone</option>
                                    <option value="5">Tablet</option>
                                    <option value="6">Connected Device</option>
                                    <option value="7">Set Top Box</option>
*/

              function dtc($id)
              {
                if($id == 1){return "Mobile/Tablet";}
                if($id == 2){return "Personal Computer";}
                if($id == 3){return "Connected TV";}
                if($id == 4){return "Phone";}
                if($id == 5){return "Tablet";}
                if($id == 6){return "Connected Device";}
                if($id == 7){return "Set Top Box";}
                return $id;
              }

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'devicetype'}))
                  array_push($o, strtolower($b->{'device'}->{'devicetype'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t6'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    $s = substr($s, 12);
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option value=\'devicetype":'.$v.'\''.$y.'>'.dtc($v).'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Carrier</b></span></br>
                                    <select name="carrier[]" class="select2-editmultiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'carrier'}))
                  array_push($o, strtolower($b->{'device'}->{'carrier'}));
                if(isset($b->{'maxmind'}))
                  array_push($o, strtolower($b->{'maxmind'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t7'));

              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.ucwords($v).'</option>';
              }

?>
                                    </select>
                                  </div>


                                  <div class="section">
                                  <span class="label label-primary"><b>Connection Type</b></span></br>
                                    <select name="contype[]" class="select2-editmultiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              //preset
              array_push($o, "Dialup");
              array_push($o, "Cable/DSL");
              array_push($o, "Corporate");
              array_push($o, "Cellular");
              foreach($bids as $b)
              {
                if(isset($b->{'contype'}))
                  array_push($o, strtolower($b->{'contype'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t17'));

              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.ucwords($v).'</option>';
              }

?>
                                    </select>
                                  </div>


                                  <div class="section">
                                  <span class="label label-primary"><b>Browser</b></span></br>
                                    <select name="browser[]" class="select2-editmultiple form-control select-primary" multiple="multiple">
                                    <?php 
                                      $v18 = $redis->get($biddername.'-'.$_GET['cid'].'-t18');
                                    ?>
                                    <option value="chrome"<?php if(isset($v18) && $v18 == 'chrome'){echo ' selected';} ?>>Chrome</option>
                                    <option value="firefox"<?php if(isset($v18) && $v18 == 'firefox'){echo ' selected';} ?>>Firefox</option>
                                    <option value="edge"<?php if(isset($v18) && $v18 == 'edge'){echo ' selected';} ?>>Edge</option>
                                    <option value="android"<?php if(isset($v18) && $v18 == 'android'){echo ' selected';} ?>>Android</option>
                                    <option value="msie"<?php if(isset($v18) && $v18 == 'msie'){echo ' selected';} ?>>Internet Explorer</option>
                                    <option value="opr/"<?php if(isset($v18) && $v18 == 'opr/'){echo ' selected';} ?>>Opera</option>
                                    <option value="ipad"<?php if(isset($v18) && $v18 == 'ipad'){echo ' selected';} ?>>iPad</option>
                                    <option value="iphone"<?php if(isset($v18) && $v18 == 'iphone'){echo ' selected';} ?>>iPhone</option>
                                    <option value="samsungbrowser"<?php if(isset($v18) && $v18 == 'samsungbrowser'){echo ' selected';} ?>>Samsung</option>
                                    <option value="midori"<?php if(isset($v18) && $v18 == 'midori'){echo ' selected';} ?>>Midori</option>
                                    </select>
                                  </div>


                                  <div class="section">
                                  <span class="label label-primary"><b>Operating System</b></span></br>
                                    <select name="os[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              //preset
              array_push($o, "android");
              array_push($o, "ios");
              //array_push($o, "");
              //array_push($o, "");
              //array_push($o, "");
              //array_push($o, "");
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'os'}))
                  array_push($o, strtolower($b->{'device'}->{'os'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t8'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                if($v == 'ios')
                  $v = 'iOS';
                else
                  $v = ucfirst($v);
                echo '<option'.$y.'>'.$v.'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Operating System Version</b></span></br>
                                    <select name="osv[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'device'}->{'osv'}))
                  array_push($o, str_replace('_', '.', strtolower($b->{'device'}->{'osv'})));
                else if(isset($b->{'osv'}))
                  array_push($o, str_replace('_', '.', strtolower($b->{'osv'})));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t9'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.$v.'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Target Sites</b></span></br>
                                    <select name="sites[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'app'}->{'domain'}))
                  array_push($o, strtolower($b->{'app'}->{'domain'}));
                if(isset($b->{'site'}->{'domain'}))
                  array_push($o, strtolower($b->{'site'}->{'domain'}));
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t10'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.$v.'</option>';
              }

?>
                                    </select>
                                  </div>

                                <div class="section">
                                  <span class="label label-primary"><b>Target Descriptions</b></span></br>
                                    <select name="desc[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              $o = array();
              foreach($bids as $b)
              {
                if(isset($b->{'app'}->{'name'}))
                  array_push($o, $b->{'app'}->{'name'});
                if(isset($b->{'site'}->{'name'}))
                  array_push($o, $b->{'site'}->{'name'});
              }
              $o = array_unique($o);
              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t16'));
              foreach($o as $v)
              {
                if($v == '')
                  continue;

                $y = '';
                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(strtolower($s) == strtolower($v))
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo '<option'.$y.'>'.$v.'</option>';
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Traffic Type</b></span></br>
                                    <select name="traffictype[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

  $v = $redis->get($biddername.'-'.$_GET['cid'].'-t14');

  if(stripos($v, 'App') === FALSE)
    echo '<option>App</option>';
  else
    echo '<option selected>App</option>';

  if(stripos($v, 'Site') === FALSE)
    echo '<option>Site</option>';
  else
    echo '<option selected>Site</option>';

?>
                                    </select>
                                  </div>


                                  <div class="section">
                                  <span class="label label-primary"><b>Exchanges</b></span></br>
                                    <select name="exchange[]" class="select2-multiple form-control select-primary" multiple="multiple">
<?php

              if(isset($_GET['cid']))
                $sel = explode(',', $redis->get($biddername.'-'.$_GET['cid'].'-t11'));
              foreach($exchanges as $e)
              {
                $y = '';

                if(isset($_GET['cid']))
                {
                  foreach($sel as $s)
                  {
                    if(stripos($s, $e) !== FALSE)
                    {
                      $y = ' selected';
                      break;
                    }
                  }
                }

                echo "<option value='exchange\":\"".$e."'".$y.">".ucfirst($e)."</option>";
              }

?>
                                    </select>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Targeting Keywords</b></span></br>
                                    <textarea name="targetkeys" class="form-control" rows="10" placeholder="Enter each targeting keyword on a new line." maxlength="12000"><?php
                                      if(isset($_GET['cid']))
                                      {
                                        $r = $redis->get($biddername.'-'.$_GET['cid'].'-t15');
                                        if($r != FALSE)
                                          echo $r;
                                      }
                                    ?></textarea>
                                  </div>

                                  <div class="section">
                                  <span class="label label-primary"><b>Blocking Keywords</b></span></br>
                                    <textarea name="blocking" class="form-control" rows="10" placeholder="Enter each blocking keyword on a new line." maxlength="12000"><?php
                                      if(isset($_GET['cid']))
                                      {
                                        $r = $redis->get($biddername.'-'.$_GET['cid'].'-t12');
                                        if($r != FALSE)
                                          echo str_replace('\r\n', "\r\n", $r);
                                      }
                                    ?></textarea>
                                  </div>





                                </div>
                                
</form>
                            </div>        
                          </div>
                          </div>
                          <!-- end: .admin-form -->

        
        </div>
      </section>
      <!-- End: Content -->

    </section>
    <!-- End: Content-Wrapper -->


  </div>
  <!-- End: Main -->

  <!-- BEGIN: PAGE SCRIPTS -->

  <!-- jQuery -->
  <script src="vendor/jquery/jquery-1.11.1.min.js"></script>
  <script src="vendor/jquery/jquery_ui/jquery-ui.min.js"></script>

  <!-- Datatables -->
  <script src="vendor/plugins/datatables/media/js/jquery.dataTables.js"></script>

  <!-- Datatables Tabletools addon -->
  <script src="vendor/plugins/datatables/extensions/TableTools/js/dataTables.tableTools.min.js"></script>

  <!-- Datatables ColReorder addon -->
  <script src="vendor/plugins/datatables/extensions/ColReorder/js/dataTables.colReorder.min.js"></script>

  <!-- Datatables Bootstrap Modifications  -->
  <script src="vendor/plugins/datatables/media/js/dataTables.bootstrap.js"></script>

  <!-- HighCharts Plugin -->
  <script src="vendor/plugins/highcharts/highcharts.js"></script>

  <!-- Select2 Plugin Plugin -->
  <script src="vendor/plugins/select2/select2.min.js"></script>

  <!-- jQuery Validate Plugin-->
  <script src="assets/admin-tools/admin-forms/js/jquery.validate.min.js"></script>

  <!-- jQuery Validate Addon -->
  <script src="assets/admin-tools/admin-forms/js/additional-methods.min.js"></script>  

  <!-- JvectorMap Plugin + US Map (more maps in plugin/assets folder) -->
  <script src="vendor/plugins/jvectormap/jquery.jvectormap.min.js"></script>
  <script src="vendor/plugins/jvectormap/assets/jquery-jvectormap-us-lcc-en.js"></script> 

  <!-- Bootstrap Tabdrop Plugin -->
  <script src="vendor/plugins/tabdrop/bootstrap-tabdrop.js"></script>

  <!-- DateRange Plugin -->
  <script src="vendor/plugins/globalize/globalize.min.js"></script>
  <script src="vendor/plugins/moment/moment.min.js"></script>
  <script src="vendor/plugins/daterange/daterangepicker.min.js"></script>
  <script src="vendor/plugins/datepicker/js/bootstrap-datetimepicker.min.js"></script>

  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>

  <script type="text/javascript">
    jQuery(document).ready(function()
    {
      "use strict";    
      Core.init();

      if($("#iurl").val() == 'No Banner (Only Script)')
        $("#script").show();
      else
        $("#script").hide();

      $("#spinner1").spinner({min: 0,max: 10,step: 0.001,start: 0});
      $("#spinner2").spinner({min: <?php if($accountid != 1){echo '0.2';}else{echo '0';} ?>,max: 10,step: 0.001,start: 0});
      $("#spinner3").spinner({min: 0,step: 1,start: 0});
      $("#spinner4").spinner({min: 0,step: 1000,start: 0});
      $("#spinner5").spinner({min: 0,max: 23,step: 1,start: 0});
      $("#spinner6").spinner({min: 0,max: 23,step: 1,start: 0});
      $("#spinner7").spinner({min: 0,max: 48,step: 1,start: 0});
      $("#spinner8").spinner({min: 0,step: 1,start: 0});

      $('#startdate').datetimepicker();
      $('#enddate').datetimepicker();

      $(".select2-single-prude").select2();
      $(".select2-single").select2({placeholder: "No Content Category", allowClear: true});
      $(".select2-multiple").select2({placeholder: "Target All", allowClear: true});

      $(".select2-editmultiple").select2(
      {
        placeholder: "Target All",
        allowClear: true,
        tags: true
      });

    });

    $("#iurl").change(function()
    {
      if($("#iurl").val() == 'No Banner (Only Script)')
      {
        $("#script").show();
        $("#miurl").val('');
      }
      else
      {
        $("#script").hide();
      }
    });

    function genexoiframe(url)
    {
      $('#adscript').val('<?xml version="1.0" encoding="ISO-8859-1"?><ad><iframeAd><url><![CDATA['+url+']]></url></iframeAd></ad>');
    }

    function genexopopunder(url)
    {
      $('#adscript').val('<?xml version="1.0" encoding="ISO-8859-1"?><ad><popunderAd><url><![CDATA['+url+']]></url></popunderAd></ad>');
    }

    function genbleedtracking()
    {
      $('#adscript').val($('#adscript').val()+"\n\n"+'<img src="https://app.voxdsp.com/bleed.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />');
    }

    function genclicktracking()
    {
      $('#adscript').val($('#adscript').val()+"\n\n"+'<div id="htp"></div><scr'+'ipt>!function(){function e(){var e=document.createElement("iframe");e.setAttribute("src","https://app.voxdsp.com/click.php?a={cid}&bid={rawb64}"),e.style.width="1px",e.style.height="1px",e.style.display="none",document.body.appendChild(e)}document.addEventListener("click",function(t){e()},!1),document.addEventListener("touchstart",function(t){e()},!1);var t=0,n=0,p=0,h=!0,i=!1;window.addEventListener("deviceorientation",function(e){n<13?(1!=e.isTrusted&&(h=!1),n++):1==h&&(0==i&&(document.getElementById("htp").innerHTML+=\'<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />\'),i=!0)},!0),window.onmousemove=function(e){var n;p<13?((n=Math.abs(e.movementX))>t&&(t=n),(n=Math.abs(e.movementY))>t&&(t=n),1!=e.isTrusted&&(h=!1),a=e.alpha,b=e.beta,g=e.gamma,p++):t<.33*window.screen.availWidth&&1==h&&(0==i&&(document.getElementById("htp").innerHTML+=\'<img src="https://app.voxdsp.com/human.php?e={cid}&d={dom}&u={idfa}" width="1" height="1" style="display:none;" />\'),i=!0)}}();</scr'+'ipt>');
    }
  </script>

</body>

</html>
<?php $mysql->close(); ?>
