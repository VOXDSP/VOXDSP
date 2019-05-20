<?php

  //Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: index.php');
    exit;
  }
  $accountid = $_SESSION['auth'];

  //Top X items of an array
  function top($array, $num)
  {
      arsort($array);
      $array = array_slice($array, 0, $num);
      return $array;
  }

  //Load MaxMind City DB
  require 'geoip2.phar';
  $reader = new GeoIp2\Database\Reader("/usr/share/GeoIP/GeoIP2-City.mmdb");

  //Lets get started
  require_once "config.php";
  $exchanges = getExchanges();

  $gdat = strtolower(file_get_contents("geocodes.txt"));
  $geo = explode(PHP_EOL, $gdat);

  $credit = 0;
  $accountname = '';
  $r = $mysql->query("SELECT spent,credit,ldat FROM account WHERE id=".$accountid);
  if($r)
  {
    $ro = $r->fetch_assoc();
    $credit = ($ro['credit']*1000)-$ro['spent'];
    $accountname = explode(';', $ro['ldat'])[0];
  }

  //If nan set 0
  function nan($in)
  {
    if(is_nan(floatval($in)))
      return 0;
    if($in <= 0.05)
      return 0;
    return $in;
  }

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

  //Load as many files for today as we can !
  $biddata = array();
  $bidlog = "";
  $date2 = date("Y-m-d");

  $si = date("G"); //-4 = just 4 hours
  if($si < 0)
    $si = 0;
  for($i = $si; $i < 24; $i++)
    if(file_exists($logsdir.'bidlogs/bidlog-'.$date2.'-'.$i.'.txt'))
      $bidlog .= file_get_contents($logsdir.'bidlogs/bidlog-'.$date2.'-'.$i.'.txt');
  
  $lines = explode(PHP_EOL, $bidlog);
  $lines = array_slice($lines, 0, 600);
  foreach($lines as $l)
  {
    if($l == '')
      continue;
    array_push($biddata, json_decode($l));
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Dashboard</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX - Demand Side Platform (DSP)">
  <meta name="author" content="VOX">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font CSS (Via CDN) -->
  <link rel='stylesheet' type='text/css' href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>

  <!-- Datatables CSS -->
  <link rel="stylesheet" type="text/css" href="vendor/plugins/datatables/media/css/dataTables.bootstrap.css">

  <!-- Plugin CSS -->
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

  <!-- Hacked Styles
  <style>
    .panel
    {
      background-color: #eee !important;
    }
  </style> -->

  <style>
  .tooltip
  {
    font-size: 16px !important;
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
          <?php $_GET['dashboard']=1; include "nav.php"; ?>

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
      <section id="content" class="table-layout animated fadeIn" style="background-color:#fff;">

        <div class="row">
        
          <div class="panel panel-primary col-xs-12">
            <div class="panel-heading">
              <span class="panel-title"><b style="font-size:1.4em;"><span class="badge badge-pill badge-info" data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>This gives you an overview of the countries traffic is currently available and what sites are sending the traffic from those countries.</b>"><i class="fa fa-info"></i></span> Bid Request World Map <badge class="pull-right"><?php echo date('H:i:s')." ".date_default_timezone_get(); ?></badge></b></span>
            </div>
            <div class="panel-body">
              
            <div id="map1" style="width:100%; height:720px;"></div>            

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

  <!-- Page Plugins -->
  <script src="vendor/plugins/c3charts/d3.min.js"></script>
  <script src="vendor/plugins/c3charts/c3.min.js"></script>

  <!-- JvectorMap Plugin + US Map (more maps in plugin/assets folder) -->
  <script src="vendor/plugins/jvectormap/jquery.jvectormap.min.js"></script>
  <script src="vendor/plugins/jvectormap/assets/jquery-jvectormap-us-lcc-en.js"></script>

  <!-- Bootstrap Tabdrop Plugin -->
  <script src="vendor/plugins/tabdrop/bootstrap-tabdrop.js"></script>

  <!-- JvectorMap Dependency (more maps can be found in plugins asset folder) -->
  <script src="vendor/plugins/jvectormap/assets/jquery-jvectormap-world-mill-en.js"></script>

  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>

  <script type="text/javascript">
  jQuery(document).ready(function()
  {
    "use strict";    
    Core.init();

    $(".select2-single-prude").select2();
  });

  $(function(){
    $('#map1').vectorMap({
      map: 'world_mill_en',
      scaleColors: ['#3498db', '#3498db'],
      normalizeFunction: 'polynomial',
      hoverOpacity: 0.7,
      hoverColor: false,
      markerStyle: {
        initial: {
          fill: '#e07699',
          stroke: '#000'
        }
      },
      backgroundColor: '#3498db',
      markers: [
<?php

foreach($biddata as $b)
{
  $lat = 0;
  $lon = 0;
  $city = 'Unknown';
  if(isset($b->{'device'}->{'geo'}->{'city'}))
    $city = $b->{'device'}->{'geo'}->{'city'};

  if(!isset($b->{'device'}->{'geo'}->{'lat'}) || !isset($b->{'device'}->{'geo'}->{'lon'}) || !isset($b->{'device'}->{'geo'}->{'city'}))
  {
    try
    {
      $r = $reader->city($b->{'device'}->{'ip'});
      $lat = $r->location->latitude;
      $lon = $r->location->longitude; 
      $city = $r->city->name;
    }
    catch(Exception $e){}
  }
  else
  {
    $lat = $b->{'device'}->{'geo'}->{'lat'};
    $lon = $b->{'device'}->{'geo'}->{'lon'};
  }

  $domain = '';
  if(isset($b->{'site'}->{'domain'}))
    $domain = $b->{'site'}->{'domain'};
  else if(isset($b->{'app'}->{'domain'}))
    $domain = $b->{'app'}->{'domain'};
  else if(isset($b->{'app'}->{'storeurl'}))
    $domain = $b->{'app'}->{'storeurl'};

  //$p = parse_url($domain);
  //$domain = $p['host']; 

  $c = $domain;
  $c = str_replace('', 'http://', $c);
  $c = str_replace('', 'https://', $c);
  $p = explode('/', $c, 2);
  $domain = $p[0];
  
  $carrier = '';
  if(!isset($b->{'device'}->{'carrier'}) || $b->{'device'}->{'carrier'} == '' || ctype_alpha($b->{'device'}->{'carrier'}[0]) == FALSE || stripos($b->{'device'}->{'carrier'}, "unknown") !== false)
    $carrier = geoip_isp_by_name($b->{'device'}->{'ip'});
  else
    $carrier = ucwords($b->{'device'}->{'carrier'});

  if($city == '')
    $city = 'Unknown';

  if($carrier == '')
    $carrier = 'Unknown';

  if($domain == '')
    $domain = 'Unknown';

  $fstr = str_replace("'", "\\'", $domain . ', ' . str_replace("'", '', $carrier) . ', ' . ucfirst(str_replace("'", '', $city)) );

  if($lat != 0 || $lon != 0)
    echo "{latLng: [".$lat.", ".$lon."], name: '".$fstr."'},";
}

?>
      ]
    });
  });

  </script>

</body>

</html>
