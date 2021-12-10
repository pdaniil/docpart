<?php
	require_once('DocpartTable.php');
	require_once('DocpartOffice_Geo.php');
	require_once('DocpartOffice_Storage.php');
	class DocpartOffice extends DocpartTable
	{
		public $id;
		public $caption;
		public $country;
		public $region;
		public $city;
		public $address;
		public $phone;
		public $email;
		public $coordinates;
		public $description;
		public $users;
		public $timetable;
		public $pay_system_id;
		public $pay_system_parameters;
		public $arr_geo_id;
		
		
		
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
			
			
			$office_geo = new DocpartOffice_Geo();
			$where_condition = '`office_id` = '. $this->id;
			$office_geo->getDataBySql('*', '`shop_offices_geo_map`', $file, 'node', 'geo', $where_condition );
			$where_condition = '`office_id` = '. $this->id.' GROUP BY `storage_id`';
			$office_storage = new DocpartOffice_Storage();
			$office_storage->getDataBySql('`storage_id`, `additional_time`', '`shop_offices_storages_map`', $file, 'storage', 'storages', $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
		
	}

?>