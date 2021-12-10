<?php
//Скрипт получения списка статусов заказов
defined('DOCPART_MOBILE_API') or die('No access');


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
	//Сессия есть - выполняем действия
	$statuses_list = array();
	
	array_push($statuses_list, array("id"=>0, "value"=>0, "caption"=>"Все", "name"=>"Все", "for_paid"=>0, "for_created"=>0, "color"=>"#FFF") );
	
	$statuses_list_query = $db_link->prepare('SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order`');
	$statuses_list_query->execute();
	while( $record = $statuses_list_query->fetch() )
	{
		array_push($statuses_list, array("id"=>$record["id"], "value"=>$record["id"], "caption"=>$record["name"], "name"=>$record["name"], "for_paid"=>$record["for_paid"], "for_created"=>$record["for_created"], "color"=>$record["color"]) );
	}
	
	$answer = array();
	$answer["status"] = true;
	$answer["statuses_list"] = $statuses_list;
	$answer["message"] = "ok";
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