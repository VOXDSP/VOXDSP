<?php

    require_once "/usr/share/nginx/app.voxdsp.com/html/platform/config.php";

    function cescape($in)
    {
        $in = str_replace('\\', '', $in); //get rid of any back slashes !!
        return str_replace("'", "\\'", $in); //Escape any single quotes
    }

    //Disable all campaigns if spend exceeds credit
    $ra = $mysql->query("SELECT spent,credit,id FROM `account`;");
    if($ra)
    {
        while($rao = $ra->fetch_assoc())
        {
            if($rao['spent'] >= $rao['credit']*1000)
                $mysql->query("UPDATE `campaigns` SET active=0 WHERE aid=".$rao['id']." AND active=1");
        }
    }

    $data = "<?php \$campaigns = array(";
    $r = $mysql->query("SELECT * FROM `campaigns` WHERE active=1 ORDER BY RAND();"); //Only active creatives between their start and end dates
    if($r && $r->num_rows > 0)
    {
        while($ro = $r->fetch_assoc())
        {
            //Fork to mysql (account)
            $spent = $redis->get('mb-'.$ro['aid'].'-spent');
            $mysql->query("UPDATE `account` SET spent=" . $spent . " WHERE id=" . $ro['aid'] . ";");

            //Fork to mysql (creative)
            $wins = $redis->get('mb-'.$ro['id'].'-wins');
            $cost = $redis->get('mb-'.$ro['id'].'-cost');
            $clicks = $redis->get($ro['id'].'-clicks');
            $mysql->query("UPDATE `campaigns` SET clicks=" . $clicks . ", cost=" . $cost . ", wins=" . $wins . " WHERE id=" . $ro['id'] . ";");

            //Don't bid on campaigns that have passed their daily cost limit
            $cl = $redis->get($ro['id'].'-cost');
            if($ro['dailycostlimit'] != 0)
                if($cl != FALSE && $cl >= $ro['dailycostlimit']*1000)
                    continue;
            
            //Dont use campaigns past their cost limit
            if($cost >= $ro['costlimit']*1000)
                continue;
            
            //Dont use campaigns past their impressions limit
            if($ro['implimit'] != 0)
                if($wins >= $ro['implimit'])
                    continue;
            
            //Build Targeting Array
            $ro['targeting'] = strtolower($ro['targeting']);
            if($ro['targeting'] == '')
            {
                $targeting = "''";
            }
            else
            {
                $targeting = rtrim(cescape($ro['targeting']), ';');
                $targeting = "'".str_replace("\r\n", "', '", str_replace(",", "', '", $targeting))."'";
                $targeting = "array(".str_replace(";", "'),array('", $targeting).")";
            }
            
            //Build Blocking Array
            $ro['blocking'] = strtolower(cescape($ro['blocking']));
            $blocking = "'".str_replace("\r\n", "', '", $ro['blocking'])."'";
            
            //Compile Array
            $data .= "array('id'=>'".$ro['id']."', 'aid'=>'".$ro['aid']."', 'imptracker'=>'".cescape($ro['imptracker'])."', 'uhours'=>'".$ro['uhours']."', 'w'=>'".$ro['w']."', 'h'=>'".$ro['h']."', 'tag'=>'".cescape(str_replace("\r\n", "", $ro['adtag']))."', 'iurl'=>'".cescape($ro['iurl'])."', 'adomain'=>'".cescape($ro['adomain'])."', 'mincpm'=>'".$ro['mincpm']."', 'maxcpm'=>'".$ro['maxcpm']."', 'iab'=>'".$ro['iab']."', 'directurl'=>'".$ro['directurl']."', 'targeting'=>array(".$targeting."), 'blocking'=>array(".$blocking.")),";
        }
        
        $data = rtrim($data, ',');
        $data .= "); ?>";
        file_put_contents($coredir."compiled.php", $data, LOCK_EX);
        file_put_contents($coredir."e6ebe0bdbb4ef83f38e5515bf9e77009.txt", $data, LOCK_EX);
        $mysql->close();
        exit;
    }
    
    $data = "<?php \$campaigns = ''; ?>";
    file_put_contents($coredir."compiled.php", $data, LOCK_EX);
    $mysql->close();

?>
