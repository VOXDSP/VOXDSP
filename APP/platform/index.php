<?php
  
  ini_set('session.gc_maxlifetime', 3600*24);
  session_set_cookie_params(3600*24);

  session_start();
  require_once "config.php";

  $failed = false;

  //log back in with cookie?
  $xr = $mysql->query("SELECT id,ldat FROM account;");
	if($xr)
	{
		while($ro = $xr->fetch_assoc())
		{
			$uah = hash('sha512', $ro['ldat'].'7b768uh!jfc6.nri3$2@Na'.$ro['id']);
			if(isset($_COOKIE['uah']) && $_COOKIE['uah'] == $uah)
			{
				$_SESSION['auth'] = $ro['id'];
				header('Location: campaigns.php');
				exit;
			}
		}
	}

  //Attempt a login
  if(isset($_POST['username']))
  {
    $login = $_POST['username'].';'.md5($_POST['username'].$_POST['password']);
    $r = $mysql->query("SELECT id FROM `account` WHERE ldat='".$mysql->real_escape_string($login)."'");
    if($r && $r->num_rows > 0)
    {
      $ro = $r->fetch_assoc();
      $_SESSION['auth'] = $ro['id']; //Login User
      setcookie("uah", hash('sha512', $login.'7b768uh!jfc6.nri3$2@Na'.$_SESSION['auth']), time()+31556952);
      if($ro['id'] == 1)
        header('Location: am.php');
      else
        header('Location: campaigns.php');
      exit;
    }
    else
    {
      $failed = true;
    }
  }



?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Login</title>
  <meta name="keywords" content="VOX, DSP, Demand Side Platform, Bidder, Traffic, Bidding" />
  <meta name="description" content="VOX Media - Demand Side Platform (DSP)">
  <meta name="author" content="VOX">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font CSS (Via CDN) -->
  <link rel='stylesheet' type='text/css' href='//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>

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
              <a href="https://voxdsp.com">
                <img src="assets/img/logos/logo_white.png" class="img-responsive w250">
              </a>
            </div>

            <div class="col-xs-6 text-right va-b pr5">
              <div class="login-links">
                <a href="index.php" class="active" title="Sign In">Sign In</a>
                <span class="text-white"> | </span>
                <a href="register.php" class="" title="Register">Register</a>
              </div>

            </div>

          </div>

          <div class="panel panel-info mt10 br-n">

            <div class="panel-heading heading-border bg-white">
              <span class="panel-title hidden">
                <i class="fa fa-sign-in"></i>Register</span>
              <div class="section row mn">
              </div>
            </div>

            <!-- end .form-header section -->
            <form method="post" action="index.php" id="contact">
              <div class="panel-body bg-light p30">
                <div class="row">
                  <div class="col-sm-7 pr30">

                    <div class="section row hidden">
                    </div>

                    <div class="section">
                      <label for="username" class="field-label text-muted fs18 mb10">Email Address</label>
                      <label for="username" class="field prepend-icon">
                        <input type="text" name="username" id="username" class="gui-input" placeholder="Enter username">
                        <label for="username" class="field-icon">
                          <i class="fa fa-user"></i>
                        </label>
                      </label>
                    </div>
                    <!-- end section -->

                    <div class="section">
                      <label for="username" class="field-label text-muted fs18 mb10">Password</label>
                      <label for="password" class="field prepend-icon">
                        <input type="password" name="password" id="password" class="gui-input" placeholder="Enter password">
                        <label for="password" class="field-icon">
                          <i class="fa fa-lock"></i>
                        </label>
                      </label>
                    </div>
                    <!-- end section -->

                    <?php
              if($failed)
                echo '<p class="text-danger"><b>Login Failed</b></p>';
            ?>

                  </div>
                  <div class="col-sm-5 br-l br-grey pl30">
                    <h3 class="mb25"> You'll Have Access To Your:</h3>
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
              <div class="panel-footer clearfix p10 ph15">
                <button type="submit" class="button btn-primary mr10 pull-right"><b>Sign In</b></button>
                <label class="switch ib switch-primary pull-left input-align mt10">
                  <span>&nbsp;<a href="privacy.html"><b>Privacy Policy</a></b></span>&nbsp;<b>|</b>&nbsp;<span><a href="tos.html"><b>Terms of Service</a></b></span>
                </label>
              </div>
              <!-- end .form-footer section -->
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
