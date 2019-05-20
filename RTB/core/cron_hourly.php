<?php
    
    set_time_limit(0);
    require_once "/usr/share/nginx/app.voxdsp.com/html/platform/config.php";


    //Loop all campaigns
    $r = $mysql->query("SELECT * FROM `campaigns` WHERE active=1;");
    if($r)
    {
        while($ro = $r->fetch_assoc())
        {
            //Do the campaigns thing for redis
            $tw = 0;
            $tb = 0;
            $th = 0;
            $os = 0;
            $wins = $redis->keys($ro['id'].'-dbl-*');
            foreach($wins as $w)
            {
                $v0 = $redis->get($w);
                $tw += $v0;
                $v1 = $redis->get(str_replace('-dbl-', '-dbc-', $w));
                $v2 = $redis->get(str_replace('-dbl-', '-dbhc-', $w));
                if($v1 == false)
                    $v1 = 0;
                if($v2 == false)
                $v2 = 0;
                
                $th += $v2; //total human
                if($v0-$v1 > 0)
                    $tb += abs($v0-$v1); //total bleed
                else
                    $os += abs($v0-$v1); //over sent
            }

            $bp = number_format(divide(100, $tw)*$tb, 2);
            $hp = number_format(divide(100, $tw)*$th, 2);

            $redis->set($ro['id'].'-CV0', $bp);
            $redis->set($ro['id'].'-CV1', $hp);


            //Create logs directory if not already exit
            if(!file_exists('/usr/share/nginx/rtb.voxdsp.com/html/logs/bleedlogs/'.$ro['id']))
                mkdir('/usr/share/nginx/rtb.voxdsp.com/html/logs/bleedlogs/'.$ro['id'], 0777);

            //Create log file data
            $fd = '';
            $fd .= "Domain | Impressins Won | Impressions Arrived | Percent Arrived\n";
            $wins = $redis->keys($ro['id'].'-dbl-*');
            foreach($wins as $w)
            {
                $v0 = $redis->get($w);
                $v1 = $redis->get(str_replace('-dbl-', '-dbc-', $w));
                if($v1 == false)
                    $v1 = 0;
                
                $domain = str_replace($ro['id'].'-dbl-', '', $w);

                $last = $redis->get($ro['id'].'-dbh-'.$domain);
                $redis->set($ro['id'].'-dbh-'.$domain, $v0 . ',' . $v1);
                if($last != false)
                {
                    $p = explode(',', $last);
                    $v0 -= $p[0];
                    $v1 -= $p[1];
                }

                $fd .= $domain . ',' . $v0 . ',' . $v1 . ',' . number_format(divide(100, $v0)*$v1, 2) . "%\n";
            }

            //Write to file
            $ret = FALSE;
		    while($ret == FALSE)
                $ret = file_put_contents('/usr/share/nginx/rtb.voxdsp.com/html/logs/bleedlogs/' . $ro['id'] . '/' . date('Y-m-d H') . '.txt', $fd, LOCK_EX);
            
        }
    }

?>