<?php

	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		$result["status"] = false;
		$result["message"] = "DB connect error";
		$result["code"] = 502;
		exit(json_encode($result));
	}
	$db_link->query("SET NAMES utf8;");
	
	for ($i = 1; $i< 20000; $i++)
	{
		
		$SQL_insert = "INSERT INTO `users`(`reg_variant`, `email`, `email_confirmed`, `email_new`, `email_code`, `email_code_expired`, `email_code_attempts`, `email_code_send_lock_expired`, `phone`, `phone_confirmed`, `phone_new`, `phone_code`, `phone_code_expired`, `phone_code_attempts`, `phone_code_send_lock_expired`, `password`, `unlocked`, `time_registered`, `time_last_visit`, `admin_created`, `forgot_password_time`, `forgot_password_code`, `ip_address`) VALUES (".$i.",'email".$i."',1,1,1,1,1,1,895".$i.",1,1,1,1,1,1,1,1,1,1,1,1,1,1)";
	
		$query = $db_link->prepare($SQL_insert);
		$query->execute();
	}
	

?>