<?php

	require_once('DocpartTable.php');
	class DocpartOrdersStatusesRef extends DocpartTable
	{
		public $id;
		public $name;
		public $color;
		public $for_paid;
		public $for_created;
		public $order;
		public $to_manager_email;
		public $to_manager_sms;
		public $to_customer_email;
		public $to_customer_sms;
	}

?>