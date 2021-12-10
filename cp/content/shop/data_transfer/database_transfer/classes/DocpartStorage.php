<?php

	require_once('DocpartTable.php');
	require_once('DocpartStorage_Handler.php');
	class DocpartStorage extends DocpartTable
	{
		public $id;
		public $name;
		public $interface_type;
		public $users;
		public $connection_options;
		public $currency;
		public $short_name;
		
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
			
			$handler = new DocpartStorage_Handler();
			$where_condition = ' `id` = '.$this->interface_type;
			$handler->getDataBySql('`handler_folder`', '`shop_storages_interfaces_types`', $file, 'handler', null, $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
		
	}
	

?>