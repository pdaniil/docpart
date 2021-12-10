<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
	$DP_Config = new DP_Config;//Конфигурация CMS

	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}	
	catch (PDOException $e) 
	{
		exit("No DB connect");
	}
	$db_link->query("SET NAMES utf8;");
	
	
	$storage_id = ;
	$product_id = ;
	$category_id = 63;
	$price = ;
	$price_purchase = ;
	$price_crossed_out = $record["price_crossed_out"];
	$exist = $record["exist"];
	$reserved = $record["reserved"];
	$issued = $record["issued"];
	$arrival_time = $record["arrival_time"];
	
	$SQL_SAVE = "INSERT INTO `shop_storages_data` (`storage_id`, `product_id`, `category_id`, `price`, `price_purchase`, `price_crossed_out`, `exist`, `reserved`, `issued`, `arrival_time`) VALUES (?,?,?,?,?,?,?,?,?,?);";
				
	$binding_values = array($storage_id, $product_id, $category_id, $price, $price_purchase, $price_crossed_out, $exist, $reserved, $issued, $arrival_time);

?>