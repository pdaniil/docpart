<?php
	require_once('DocpartTable.php');
	
	class DocpartNotification extends DocpartTable
	{
		public $name;
		public $email_subject;
		public $email_body;
		public $sms_body;
		public $email_on;
		public $sms_on;
		
		const PERCENT_VALUE = 20;
		
	}
?>