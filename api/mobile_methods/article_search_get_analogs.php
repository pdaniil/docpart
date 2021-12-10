<?php
//ПОИСК ПО АРТИКУЛУ. Получение списка аналогов
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];
$article = $params["article"];
$manufacturer = $params["manufacturer"];


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
	//Сессия есть - Выполняем действие
	$search_object = array();
	$search_object["article"] = $article;
	$search_object["manufacturer"] = $manufacturer;
	
	//Вызываем тот же скрипт, что и в корзине на сайте
	$postdata = http_build_query(
		array(
			'search_object' => json_encode( $search_object )
		)
	);//Аргументы
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."/content/shop/docpart/ajax_getAnalogsList.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	$curl_result = json_decode($curl_result, true);
	
	
	
	//Обработать и отправить результат
	if( $curl_result["result"] == 1 )
	{
		//ВЫДАЕМ ОТВЕТ
		$answer = array();
		$answer["status"] = true;
		$answer["message"] = "Ok";
		$answer["handler_answer"] = $curl_result;
		exit(json_encode($answer));
	}
	else
	{
		//ВЫДАЕМ ОТВЕТ - нечитаемая ошибка
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Ошибка получения списка кроссов";
		exit(json_encode($answer));
	}
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No session";
	exit(json_encode($answer));
}
?>