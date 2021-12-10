<?php

	require_once('DocpartTable.php');
	class DocpartOrder_Message extends DocpartTable
	{
		public $id;
		public $order_id;
		public $is_customer;
		public $text;
		public $time;
	}

?>