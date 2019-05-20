<?php

  //Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: account.php');
    exit;
  }
  $accountid = $_SESSION['auth'];

  if($accountid != 1 && !isset($_SESSION['switcher']))
  {
    header('Location: campaigns.php');
    exit;
  }
  $accountid = 1;
  $_SESSION['auth'] = 1;
  $_SESSION['switcher'] = 1;

  //Lets get started
  require_once "config.php";
  $exchanges = getExchanges();

  //Calculate campaign completion
  $tcost = 0;
  $tcostlimit = 0;
  $r = $mysql->query("SELECT cost,costlimit FROM `campaigns`");
  if($r)
  {
    $ro = $r->fetch_assoc();
    $tcost = $ro['cost'];
    $tcostlimit = $ro['costlimit'] * 1000;
  }
  $rt = divide(100, $tcostlimit) * $tcost;
  if($rt >= 100)
    $rt = 100;
  if($rt <= 0)
    $rt = 0;
  $rt = number_format($rt, 2);
  if($rt == "nan"){$rt=0;}

  //Switch account without password
  if(isset($_POST['suid']))
  {
    $_SESSION['switcher'] = 1;
    $_SESSION['auth'] = $_POST['suid'];

    if(stripos($_SERVER['HTTP_REFERER'], 'am.php') !== FALSE)
      $_SERVER['HTTP_REFERER'] = 'campaigns.php';

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
  }

  //Enable redirects on a campaign
  if(isset($_POST['directurl']))
  {
    $mysql->query("UPDATE campaigns SET directurl='".$_POST['directurl']."' WHERE id=" . $mysql->real_escape_string($_GET['cid']));
    header('Location: campaigns.php');
    exit;
  }

  //Disbale redirects on campaign
  if(isset($_GET['killdirect']))
  {
    $mysql->query("UPDATE campaigns SET directurl='' WHERE id=" . $mysql->real_escape_string($_GET['killdirect']));
    header('Location: campaigns.php');
    exit;
  }

  //Create new account
  if(isset($_POST['password']))
  {
    $user = strtolower($_POST['email']);
    $pass = $_POST['password'];
    $mysql->query("INSERT INTO `account` (`ldat`, `spent`, `credit`, `info`) VALUES('".$user.";".md5($user.$pass)."',0,0,'');");
    echo "<h2>Account Created</h2></br><b>Username:</b> " . $user . "</br><b>Password:</b> " . $pass . "</br></br><a href='am.php'><b>Click here to Return</b></a>";
    exit;
  }

  //Ban account
  if(isset($_POST['ban']))
  {
    $user = $_POST['email'];

    $ra = $mysql->query("SELECT * FROM `account` where INSTR(ldat, '".$user."')>0;");
    if($ra)
    {
        $mysql->query("UPDATE account SET ldat='".$user.";BANNED' WHERE id=" . $mysql->real_escape_string($rao['id']));

        echo "<h2>Account Banned</h2></br><b>Username:</b> " . $user . "</br></br><a href='am.php'><b>Click here to Return</b></a>";
        exit;
    }

    echo '<h1>User could not be found.</h1>';
    exit;
  }

  //Increment Account Credit
  if(isset($_POST['credit']))
  {
    $user = $_POST['email'];
    $amount = $_POST['credit'];

    $ra = $mysql->query("SELECT * FROM `account` where INSTR(ldat, '".$user."')>0;");
    if($ra)
    {
        $rao = $ra->fetch_assoc();

        //log it in the ledger
        file_put_contents('ledgers/' . $rao['id'] . '_ledger.log', date('Y-m-d H:i:s').",Credit Transfer,".$amount."\n", LOCK_EX | FILE_APPEND);

        //Increment account balance
        $mysql->query("UPDATE account SET credit=credit+" . $mysql->real_escape_string($amount) . " WHERE id=" . $mysql->real_escape_string($rao['id']));

        echo "<h2>Account Credit Incremented</h2></br><b>Username:</b> " . $user . "</br><b>Old Credit:</b> " . number_format($rao['credit']) . "</br><b>New Credit:</b> " . number_format(intval($rao['credit']+$amount)) . "</br></br><a href='am.php'><b>Click here to Return</b></a>";
        exit;
    }

    echo '<h1>User could not be found.</h1>';
    exit;
  }

  //Create new endpoint
  if(isset($_POST['endpoint']))
  {
    $_POST['endpoint'] = strtolower($_POST['endpoint']);
    $fn = $endpointsdir.$_POST['endpoint'].'.php';
    if(file_exists($fn))
    {
        unlink($fn);
        echo "<b>End Point deleted at:</b></br>http://" . $backhn . '/' . $_POST['endpoint'] . '.php</br></br><a href="am.php"><b>Click here to Return</b></a>';
        exit;
    }
    else
    {
        file_put_contents($fn, '<?php require_once "core/main.php"; ?>');
        echo "<b>End Point created at:</b></br>http://" . $backhn . '/' . $_POST['endpoint'] . '.php</br></br><a href="am.php"><b>Click here to Return</b></a>';
        exit;
    }
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Admin</title>
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
          <li class="sidebar-label pt20">Administrator Menu</li>
          <?php include "nav.php"; ?>

          <li class="sidebar-label pt20">User Account Swicher</li>
<?php

if(isset($_SESSION['switcher']))
{
  echo '<form action="am.php" method="POST">';
  echo '<li><center><select name="suid" style="width:80%;" onchange="this.form.submit();" class="input-sm form-control select2-single-prude">';
  $ra = $mysql->query("SELECT * FROM `account`;");
  if($ra)
  {
    while($rao = $ra->fetch_assoc())
    {
      echo '<option value="'.$rao['id'].'">'.explode(';', $rao['ldat'])[0].'</option>';
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
      <section id="content" class="table-layout animated fadeIn">
        <div class="row">


<?php
  $m = FALSE;//file_get_contents('https://voxdsp.com/req.txt');
  if($m != FALSE && $m != '')
  {
?>
        <div class="col-xs-12">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> Feedback on VOXDSP.com </span>
                    </div>

                    <?php echo nl2br($m); ?>

                </div>
            </div>        
        </div>
        </div>
<?php
  }
?>


        <div class="col-xs-12 col-sm-6">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> Administrator Functions </span>
                    </div>

                    <form method="post" action="am.php">

                        <div class="section row">

                            <div class="col-xs-12"><center><h2>Create a new account</h2></center></div>

                            <div class="col-xs-12 col-md-5">
                                <input name="email" type="email" class="gui-input" placeholder="Email Address..." required>
                            </div>

                            <div class="col-xs-12 col-md-5">
                                <input type="text" name="password" class="gui-input" placeholder="Password..." required>
                            </div>

                            <div class="col-xs-12 col-md-2">
                                <button type="submit" class="button btn-primary"> <b>Go!</b> </button>
                            </div>

                        </div>

                    </form>

                    <form method="post" action="am.php">

                        <div class="section row">

                            <div class="col-xs-12"><center><h2>Add Credit to Account</h2></center></div>

                            <div class="col-xs-12 col-md-5">
                                <input name="email" type="email" class="form-control" placeholder="Email Address..." required>
                            </div>

                            <div class="col-xs-12 col-md-5">
                            <div class="form-group">
                            <div class="input-group" style="width: 100%">
                                <input id="spinner" name="credit" class="form-control ui-spinner-input" placeholder="Credit Quantity..." required>
                            </div>
                            </div>
                            </div>

                            <div class="col-xs-12 col-md-2">
                                <button type="submit" class="button btn-primary"> <b>Go!</b> </button>
                            </div>

                        </div>

                    </form>

                    <form method="post" action="am.php">

                        <div class="section row">

                            <div class="col-xs-12"><center><h2>Add/Delete Endpoint</h2></center></div>

                            <div class="col-xs-12 col-md-10">
                                <input type="text" name="endpoint" class="gui-input" placeholder="Endpoint Name..." required>
                            </div>

                            <div class="col-xs-12 col-md-2">
                                <button type="submit" class="button btn-primary"> <b>Go!</b> </button>
                            </div>

                            <?php
                              echo '<center>Endpoints: ';
                              $exc = '';
                              foreach($exchanges as $e)
                                $exc .= '<b>' . $e . '</b>, ';
                              echo rtrim($exc, ', ').'</center>';
                            ?>

                        </div>

                    </form>

                    <form method="post" action="am.php?ban=1">

                        <div class="section row">

                            <div class="col-xs-12"><center><h2>Ban Account</h2></center></div>

                            <div class="col-xs-12 col-md-10">
                              <input name="email" type="email" class="form-control" placeholder="Email Address..." required>
                            </div>

                            <div class="col-xs-12 col-md-2">
                                <button type="submit" class="button btn-primary"> <b>Go!</b> </button>
                            </div>

                        </div>

                    </form>
    
                </div>
            </div>
        </div>
        </div>
    
        <div class="col-xs-12 col-sm-6">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> Total Bidder Spend Per Exchange </span>
                    </div>
    
    
                      <table class="table table-striped table-hover" id="datatable2" cellspacing="0" width="100%">
                            <thead>
                              <tr>
                                <th>Date</th>
                                <th>Account ID / Exchange</th>
                                <th class="sum">Wins</th>
                                <th class="sum">Cost <b>USD</b></th>
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

$ra = $mysql->query("SELECT * FROM `account`;");
if($ra)
{
    while($rao = $ra->fetch_assoc())
    {
        foreach($exchanges as $e)
        {
            if(!file_exists($logsdir.$rao['id'].'-'.$e.'.txt'))
                continue;

            $fd = file_get_contents($logsdir.$rao['id'].'-'.$e.'.txt');
            $log = explode(PHP_EOL, $fd);
            foreach($log as $l)
            {
                if($l == "")
                continue;
                echo "<tr>";
                $p = explode(',', $l);
                echo "<td>" . explode(' ', $p[0], 2)[0] . "</td><td data-html='true' data-toggle='tooltip' data-placement='bottom' title='<b>".explode(';', $rao['ldat'])[0]." (".$rao['id'].")</b>'>". $rao['id'] . ' - ' . ucfirst($e)."</td><td>" . number_format($p[1]) . "</td><td>" . number_format($p[2], 2) . "</td>";
                echo "</tr>";
            }
        }
    }
}

?>
                            </tbody>
                          </table>
    
                </div>
            </div>        
        </div>
        </div>

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

      $("#spinner").spinner({min: 0, step: 1, start: 0});
    });

    $('#datatable2').dataTable({
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
      "order": [[0, "desc"]],
      stateSave: false,
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
          .column( 2, {page: 'current'} )
          .data()
          .reduce( function (a, b) {
          return intVal(a) + intVal(b);
          }, 0 );

          var Total4 = api
          .column( 3, {page: 'current'} )
          .data()
          .reduce( function (a, b) {
          return intVal(a) + intVal(b);
          }, 0 );


          // Update footer by showing the total with the reference of the column index 
          $( api.column( 0 ).footer() ).html('<b>TOTALS:</b>');
          $( api.column( 2 ).footer() ).html(number_format(ThreeTotal));
          $( api.column( 3 ).footer() ).html(number_format(Total4, 2) + ' <b>USD</b>');
      }
    });
  </script>

</body>

</html>
