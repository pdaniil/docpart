<?php

	require_once('DocpartTable.php');
	require_once('DocpartManufacturers_Synonym.php');
	class DocpartManufacturers extends DocpartTable
	{
		public $id;
		public $name;
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
			
			$synonyms = new DocpartManufacturers_Synonym();
			$where_condition = '`manufacturer_id` = '. $this->id;
			$synonyms->getDataBySql('`synonym`', 'shop_docpart_manufacturers_synonyms', $file, 'synonyms', 'synonyms', $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
		
	}

?>