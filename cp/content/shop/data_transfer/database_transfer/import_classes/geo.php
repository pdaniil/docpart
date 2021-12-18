<?php 
	
	class geo extends base
	{
		public $table_name 	 = "shop_geo";
		
		public $SQL_truncate = "TRUNCATE TABLE `shop_geo`;";
		
		public $SQL_insert   = "INSERT INTO `shop_geo`(`id`, `count`, `level`, `value`, `parent`, `order`) VALUES (?,?,?,?,?,?);";
		
	}

	
?>