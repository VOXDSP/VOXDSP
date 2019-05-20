<?php
	//Check for logged in
	session_start();
	if(!isset($_SESSION['auth']))
	{
	  header('Location: index.php');
	  exit;
	}
	require_once "config.php";
?>
<center><b><font size="4m">Are you sure you wish to <b class="text-danger">delete</b> this campaign?</font></b></br></br><b><font size="4m"><?php echo htmlspecialchars($_GET['t']); ?></font></b></br></br><a href="campaigns.php?del=<?php echo $_GET['cid']; ?>"><button class="btn btn-md btn-danger" formnovalidate><font size="3em"><b>Permanent Delete</b></font></button></a></center>
