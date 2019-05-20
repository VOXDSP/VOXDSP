<?php

    //Check for logged in
    session_start();
    if(!isset($_SESSION['auth']))
    {
        header('Location: account.php');
        exit;
    }
    $accountid = $_SESSION['auth'];

    //Lets get started
    require_once "config.php";
    $exchanges = getExchanges();

    //Secure
    if(isset($_GET['cid']))
        secureAC($_GET['cid']);

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


    //Alph-3 to String
    function a3ts($in)
    {
        GLOBAL $rootdir;
        $in = strtoupper($in);
        $gc = explode(PHP_EOL, file_get_contents($rootdir."countrycodes.txt"));
        foreach($gc as $c)
        {
            if($c[0] == $in[0] && $c[1] == $in[1] && $c[2] == $in[2])
                return substr($c, 4);
        }
        return $in;
    }


    //Load as many files for today as we can !
    $biddata = array();
    $bidlog = file_get_contents($logsdir.'clicklogs/'.$_GET['cid'].'.txt');

    //Decode these bid requests loaded into memory and push them onto a biddata array.
    $lines = explode(PHP_EOL, $bidlog);
    foreach($lines as $l)
    {
        if($l == '')
            continue;

        //Only add requests from specified exchange
        $dec = json_decode(rtrim($l));
        if($dec)
            array_push($biddata, $dec);
        
    }

    //Size of biddata
    $bdlen = sizeof($biddata);
    $traffpercent = (100/(sizeof($lines)-1)) * $bdlen;


    //Top 5 Carriers
    $carriers = array();
    foreach($biddata as $d)
    {
        $geoisp = 'Unknown';

        if(isset($d->{'device'}->{'carrier'}))
        $geoisp = $d->{'device'}->{'carrier'};
        if(isset($d->{'maxmind'}))
        $geoisp = $d->{'maxmind'};
        

        if($geoisp != '')
            $carriers[$geoisp]++;
    }
    arsort($carriers);

    //Top 10 Countries
    $countries = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'device'}->{'geo'}->{'country'}))
            $countries[$d->{'device'}->{'geo'}->{'country'}]++;
    }
    arsort($countries);

    //Top 10 Cities
    $cities = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'device'}->{'geo'}->{'city'}))
            $cities[strtolower($d->{'device'}->{'geo'}->{'city'})]++;
    }
    arsort($cities);

    //Top 3 Dimensions
    $dimensions = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'imp'}[0]->{'banner'}->{'w'}))
        {
            $dim = $d->{'imp'}[0]->{'banner'}->{'w'} . 'x' . $d->{'imp'}[0]->{'banner'}->{'h'};
            $dimensions[$dim]++;
        }
    }
    arsort($dimensions);

    //Top 3 Operating Systems
    $oss = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'device'}->{'os'}))
            $oss[strtolower($d->{'device'}->{'os'})]++;
    }
    arsort($oss);

    //Top Domains
    $doms = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'app'}->{'domain'}) && $d->{'app'}->{'domain'} != '')
            $doms[strtolower($d->{'app'}->{'domain'})]++;
        else if(isset($d->{'site'}->{'domain'}) && $d->{'site'}->{'domain'} != '')
            $doms[strtolower($d->{'site'}->{'domain'})]++;
        else if(isset($d->{'app'}->{'storeurl'}) && $d->{'app'}->{'storeurl'} != '')
            $doms[strtolower($d->{'app'}->{'storeurl'})]++;
    }
    arsort($doms);

    //Top Names
    $nams = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'app'}->{'name'}) && $d->{'app'}->{'name'} != '')
            $nams[strtolower($d->{'app'}->{'name'})]++;
        else if(isset($d->{'site'}->{'name'}) && $d->{'site'}->{'name'} != '')
            $nams[strtolower($d->{'app'}->{'name'})]++;
    }
    arsort($nams);

    //Top 5 Browsers
    $uas = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'device'}->{'ua'}))
        {
            $ua = strtolower($d->{'device'}->{'ua'});
            $browser = '';

            if(strpos($ua, 'macintosh') !== false)
                $browser = 'Safari';
            else if(strpos($ua, 'chrome') !== false)
                $browser = 'Chrome';
            else if(strpos($ua, 'ipad') !== false)
                $browser = 'iPad Browser';
            else if(strpos($ua, 'iphone') !== false)
                $browser = 'iPhone Browser';
            else if(strpos($ua, 'firefox') !== false)
                $browser = 'Firefox';
            else if(strpos($ua, 'opr/') !== false)
                $browser = 'Opera';
            else if(strpos($ua, 'edge') !== false)
                $browser = 'Edge';
            else if(strpos($ua, 'android') !== false)
                $browser = 'Android Browser';
            else if(strpos($ua, 'msie') !== false || strpos($ua, 'windows') !== false)
                $browser = 'Internet Explorer';

            if($browser != '')
                $uas[$browser]++;
        }
    }
    arsort($uas);

    //Top Exchanges
    $excs = array();
    foreach($biddata as $d)
    {
        if(isset($d->{'exchange'}) && $d->{'exchange'} != '')
            $excs[strtolower($d->{'exchange'})]++;
    }
    arsort($excs);


?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Click Reporting</title>
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

if(isset($_SESSION['switcher']))
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


        



        <div class="col-xs-12">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> Click Statistics for <?php echo $_GET['n']; ?></span>
                    </div>

    <table class="table table-bordered table-striped table-hover table-responsive">
    <tr><td>
    <font size='3m'><b>Clicks:</b> <?php echo number_format(count($biddata)); ?></font>
    </td></tr>
    <tr><td>
    <font size='3m'><b>Dimensions:</b> <?php
        $r = '';
        foreach($dimensions as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo rtrim($r, ', ');
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Countries:</b> <?php
        $r = '';
        foreach($countries as $c => $i)
            $r .= a3ts($c) . ' (<b>'.number_format($i).'</b>), ';
        echo rtrim($r, ', ');
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Cities:</b> <?php
        $r = '';
        foreach($cities as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo ucwords(rtrim($r, ', '));
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Carriers:</b> <?php
        $r = '';
        foreach($carriers as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo ucwords(rtrim($r, ', '));
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Pub Names:</b> <?php
        $r = '';
        foreach($nams as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo ucwords(rtrim($r, ', '));
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Pub Domains:</b> <?php
        $r = '';
        foreach($doms as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo ucwords(rtrim($r, ', '));
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Platforms:</b> <?php
        $r = '';
        foreach($oss as $c => $i)
        {
            $c = ucwords($c);
            if($c == 'Ios')
                $c = 'iOS';
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        }
        echo rtrim($r, ', ');
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Browsers:</b> <?php
        $r = '';
        foreach($uas as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo rtrim($r, ', ');
    ?>
    </font></td></tr>
    <tr><td>
    <font size='3m'><b>Exchanges:</b> <?php
        $r = '';
        foreach($excs as $c => $i)
            $r .= $c . ' (<b>'.number_format($i).'</b>), ';
        echo ucwords(rtrim($r, ', '));
    ?>
    </font></td></tr>
    </table>
    </div>

    
                </div>
            </div>
        </div>
        </div>
  
        </section>
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

  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>

  <!-- Zopim Javascript -->
  <script type="text/javascript">
  window.$zopim||(function(d,s){var z=$zopim=function(c){
  z._.push(c)},$=z.s=
  d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
  _.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute('charset','utf-8');
  $.src='https://v2.zopim.com/?5JRZM2WA4ytQPspxSBk5FieMDW2qjS3w';z.t=+new Date;$.
  type='text/javascript';e.parentNode.insertBefore($,e)})(document,'script');
  </script>

  <script>
    jQuery(document).ready(function()
    {
        "use strict";
        Core.init();
    });

    function copyTextToClipboard(text)
    {
        var textArea = document.createElement("textarea");

        // Place in top-left corner of screen regardless of scroll position.
        textArea.style.position = 'fixed';
        textArea.style.top = 0;
        textArea.style.left = 0;

        // Ensure it has a small width and height. Setting to 1px / 1em
        // doesn't work as this gives a negative w/h on some browsers.
        textArea.style.width = '2em';
        textArea.style.height = '2em';

        // We don't need padding, reducing the size if it does flash render.
        textArea.style.padding = 0;

        // Clean up any borders.
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';

        // Avoid flash of white box if rendered for any reason.
        textArea.style.background = 'transparent';

        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Copied to Clipboard: '+text);
    }
  </script>

</body>

</html>
