<?php
	class offices_geo extends base
	{
		public $table_name = 'offices_geo';
		public $SQL_truncate = "TRUNCATE TABLE `shop_offices_geo_map`;";
		public $SQL_insert = "INSERT INTO `shop_offices_geo_map`(`geo_id`, `office_id`) VALUES (?, ?);";
	}
?>