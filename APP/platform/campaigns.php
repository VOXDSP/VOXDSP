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

  //Don't allow access to campaigns that do not belong to the signed in account
  if(isset($_GET['cid'])) //Secure active/deactive
    secureAC($_GET['cid']);
  if(isset($_GET['dup'])) //Secure duplicate
    secureAC($_GET['dup']);
  
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


  //Remove Campaign
  function deleteCampaign($id)
  {
    GLOBAL $mysql, $redis, $biddername, $logsdir;
    secureAC($id); //Secure ANY delete operation

    $winlogs = $logsdir.'winlogs/'.$_SESSION['auth'].'/'.$id.'/';
    $bleedlogs = $logsdir.'bleedlogs/'.$id.'/';
    $clicklogs = $logsdir.'clicklogs/'.$id.'.txt';
    $dayreport = $logsdir.'dayreport/'.$id.'.txt';

    //Delete folders
    //system('rm -r ' . $winlogs);
    //system('rm -r ' . $bleedlogs);
    //system('rm -r ' . $clicklogs);
    //system('rm -r ' . $dayreport);

    //Delete all the saved targeting parameters for UI
    $redis->delete($biddername.'-'.$id.'-t1');
    $redis->delete($biddername.'-'.$id.'-t2');
    $redis->delete($biddername.'-'.$id.'-t3');
    $redis->delete($biddername.'-'.$id.'-t4');
    $redis->delete($biddername.'-'.$id.'-t5');
    $redis->delete($biddername.'-'.$id.'-t6');
    $redis->delete($biddername.'-'.$id.'-t7');
    $redis->delete($biddername.'-'.$id.'-t8');
    $redis->delete($biddername.'-'.$id.'-t9');
    $redis->delete($biddername.'-'.$id.'-t10');
    $redis->delete($biddername.'-'.$id.'-t11');
    $redis->delete($biddername.'-'.$id.'-t12');
    $redis->delete($biddername.'-'.$id.'-t13');
    $redis->delete($biddername.'-'.$id.'-t14');
    $redis->delete($biddername.'-'.$id.'-t15');
    $redis->delete($biddername.'-'.$id.'-t16');
    $redis->delete($biddername.'-'.$id.'-t17');
    $redis->delete($biddername.'-'.$id.'-t18');

    //Vars never reset from creation
    $redis->delete('mb-'.$id.'-wins');
    $redis->delete('mb-'.$id.'-cost');
    $redis->delete($id.'-clicks');

    //Reset Daily Vars
    $redis->delete($id.'-cost');

    //rtvars
    $redis->delete($id.'-lbid');
    $redis->delete($id.'-lwin');
    $redis->delete($id.'-lbids');
    $redis->delete($id.'-lwins');

    //cv vars
    $redis->delete($id.'-CV0');
    $redis->delete($id.'-CV1');

    //Delete any bleeding rate test logs
    $keys = $redis->keys($id.'-dbl-*'); //Total win count per side
    foreach($keys as $k)
      $redis->del($k);

    //Impressions counted via bleed pixel
    $keys = $redis->keys($id.'-dbc-*');
    foreach($keys as $k)
      $redis->del($k);

    //Making sure they are unique counts
    $keys = $redis->keys($id.'-dbu-*');
    foreach($keys as $k)
      $redis->del($k);

    //Hourly logging
    $keys = $redis->keys($id.'-dbh-*');
    foreach($keys as $k)
      $redis->del($k);

    //Human Count 
    $keys = $redis->keys($id.'-dbhc-*');
    foreach($keys as $k)
      $redis->del($k);

    //Human Count Unique
    $keys = $redis->keys($id.'-dbhu-*');
    foreach($keys as $k)
      $redis->del($k);
    
    //Ok delete that master mysql entry
		$mysql->query("DELETE FROM `campaigns` WHERE id=".$mysql->real_escape_string($id));
    
  }

  //Multi delete?
  if(isset($_GET['mdel']))
  {
      foreach($_POST['check_list'] as $i=>$v)
        deleteCampaign($v);

      $mysql->close();
	  	header('Location: campaigns.php');
		  exit;
  }

  //Multi toggle?
  if(isset($_GET['mtog']))
  {
    //Check if we are allowed to enable campaigns
    $r = $mysql->query("SELECT spent,credit FROM account WHERE id=".$accountid);
    if($r)
    {
      $ro = $r->fetch_assoc();
      if($ro['spent'] >= $ro['credit']*1000)
      {
        header('Location: campaigns.php');
        exit;
      }
    }

    //If we are, enable selected
    foreach($_POST['check_list'] as $i=>$v)
    {
      secureAC($v); //Secure ANY toggle operation
      $mysql->query("UPDATE `campaigns` SET active=1-active WHERE id=".$mysql->real_escape_string($v));
    }

    $mysql->close();
    header('Location: campaigns.php');
    exit;
  }

  //Multi toggle?
  if(isset($_GET['mhide']))
  {
    //If we are, enable selected
    foreach($_POST['check_list'] as $i=>$v)
    {
      secureAC($v); //Secure ANY hide operation
      $mysql->query("UPDATE `campaigns` SET active=-1 WHERE id=".$mysql->real_escape_string($v));
    }

    $mysql->close();
    header('Location: campaigns.php');
    exit;
  }

  //Toggle Active State of all Campaigns owned by logged in account
	if(isset($_GET['unhide']))
	{
    $mysql->query("UPDATE `campaigns` SET active=0 WHERE active=-1 AND aid=".$accountid);
    $mysql->close();
    header('Location: campaigns.php');
    exit;
	}

	//Toggle Active State of Campaign
	if(isset($_GET['cid']) && isset($_GET['s']))
	{
    //Check if we are allowed to enable campaigns
    if($_GET['s'] == 1)
    {
      $r = $mysql->query("SELECT spent,credit FROM account WHERE id=".$accountid);
      if($r)
      {
        $ro = $r->fetch_assoc();
        if($ro['spent'] >= $ro['credit']*1000)
        {
          header('Location: campaigns.php');
          exit;
        }
      }
    }

    //If we are, enable it.
    $mysql->query("UPDATE `campaigns` SET active=".$_GET['s']." WHERE id=".$mysql->real_escape_string($_GET['cid']));
    $mysql->close();
    header('Location: campaigns.php');
    exit;
	}

  //Delete a campaign
	if(isset($_GET['del']))
	{
    deleteCampaign($_GET['del']);
		$mysql->close();
		header('Location: campaigns.php');
		exit;
  }
  
  //Duplicate a Campaign
	if(isset($_GET['dup']))
	{
    $id = $mysql->real_escape_string($_GET['dup']);
    $result = $mysql->query("SELECT * FROM campaigns WHERE id=".$id);
		if($result)
		{
      $row = $result->fetch_assoc();
      unset($row['id']);
      unset($row['wins']);
      unset($row['clicks']);
      unset($row['cost']);
      unset($row['active']);
      $q = "INSERT INTO campaigns";
      $q .= " ( " . implode(', ', array_keys($row)) . ") ";
      $ars = array_values($row);
      foreach($ars as $k=>$iars)
        $ars[$k] = $mysql->real_escape_string($iars);
      $q .= " VALUES ('" . implode("', '", $ars) . "')";
      $mysql->query($q);

      //Duplicate redis records
      $t1 = $redis->get($biddername.'-'.$id.'-t1');
      $t2 = $redis->get($biddername.'-'.$id.'-t2');
      $t3 = $redis->get($biddername.'-'.$id.'-t3');
      $t4 = $redis->get($biddername.'-'.$id.'-t4');
      $t5 = $redis->get($biddername.'-'.$id.'-t5');
      $t6 = $redis->get($biddername.'-'.$id.'-t6');
      $t7 = $redis->get($biddername.'-'.$id.'-t7');
      $t8 = $redis->get($biddername.'-'.$id.'-t8');
      $t9 = $redis->get($biddername.'-'.$id.'-t9');
      $t10 = $redis->get($biddername.'-'.$id.'-t10');
      $t11 = $redis->get($biddername.'-'.$id.'-t11');
      $t12 = $redis->get($biddername.'-'.$id.'-t12');
      $t13 = $redis->get($biddername.'-'.$id.'-t13');
      $t14 = $redis->get($biddername.'-'.$id.'-t14');
      $t15 = $redis->get($biddername.'-'.$id.'-t15');
      $t16 = $redis->get($biddername.'-'.$id.'-t16');
      $t17 = $redis->get($biddername.'-'.$id.'-t17');
      $t17 = $redis->get($biddername.'-'.$id.'-t18');

      $redis->set($biddername.'-'.$mysql->insert_id.'-t1', $t1);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t2', $t2);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t3', $t3);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t4', $t4);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t5', $t5);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t6', $t6);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t7', $t7);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t8', $t8);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t9', $t9);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t10', $t10);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t11', $t11);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t12', $t12);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t13', $t13);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t14', $t14);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t15', $t15);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t16', $t16);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t17', $t17);
      $redis->set($biddername.'-'.$mysql->insert_id.'-t17', $t18);

      //Create required logging directories
      if(!file_exists($logsdir.'winlogs/'.$accountid))
        mkdir($logsdir.'winlogs/'.$accountid);
      if(!file_exists($logsdir.'winlogs/'.$accountid.'/'.$mysql->insert_id))
		    mkdir($logsdir.'winlogs/'.$accountid.'/'.$mysql->insert_id);

    }

		//Done
		$mysql->close();
		header('Location: campaigns.php');
		exit;
	}

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Campaigns</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX - Demand Side Platform (DSP)">
  <meta name="author" content="VOX">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

  <!-- Select2 CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/select2/css/core.css">

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
    .table-scrollable
    { 
      overflow-y: visible!important;
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
        <a class="navbar-brand" href="campaigns.php">
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
          <?php $_GET['campaigns']=1; include "nav.php"; ?>

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
      <form method="post" id="mform" name="mform" action="campaigns.php?mdel=1">

      <section id="content" class="table-layout animated fadeIn">

      <div class="admin-form theme-primary mw1000 center-block">
      <div class="col-xs-12 panel panel-primary heading-border">
      <div class="panel-body">

        <span class="panel-title"><b style="font-family: 'Lato', sans-serif;font-size:1.4em;">
        Campaigns</b> 
        
          <ul class="hidden-xs breadcrumb pull-right">
          <li>
          <span>
            <div class="btn-group">
              <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><b style="color:#000;">Unhide</b> <span style="color:#333;" class="caret ml5"></span></button>
              <ul class="dropdown-menu" role="menu">
                <li><a href="campaigns.php?unhide=1"><b>All Campaigns</b></a></li>
<?php

        if($accountid == 1)
          $r = $mysql->query("SELECT * FROM `campaigns` WHERE active = -1 ORDER BY active DESC;");
        else
				  $r = $mysql->query("SELECT * FROM `campaigns` WHERE aid='".$accountid."' AND active = -1 ORDER BY active DESC;");
				if($r)
				{
					$out = '';
          while($ro = $r->fetch_assoc())
            $out = '<li><a href="campaigns.php?cid='.$ro['id'].'&s=0"><b>'.$ro['name'].'</b></a></li>' . $out;
          echo $out;
        }

?>
              </ul>
            </div>
          </span>
          </li>
          <li>
          <span>
            <div class="btn-group">
              <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><b style="color:#000;">Selected</b> <span style="color:#333;" class="caret ml5"></span></button>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" onclick="$('input:checkbox').prop('checked', true);"><b>Select All</b></a></li>
                <li><a href="#" onclick="$('input:checkbox').prop('checked', false);"><b>UnSelect All</b></a></li>
                <li><a href="#" onclick="postMultiToggle();"><b>Toggle Selected</b></a></li>
                <li><a href="#" onclick="postMultiHide();"><b>Hide Selected</b></a></li>
                <li><a href="#" onclick="postMultiDelete();"><b>Delete Selected</b></a></li>
              </ul>
            </div>
          </span>
          </li>
          <li>
          <span><a href="configure.php"><button type="button" class="btn btn-sm btn-primary"><b style="font-size:1.2em;">New Campaign</b></button></a></span>
          </li>
          </ul>
          
        </span>
        <br><br>
      
            
              <div id="tab2_1" class="tab-pane active">
                <div class="row">
                      
                      <table class="table table-scrollable table-striped table-hover" style="overflow:hidden;" id="datatable" cellspacing="0" width="100%">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Banner Dimensions</b><br><i>This is the Width and Height of the banner size your campaign targets, if set to 'ALL' it will target any and all banner sizes available.</i>" data-html="true">W/H</th>
                            <th>Name</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Total Banner Displays</b><br><i>Number of times your banner/adcode was displayed to a user on publisher website.</i>" data-html="true">Impressions</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Total Banner Clicks</b><br><i>Total banner displays that resulted in a user clicking through to the advertiser landing page.</i>" data-html="true">Clicks</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Click-through Rate</b><br><i>Percentage of traffic bidded on and won that clicked through to the advertiser landing page.</i>" data-html="true">CTR</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Cost Per Click</b><br><i>The average price paid per click on the advertisement.</i>" data-html="true">CPC</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Total Cost</b><br><i>This is the total spend in USD for this campaign.</i>" data-html="true">Cost</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Maximum Cost</b><br><i>This is the maximum spend limit in USD for this campaign.</i>" data-html="true">Max</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Effective Cost Per Mile</b><br><i>This is the average cost of 1,000 impressions for this campaign.</i>" data-html="true">eCPM - CPM</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Bleed Percentage</b><br><i>The percentage of impressions that where won but never arrived.</i>" data-html="true">Bleed</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Percentage Human</b><br><i>The percentage of impressions that passed the human verification test.</i>" data-html="true">Human</th>
                            <th>#</th>
                          </tr>
                        </thead>
                        <tbody>
    <?php

        $t3 = 0;
        $t4 = 0;
        $t7 = 0;
        $t8 = 0;

        $t9 = 0;
        $t10 = 0;


        //if($accountid == 1)
        //  $r = $mysql->query("SELECT * FROM `campaigns` WHERE active != -1 ORDER BY active DESC;");
        //else
				  $r = $mysql->query("SELECT * FROM `campaigns` WHERE aid='".$accountid."' AND active != -1 ORDER BY active DESC;");
				if($r)
				{
					while($ro = $r->fetch_assoc())
					{
            $ecpm = 0;
            if($ro['wins'] != 0)
              $ecpm = (($ro['cost']/1000) / ($ro['wins']/1000));
            if($ecpm <= 0.01)
              $ecpm = $ro['maxcpm'];
            $ecpm = number_format($ecpm, 2);

            $clock = "";
            $dtt = '';
            if($ro['startdate'] != 0)
              $dtt = "<b>Starts: ".$ro['startdate'];
            if($ro['enddate'] != 0)
              $dtt .= "<br>Ends:".$ro['enddate']."</b>";

            if($dtt != '')
              $clock = " <font size=\"3m\"><i data-toggle=\"tooltip\" data-placement=\"right\" title=\"".$dtt."\" data-html=\"true\" style=\"color:#3498db;cursor:pointer;\" class=\"fa fa-clock-o\"></i></font>";
            

            $as = '<input type="checkbox" name="check_list['.$ro['id'].']" value="'.$ro['id'].'"> <a href="campaigns.php?cid='.$ro['id'].'&s=1"><font size="3m"><i data-toggle="tooltip" data-placement="right" title="<b>Enable Creative</b>" data-html="true" class="fa fa-power-off fa-fw" style="color:#888;"></i></font></a><b>'.$clock.'</b>';
            if($ro['active'] == 1)
              $as = '<input type="checkbox" name="check_list['.$ro['id'].']" value="'.$ro['id'].'"> <a href="campaigns.php?cid='.$ro['id'].'&s=0"><font size="3m"><i data-toggle="tooltip" data-placement="right" title="<b>Disable Creative</b>" data-html="true" class="fa fa-power-off fa-fw" style="color:#3498db;"></i></font></a><b>'.$clock.'</b>';
        
?>
<tr><td class="min">
          <span>
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown"><i class="fa fa-line-chart fa-fw" style="color:#3498db;"></i></button>
              
              <ul class="dropdown-menu" role="menu">
                <li><a href="daystats.php?cid=<?php echo $ro['id']; ?>&n=<?php echo $ro['name']; ?>" target="_blank"><b>Daily Reports</b></a></li>
                <li><a href="clickstats.php?cid=<?php echo $ro['id']; ?>&n=<?php echo $ro['name']; ?>" target="_blank"><b>Click Stats</b></a></li>
                <li><a href="bleedstats.php?cid=<?php echo $ro['id']; ?>&n=<?php echo $ro['name']; ?>" target="_blank"><b>Delivery Report</b></a></li>
                <li><a href="report.php?cid=<?php echo $ro['id']; ?>&n=<?php echo $ro['name']; ?>" target="_blank"><b>Impression Log -1hr</b></a></li>
                <li><a href="dl.php?cid=<?php echo $ro['id']; ?>"><b>Download all Data (zip)</b></a></li>
              </ul>
            </div>
          </span>&nbsp;&nbsp;
<?php
            echo $as;
            
            /*if($accountid == 1) // Show redirects only to admin account
            {
              if($ro['directurl'] == '')
                echo ' <font size="3m"><a href="#" href="#" data-toggle="modal" data-target="#dataModal" data-type=1 data-cid="' . $ro['id'] . '" data-title="Redirect for ' . $ro['name'] . '"><i data-toggle="tooltip" data-placement="right" title="<b>Enabled Redirects</b>" data-html="true" class="fa fa-random fa-fw" style="color:#888;"></i></a> </font>';
              else
                echo ' <font size="3m"><a href="am.php?killdirect=' . $ro['id'] . '"><i data-toggle="tooltip" data-placement="right" title="<b>Disable Redirects</b>" data-html="true" class="fa fa-random fa-fw" style="color:#3498db;"></i></a> </font>';
            }*/

            if($ro['targeting'] == '')
              $ro['targeting'] = 'Targets Everything';

            $wah = $ro['w'].'</b>x<b>'.$ro['h'];
            if($ro['w'] == 0)
              $wah = 'ALL';

            $rdl = '';
            $cl = $redis->get($ro['id'].'-cost');
            if($ro['dailycostlimit'] != 0)
                if($cl != FALSE && $cl >= $ro['dailycostlimit']*1000)
                    $rdl = ' color="#d9534f"><b data-toggle="tooltip" data-placement="bottom" title="<b>Daily Cap Reached</b>" data-html="true"';

            $lwins = $redis->get('mb-'.$ro['id'].'-wins');
            $lcost = $redis->get('mb-'.$ro['id'].'-cost');
            $lclicks = $redis->get($ro['id'].'-clicks');
            if($lwins == FALSE)
              $lwins = $ro['wins'];
            if($lcost ==  FALSE)
              $lwins = $ro['cost'];
            if($lclicks ==  FALSE)
              $lclicks = $ro['clicks'];

            $t3 += $lwins;
            $t4 += $lclicks;
            $t7 += $lcost/1000;
            $t8 += $ro['costlimit'];

            if($lcost >= $ro['costlimit']*1000)
              $rdl = ' color="#d9534f"><b data-toggle="tooltip" data-placement="bottom" title="<b>Campaign Cap Reached</b>" data-html="true"';


            //Gen bleed and human stats
            $tb = $redis->get($ro['id'].'-CV0');
            $th = $redis->get($ro['id'].'-CV1');

            //Totals for bleed
            $t9 += $tb;
            $t10 += $th;

            echo '</td><td class="min"><b>'.$wah.'</b></td>';
            echo '<td><b data-toggle="tooltip" data-placement="right" title="<b>ID: ' . hash('crc32', $ro['iurl'].$ro['id']) . '</b>" data-html="true">'.htmlspecialchars($ro['name']).'</b></td>';
            echo '<td class="min"><font size="3m">'.number_format($lwins).'</font></td>';
            echo '<td class="min"><font size="3m">'.number_format($lclicks).'</font></td>';
            echo '<td class="min"><font size="3m">'.number_format(divide(100, $lwins)*$lclicks, 2).'%</font></td>';
            echo '<td class="min"><font size="3m">'.number_format(divide($lcost*0.001, $lclicks), 2).'</font></td>';
            echo '<td class="min"><font size="3m"'.$rdl.'>'.fprice($lcost).'</font></td>';
            echo '<td class="min"><font size="3m">'.number_format($ro['costlimit'], 2).'</font></td><td><font size="3m">'.$ecpm.' - '.number_format($ro['maxcpm'], 2).'</font></td>';
            echo '<td class="min"><font size="3m">'.$tb.'%</font></td>';
            echo '<td class="min"><font size="3m">'.$th.'%</font></td>';
            echo '<td class="min">';
            if($ro['blocking'] != '')
              echo '<font size="3m"><a href="#"><i data-toggle="tooltip" data-placement="left" title="<b>[BLOCKING]<br>'.str_replace("\n", '<br>', str_replace('"', '', str_replace(';', '<br>', str_replace(',', '<br>', $ro['blocking'])))).'</b>" data-html="true" class="fa fa-ban fa-fw" style="color:#d9534f;"></i></a> </font>';
            else
              echo '<font size="3m"><a href="#"><i data-toggle="tooltip" data-placement="left" title="<b>[BLOCKING]<br>None</b>" data-html="true" class="fa fa-ban fa-fw" style="color:#d9534f;"></i></a> </font>';
            echo '<font size="3m"><a href="#"><i data-toggle="tooltip" data-placement="left" title="<b>[TARGETING]<br>'.str_replace("\n", '<br>', str_replace('"', '', str_replace(';', '<br>', str_replace(',', '<br>', $ro['targeting'])))).'</b>" data-html="true" class="fa fa-bullseye fa-fw" style="color:#d9534f;"></i></a> </font>';
            echo '<font size="3m"><a href="campaigns.php?dup=' . $ro['id'] . '"><i data-toggle="tooltip" data-placement="left" title="<b>Duplicate Campaign</b>" data-html="true" class="fa fa-copy fa-fw" style="color:#3498db;"></i></a> </font>';
            echo '<font size="3m"><a href="configure.php?cid='.$ro['id'].'"><i data-toggle="tooltip" data-placement="left" title="<b>Edit Campaign</b>" data-html="true" class="fa fa-edit fa-fw" style="color:#3498db;"></i></a> </font>';
            echo '<font size="3m"><a href="#" data-toggle="modal" data-target="#dataModal" data-type=2 data-cid="' . $ro['id'] . '" data-title="Preview ' . $ro['name'] . '"><i data-toggle="tooltip" data-placement="left" title="<b>Preview</b>" data-html="true" class="fa fa-search fa-fw" style="color:#5cb85c;"></i></a> </font>';
            echo '<font size="3m"><a href="#" data-toggle="modal" data-target="#dataModal" data-type=3 data-cid="' . $ro['id'] . '" data-title="' . $ro['name'] . '"><i data-toggle="tooltip" data-placement="left" title="<b>Delete</b>" data-html="true" class="fa fa-remove fa-fw" style="color:#d9534f;"></i></a> </font>';
            echo '<font size="3m"><a href="campaigns.php?cid=' . $ro['id'] . '&s=-1"><i data-toggle="tooltip" data-placement="left" title="<b>Hide</b>" data-html="true" class="fa fa-minus fa-fw" style="color:#3498db;"></i></a> </font>';

            echo '</td></tr>';
					}
				}
		?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="min"></th>
                                <th class="min"></th>
                                <th class="min"></th>
                                <th class="min"><font size="3m"><?php echo number_format($t3); ?></font></th>
                                <th><font size="3m"><?php echo number_format($t4); ?></font></th>
                                <th class="min"><font size="3m"><?php echo number_format(divide(100, $t3)*$t4, 2); ?>%</font></th>
                                <th class="min"><font size="3m"><?php echo number_format(divide($t7, $t4), 2); ?></font></th>
                                <th class="min"><font size="3m"><?php echo number_format($t7, 2); ?></font></th>
                                <th class="min"><font size="3m"><?php echo number_format($t8); ?></font></th>
                                <th class="min"><font size="3m">
                                <?php
                                  $tecpm = 0;
                                  if($t3 != 0)
                                    $tecpm = ($t7 / ($t3/1000));
                                  echo number_format($tecpm, 2);
                                ?></font></th>
                                <th class="min"><font size="3m"><?php echo number_format(divide(100, $t3)*$t9, 2); ?>%</font></th>
                                <th class="min"><font size="3m"><?php echo number_format(divide(100, $t3-$t9)*$t10, 2); ?>%</font></th>
                                <th class="min"></th>
                            </tr>
                        </tfoot>
                      </table>

                </div>
              </div>

<div class="visible-xs col-xs-12">
<center>
<br>
<div class="btn-group">
<button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><b style="color:#000;">Unhide</b> <span class="caret ml5"></span></button>
<ul class="dropdown-menu" role="menu">
<li><a href="campaigns.php?unhide=1"><b>All Campaigns</b></a></li>
<?php
  if($accountid == 1)
    $r = $mysql->query("SELECT * FROM `campaigns` WHERE active = -1 ORDER BY active DESC;");
  else
    $r = $mysql->query("SELECT * FROM `campaigns` WHERE aid='".$accountid."' AND active = -1 ORDER BY active DESC;");
  if($r)
  {
    $out = '';
    while($ro = $r->fetch_assoc())
      $out = '<li><a href="campaigns.php?cid='.$ro['id'].'&s=0"><b>'.$ro['name'].'</b></a></li>' . $out;
    echo $out;
  }
?>
</ul>
</div>
<br>
<div class="btn-group">
<button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><b style="color:#000;">Selected</b> <span class="caret ml5"></span></button>
<ul class="dropdown-menu" role="menu">
<li><a href="#" onclick="postMultiDelete();"><b>Delete Selected</b></a></li>
<li><a href="#" onclick="postMultiToggle();"><b>Toggle Selected</b></a></li>
<li><a href="#" onclick="postMultiHide();"><b>Hide Selected</b></a></li>
</ul>
</div>
<br>
<span><a href="configure.php"><button type="button" class="btn btn-sm btn-primary"><b style="font-size:1.2em;">New Campaign <i class="fa fa-edit fa-plus" style="color:#FFF;"></i></b></button></a></span>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
</center>
</div>
        
          </div>
        </div>

        
      </section>
      <!-- End: Content -->
      </form>

    </section>
    <!-- End: Content-Wrapper -->

    <!-- Dynamic Content Modal-->
    <div class="modal fade" id="dataModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    



  </div>
  <!-- End: Main -->

  <!-- BEGIN: PAGE SCRIPTS -->


  <!-- jQuery -->
  <script src="vendor/jquery/jquery-1.11.1.min.js"></script>
  <script src="vendor/jquery/jquery_ui/jquery-ui.min.js"></script>

  <!-- Datatables -->
  <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
  <script src="vendor/plugins/datatables/extensions/TableTools/js/dataTables.tableTools.min.js"></script>
  <script src="vendor/plugins/datatables/extensions/ColReorder/js/dataTables.colReorder.min.js"></script>
  <script src="vendor/plugins/datatables/media/js/dataTables.bootstrap.js"></script>

  <!-- HighCharts Plugin -->
  <script src="vendor/plugins/highcharts/highcharts.js"></script>

  <!-- Select2 Plugin Plugin -->
  <script src="vendor/plugins/select2/select2.min.js"></script>

  <!-- JvectorMap Plugin + US Map (more maps in plugin/assets folder) -->
  <script src="vendor/plugins/jvectormap/jquery.jvectormap.min.js"></script>
  <script src="vendor/plugins/jvectormap/assets/jquery-jvectormap-us-lcc-en.js"></script> 

  <!-- Bootstrap Tabdrop Plugin -->
  <script src="vendor/plugins/tabdrop/bootstrap-tabdrop.js"></script>


  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>

  <script type="text/javascript">
    function number_format(number, decimals, decPoint, thousandsSep)
    {
      number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
      var n = !isFinite(+number) ? 0 : +number
      var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
      var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep
      var dec = (typeof decPoint === 'undefined') ? '.' : decPoint
      var s = ''
      var toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec)
        return '' + (Math.round(n * k) / k)
          .toFixed(prec)
      }
      // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
      s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
      if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
      }
      if ((s[1] || '').length < prec) {
        s[1] = s[1] || ''
        s[1] += new Array(prec - s[1].length + 1).join('0')
      }
      return s.join(dec)
    }

    jQuery(document).ready(function()
    {
      "use strict";    
      Core.init();

      $(".select2-single-prude").select2();

      $('#dataModal').on('show.bs.modal', function (event)
      {
            var button = $(event.relatedTarget);
            var cid = button.data('cid');
            var type = button.data('type');
            var title = button.data('title');
            var modal = $(this);
            modal.find(".modal-body").text("Loading page, please wait ...");

            if(type == 1)
                modal.find(".modal-body").load('modaldirect.php?cid=' + cid);
            if(type == 2)
                modal.find(".modal-body").load('modalpreview.php?cid=' + cid);
            if(type == 3)
                modal.find(".modal-body").load('modaldelete.php?cid=' + cid + '&t=' + encodeURI(title));

            modal.find('.modal-title').text(title);
        });
    });

    function postMultiDelete()
    {
      $("#mform").attr("action", "campaigns.php?mdel=1");
      if(confirm("Are you sure you want to delete the selected records?"))
        document.forms.mform.submit();
    }

    function postMultiToggle()
    {
      $("#mform").attr("action", "campaigns.php?mtog=1");
      document.forms.mform.submit();
    }

    function postMultiHide()
    {
      $("#mform").attr("action", "campaigns.php?mhide=1");
      document.forms.mform.submit();
    }

    $('#datatable').dataTable(
    {
      "aoColumnDefs": [{
        'aTargets': [-1]
      }],
      "oLanguage": {
        "oPaginate": {
          "sPrevious": "",
          "sNext": ""
        }
      },
      "iDisplayLength": 25,
      "aLengthMenu": [
        [5, 10, 25, 50, -1],
        [5, 10, 25, 50, "All"]
      ],
      "sDom": '<"dt-panelmenu clearfix"lfr>t<"dt-panelfooter clearfix"ip>',
      "oTableTools": {
        "sSwfPath": "vendor/plugins/datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf"
      },
      "order": [[3, "desc"]],
      scrollX: true,
      stateSave: true
    });
  </script>

</body>

</html>
