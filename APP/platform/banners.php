<?php

set_time_limit(0);

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

  //Re Array Files
  function reArrayFiles(&$file_post)
  {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for($i=0; $i < $file_count; $i++)
        foreach($file_keys as $key)
            $file_ary[$i][$key] = $file_post[$key][$i];

    return $file_ary;
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
  
  //Delete banner
	if(isset($_GET['del']))
	{
    $redis->del($accountid.'-'.$_GET['if']);
		unlink($rootdir.'b/'.$accountid.'/' . $_GET['del']);
		header("Location: banners.php");
		exit;
	}
	
	$caup = false; //Failed as file already uploaded
	$cmax = false; //Failed as file is too large
	$cnoi = true; //Failed as the file is not an image
	if(isset($_FILES["fileToUpload"]["name"]))
	{
    $file_ary = reArrayFiles($_FILES['fileToUpload']);

    foreach($file_ary as $f)
    {
      //Upload to
      $target_file = $rootdir . 'b/'.$accountid.'/' . basename($f['name']);

      //Make sure dir exists
      mkdir($rootdir . 'b/'.$accountid.'/');

      //Dont overwrite files
      $caup = file_exists($target_file);

      //Max size is ~600kb
      if($f['size'] > 600999)
        $cmax = true;

      //If it is an image, then upload it :)
      $cnoi = getimagesize($f["tmp_name"]); //is this an image?
      if($cnoi != false && $caup == false && $cmax == false)
        move_uploaded_file($f["tmp_name"], $target_file);
    }
		
		//header("Location: banners.php");
		//exit;
  }


  //Submit Click URL
  if(isset($_POST['curl']))
  {
    $redis->set($accountid.'-'.$_GET['if'], $_POST['curl']);
    header("Location: banners.php");
		exit;
  }


?>
<!DOCTYPE html>
<html>

<head>
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <title>VOX DSP - Banners</title>
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

  <!-- Custom File Input CSS -->
  <link href="third/cfile.css" rel="stylesheet">

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
          <li class="sidebar-label pt20">Menu</li>
          <?php $_GET['banners']=1; include "nav.php"; ?>

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
              <span class="panel-title"><b style="font-size:1.4em;">Banner Management / Uploader</b></span>
            </div>
            <div class="panel-body">
              
            <div class="scrollable scrollbar-macosx">
              <div class="container-fluid">
                  <table class="datalist__table table datatable display table-hover"
                          id="hidden-table-info">
                      <thead>
                      <tr>
                          <th></th>
                          <th></th>
                      </tr>
                      </thead>
                      <tbody>
                      <tr><td>
                      <center>
                      <?php if($caup == true){echo "<b>A banner with this name already exists.</b></br>";} 
                          else if($cmax == true){echo "<b>Your banner must be under 300kb</b></br>";}
                          else if($cnoi == false){echo "<b>Your banner must be an supported image type.</b></br>";} ?>
                                            <br/>
                      <form action="banners.php" method="post" enctype="multipart/form-data">
                      <input type="file" name="fileToUpload[]" id="file-1" class="inputfile inputfile-1" data-multiple-caption="{count} files selected" style="display:none;" multiple />
                      <label for="file-1" style="cursor:pointer;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17"><path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z"/></svg> <span>Choose files&hellip;</span></label>
                      <br><button type="submit" class="btn btn-sm btn-info"><b>Start Upload</b></button>
                      </form>
                      </br>
                      </center>
                      </td><td></td></tr>
<?php
$cnt = 0;
foreach(glob($rootdir.'b/'.$accountid.'/*') as $file)
{
  $url = 'https://' . $fronthn .'/'.$biddername.'/b/'.$accountid.'/' . basename($file);
  $imgsize = getimagesize($file);
  $fsz = filesize($file) / 1024;

  $curl = $redis->get($accountid.'-'.$url);
  if($curl != FALSE)
    $curl = '<b>Landing Page:</b> <a href="'.$curl.'" target="_blank"><b>'.substr($curl,0,33).'...</b></a>';
  else
    $curl = '<form method="post" action="banners.php?if='.urlencode($url).'"><div class="input-group"><input name="curl" class="form-control input-sm" type="url" placeholder="Please insert a Landing Page / Click URL" required><span class="input-group-btn"><button type="submit" class="btn btn-sm btn-primary"> <b>Submit</b> </button></span></div></form>';

  echo '<tr><td class="text-left"><img src="'.$url.'" /><br><button onclick="javascript:copyTextToClipboard(\''.$url.'\');" data-note-stack="stack_top_right" data-note-style="info" data-html="true" data-toggle="tooltip" data-placement="right" title="<b>Copy Image URL to Clipboard</b>" class="btn btn-xs btn-info notification"><b><i class="fa fa-fw fa-copy"></i></b></button> <a href="#" data-toggle="modal" data-target="#dataModal" data-type=0 data-cid="' . urlencode($url) . '" data-title="' . basename($file) . '">' . basename($file) . ' <i>(<font color=#555><b>'.$imgsize[0].'</b></font>x<font color=#555><b>'.$imgsize[1].'</b></font>, <font color=#555><b>'.number_format($fsz, 0, "","").'</b></font>kb)</i></a></br>'.$curl.'</td><td class="text-right"> <a class="text-danger" href="banners.php?del=' . urlencode(basename($file)) . '&if='.urlencode($url).'"><button data-html="true" data-toggle="tooltip" data-placement="left" title="<b>Delete Banner</b>" class="btn btn-xs btn-danger"><b><i class="fa fa-fw fa-close"></i></b></button></a></td></tr>';
  $cnt++;
}
if($cnt == 0)
echo "<tr><td><center><b><br>No Banners have been added yet.</b></center></td></tr>";
?>
                        </tbody>
                    </table>
                </div>
            </div>
                
            </div>
          </div>

        
        </div>
      </section>
      <!-- End: Content -->

    </section>
    <!-- End: Content-Wrapper -->

    <div class="modal fade" id="dataModal" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title"></h4>
						</div>
						<div class="modal-body"></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
					</div>
				</div>
			</div>


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

  <!-- PNotify -->
  <script src="vendor/plugins/pnotify/pnotify.js"></script>

  <!-- Theme Javascript -->
  <script src="assets/js/utility/utility.js"></script>
  <script src="assets/js/main.js"></script>


<script src="third/custom-file-input.js"></script>
<script>
$(document).ready(function ()
{
  "use strict";    
  Core.init();

  $(".select2-single-prude").select2();

	$("[data-toggle='tooltip']").tooltip();
	$('#dataModal').on('show.bs.modal', function (event)
	{
		var button = $(event.relatedTarget);
		var cid = button.data('cid');
		var type = button.data('type');
		var title = button.data('title');
		var modal = $(this);
		modal.find(".modal-body").text("Loading page, please wait ...");

		if(type == 0)
			modal.find(".modal-body").load('bprv.php?bnr=' + cid);

		modal.find('.modal-title').text(title);
	});

  // A "stack" controls the direction and position
    // of a notification. Here we create an array w
    // with several custom stacks that we use later
    var Stacks = {
      stack_top_right: {
        "dir1": "down",
        "dir2": "left",
        "push": "top",
        "spacing1": 10,
        "spacing2": 10
      },
      stack_top_left: {
        "dir1": "down",
        "dir2": "right",
        "push": "top",
        "spacing1": 10,
        "spacing2": 10
      },
      stack_bottom_left: {
        "dir1": "right",
        "dir2": "up",
        "push": "top",
        "spacing1": 10,
        "spacing2": 10
      },
      stack_bottom_right: {
        "dir1": "left",
        "dir2": "up",
        "push": "top",
        "spacing1": 10,
        "spacing2": 10
      },
      stack_bar_top: {
        "dir1": "down",
        "dir2": "right",
        "push": "top",
        "spacing1": 0,
        "spacing2": 0
      },
      stack_bar_bottom: {
        "dir1": "up",
        "dir2": "right",
        "spacing1": 0,
        "spacing2": 0
      },
      stack_context: {
        "dir1": "down",
        "dir2": "left",
        "context": $("#stack-context")
      },
    }


  // PNotify Plugin Event Init
  $('.notification').on('click', function(e) {
      var noteStyle = $(this).data('note-style');
      var noteShadow = $(this).data('note-shadow');
      var noteOpacity = $(this).data('note-opacity');
      var noteStack = $(this).data('note-stack');
      var width = "320px";

      // If notification stack or opacity is not defined set a default
      var noteStack = noteStack ? noteStack : "stack_top_right";
      var noteOpacity = noteOpacity ? noteOpacity : "1";

      // We modify the width option if the selected stack is a fullwidth style
      function findWidth() {
        if (noteStack == "stack_bar_top") {
          return "100%";
        }
        if (noteStack == "stack_bar_bottom") {
          return "70%";
        } else {
          return "320px";
        }
      }

      // Create new Notification
      new PNotify({
        title: 'Notification',
        text: '<b>Copied Banner URL to the Clipboard.</b>',
        shadow: noteShadow,
        opacity: noteOpacity,
        addclass: noteStack,
        type: noteStyle,
        stack: Stacks[noteStack],
        width: findWidth(),
        delay: 1400
      });

    });

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
}
</script>

</body>

</html>
