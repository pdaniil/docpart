<?php
	
	require_once('DocpartTable.php');
	class DocpartUser_Profile extends DocpartTable
	{
		public $data_key;
		public $data_value;
		
		public function printTag($tagName, $file)
		{
			//$class_fields = get_class_vars(get_class($this));
			//fwrite($file, '<'.$tagName.'>');
			//foreach($class_fields as $key => $value)
			//{
				fwrite($file, '<'.$this->data_key.'>');
				fwrite($file, $this->data_value);
				fwrite($file, '</'.$this->data_key.'>');
			//}
			
			//fwrite($file, '</'.$tagName.'>');
		}
	}
?>