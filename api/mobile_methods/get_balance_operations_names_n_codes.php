<?php
//Скрипт для метода получения возможных наименований финансовых операций
defined('DOCPART_MOBILE_API') or die('No access');


//Общая информация по заказам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];

//Сначала проверяем наличие такого пользователя
$user_query = $db_link->prepare('SELECT `user_id` FROM `users` WHERE `main_field` = ?;');
$user_query->execute( array($login) );
$user_record = $user_query->fetch();
if( $user_record == false )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "User not found";
	exit(json_encode($answer));
}

$user_id = $user_record["user_id"];

//Теперь проверяем наличие сессии
$session_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `user_id` = ? AND `session` = ?;');
$session_query->execute( array($user_id, $session) );
if( $session_query->fetchColumn() > 0 )
{
	//Сессия есть - выполняем действие
	
	$accounting_name_vars = array();
	array_push($accounting_name_vars, array("value"=>-1, "caption" => "Все") );
	
	$accounting_name_query = $db_link->prepare('SELECT * FROM `shop_accounting_codes` ORDER BY `id`;');
	$accounting_name_query->execute();
	while( $accounting_name = $accounting_name_query->fetch() )
	{
		array_push($accounting_name_vars, array("value"=>$accounting_name["id"], "caption" => $accounting_name["name"]) );
	}
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["accounting_name_vars"] = $accounting_name_vars;
	$answer["message"] = "Balance operations_names_n_codes ok!";
	exit(json_encode($answer));
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No session";
	exit(json_encode($answer));
}
?>