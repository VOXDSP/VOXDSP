<?php
	
	require_once "config.php";

	$logfile = 'paypal-ipn-ledger.txt';
 

	//Terminate if IPN is not from notify.paypal.com
	if(gethostbyaddr(getIP()) != "notify.paypal.com")
	{
		file_put_contents($logfile, "[".date("Y-m-d H:i:s")."] !!! WARNING !!! INVALID IPN HOST: ".gethostbyaddr(getIP())."\n", FILE_APPEND | LOCK_EX);
		exit;
	}

	//Get remote ip
	function getIP()
	{
		if(!empty($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}

	///Terminate Execution
	function done()
	{
		//Finish
		header("Status: 200");
		exit;
	}
	
	//Bail if this is not an IPN
	if(!isset($_POST['txn_id']))
	{
		file_put_contents($logfile, "[".date("Y-m-d H:i:s")."] !!! WARNING !!! INVALID IPN FROM: ".gethostbyaddr(getIP())."\n", FILE_APPEND | LOCK_EX);
		exit;
	}
	file_put_contents($logfile, "[".date("Y-m-d H:i:s")."] STARTED PROCESSING: ".$_POST['txn_id']." FOR: ".gethostbyaddr(getIP())."\n", FILE_APPEND | LOCK_EX);
	
	//Check we havent already processed this id
	if($redis->setNx("paypal-".$_POST['txn_id'], "n") == FALSE)
	{
		file_put_contents($logfile, "FAIL: IPN ALREADY PROCESSED\n", FILE_APPEND | LOCK_EX);
		done();
	}
	
	//Get IPN data
	$data = file_get_contents("php://input");
	
	//Is the transaction completed?
	if($_POST['payment_status'] != "Completed")
	{
		file_put_contents($logfile, "FAIL: INCOMPLETE PAYMENT\n", FILE_APPEND | LOCK_EX);
		done();
	}
	
	//Is this the correct receiver?
	if($_POST['receiver_email'] != $paypalemail)
	{
		file_put_contents($logfile, "FAIL: INCORRECT PAYEE EMAIL: ".$_POST['receiver_email'] ."\n", FILE_APPEND | LOCK_EX);
		done();
	}
	
	//Is this the correct currency?
	if($_POST['mc_currency'] != "USD")
	{
		file_put_contents($logfile, "FAIL: INCORRECT CURRENCY\n", FILE_APPEND | LOCK_EX);
		done();
	}

	//log it in the ledger
	file_put_contents('ledgers/' . $_POST['custom'] . '_ledger.log', date('Y-m-d H:i:s').",PayPal,".$_POST['mc_gross']."\n", LOCK_EX | FILE_APPEND);

	//Increment account balance
	$mysql->query("UPDATE account SET credit=credit+" . $mysql->real_escape_string($_POST['mc_gross']) . " WHERE id=" . $mysql->real_escape_string($_POST['custom']));

	//Done
	file_put_contents($logfile, "SUCCESS: PAYMENT PROCESSED.\n", FILE_APPEND | LOCK_EX);
	done();
	
?>




