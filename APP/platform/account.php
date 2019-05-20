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

  //Get account data to display
  $data = "";
  $r = $mysql->query("SELECT ldat,info FROM `account` WHERE id=".$accountid);
  if($r)
  {
    $ro = $r->fetch_assoc();
    $data = $ro['info'];
    $ldata = $ro['ldat'];
  }
  $dat = explode(';', $data);
  $ldat = explode(';', $ldata);

  //Set user data if validation passed
  if(isset($_POST['firstname']))
  {
    $mysql->query("UPDATE `account` SET ldat='".$mysql->real_escape_string($_POST['useremail'].";".md5($_POST['useremail'].$_POST['password']))."', info='".$mysql->real_escape_string($_POST['firstname'].";".$_POST['lastname'].";".$_POST['website'].";".$_POST['mobile_phone'].";".$_POST['home_phone'].";".$_POST['job_title'].";".$_POST['company_name'].";".$_POST['company_reg'].";".$_POST['address']).";"."' WHERE id=".$accountid);
    header('Location: account.php');
    exit;
  }

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Account</title>
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
    font-size: 14px !important;
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
          <?php $_GET['account']=1; include "nav.php"; ?>

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



                <!-- Validation Example -->
                <div class="col-xs-12 col-sm-6">
                <div class="admin-form theme-primary mw1000 center-block">
                  
                            <div class="panel panel-primary heading-border">
                  
                              <form method="post" action="account.php" id="admin-form">
                                <div class="panel-body">
                  
                                  <div class="section-divider mt20 mb40">
                                    <span> <b style="color:#000;">Account Information</b> </span>
                                  </div>
                                  <!-- .section-divider -->
                  
                                  <div class="section row">
                                    <div class="col-xs-12 col-md-6" data-toggle="tooltip" data-placement="bottom" title="First Name">
                                      <label for="firstname" class="field prepend-icon">
                                        <input type="text" name="firstname" id="firstname" <?php if(isset($dat[0])){echo 'value="'.$dat[0].'"';} ?> class="gui-input" placeholder="First name...">
                                        <label for="firstname" class="field-icon">
                                          <i class="fa fa-user"></i>
                                        </label>
                                      </label>
                                    </div>
                                    <!-- end section -->
                  
                                    <div class="col-xs-12 col-md-6" data-toggle="tooltip" data-placement="bottom" title="Second Name">
                                      <label for="lastname" class="field prepend-icon">
                                        <input type="text" name="lastname" id="lastname" <?php if(isset($dat[1])){echo 'value="'.$dat[1].'"';} ?> class="gui-input" placeholder="Last name...">
                                        <label for="lastname" class="field-icon">
                                          <i class="fa fa-user"></i>
                                        </label>
                                      </label>
                                    </div>
                                    <!-- end section -->
                                  </div>
                                  <!-- end .section row section -->
                  
                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Email Address">
                                    <label for="useremail" class="field prepend-icon">
                                      <input type="email" name="useremail" id="useremail" <?php if(isset($ldat[0])){echo 'value="'.$ldat[0].'"';} ?> class="gui-input" placeholder="Email address" readonly>
                                      <label for="useremail" class="field-icon">
                                        <i class="fa fa-envelope"></i>
                                      </label>
                                    </label>
                  
                                  </div>
                                  <!-- end section -->
                  
                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Website URL">
                                    <label for="website" class="field prepend-icon">
                                      <input type="url" name="website" id="website" <?php if(isset($dat[2])){echo 'value="'.$dat[2].'"';} ?> class="gui-input" placeholder="Current website url">
                                      <label for="website" class="field-icon">
                                        <i class="fa fa-globe"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->
                  
                                  <div class="section row">
                  
                                    <div class="col-xs-12 col-md-6" data-toggle="tooltip" data-placement="bottom" title="Mobile Number">
                                      <label for="mobile_phone" class="field prepend-icon">
                                        <input type="tel" name="mobile_phone" id="mobile_phone" <?php if(isset($dat[3])){echo 'value="'.$dat[3].'"';} ?> class="gui-input phone-group" placeholder="Mobile number">
                                        <label for="mobile_phone" class="field-icon">
                                          <i class="fa fa-mobile-phone"></i>
                                        </label>
                                      </label>
                                    </div>
                                    <!-- end section -->
                  
                                    <div class="col-xs-12 col-md-6" data-toggle="tooltip" data-placement="bottom" title="Home Number">
                                      <label for="home_phone" class="field prepend-icon">
                                        <input type="tel" name="home_phone" id="home_phone" <?php if(isset($dat[4])){echo 'value="'.$dat[4].'"';} ?> class="gui-input phone-group" placeholder="Home number">
                                        <label for="home_phone" class="field-icon">
                                          <i class="fa fa-phone"></i>
                                        </label>
                                      </label>
                                    </div>
                                    <!-- end section -->
                  
                                  </div>
                                  <!-- end .section row section -->

                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Job Title">
                                    <label for="Job Title" class="field prepend-icon">
                                      <input type="text" name="job_title" id="job_title" <?php if(isset($dat[5])){echo 'value="'.$dat[5].'"';} ?> class="gui-input" placeholder="Job Title...">
                                      <label for="firstname" class="field-icon">
                                        <i class="fa fa-user"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->

                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Company Name">
                                    <label for="Company Name" class="field prepend-icon">
                                      <input type="text" name="company_name" <?php if(isset($dat[6])){echo 'value="'.$dat[6].'"';} ?> id="company_name" class="gui-input" placeholder="Company Name...">
                                      <label for="firstname" class="field-icon">
                                        <i class="fa fa-user"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->

                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Company Reg No.">
                                    <label for="Company Reg No." class="field prepend-icon">
                                      <input type="text" name="company_reg" id="company_reg" <?php if(isset($dat[7])){echo 'value="'.$dat[7].'"';} ?> class="gui-input" placeholder="Company Reg No...">
                                      <label for="firstname" class="field-icon">
                                        <i class="glyphicon glyphicon-registration-mark"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->

                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Address">
                                    <label for="Address" class="field prepend-icon">
                                      <input type="text" name="address" id="address" <?php if(isset($dat[8])){echo 'value="'.$dat[8].'"';} ?> class="gui-input" placeholder="Address...">
                                      <label for="firstname" class="field-icon">
                                        <i class="glyphicon glyphicon-home"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->
                  
                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Change Password">
                                    <label for="password" class="field prepend-icon">
                                      <input type="password" name="password" id="password" class="gui-input" placeholder="Change password">
                                      <label for="password" class="field-icon">
                                        <i class="fa fa-user"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->
                  
                                  <div class="section" data-toggle="tooltip" data-placement="bottom" title="Repeat Password">
                                    <label for="repeatPassword" class="field prepend-icon">
                                      <input type="password" name="repeatPassword" id="repeatPassword" class="gui-input" placeholder="Repeat password">
                                      <label for="repeatPassword" class="field-icon">
                                        <i class="fa fa-unlock-alt"></i>
                                      </label>
                                    </label>
                                  </div>
                                  <!-- end section -->
                  
                                </div>
                                <!-- end .form-body section -->
                                <div class="panel-footer text-right">
                                  <button type="submit" class="button btn-primary"> <b>Update Account</b> </button>
                                  <button type="reset" class="button"> Cancel </button>
                                </div>
                                <!-- end .form-footer section -->
                              </form>
                  
                            </div>
                  
                          </div>
                          </div>
                          <!-- end: .admin-form -->
                  
                          <div class="col-xs-12 col-sm-6">
                          <div class="admin-form theme-primary mw1000 center-block">
                            <div class="panel panel-primary heading-border">

                                <div class="panel-body">
                  
                                  <div class="section-divider mt20 mb40">
                                    <span> <b style="color:#000;">Support Contact</b> </span>
                                  </div>
                                  <!-- .section-divider -->
                                    
                                  <div class="section row">
                                    <center>
                                        <script type="text/javascript" src="https://secure.skypeassets.com/i/scom/js/skype-uri.js"></script>
                                        <div id="SkypeButton_Call_sirtames_1">
                                        <script type="text/javascript">
                                        Skype.ui({
                                        "name": "chat",
                                        "element": "SkypeButton_Call_sirtames_1",
                                        "participants": ["sirtames"],
                                        "imageSize": 32
                                        });
                                        </script>
                                      </div>
                                      <br><b><a href="mailto:support@voxdsp.com" target="_blank">Alternatively you can send us an email.</a></b>
                                    </center>
                                  </section>

                                </div>
                                
                            </div>        
                          </div>
                          </div>
                          <!-- end: .admin-form -->



        
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
          firstname: {
            required: true
          },
          lastname: {
            required: true
          },
          useremail: {
            required: true,
            email: true
          },
          website: {
            required: true,
            url: true
          },
          mobile_phone: {
            require_from_group: [1, ".phone-group"]
          },
          home_phone: {
            require_from_group: [1, ".phone-group"]
          },
          job_title: {
            required: true
          },
          company_name: {
            required: true
          },
          company_reg: {
            required: true
          },
          address: {
            required: true
          },
          password: {
            required: true,
            minlength: 6,
            maxlength: 16
          },
          repeatPassword: {
            required: true,
            minlength: 6,
            maxlength: 16,
            equalTo: '#password'
          }

        },

        /* @validation error messages 
        ---------------------------------------------- */

        messages: {
          firstname: {
            required: 'Enter first name'
          },
          lastname: {
            required: 'Enter last name'
          },
          useremail: {
            required: 'Enter email address',
            email: 'Enter a VALID email address'
          },
          website: {
            required: 'Enter your website URL',
            url: 'URL should start with - http://www'
          },
          mobile_phone: {
            require_from_group: 'Fill at least a mobile contact'
          },
          home_phone: {
            require_from_group: 'Fill at least a home contact'
          },
          job_title: {
            required: 'Enter Job Title'
          },
          company_name: {
            required: 'Enter Company Name'
          },
          company_reg: {
            required: 'Enter Reg No.'
          },
          address: {
            required: 'Enter Address'
          },
          password: {
            required: 'Please enter a password'
          },
          repeatPassword: {
            required: 'Please repeat the above password',
            equalTo: 'Password mismatch detected'
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
  </script>

</body>

</html>
<?php $mysql->close(); ?>
