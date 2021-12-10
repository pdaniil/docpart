<?php
/**
Серверный скрипт для сохранения изменений в поставках
*/
header('Content-Type: application/json;charset=utf-8;');
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	exit("Forbidden");
}
$user_id = DP_User::getAdminId();



//Исходные данные
$product_id = $_POST["product"];
$storage_id = $_POST["storage"];
$supplies_objects = json_decode($_POST["supplies_objects"], true);



for($i=0; $i < count($supplies_objects); $i++)
{
	$product_id = $supplies_objects[$i]["product_id"];
	$category_id = $supplies_objects[$i]["category_id"];
	$price = $supplies_objects[$i]["price"];
	$price_crossed_out = $supplies_objects[$i]["price_crossed_out"];
	$price_purchase = $supplies_objects[$i]["price_purchase"];
	$arrival_time = $supplies_objects[$i]["arrival_time"];
	$exist = $supplies_objects[$i]["exist"];
	$reserved = $supplies_objects[$i]["reserved"];
	$issued = $supplies_objects[$i]["issued"];
	
	
	if( $supplies_objects[$i]["id"] > 0 )
	{
		if( $db_link->prepare("UPDATE `shop_storages_data` SET `price` = ?, `price_crossed_out` = ?, `price_purchase` = ?, `arrival_time` = ?, `exist` = ?, `reserved` = ?, `issued` = ? WHERE `id` = ?;")->execute( array($price, $price_crossed_out, $price_purchase, $arrival_time, $exist, $reserved, $issued, $supplies_objects[$i]["id"]) ) != true)
		{
			$supplies_objects[$i]["no_error"] = 0;
		}
		else
		{
			$supplies_objects[$i]["no_error"] = 1;
			$supplies_objects[$i]["is_new"] = 0;//Указываем, что данная поставка была обновлена (UPDATE)
		}
	}
	else
	{
		if( $db_link->prepare("INSERT INTO `shop_storages_data` (`storage_id`, `product_id`, `category_id`, `price`, `price_crossed_out`, `price_purchase`, `arrival_time`, `exist`, `reserved`, `issued`) VALUES (?,?,?,?,?,?,?,?,?,?);")->execute( array($storage_id, $product_id, $category_id, $price, $price_crossed_out, $price_purchase, $arrival_time, $exist, $reserved, $issued) ) != true)
		{
			$supplies_objects[$i]["no_error"] = 0;
		}
		else
		{
			$supplies_objects[$i]["id"] = $db_link->lastInsertId();
			$supplies_objects[$i]["no_error"] = 1;
			$supplies_objects[$i]["is_new"] = 1;//Указываем, что данная поставка была создана (INSERT)
		}
		
	}
}






//Возвращаем результат
$answer["status"] = "ok";
$answer["supplies_objects"] = $supplies_objects;
exit(json_encode($answer));
?>