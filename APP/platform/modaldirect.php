<?php
	
	//Check for logged in
	session_start();
	if(!isset($_SESSION['auth']))
	{
	  header('Location: index.php');
	  exit;
	}
	require_once "config.php";

	if($_SESSION['auth'] != 1)
		exit;
?>
<form action="am.php?cid=<?php echo $_GET['cid']; ?>" method="POST">

<div class="input-group">
<input name="directurl" type="url" class="form-control" placeholder="Redirect URL" required>
<span class="input-group-btn"><button type="submit" class="btn btn-info"><b>Submit</b></button></span>
</div>

</form>