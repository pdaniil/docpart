<?php
//Скрипт для метода авторизации
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$password = $params["password"];


$check_user_query = $db_link->prepare('SELECT * FROM `users` WHERE `main_field`=? AND `password`=?;');
$check_user_query->execute( array($login, md5($password.$DP_Config->secret_succession)) );
$autentification_array = $check_user_query->fetch();
if( $autentification_array == false )
{
	//НЕ ПРАВИЛЬНЫЕ ЛОГИН ИЛИ ПАРОЛЬ
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Wrong login or password";
	exit(json_encode($answer));
}
else//Успешная аутентификация
{
	//Определяем id пользователя:
	$user_id = $autentification_array["user_id"];
	$time = time();
	
	$session_succession = md5($login.$time.$DP_Config->secret_succession);//Код сессии - собираем его из логина, текущего дампа времени и секретной последовательности
	
	//Записываем сеcсию в БД
	$db_link->prepare('INSERT INTO `sessions` (`session`, `user_id`, `time`, `data`) VALUES (?, ?, ?, ?);')->execute( array($session_succession, $user_id, $time, '') );
	
	//УСПЕШНАЯ АУТЕНТИФИКАЦИЯ
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "Success";
	$answer["shop_currency"] = $DP_Config->shop_currency;
	$answer["session"] = $session_succession;
	exit(json_encode($answer));
}
?>