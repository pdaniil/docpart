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


$data = file_get_contents('php://input');


$f = fopen('logs/log_notification_'.date('Y_m', time()).'.txt', 'a');
fwrite($f, date("d.m.Y H:i:s", time())."\n");
fwrite($f, $data."\n\n\n");


$data = json_decode($data, true);


$check_id = $data['external_id'];
$status_sent = true;//Флаг отправки запроса
$status_approved = false;//Флаг печати чека
$message = '';

switch($data['state']){
	case 'done' :
		$message = 'Фискализация произведена. Уникальный идентификатор выполненной задачи '. $data['id'];
		$status_approved = true;
	break;
	case 'error' :
		$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['id'] .'. Полученная ошибка: '. $data['error_description'];
		$status_approved = false;
	break;
	default :
		$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.'.'. Полученная ошибка: '. $data['error_description'];
		$status_sent = false;
		$status_approved = false;
	break;
}

$db_link->prepare("UPDATE `shop_kkt_checks` SET `sent_to_real_device_flag` = ?, `real_device_approved_flag` = ?, `real_device_text_answer` = ? WHERE `id` = ? AND `real_device_approved_flag` = 0;")->execute( array($status_sent, $status_approved, $message, $data['external_id']) );

?>