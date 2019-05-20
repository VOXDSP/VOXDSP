<?php

    require_once $coredir."compiled.php";

    $cid = $_GET['id'];
    if(!is_array($cid) || $cid == '')
        return "CID 404";

    //No campaigns? Do nothing.
	if(!is_array($campaigns) || $campaigns == '')
        return "Banner 404";

    //Loop through campaigns		
    foreach($campaigns as $c)
    {
        if($c['id'] == $cid)
        {
            return $c['tag'];
            exit;
        }
    }

?>