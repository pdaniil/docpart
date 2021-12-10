<?php
// Обработка уведомления от сервера ККТ

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
	$answer["message"] = 'Не удалось подключиться к локальной базе данных';//Текстовое сообщение
	return $answer;
}
$db_link->query("SET NAMES utf8;");


//...........................................................................................................................


$response = file_get_contents('php://input');


$f = fopen('logs/log_notification_'.date('Y_m', time()).'.txt', 'a');
fwrite($f, date("d.m.Y H:i:s", time())."\n");
fwrite($f, $response."\n\n\n");


$response = json_decode($response, true);

$status_sent = true;//Флаг отправки запроса
$status_approved = false;//Флаг печати чека
$message = '';

if(!empty($response['data']['id'])){
	$response_id = $response['data']['id'];
	
	$operation_query = $db_link->prepare("SELECT `id` FROM `shop_kkt_checks` WHERE `real_device_text_answer` LIKE ?;");
	$operation_query->execute(array('%('.$response_id.')%'));
	$record = $operation_query->fetch();
	$check_id = $record['id'];
	
	if(!empty($check_id)){
		
		switch($response['data']['status']){
			case '401' :
				$message = 'Требуется авторизация на сервере dreamkas. Проверьте Токен.';
				$status_sent = true;
				$status_approved = true;
			break;case 'SUCCESS' :
				$message = 'Готово. Фискализация произведена. Уникальный идентификатор задачи '. $response_id;
				$status_sent = true;
				$status_approved = true;
			break;
			case 'ERROR' :
				$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $response_id;
				$status_sent = true;
				$status_approved = false;
			break;
			default :
				$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
				$status_sent = false;
				$status_approved = false;
			break;
		}
		
		$db_link->prepare("UPDATE `shop_kkt_checks` SET `sent_to_real_device_flag` = ?, `real_device_approved_flag` = ? WHERE `id` = ? AND `real_device_approved_flag` = 0;")->execute( array($status_sent, $status_approved, $check_id) );
	}
}
?>