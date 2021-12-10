<?php

	require_once('DocpartTable.php');
	class DocpartCarts_Detail extends DocpartTable
	{
		public $id;
		public $cart_record_id;
		public $office_id;
		public $storage_id;
		public $storage_record_id;
		public $count_reserved;
		public $price_purchase;
	}

?>