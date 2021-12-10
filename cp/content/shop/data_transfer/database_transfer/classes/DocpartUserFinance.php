<?php

	require_once('DocpartTable.php');
	class DocpartUserFinance extends DocpartTable
	{
		public $id;
		public $user_id;
		public $time;
		public $income;
		public $amount;
		public $operation_code;
		public $active;
		public $pay_orders;
		public $tech_value_text;
		public $order_id;
		public $office_id;
	}

?>