<?php

    //Check for logged in
    session_start();
    if(!isset($_SESSION['auth']))
    {
        header('Location: campaigns.php');
        exit;
    }
    require_once "config.php";

    //Secure
    if(isset($_GET['cid']))
        secureAC($_GET['cid']);

    $cid = $_GET['cid'];
    $filename = '/var/tmp/'.$cid.'.zip';

    function ziplogs($cid)
    {
        GLOBAL $logsdir;
        GLOBAL $filename;
        $winlogs = $logsdir.'winlogs/'.$_SESSION['auth'].'/'.$cid.'/';
        $bleedlogs = $logsdir.'bleedlogs/'.$cid.'/';
        $clicklogs = $logsdir.'clicklogs/'.$cid.'.txt';
        $dayreport = $logsdir.'dayreport/'.$cid.'.txt';

        try
        {
            // Get real path for our folder
            $zip = new ZipArchive();
            $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($winlogs),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $zip->addEmptyDir('winlogs');

            foreach($files as $name => $file)
            {
                if(!$file->isDir())
                {
                    $filePath = $file->getRealPath();
                    $relativePath = 'winlogs/'.substr($filePath, strlen($winlogs) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }


            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($bleedlogs),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $zip->addEmptyDir('bleedlogs');

            foreach($files as $name => $file)
            {
                if(!$file->isDir())
                {
                    $filePath = $file->getRealPath();
                    $relativePath = 'bleedlogs/'.substr($filePath, strlen($bleedlogs) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->addFile($clicklogs, 'clicklogs.txt');
            $zip->addFile($dayreport, 'dailyreports.txt');
            $zip->close();
        }
        catch(Exception $e){}
    }

    ziplogs($cid);

    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="'.$cid.'.zip"');
    readfile($filename);
    exit;

?>
