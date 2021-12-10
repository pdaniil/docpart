<?php
	
	require_once('DocpartTable.php');
	class DocpartConfig extends DocpartTable
	{
		public $site_name;
		public $domain_path;
		public $backend_dir;
		public $list_page_limit;
		public $min_password_len;
		public $description_tag;
		public $keywords_tag;
		public $show_page_title;
		public $show_site_name;
		public $page_title_first;
		public $secret_succession;
		public $from_name;
		public $from_email;
		public $smtp_mode;
		public $smtp_encryption;
		public $smtp_host;
		public $smtp_port;
		public $smtp_username;
		public $smtp_password;
		public $products_count_for_page;
		public $product_url;
		public $products_table_mode;
		public $shop_currency;
		public $currency_show_mode;
		public $price_rounding;
		public $ucats_login;
		public $ucats_password;
		public $ucats_shiny;
		public $ucats_disks;
		public $ucats_accessories;
		public $ucats_to;
		public $ucats_oil;
		public $ucats_akb;
		public $ucats_caps;
		public $ucats_bolty;
		public $catalogue_html_way;
		public $levam_code;
		public $partial_payment;
		public $partial_payment_min_percent;
		public $client_overdraft;
		public $client_overdraft_value;
		public $order_without_auth;
		public $prices_email_server;
		public $prices_email_encryption;
		public $prices_email_port;
		public $prices_email_username;
		public $prices_email_password;
		public $wholesaler;
		
		function __construct() {
			
			global $DP_Config;
			$class_fields = get_class_vars(get_class($this));
			$arr = [];
			foreach($class_fields as $key => $value)
			{	
				$this->{$key} = $DP_Config->{$key};
				
			}
		}
	}
	
?>