<?php
/**
Серверный скрипт для удаления записи поставки
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
$product_is = $_POST["product"];
$storage_id = $_POST["storage"];
$supplies_ids = json_decode($_POST["supplies"], true);

$supplies_ids_str = "";
$binding_values = array();
for( $i=0 ; $i < count($supplies_ids) ; $i++ )
{
	if( $i > 0 )
	{
		$supplies_ids_str = $supplies_ids_str.",";
	}
	$supplies_ids_str = $supplies_ids_str."?";
	array_push($binding_values, $supplies_ids[$i]);
}




if( $db_link->prepare("DELETE FROM shop_storages_data WHERE id IN ($supplies_ids_str);")->execute($binding_values) != true )
{
	$status = false;
}
else
{
	$status = true;
}


//Возвращаем результат
$answer["status"] = $status;
exit(json_encode($answer));
?>