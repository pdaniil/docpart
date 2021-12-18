<?php 
	
	class sms extends base
	{
		public $table_name = "sms_api";
		
		public $SQL_update = "UPDATE `sms_api` SET `parameters_values` = ?, `active` = 1 WHERE `handler` = ?;";
		
	}

	
?>