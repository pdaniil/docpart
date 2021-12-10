<?php

	require_once('DocpartTable.php');
	class DocpartRegFields extends DocpartTable
	{
		public $record_id;
		public $main_flag;
		public $name;
		public $caption;
		public $show_for;
		public $required_for;
		public $maxlen;
		public $regexp;
		public $widget_type;
		public $widget_options;
		public $example;
		public $order;
		public $to_filter;
		public $to_users_table;
		
	}

?>