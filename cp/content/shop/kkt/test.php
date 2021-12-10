<?php
// Скрипт тестирования работы функционала

exit;

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = 'Не соединения с БД. Чек не создан';
	exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

	
	
if(1){	
	
	// Создание и печать чека ККТ после онлайн платежа
	$operation_id = 13;
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/inc_create_check_for_online_pay.php");
	
}else{
	// Печать чека ККТ созданного вручную
	$check_id = 37;

	$_POST["check_object"] = '{"check_type":"usual","products":[{"local_id":1,"name":"KNECHT OC247. Фильтр масляный","price":366.7,"count":1,"tag_1199":"1","tag_1214":"1","tag_1212":"1","order_item_id":2},{"local_id":2,"name":"UFI 2324900. Фильтр масляный","price":202.5,"count":1,"tag_1199":"1","tag_1214":"1","tag_1212":"1","order_item_id":3},{"local_id":3,"name":"KS 50013139. Фильтр масляный","price":211.1,"count":1,"tag_1199":"1","tag_1214":"1","tag_1212":"1","order_item_id":4}],"payments":[{"local_id":1,"type_tag":"1","amount":780.3}],"tag_1054":{"value":"1","for_print":"Приход"},"tag_1055":{"value":"0","for_print":"ОСН"},"kkt":{"id":"1","for_print":"Основная"},"customer_contact":""}';

	//Далее идет создание чека и его отправка.
	$check_object = json_decode($_POST["check_object"], true);
	if( $check_object == null )
	{
		//Обработка и выдача результата
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Ошибка json_decode. Чек не создан';
		exit(json_encode($answer));
	}

	//Подключаем класс ККТ
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/docpart_kkt.php");

	$DocpartKKT = new DocpartKKT($check_object["kkt"]["id"]);//Создаем объект ККТ
	if( $DocpartKKT->kkt_ready )
	{
		//Если чек успешно создан - отправляем его в реальную кассу
		
		$send_check_result = $DocpartKKT->send_check($check_id);

		if( $send_check_result["status_sent"] == true && $send_check_result["status_approved"] == true )
		{
			//Обработка и выдача результата - ПОЛНЫЙ УСПЕХ
			$answer = array();
			$answer["status"] = true;
			$answer["message"] = '';
			exit(json_encode($answer));
		}
		else
		{
			//Обработка и выдача результата
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = 'Чек записан в локальную базу данных (ID '.$check_id.'), но, возникла ошибка: '.$send_check_result["message"];
			exit(json_encode($answer));
		}
	}
}
?>