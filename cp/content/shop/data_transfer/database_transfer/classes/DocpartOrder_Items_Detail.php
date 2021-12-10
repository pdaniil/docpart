<?php

	require_once('DocpartTable.php');
	class DocpartOrder_Items_Detail extends DocpartTable
	{
		public $id;
		public $order_id;
		public $order_item_id;
		public $office_id;
		public $storage_id;
		public $storage_record_id;
		public $count_reserved;
		public $count_issued;
		public $count_canceled;
		public $price_purchase;
	}

?>