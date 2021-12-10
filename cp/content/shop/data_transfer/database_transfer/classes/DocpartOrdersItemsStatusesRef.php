<?php

	require_once('DocpartTable.php');
	class DocpartOrdersItemsStatusesRef extends DocpartTable
	{
		public $id;
		public $name;
		public $color;
		public $for_created;
		public $order;
		public $count_flag;
		public $issue_flag;
		public $to_manager_email;
		public $to_manager_sms;
		public $to_customer_email;
		public $to_customer_sms;
	}