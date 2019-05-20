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

    //Secure active/deactive
    if(isset($_GET['cid']))
        secureAC($_GET['cid']);

    //Reset
    if(isset($_GET['del']))
    {
        $keys = $redis->keys($_GET['cid'].'-dbl-*');
        foreach($keys as $k)
            $redis->del($k);
        $keys = $redis->keys($_GET['cid'].'-dbc-*');
        foreach($keys as $k)
            $redis->del($k);
    }

    //Get date
    $date = date('Y-m-d H');
    if(isset($_POST['date']))
      $date = $_POST['date'];

    if($date == 'All Time')
      header('Location: bleedstats.php?cid='.$_GET['cid'].'&n='.$_GET['n']);
  
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


?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Delivery Report</title>
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
          <?php $_GET['report']=1; include "nav.php"; ?>

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

      <div class="panel panel-primary col-xs-12 col-sm-6">
      <div class="panel-heading">
        <span class="panel-title"><b style="font-size:1.4em;">Delivery Report - <?php echo $_GET['n'] . ' - ' . $date; ?>:00</b>
        
        <ul class="breadcrumb pull-right">
            <?php

  echo '<li><form action="bleedhistory.php?cid='.$_GET['cid'].'&n='.$_GET['n'].'" method="POST"><select name="date" style="width:160px;" onchange="this.form.submit();" class="input-sm form-control select2-single-prude"><option>All Time</option>';
  if(file_exists($logsdir.'bleedlogs/'.$_GET['cid']))
  {
    $files = scandir($logsdir.'bleedlogs/'.$_GET['cid']);
    foreach($files as $f)
    {
      if($f == '.' || $f == '..')
        continue;

      $name = str_replace('.txt', '', $f);

      $ch = '';
      if($name == $date)
        $ch = ' selected';
      
      echo '<option value="'.$name.'"'.$ch.'>'.$name.':00</option>';
    }
  }
  echo '</select></form></li>';

            ?>
        </ul>

        </span>
      </div>
      <div class="panel-body">
            
              <div id="tab2_1" class="tab-pane active">
                <div class="row">
                      
                      <table class="table table-striped table-hover" style="overflow:hidden;" id="datatable" cellspacing="0" width="100%">
                        <thead>
                          <tr>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Source Domain</b><br><i>Hostname of the website that provided the impressions.</i>" data-html="true">Source Domain</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Impressions Paid For</b><br><i>The quantity of impressions bidded on and won.</i>" data-html="true">Wins</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Impressions Counted</b><br><i>The amount of impressions won that where detected by our tracking pixel to have actually viewed the advertisement.</i>" data-html="true">Arrived</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Percentage of Arrival</b><br><i>The percentage of impressions that arrived after bleeding.</i>" data-html="true">Percent</th>
                          </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>
    <?php

        $tw = 0;
        $tb = 0;
        $os = 0;
        $logs = file_get_contents($logsdir.'bleedlogs/'.$_GET['cid'].'/'.$date.'.txt');
        $lines = explode(PHP_EOL, $logs);
        foreach($lines as $l)
        {
            if($l == '' || $l == 'Domain | Impressins Won | Impressions Arrived | Percent Arrived')
                continue;
            
            $p = explode(',', $l);

            $v0 = $p[1];
            $tw += $v0;
            $v1 = $p[2];

            echo '<tr>';
            echo '<td class="min">'.substr($p[0], 0, 32).'</td>';
            echo '<td class="min">'.number_format($v0).'</td>';
            echo '<td class="min">'.number_format($v1).'</td>';
            echo '<td class="min">'.number_format(divide(100, $v0)*$v1, 2).'%</td>';
            echo '</tr>';
            
            if($v0-$v1 > 0)
                $tb += abs($v0-$v1);
            else
                $os += abs($v0-$v1);

        }
		
	?>
                        </tbody>
                      </table>

                      <br><center><font size="3m">
                      <b>Total Wins: </b><?php echo number_format($tw); ?><br>
                      <b>Total Bleed: </b><?php echo number_format($tb); ?><br>
                      <b>Total Bleed Percent: </b><?php echo number_format(divide(100, $tw)*$tb); ?>%<br>
                      <b>Over Sent: </b><?php echo number_format($os); ?><br></font></center>

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
            "iDisplayLength": 50,
            "aLengthMenu": [
                [5, 10, 25, 50, 100, 150, 250, 500, -1],
                [5, 10, 25, 50, 100, 150, 250, 500, "All"]
            ],
            "sDom": '<"dt-panelmenu clearfix"lfr>t<"dt-panelfooter clearfix"ip>',
            "oTableTools": {
                "sSwfPath": "vendor/plugins/datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf"
            },
            "order": [[1, "desc"]],
            scrollX: true,
            stateSave: true

        });
      });

  </script>

</body>

</html>
