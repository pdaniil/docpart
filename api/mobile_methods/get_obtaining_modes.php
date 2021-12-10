<?php
//Скрипт получения способов доставки
defined('DOCPART_MOBILE_API') or die('No access');


/*
Данный скрипт формирует перечень способов доставки, отображаемые для каждого способа виджеты, а также выдает данные по заказу.
*/


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
	
	$obtaining_modes = array();//Возвращаемый в ответе список с описанием
	
	$obtaining_modes_query = $db_link->prepare('SELECT * FROM `shop_obtaining_modes` WHERE `available` = 1 ORDER BY `order`;');
	$obtaining_modes_query->execute();
	while($obtaining_mode = $obtaining_modes_query->fetch() )
	{
		//Здесь должны отработать скрипты каждого способа доставки и сгенерировать JSON-описание виджетов, которые затем нужно будет отобразить в приложении
		$object = array();//Объект данного способа
		
		$object["id"] = $obtaining_mode["id"];
		$object["caption"] = $obtaining_mode["caption"];
		$object["widgets"] = array();
		
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/obtaining_modes/".$obtaining_mode["handler"]."/get_mobile_widgets.php");
		
		
		array_push($obtaining_modes, $object);
	}
	
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["obtaining_modes"] = $obtaining_modes;
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