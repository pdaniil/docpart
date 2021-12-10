<?php

	require_once('DocpartTable.php');
	require_once('DocpartOffice_Storage_Group.php');
	
	class DocpartOffice_Storage extends DocpartTable
	{
		public $storage_id;
		public $additional_time;
		
		
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
			
			$where_condition = ' `storage_id` = '.$this->storage_id.' GROUP BY `group_id`';
			$group = new DocpartOffice_Storage_Group();
			$group->getDataBySql('`group_id`', '`shop_offices_storages_map`', $file, 'group', 'groups', $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
		
	}

?>