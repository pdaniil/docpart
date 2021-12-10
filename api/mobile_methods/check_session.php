<?php
//Скрипт для метода проверки сессии
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
	$answer = array();
	$answer["status"] = true;
	$answer["shop_currency"] = $DP_Config->shop_currency;
	$answer["message"] = "Session is active";
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