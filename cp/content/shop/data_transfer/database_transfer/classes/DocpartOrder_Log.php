<?php

	require_once('DocpartTable.php');
	class Docpartorder_Log extends DocpartTable
	{
		public $id;
		public $order_id;
		public $time;
		public $user_id;
		public $is_manager;
		public $text;
		public $is_robot;
	}

?>