<html>
<head>
</head>
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

	//Secure
	if(isset($_GET['cid']))
		secureAC($_GET['cid']);

	function getTag($cid)
	{
		GLOBAL $mysql;
		GLOBAL $tag;
		$result = $mysql->query("SELECT w,h,adtag FROM campaigns WHERE id=".$mysql->real_escape_string($cid));
		if($result)
		{
			$row = $result->fetch_assoc();
			$tag = $row['adtag'];
			return '<center><div class="table-responsive"><table width="' . $row['w'] . '" height="' . $row['h'] . '" class="table"><tr><td>' . $row['adtag'] . '</td></tr></table></div></center>';
		}
	}

	$tag = '';
	getTag($_GET['cid']);

	if(isset($_GET['f']))
	{
		echo $tag;
		exit;
	}
	
	$mysql->close();

?>
<iframe width="100%" height="320" src="modalpreview.php?cid=<?php echo $_GET['cid']; ?>&f=1" frameborder="0"></iframe>
<?php
	echo '<br><textarea class="form-control" rows="5" placeholder="Enter additional HTML code." maxlength="12000">' . htmlspecialchars($tag) . '</textarea><br>';
?>
</body>
</html>
