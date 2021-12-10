<?php

	require_once('DocpartTable.php');
	class DocpartGroup extends DocpartTable
	{
		public $id;
		public $value;
		public $count;
		public $level;
		public $parent;
		public $unblocked;
		public $for_guests;
		public $for_registrated;
		public $for_backend;
		public $description;
		public $order;
	}

?>