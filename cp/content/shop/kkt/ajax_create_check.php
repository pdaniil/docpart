<?php
//Серверный скрипт для создания чека. Его вызывает модальное окно через AJAX.
header('Content-Type: application/json;charset=utf-8;');
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

if($_POST["initiator"] == 'php'){
	// Запрос пришел от скрипта /content/shop/finance/pay_for_order.php после онлайн платежа
	if($_POST["key"] !== $DP_Config->tech_key){
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Нет доступа. Чек не создан';
		exit(json_encode($answer));
	}
}else{
	//Проверяем доступ в панель управления
	if( ! DP_User::isAdmin())
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Нет доступа. Чек не создан';
		exit(json_encode($answer));
	}
}



//Здесь можно реализовать дополнительные ограничения на вызов данного скрипта - например, проверять, является ли пользователь менеджером магазина и т.д. Это уже будет не стандартный вариант, поэтому, адаптируется персонально под каждый сайт.



/*
$f = fopen('log_check_object.txt', 'w');
fwrite($f, $_POST["check_object"]);
$answer = array();
$answer["status"] = false;
$answer["message"] = 'Ошибка json_decode. Чек не создан';
exit(json_encode($answer));
*/



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
	if( $check_object["check_type"] == "usual" )
	{
		//Создание обычного чека
		$create_check_result = $DocpartKKT->create_check( $check_object );//Передаем данные для создаваемого чека
	}
	else if($check_object["check_type"] == "correction")
	{
		//Создание чека коррекции
		$create_check_result = $DocpartKKT->create_check_of_correction( $check_object );//Передаем данные для создаваемого чека
	}
	else
	{
		//Обработка и выдача результата
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Тип чека не указан. Чек не создан';
		exit(json_encode($answer));
	}
	
	//Если чек успешно создан - отправляем его в реальную кассу
	if( $create_check_result["status"] == true )
	{
		$check_id = $create_check_result["check_id"];
		
		$send_check_result = $DocpartKKT->send_check($check_id, $_POST["initiator"]);

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
	else
	{
		//Обработка и выдача результата
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Ошибка записи чека в базу данных. Чек не создан. '.$create_check_result["message"];
		exit(json_encode($answer));
	}
}
else
{
	//Обработка и выдача результата
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = $DocpartKKT->kkt_ready_error_message.'. Не удалось создать объект ККТ. Чек не создан';
	exit(json_encode($answer));
}
?>