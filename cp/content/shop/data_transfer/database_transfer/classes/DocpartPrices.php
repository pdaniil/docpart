<?php

	require_once('DocpartTable.php');
	class DocpartPrices extends DocpartTable
	{
		public $id;
		public $name;
		public $load_mode;
		public $ftp_host;
		public $ftp_user;
		public $ftp_password;
		public $sender_email;
		public $delete_email_messages;
		public $strings_to_left;
		public $manufacturer_col;
		public $article_col;
		public $name_col;
		public $exist_col;
		public $price_col;
		public $time_to_exe_col;
		public $min_order_col;
		public $storage_col;
		public $last_updated;
		public $clean_before;
		public $file_name_substring;
	}

?>