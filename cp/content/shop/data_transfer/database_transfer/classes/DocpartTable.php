<?php 

	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		$result["status"] = false;
		$result["message"] = "DB connect error";
		$result["code"] = 502;
		exit(json_encode($result));
	}
	$db_link->query("SET NAMES utf8;");

	class DocpartTable {
		
		public $percent;
		
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
			fwrite($file, '</'.$tagName.'>');
		}
		
		public function getDataBySql($needed, $table , $file, $tagName, $parent_tag = null, $where_condition = 1)
		{
			global $db_link;
			
			if (!is_null($parent_tag))
			{
				fwrite($file, '<'.$parent_tag.'>');
			}
				
			$SQL_select = "SELECT ".$needed." FROM ".$table." WHERE ".$where_condition;
			$query = $db_link->prepare($SQL_select);
			$query->execute();
			while($result = $query->fetch())
			{
				$class_fields = get_class_vars(get_class($this));
				
				foreach($class_fields as $key => $value)
					$this->{$key} = htmlspecialchars($result[$key]);
					
				$this->printTag($tagName, $file);
			}
			
			if (!is_null($parent_tag))
			{
				fwrite($file, '</'.$parent_tag.'>');
			}
		}
		
	}
?>