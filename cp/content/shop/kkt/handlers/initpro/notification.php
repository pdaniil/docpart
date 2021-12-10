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

switch($data['status']){
	case 'done' :
		$message = 'Фискализация произведена. Уникальный идентификатор выполненной задачи '. $data['uuid'];
		$status_approved = true;
	break;
	case 'fail' :
		$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['uuid'] .'. Полученная ошибка: '. $data['error']['text'];
		$status_approved = false;
	break;
	case 'wait' :
		$message = 'Ожидание в очереди на фискализацию. Уникальный идентификатор задачи '. $data['uuid'];
		$status_approved = false;
	break;
	default :
		$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
		$status_sent = false;
		$status_approved = false;
	break;
}

$db_link->prepare("UPDATE `shop_kkt_checks` SET `sent_to_real_device_flag` = ?, `real_device_approved_flag` = ?, `real_device_text_answer` = ? WHERE `id` = ? AND `real_device_approved_flag` = 0;")->execute( array($status_sent, $status_approved, $message, $data['external_id']) );

?>