<?php

	require_once('DocpartTable.php');
	require_once('DocpartOrder_Items_Detail.php');
	class DocpartOrder_Items extends DocpartTable
	{
		public $id;
		public $order_id;
		public $product_type;
		public $price;
		public $count_need;
		public $product_id;
		public $status;
		public $t2_manufacturer;
		public $t2_article;
		public $t2_article_show;
		public $t2_name;
		public $t2_exist;
		public $t2_time_to_exe;
		public $t2_time_to_exe_guaranteed;
		public $t2_storage;
		public $t2_min_order;
		public $t2_probability;
		public $t2_markup;
		public $t2_price_purchase;
		public $t2_office_id;
		public $t2_storage_id;
		public $t2_product_json;
		public $t2_json_params;
		public $sao_state;
		public $sao_robot;
		public $sao_state_object;
		public $sao_message;
		
		public function printTag($tagName, $file)
		{
			$class_fields = get_class_vars(get_class($this));
			fwrite($file, '<'.$tagName.'>');
			foreach($class_fields as $key => $value)
			{
				fwrite($file, '<'.$key.'>');
				fwrite($file, $this->{$key});
				fwrite($file, '</'.$key.'>');
			}
			
			if ($this->product_type == '1')
			{
				$detail = new DocpartOrder_Items_Detail();
				$where_condition = ' `order_id` = '.$this->order_id.' AND `order_item_id` = '.$this->id;
				$detail->getDataBySql('*', 'shop_orders_items_details', $file, 'detail', 'details', $where_condition);
			}
			
			fwrite($file, '</'.$tagName.'>');
		}
	}

?>