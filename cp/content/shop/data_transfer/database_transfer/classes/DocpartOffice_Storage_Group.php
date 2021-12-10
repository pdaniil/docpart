<?php

	require_once('DocpartTable.php');
	require_once('DocpartOffice_Storage_Group_Markup.php');
	
	class DocpartOffice_Storage_Group extends DocpartTable
	{
		public $group_id;
	
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
			
			$where_condition = ' `group_id` = '.$this->group_id.' GROUP BY `group_id` ';
			$group = new DocpartOffice_Storage_Group_Markup();
			$group->getDataBySql(' `min_point`, `max_point`, `markup` ', ' `shop_offices_storages_map` ', $file, 'markup', 'markups', $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
	}
?>