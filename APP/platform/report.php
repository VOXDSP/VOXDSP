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
  $dir = '/usr/share/nginx/rtb.voxdsp.com/html/logs/winlogs/'.$accountid.'/'.$_GET['cid'].'/';
  //$ar = scandir($dir);
  //$ar = array_slice($ar, 2);
  //$bidlog = '';
  //foreach($ar as $a)
  $bidlog = file_get_contents($dir.date('Y-m-d-G').'.txt');

  //$date2 = date("Y-m-d");
  //$bidlog = file_get_contents($dir.$date2.'-'.date("G").'.txt');

  //Decode these bid requests loaded into memory and push them onto a biddata array.
  $biddata = array();
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

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Reporting</title>
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
        <span class="panel-title"><b style="font-size:1.4em;">Impression Reporting - <?php echo $_GET['n']; ?> - Last Hour</b></span>
      </div>
      <div class="panel-body">
            
              <div id="tab2_1" class="tab-pane active">
                <div class="row">
                      
                      <table class="table table-striped table-hover" style="overflow:hidden;" id="datatable" cellspacing="0" width="100%">
                        <thead>
                          <tr>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Unix Timestamp</b><br><i>Date & Time of Impression.</i>" data-html="true">Timestamp</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Exchange Name</b><br><i>Exchange that supplied the impression.</i>" data-html="true">Exchange</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Source Domain</b><br><i>Website the banner will be displayed on.</i>" data-html="true">Domain</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Country</b><br><i>Country origin of impression.</i>" data-html="true">GEO</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>City</b><br><i>City origin of impression.</i>" data-html="true">City</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Operating System</b><br><i>Operating System of impression.</i>" data-html="true">OS</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>OS Version</b><br><i>Operating System Version of impression.</i>" data-html="true">OSv</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Carrier</b><br><i>Mobile Network Name of Impression.</i>" data-html="true">Carrier</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Carrier</b><br><i>Mobile Network Type of Impression.</i>" data-html="true">Type</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>IP Address</b><br><i>IPv4 Address of impression.</i>" data-html="true">IP Address</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Unique User-ID</b><br><i>Unique identifier of impression.</i>" data-html="true">UID</th>
                            <th data-toggle="tooltip" data-placement="bottom" title="<b>Unique Publisher-ID</b><br><i>Unique identifier of the publisher that provided the impression.</i>" data-html="true">Publisher ID</th>
                          </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>
    <?php

      foreach($biddata as $b)
      {
        $domain = '';
				if(isset($b->{'site'}->{'domain'}))
					$domain = $b->{'site'}->{'domain'};
				else if(isset($b->{'app'}->{'domain'}))
					$domain= $b->{'app'}->{'domain'};

        $osv = '';
        if(!isset($b->{'device'}->{'osv'}))
        {
          $sosv = $b->{'device'}->{'ua'};
          $sosv = str_replace(')', ' ', $sosv);
          $sosv = str_replace(';', ' ', $sosv);
          $p = stristr($sosv, 'OS ');
          if($p)
          {
            $p = substr($p, 3);
            $osv = explode(' ', $p, 2)[0];
          }
          else
          {
            $p = stristr($sosv, 'Android ');
            if($p)
            {
              $p = substr($p, 8);
              $sosv = str_replace(')', ' ', $sosv);
              $sosv = str_replace(';', ' ', $sosv);
              $osv = explode(' ', $p, 2)[0];
            }
          }
        }
        else
        {
          $osv = $b->{'device'}->{'osv'};
        }
        if($osv != '')
          $osv = rtrim(str_replace('_', '.', $osv));

        $city = '';
        if(isset($b->{'device'}->{'geo'}->{'city'}))
          $city = $b->{'device'}->{'geo'}->{'city'};

        $carrier = '';
        if(isset($b->{'maxmind'}))
          $carrier = $b->{'maxmind'};

        $contype = '';
        if(isset($b->{'contype'}))
          $contype = $b->{'contype'};

        $uid = '';
        if(isset($b->{'user'}->{'id'}))
          $uid = $b->{'user'}->{'id'};

        $os = '';
        if(isset($b->{'device'}->{'os'}))
          $os = $b->{'device'}->{'os'};

        $ua = '';
        if(isset($b->{'device'}->{'ua'}))
          $ua = $b->{'device'}->{'ua'};

        $ip = '';
        if(isset($b->{'device'}->{'ip'}))
          $ip = '<b style="cursor:pointer;" data-toggle="tooltip" data-placement="bottom" title="<b>'.$ua.'</b>" data-html="true">'.$b->{'device'}->{'ip'}.'</b>';
        else if(isset($b->{'device'}->{'ipv6'}))
          $ip = '<b style="cursor:pointer;" data-toggle="tooltip" data-placement="bottom" title="<b>'.$b->{'device'}->{'ipv6'}.'<br><br>'.$ua.'</b>" data-html="true">IPv6</b>';

        $pubid = '';
        if(isset($b->{'site'}->{'id'}))
          $pubid = $b->{'site'}->{'id'};
        else if(isset($b->{'app'}->{'id'}))
          $pubid = $b->{'app'}->{'id'};
          
        echo '<tr>';
        echo '<td class="min"><b>'.$b->{'timestamp'}.'</b></td>';
        echo '<td class="min"><b>'.ucfirst($b->{'exchange'}).'</b></td>';
        echo '<td class="min">'.ucfirst($domain).'</td>';
        echo '<td class="min">'.strtoupper($b->{'device'}->{'geo'}->{'country'}).'</td>';
        echo '<td class="min">'.ucwords($city).'</td>';
        echo '<td class="min">'.ucwords($os).'</td>';
        echo '<td class="min">'.$osv.'</td>';
        echo '<td class="min">'.ucwords($carrier).'</td>';
        echo '<td class="min">'.ucwords($contype).'</td>';
        echo '<td class="min">'.$ip.'</td>';
        echo '<td class="min">'.$uid.'</td>';
        echo '<td class="min">'.$pubid.'</td>';
        echo '</tr>';
      }
					

		?>
                        </tbody>
                      </table>

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
            "iDisplayLength": 25,
            "aLengthMenu": [
                [5, 10, 25, 50, 100, 150, 250, 500, -1],
                [5, 10, 25, 50, 100, 150, 250, 500, "All"]
            ],
            "sDom": '<"dt-panelmenu clearfix"lfr>t<"dt-panelfooter clearfix"ip>',
            "oTableTools": {
                "sSwfPath": "vendor/plugins/datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf"
            },
            "order": [[0, "desc"]],
            scrollX: true,
            stateSave: true,
            "bFooter": true,
            "footerCallback": function ( row, data, start, end, display )
            {
                var api = this.api(), data;

                // converting to interger to find total
                var intVal = function ( i ) {
                return typeof i === 'string' ?
                i.replace(/[\$,]/g, '')*1 :
                typeof i === 'number' ?
                i : 0;
                };

                // computing column Total of the complete result 
                var ThreeTotal = api
                .column( 3, {page: 'current'} )
                .data()
                .reduce( function (a, b) {
                return intVal(a) + intVal(b);
                }, 0 );


                // Update footer by showing the total with the reference of the column index 
                $( api.column( 0 ).footer() ).html('<b>TOTALS:</b>');
                $( api.column( 3 ).footer() ).html(number_format(ThreeTotal, 6) + ' <b>USD</b>');
            }

        });
      });

  </script>

</body>

</html>
