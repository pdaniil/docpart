<?php
//ПОИСК ПО АРТИКУЛУ. Получение списка производителей по артикулу
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];
$query = json_decode($params["query"], true);
$article = $params["article"];

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
	if($query["is_cross_service"] == true)
	{
		$url = "/content/shop/docpart/ajax_getManufacturersListFromCrossServer.php";
	}
	else
	{
		$url = "/content/shop/docpart/ajax_getManufacturersList.php";
	}
	
	
	$request_object = array();
	$request_object["article"] = $article;
	
	
	//Вызываем тот же скрипт, что и в корзине на сайте
	$postdata = http_build_query(
		array(
			'query' => json_encode( $request_object ),
			'office_id' => $query["office_id"],
			'storage_id' => $query["storage_id"]
		)
	);//Аргументы
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path.$url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	$curl_result = json_decode($curl_result, true);
	
	
	
	//Обработать и отправить результат
	if( $curl_result["status"] == true )
	{
		//ВЫДАЕМ ОТВЕТ
		$answer = array();
		$answer["status"] = true;
		$answer["message"] = "Ok";
		$answer["handler_answer"] = $curl_result;
		$answer["query"] = $query;
		exit(json_encode($answer));
	}
	else
	{
		//Есть два варианта - читаемая ошибка и нечитаемая ошибка (когда не удалось получить JSON)
		if( $curl_result["message"] != NULL )
		{
			//ВЫДАЕМ ОТВЕТ - читаемая ошибка
			$answer = array();
			$answer["status"] = false;
			$answer["handler_answer"] = $curl_result;
			$answer["message"] = "Ошибка обработчика на сервере. Читаемая ошибка";
			$answer["readable_error"] = true;//Читаемая ошибка $curl_result
			$answer["query"] = $query;
			exit(json_encode($answer));
		}
		else
		{
			//ВЫДАЕМ ОТВЕТ - нечитаемая ошибка
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = "Ошибка обработчика на сервере. Не читаемая ошибка";
			$answer["readable_error"] = false;//Не читаемая ошибка
			$answer["query"] = $query;
			exit(json_encode($answer));
		}
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