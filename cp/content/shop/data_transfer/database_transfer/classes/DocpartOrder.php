<?php

	require_once('DocpartTable.php');
	require_once('DocpartOrder_Items.php');
	require_once('DocpartOrder_Log.php');
	require_once('DocpartOrder_Message.php');
	require_once('DocpartOrder_View.php');
	class DocpartOrder extends DocpartTable
	{
		public $id;
		public $user_id;
		public $session_id;
		public $time;
		public $successfully_created;
		public $status;
		public $paid;
		public $paid_time;
		public $how_get;
		public $how_get_json;
		public $office_id;
		public $phone_not_auth;
		public $email_not_auth;
		
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
			
			$items = new DocpartOrder_Items();
			$where_condition = ' `order_id` = '. $this->id;
			$items->getDataBySql('*', '`shop_orders_items`', $file, 'item', 'items', $where_condition );
			
			$items = new DocpartOrder_Log();
			$where_condition = ' `order_id` = '. $this->id;
			$items->getDataBySql('*', '`shop_orders_logs`', $file, 'log', 'logs', $where_condition );
			
			$messages = new DocpartOrder_Message();
			$where_condition = ' `order_id` = '. $this->id;
			$messages->getDataBySql('*', '`shop_orders_messages`', $file, 'message', 'messages', $where_condition );
			
			$messages = new DocpartOrder_View();
			$where_condition = ' `order_id` = '. $this->id;
			$messages->getDataBySql('`viewed_flag` , `user_id`', '`shop_orders_viewed`', $file, 'view', 'views', $where_condition );
			
			fwrite($file, '</'.$tagName.'>');
		}
	}

?>