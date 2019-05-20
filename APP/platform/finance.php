<?php

  //Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: account.php');
    exit;
  }
  $accountid = $_SESSION['auth'];

  //If nan set 0
  function nan($in)
  {
    if(is_nan(floatval($in)))
      return 0;
    if($in <= 0.05)
      return 0;
    return $in;
  }

  //Lets get started
  require_once "config.php";
  $exchanges = getExchanges();

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

  //Get account credit
  $credit = 0;
  $accountname = '';
  $r = $mysql->query("SELECT spent,credit,ldat FROM account WHERE id=".$accountid);
  if($r)
  {
    $ro = $r->fetch_assoc();
    $credit = ($ro['credit']*1000)-$ro['spent'];
    $accountname = explode(';', $ro['ldat'])[0];
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Finance</title>
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
          <?php $_GET['finance']=1; include "nav.php"; ?>

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


        



        <div class="col-xs-12 col-sm-6">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> <b style="color:#000;">Account Finance</b> </span>
                    </div>

                      <div class="section row">

                      <div class="col-xs-12">
                        <center><h2 style="font-family: 'Lato', sans-serif;"><b>Current Balance: </b><font color="#5cb85c"><?php echo fprice($credit); ?></font> <b>USD</b></h2></center>
                      </div>

                      <div class="col-xs-12">&nbsp;</div>

              <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
								<input type="hidden" name="cmd" value="_xclick">
								<input type="hidden" name="business" value="<?php echo $paypalemail; ?>">
								<input type="hidden" name="lc" value="GB">
								<input type="hidden" name="item_name" value="Credit Deposit">
                
                <div class="col-xs-4">
								<input class="form-control" type="number" min="0" step="50" value="50" name="amount">
                </div>
                <div class="col-xs-3">
                <span class="input-group-btn"><button type="submit" name="submit" class="btn btn-primary" data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Processes and credits account instantly.</b>"> <b>Add Credit with PayPal (USD)</b> </button></span>
                </div>

								<input type="hidden" name="custom" value="<?php echo $accountid; ?>" />
								<input type="hidden" name="currency_code" value="USD">
								<input type="hidden" name="button_subtype" value="services">
								<input type="hidden" name="no_note" value="0">
								<input type="hidden" name="cn" value="Add special instructions to the seller:">
								<input type="hidden" name="no_shipping" value="2">
								<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHosted">
								<input type="hidden" name="notify_url" value="https://<?php echo $fronthn; ?>/<?php echo $biddername; ?>/ppipn.php">
                <input type="hidden" name="return" value="https://<?php echo $fronthn; ?>/<?php echo $biddername; ?>/finance.php">
                <input type="hidden" name="rm" value="2">
                <input type="hidden" name="cbt" value="Return to VOX DSP">
                <input type="hidden" name="cancel_return" value="https://<?php echo $fronthn; ?>/<?php echo $biddername; ?>/finance.php">
								<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
							</form>
                
              <div class="col-xs-12">&nbsp;</div>

              <form action="https://www.coinpayments.net/index.php" method="post">
                <input type="hidden" name="cmd" value="_pay_simple">
                <input type="hidden" name="reset" value="1">
                <input type="hidden" name="merchant" value="606a89bb575311badf510a4a8b79a45e">
                <input type="hidden" name="currency" value="USDT">
                <input type="hidden" name="success_url" value="https://<?php echo $fronthn; ?>/<?php echo $biddername; ?>/finance.php">	
                <input type="hidden" name="cancel_url" value="https://<?php echo $fronthn; ?>/<?php echo $biddername; ?>/finance.php">	
                <div class="col-xs-4 hidden">
                  <input class="form-control" type="number" name="amountf" min="0" step="50" value="50">
                </div>
                <input type="hidden" name="item_name" value="VOXDSP.com Advertising Credit">
                <input type="hidden" name="item_number" value=<?php echo $accountid; ?>>
                <div class="col-xs-3 hidden">
                  <span class="input-group-btn"><button type="submit" name="submit" class="btn btn-primary" data-html="true" data-toggle="tooltip" data-placement="bottom" title="<b>Takes 24 hours to process.</b>"><b>Add Credit with CryptoCurrency (USDT)</b></button></span>
                </div>
              </form>

                      </div>
    
                      <table class="table table-striped table-hover" id="datatable" cellspacing="0" width="100%">
                            <thead>
                              <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Amount</th>
                              </tr>
                            </thead>
                            <tfoot>
                              <tr>
                                  <th></th>
                                  <th></th>
                                  <th></th>
                              </tr>
                          </tfoot>
                            <tbody>
    <?php
      $fd = file_get_contents('ledgers/'.$accountid.'_ledger.log');
      $log = explode(PHP_EOL, $fd);
      foreach($log as $l)
      {
        if($l == "")
          continue;
        echo "<tr>";
          $p = explode(',', $l);
          echo "<td>" . explode(' ', $p[0], 2)[0] . "</td><td>" . $p[1] . "</td><td>" . number_format($p[2]). "</td>";
        echo "</tr>";
      }
    ?>
                            </tbody>
                          </table>
    
                </div>
            </div>
        </div>
        </div>
    
        <div class="col-xs-12 col-sm-6">
        <div class="admin-form theme-primary mw1000 center-block">
            <div class="panel panel-primary heading-border">
                <div class="panel-body">
                    <div class="section-divider mt20 mb40">
                        <span> <b style="color:#000;">Account Spend Per Exchange</b> </span>
                    </div>
    
    
                      <table class="table table-striped table-hover" id="datatable2" cellspacing="0" width="100%">
                            <thead>
                              <tr>
                                <th>Date</th>
                                <th>Exchange</th>
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
    foreach($exchanges as $e)
    {
      if(!file_exists($logsdir.$accountid.'-'.$e.'.txt'))
        continue;

      $fd = file_get_contents($logsdir.$accountid.'-'.$e.'.txt');
      $log = explode(PHP_EOL, $fd);
      foreach($log as $l)
      {
        if($l == "")
          continue;
        echo "<tr>";
          $p = explode(',', $l);
          echo "<td>" . explode(' ', $p[0], 2)[0] . "</td><td>".ucfirst($e)."</td><td>" . number_format($p[1]) . "</td><td>" . number_format($p[2], 2) . "</td>";
        echo "</tr>";
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

      $.validator.methods.smartCaptcha = function(value, element, param) {
        return value == param;
      };

      $("#admin-form").validate({

        /* @validation states + elements 
        ------------------------------------------- */
        errorClass: "state-error",
        validClass: "state-success",
        errorElement: "em",

        /* @validation rules 
        ------------------------------------------ */
        rules: {
          inccred: {
            required: true
          }
        },

        /* @validation error messages 
        ---------------------------------------------- */
        messages: {
          inccred: {
            required: 'Enter amount in USD'
          }
        },

        /* @validation highlighting + error placement  
        ---------------------------------------------------- */

        highlight: function(element, errorClass, validClass) {
          $(element).closest('.field').addClass(errorClass).removeClass(validClass);
        },
        unhighlight: function(element, errorClass, validClass) {
          $(element).closest('.field').removeClass(errorClass).addClass(validClass);
        },
        errorPlacement: function(error, element) {
          if (element.is(":radio") || element.is(":checkbox")) {
            element.closest('.option-group').after(error);
          } else {
            error.insertAfter(element.parent());
          }
        }

      });

    });

    $('#datatable').dataTable({
      "iDisplayLength": 25,
      "aLengthMenu": [
        [5, 10, 25, 50, -1],
        [5, 10, 25, 50, "All"]
      ],
      "order": [[0, "desc"]],
      stateSave: false,
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
          .column( 2, {page: 'current'} )
          .data()
          .reduce( function (a, b) {
          return intVal(a) + intVal(b);
          }, 0 );


          // Update footer by showing the total with the reference of the column index 
          $( api.column( 0 ).footer() ).html('<b>TOTALS:</b>');
          $( api.column( 2 ).footer() ).html(number_format(ThreeTotal, 2) + ' <b>USD</b>');
      }
    });

    $('#datatable2').dataTable({
      "iDisplayLength": 25,
      "aLengthMenu": [
        [5, 10, 25, 50, -1],
        [5, 10, 25, 50, "All"]
      ],
      "order": [[0, "desc"]],
      stateSave: false,
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
