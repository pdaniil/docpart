<?php 
	
	class notifications extends base
	{
		public $table_name = "notifications_settings";
		
		public $SQL_update = "UPDATE `notifications_settings` SET `email_subject` = ?, `email_body` = ?, `sms_body` = ?, `email_on` = ?, `sms_on` = ? WHERE `name` = ?;";
		
	}

	
?>