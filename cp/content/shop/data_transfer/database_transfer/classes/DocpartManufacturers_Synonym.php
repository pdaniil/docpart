<?php

	require_once('DocpartTable.php');
	class DocpartManufacturers_Synonym extends DocpartTable
	{
		public $synonym;
		public function printTag($tagName, $file)
		{
			$class_fields = get_class_vars(get_class($this));
			//fwrite($file, '<'.$tagName.'>');
			foreach($class_fields as $key => $value)
			{
				fwrite($file, '<'.$key.'>');
				fwrite($file, $this->{$key});
				fwrite($file, '</'.$key.'>');
			}
			//fwrite($file, '</'.$tagName.'>');
		}
	}

?>