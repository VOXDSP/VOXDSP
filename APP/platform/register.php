<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Register</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX - Demand Side Plafrom (DSP)">
  <meta name="author" content="VOX Media">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font CSS (Via CDN) -->
  <link rel='stylesheet' type='text/css' href='http://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>

  <!-- Theme CSS -->
  <link rel="stylesheet" type="text/css" href="assets/skin/default_skin/css/theme.css">

  <!-- Admin Forms CSS -->
  <link rel="stylesheet" type="text/css" href="assets/admin-tools/admin-forms/css/admin-forms.min.css">

  <!-- Favicon -->
  <link rel="shortcut icon" href="assets/img/favicon.ico">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
   <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
   <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
   <![endif]-->
</head>

<body class="external-page sb-l-c sb-r-c">

  <!-- Start: Main -->
  <div id="main" class="animated fadeIn">

    <!-- Start: Content-Wrapper -->
    <section id="content_wrapper">

      <!-- begin canvas animation bg -->
      <div id="canvas-wrapper">
        <canvas id="demo-canvas"></canvas>
      </div>

      <!-- Begin: Content -->
      <section id="content">

        <div class="admin-form theme-info" id="login1">

          <div class="row mb15 table-layout">

            <div class="col-xs-6 va-m pln">
              <a href="dashboard.php" title="Return to Dashboard">
                <img src="assets/img/logos/logo_white.png" title="VOX Logo" class="img-responsive w250">
              </a>
            </div>

            <div class="col-xs-6 text-right va-b pr5">
              <div class="login-links">
                <a href="index.php" title="Sign In">Sign In</a>
                <span class="text-white"> | </span>
                <a href="register.php" class="active" title="Register">Register</a>
              </div>

            </div>

          </div>

          <div class="panel panel-info mt10 br-n">

            <div class="panel-heading heading-border bg-white">
              <span class="panel-title hidden">
                <i class="fa fa-sign-in"></i>Register
              </span>
            </div>

            <!-- end .form-header section -->
            <form method="post" action="/" id="contact">
              <div class="panel-body bg-light p30">
                <div class="row">
                  <div class="col-sm-7 pr30">

                    <div class="section">
                      Registration is closed to private members only, if you would like to be included as a member of our Demand Side Plaform technology please contact us on the main page by <a href="https://voxdsp.com/#contact">Clicking Here</a>
                    </div>
                    <!-- end section -->

                  </div>
                  <div class="col-sm-5 br-l br-grey pl30">
                    <h3 class="mb25"> You'll Have Access To:</h3>
                    <p class="mb15">
                      <span class="fa fa-check text-success pr5"></span> Targeted Bidding</p>
                    <p class="mb15">
                      <span class="fa fa-check text-success pr5"></span> Campaign Management</p>
                    <p class="mb15">
                      <span class="fa fa-check text-success pr5"></span> SSP Traffic Sources</p>
                    <p class="mb15">
                      <span class="fa fa-check text-success pr5"></span> Bidding Statistics</p>
                  </div>
                </div>
              </div>
              <!-- end .form-body section -->
            </form>
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

  <!-- CanvasBG Plugin(creates mousehover effect) -->
  <script src="vendor/plugins/canvasbg/canvasbg.js"></script>

  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>

  <!-- Page Javascript -->
  <script type="text/javascript">
  jQuery(document).ready(function()
  {
    "use strict";    
    Core.init();
    CanvasBG.init({Loc: {x: window.innerWidth / 2, y: window.innerHeight / 3.3},});
  });
  </script>

  <!-- END: PAGE SCRIPTS -->

</body>

</html>
