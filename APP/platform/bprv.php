<html>
<body>
<?php
	//Check for logged in
  session_start();
  if(!isset($_SESSION['auth']))
  {
    header('Location: index.php');
    exit;
  }
  require_once "config.php";

	if(isset($_GET['bnr']))
	{
		echo '<center><img src="'.$_GET['bnr'].'" class="img-responsive" /></center>';
		exit;
	}
?>
</body>
</html>