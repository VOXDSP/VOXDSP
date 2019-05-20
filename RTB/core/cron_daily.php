<?php
set_time_limit(0);

    require_once "/usr/share/nginx/app.voxdsp.com/html/platform/config.php";
    $exchanges = getExchanges();

    function sEmail($sub, $body, $rcp)
    {
        file_get_contents('http://rtb.voxdsp.com/core/mail/send.php?sub=' . urlencode($sub) . '&body=' . urlencode($body) . '&rcp=' . urlencode($rcp));
        file_put_contents('/usr/share/nginx/rtb.voxdsp.com/html/core/msa.txt', '', LOCK_EX | FILE_APPEND);
    }

    function fSet($key, $val)
	{
		GLOBAL $redis;
        return $redis->set($key, $val);
		//$ret = FALSE;
		//while($ret == FALSE)
			//$ret = $redis->set($key, $val);
	}

    function fGet($key)
	{
		GLOBAL $redis;
        return $redis->get($key);
		//$ret = FALSE;
		//while($ret == FALSE)
			//$ret = $redis->get($key);
        //return $ret;
	}

    $ereport='';
 
$ra = $mysql->query("SELECT * FROM `account`;");
if($ra)
{
    while($rao = $ra->fetch_assoc())
    {

        //Save all exchange stats
        foreach($exchanges as $e)
        {
            $ew = fGet($rao['id'].'-'.$e.'-wins');
            if($ew == FALSE || $ew == 0) //Skip if null
                continue;
            $ec = divide(fGet($rao['id'].'-'.$e.'-cost'), 1000);
            
            $ret = FALSE;
		    while($ret == FALSE)
                $ret = file_put_contents($logsdir . $rao['id'] . '-' . $e . ".txt", date('Y-m-d H:i:s', strtotime("-1 day")).",".$ew.",".number_format($ec, 2)."\n", FILE_APPEND | LOCK_EX);
        }

        //Clear all 24 hour stats
        fSet($rao['id'].'-wins', 0);
        fSet($rao['id'].'-cost', 0);

        foreach($exchanges as $e)
        {
            fSet($rao['id'].'-'.$e.'-wins', 0);
            fSet($rao['id'].'-'.$e.'-cost', 0);
        }

        
        //Reset daily cap count per campaign and create daily report
        $ereport .= 'Stats (UTC) ' . date('Y-m-d', strtotime("-1 day")) . "\nID | CAMPAIGN NAME | IMPRESSIONS | COST | CLICKS\n";
        $rai = 0;
        $r = $mysql->query("SELECT * FROM `campaigns` where aid=".$rao['id'].";");
        if($r)
        {
            while($ro = $r->fetch_assoc())
            {
                //Add to daily report
                $wins = fGet('mb-'.$ro['id'].'-wins');
                $cost = fGet('mb-'.$ro['id'].'-cost');
                $clicks = fGet($ro['id'].'-clicks');

                $lswins = fGet('lsmb-'.$ro['id'].'-wins');
                $lscost = fGet('lsmb-'.$ro['id'].'-cost');
                $lsclicks = fGet('lsmb-'.$ro['id'].'-clicks');
                if(!$lswins)
                    $lswins = 0;
                if(!$lscost)
                    $lscost = 0;
                if(!$lsclicks)
                    $lsclicks = 0;
                $today_wins = $wins-$lswins;
                $today_cost = $cost-$lscost;
                $today_clicks = $clicks-$lsclicks;
                $ret = FALSE;
                while($ret == FALSE)
                {
                    if($today_wins > 0)
                    {
                        $rai++;
                        $ereport .= $ro['id'] . ', ' . $ro['name'] . ', ' . number_format($today_wins) . ", " . number_format(divide($today_cost, 1000), 2) . ", ". number_format($today_clicks) ."\n";
                    }
                    $ret = file_put_contents($logsdir . 'dayreport/' . $ro['id'] . ".txt", date('Y-m-d', strtotime("-1 day")).",".$wins.",". divide($cost, 1000) .",".$clicks."\n", FILE_APPEND | LOCK_EX);
                }

                //Reset daily cap count
                fSet($ro['id'].'-cost', 0);

                //Log todays offset
                fSet('lsmb-'.$ro['id'].'-wins', $wins);
                fSet('lsmb-'.$ro['id'].'-cost', $cost);
                fSet('lsmb-'.$ro['id'].'-clicks', $clicks);
            }
        }


        //Email daily stats
        if($rai > 0)
        {
            sEmail("[VOX] Stats " . date('Y-m-d', strtotime("-1 day")), $ereport, explode(';', $rao['ldat'], 2)[0]);
            sEmail("[VOX] Stats " . date('Y-m-d', strtotime("-1 day")), $ereport, 'support@voxdsp.com');
        }
        $ereport = '';

    }
}

//Erase logs older than two days
system('find '.$logsdir.'bidlogs/* -mtime +2 -exec rm {} \;');
system('find '.$logsdir.'rsplogs/* -mtime +2 -exec rm {} \;');

//Done
$mysql->close();

?>
