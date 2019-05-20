<?php

set_time_limit(0);
error_reporting(0);

  //Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: index.php');
    exit;
  }
  $accountid = $_SESSION['auth'];

  /*if($accountid != 1 && !isset($_SESSION['switcher']))
  {
    echo "You are not meant to be here.";
    exit;
  }
  $accountid = 1;
  $_SESSION['auth'] = 1;*/

  //Lets get started
  require_once "config.php";
  $bidlogpath = $logsdir.'bidlogs/bidlog-'.date("Y-m-d-G").'.txt';

  if(isset($_GET['rblog']))
  {
    //Delete any bleeding rate test logs
    $keys = $redis->keys('*-dbl-*'); //Total win count per side
    foreach($keys as $k)
      $redis->del($k);

    //Impressions counted via bleed pixel
    $keys = $redis->keys('*-dbc-*');
    foreach($keys as $k)
      $redis->del($k);

    //Making sure they are unique counts
    $keys = $redis->keys('*-dbu-*');
    foreach($keys as $k)
      $redis->del($k);

    //Hourly logging
    $keys = $redis->keys('*-dbh-*');
    foreach($keys as $k)
      $redis->del($k);

    //Human Count 
    $keys = $redis->keys('*-dbhc-*');
    foreach($keys as $k)
      $redis->del($k);

    //Human Count Unique
    $keys = $redis->keys('*-dbhu-*');
    foreach($keys as $k)
      $redis->del($k);

    header('Location: explorer.php');
    exit;
  }

  if(isset($_GET['rlog']))
  {
    system('rm '.$logsdir.'bidlogs/*');
    header('Location: explorer.php');
    exit;
  }


	if($_GET['line'] < 0)
    header('Location: explorer.php?line=0');

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
   
  //Generates Total data into Array for Pie's
  $bidlog = str_replace(': ', ':', strtolower(substr(file_get_contents($bidlogpath), 0, 1000000)));
  function FindNext($str, $needle)
  {
    $s1 = strstr($str, $needle);
    if($s1 == FALSE)
      return FALSE;
    return substr($s1, strlen($needle));
  }
  function taParam($find)
  {
    GLOBAL $bidlog;
    $array = array();
    $str = FindNext($bidlog, $find);
    while($str != FALSE)
    {
      $p = explode('"', $str, 2);
      $array[$p[0]] += 1;
  
      $str = FindNext($str, $find);
    }
    arsort($array);
    return $array;
  }
  function taParam2()
  {
    GLOBAL $bidlog;
    $find = '"w":';
    $array = array();
    $str = FindNext($bidlog, $find);
    while($str != FALSE)
    {
      $ds = "";
      $p = explode(',', $str, 2);
      $ds .= str_replace(']', '', str_replace('}', '', $p[0])) . 'x';
      $str = strstr($str, '"h":');
      $str = substr($str, 4);
      $p = explode(',', $str, 2);
      $ds .= str_replace(']', '', str_replace('}', '', $p[0]));
      $array[$ds] += 1;
  
      $str = FindNext($str, $find);
    }
    arsort($array);
    return $array;
  }
  function taParam3($find)
  {
    GLOBAL $bidlog;
    $array = array();
    $str = FindNext($bidlog, $find);
    while($str != FALSE)
    {
      $p = explode(',', $str, 2);
      $p[0] = str_replace('}', '', $p[0]);
      $p[0] = str_replace(']', '', $p[0]);
      $array[$p[0]] += 1;
  
      $str = FindNext($str, $find);
    }
    arsort($array);
    return $array;
  }
  function top10($array)
  {
    return array_slice($array, 0, 10);
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Bid Explorer</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX - Demand Side Platform (DSP)">
  <meta name="author" content="VOX">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

 <!-- Font CSS (Via CDN) -->
 <link rel='stylesheet' type='text/css' href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>

<!-- Datatables CSS -->
<link rel="stylesheet" type="text/css" href="vendor/plugins/datatables/media/css/dataTables.bootstrap.css">

<!-- Chart Plugin CSS -->
<link rel="stylesheet" type="text/css" href="vendor/plugins/c3charts/c3.min.css">  

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
  .ton
  {
    font-weight: bold;
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
      <li class="sidebar-label pt20">Home</li>
      <?php $_GET['explorer']=1; include "nav.php"; ?>

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
    <section id="content" class="table-layout animated fadeIn" style="background-color:#fff;">
      <div class="row">

        <div class="panel panel-primary col-xs-12">
          <div class="panel-heading">
            <span class="panel-title"><b style="font-family: 'Lato', sans-serif;font-size:1.4em;">Available Traffic</b></span>
          </div>
          <div class="panel-body">
            
          <center>
            <div class="col-xs-12 col-sm-2">
            <center class="ton">Banner Size</center>
              <div id="pie-chart2" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
            <center class="ton">Source Name</center>
              <div id="pie-chart10" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
            <center class="ton">Source Domain</center>
              <div id="pie-chart3" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">Carrier</center>
              <div id="pie-chart4" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">Country</center>
              <div id="pie-chart5" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">City</center>
              <div id="pie-chart13" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">OS</center>
              <div id="pie-chart9" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">OSv</center>
              <div id="pie-chart14" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">Make</center>
              <div id="pie-chart11" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">Model</center>
              <div id="pie-chart12" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">JS Enabled</center>
              <div id="pie-chart15" style="height: 230px; width: 100%;"></div>
            </div>
            <div class="col-xs-12 col-sm-2">
             <center class="ton">Language</center>
              <div id="pie-chart16" style="height: 230px; width: 100%;"></div>
            </div>
          </center>

          </div>
        </div>

        <div class="panel panel-primary col-xs-12">
          <div class="panel-heading">
            <span class="panel-title"><b style="font-family: 'Lato', sans-serif;font-size:1.4em;">Bid Explorer</b></span>
          </div>
          <div class="panel-body">
            
              <div class="col-xs-4 pull-left"><a href="explorer.php?line=<?php echo intval($_GET['line'])-1; ?>"><b><button type="button" class="btn btn-info pull-left"><b style="font-size:1.2em;">< Previous</b></button></b></a></div>
              <div class="col-xs-4 text-center">
              <form action="explorer.php" method="POST" id="frep">
              <select name="srep" class="form-control" id="srep">
                <?php if(isset($_POST['srep'])){echo "<option>".$_POST['srep']."</option>";} ?>
                <option>Bidfloor</option>
                <option>Country</option>
                <option>City</option>
                <option>OS</option>
                <option>OSV</option>
                <option>Make</option>
                <option>Model</option>
                <option>Name</option>
                <option>Domain</option>
                <option>StoreURL</option>
                <option>Carrier</option>
                <option>MaxMind</option>
                <option>Exchange</option>
              </select>
              </form>
              </div>
              <div class="col-xs-4 pull-right"><a href="explorer.php?line=<?php echo intval($_GET['line'])+1; ?>"><b><button type="button" class="btn btn-info pull-right"><b style="font-size:1.2em;">Next ></b></button></b></a></div>
              
              <div class="col-xs-12">&nbsp;</div>

              <div class="table-responsive">
              <table class="table">
                <tbody>
<?php

if(isset($_POST['srep']))
{
  if($_POST['srep'] == "Bidfloor")
  {
    $dat = taParam3('"'.strtolower(rtrim($_POST['srep'])).'":');
    foreach($dat as $c => $i)
        echo "<tr><td style=\"font-size:1.2em;\"><b>" . $c . "</td></tr>";
  }
  else if($_POST['srep'] == "Carrier")
  {
    $d1 = taParam3('"maxmind":');
    $d2 = taParam3('"carrier":');
    $dat = array_merge($d1, $d2);
    foreach($dat as $c => $i)
        echo "<tr><td style=\"font-size:1.2em;\"><b>" . strtoupper(str_replace('"', '', $c)) . "</td></tr>";
  }
  else
  {
    $dat = taParam('"'.strtolower(rtrim($_POST['srep'])).'":"');
    foreach($dat as $c => $i)
        echo "<tr><td style=\"font-size:1.2em;\"><b>" . strtoupper($c) . "</td></tr>";
  }
}
else
{
  $index = intval($_GET['line']);
  $lines = file($bidlogpath);
  $line = $lines[$index];

  $line = str_replace(',', '</br>', $line);
  $line = str_replace('{', '', $line);
  $line = str_replace('}', '', $line);
  $line = str_replace('[', '', $line);
  $line = str_replace(']', '', $line);
  $line = str_replace('\/', '/', $line);

  echo "<tr><td style=\"font-size:1.2em;\">" . $line . "</td></tr>";
}
?>
                </tbody>
              </table>
              </div>

              <div class="col-xs-12">
                </br>
                <a href="http://rtb.voxdsp.com/logs/bidlogs/bidlog-<?php echo date("Y-m-d-G"); ?>.txt" target="_blank"><button type="button" class="btn btn-sm btn-success"><b style="font-size:1.2em;"><span class="fa fa-download"></span> Raw Bid Log</b></button></a>
                <?php
                  if($accountid == 1 || isset($_SESSION['switcher']))
                  {
                ?>
                <a href="http://rtb.voxdsp.com/logs/rsplogs/rsplog-<?php echo date("Y-m-d-G"); ?>.txt" target="_blank"><button type="button" class="btn btn-sm btn-success"><b style="font-size:1.2em;"><span class="fa fa-download"></span> Raw Response Log</b></button></a>
                <a href="explorer.php?rlog"><button type="button" class="btn btn-sm btn-danger"><b style="font-size:1.2em;">Clear Logs</b></button></a>
                <a href="explorer.php?rblog"><button type="button" class="btn btn-sm btn-danger"><b style="font-size:1.2em;">Clear Bleed Logs</b></button></a>
                
                <?php
                  }
                ?>
              </div>

          </div>
        </div>

      
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

<!-- Chart Page Plugins -->
<script src="vendor/plugins/c3charts/d3.min.js"></script>
<script src="vendor/plugins/c3charts/c3.min.js"></script>

<!-- JvectorMap Plugin + US Map (more maps in plugin/assets folder) -->
<script src="vendor/plugins/jvectormap/jquery.jvectormap.min.js"></script>
<script src="vendor/plugins/jvectormap/assets/jquery-jvectormap-us-lcc-en.js"></script> 

<!-- Bootstrap Tabdrop Plugin -->
<script src="vendor/plugins/tabdrop/bootstrap-tabdrop.js"></script>

<!-- Theme Javascript -->
<script src="assets/js/utility/utility.js"></script>
<script src="assets/js/main.js"></script>

<script type="text/javascript">
  jQuery(document).ready(function()
  {
    "use strict";    
    Core.init();

    $(".select2-single-prude").select2();

    $("#srep").change(function() 
    {
      $("#frep").submit();
    });
  });

  var Colors = [bgInfo];

  c3.generate({
      bindto: '#pie-chart2',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dm = top10(taParam2());
foreach($dm as $c => $i)
    echo "['".$c."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });
  
  c3.generate({
      bindto: '#pie-chart10',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"name":"'));
foreach($dat as $c => $i)
    echo "['".strtoupper($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

  c3.generate({
      bindto: '#pie-chart3',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$a1 = taParam('"domain":"');
$a2 = taParam('"storeurl":"');
$dat = array_merge($a1, $a2);
$dat = top10($dat);
foreach($dat as $c => $i)
{
    //$p = parse_url($c); 
    //$c = $p['host']; 
    $c = str_replace('', 'http://', $c);
    $c = str_replace('', 'https://', $c);
    $p = explode('/', $c, 2);
    $c = $p[0];
    echo "['".strtoupper($c)."', ".$i."],";
}

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

  c3.generate({
      bindto: '#pie-chart4',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php
$a1 = taParam('"carrier":"');
$a2 = taParam('"maxmind":"');
$a3 = array_merge($a1, $a2);
$dat = top10($a3);
foreach($dat as $c => $i)
    echo "['".strtoupper(str_replace("'", "\\'", $c))."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

  c3.generate({
      bindto: '#pie-chart5',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"country":"'));
foreach($dat as $c => $i)
    echo "['".strtoupper($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

  c3.generate({
      bindto: '#pie-chart9',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"os":"'));
foreach($dat as $c => $i)
    echo "['".ucfirst($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

    c3.generate({
      bindto: '#pie-chart11',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"make":"'));
foreach($dat as $c => $i)
    echo "['".ucfirst($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

    c3.generate({
      bindto: '#pie-chart12',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"model":"'));
foreach($dat as $c => $i)
    echo "['".ucfirst($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });


c3.generate({
      bindto: '#pie-chart14',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"osv":"'));
foreach($dat as $c => $i)
    echo "['".ucfirst($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });


c3.generate({
      bindto: '#pie-chart13',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"city":"'));
foreach($dat as $c => $i)
    echo "['".ucfirst(str_replace("'", "\\'", $c))."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });


c3.generate({
      bindto: '#pie-chart15',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"js":'));
foreach($dat as $c => $i)
    echo "['".ucfirst(str_replace("1,", "Yes", str_replace("0,", "No", $c)))."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

c3.generate({
      bindto: '#pie-chart16',
      color: {
        pattern: Colors,
      },
      data:
      {
          columns: [
<?php

$dat = top10(taParam('"language":"'));
foreach($dat as $c => $i)
    echo "['".strtoupper($c)."', ".$i."],";

?>
          ],
          type : 'pie',
          onclick: function (d, i) { console.log("onclick", d, i); },
          onmouseover: function (d, i) { console.log("onmouseover", d, i); },
          onmouseout: function (d, i) { console.log("onmouseout", d, i); }
      },
      legend:
      {
        show: false
      }
  });

</script>

</body>

</html>
