<?php
/**
Серверный скрипт для получения записей поставок.
Аргументы:
- ID склада
- ID товара

Ответ:
Массив записей поставок
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




//ИСХОДНЫЕ ДАННЫЕ:
$storages = json_decode($_POST["storages"], true);
$products = json_decode($_POST["products"], true);


$answer = array();//Объект ответа
$answer["storages"] = array();



for($s = 0; $s < count($storages); $s++)
{
	$storage_id = $storages[$s]["id"];
	
	$answer["storages"][$storage_id] = array();
	
	
	for($p=0; $p < count($products); $p++)
	{
		$product_id = $products[$p];
		
		$supplies_query = $db_link->prepare("SELECT * FROM `shop_storages_data` WHERE `product_id` = ? AND storage_id = ?;");
		$supplies_query->execute( array($product_id, $storage_id) );
		while($supply = $supplies_query->fetch() )
		{
			array_push($answer["storages"][$storage_id], $supply);
		}
	}
}



$answer["status"] = "ok";
/*
$log = fopen("log.txt", "w");
fwrite($log, print_r($answer, true));
fclose($log);
*/

exit(json_encode($answer));
?>