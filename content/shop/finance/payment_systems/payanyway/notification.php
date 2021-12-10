<?php
header('Content-Type: text/html; charset=utf-8');

//Соединение с БД
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
	$answer["result"] = false;
	exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");



$operation_id = $_POST['MNT_TRANSACTION_ID'];


//Общий скрипт получения настроек платежной системы.
require_once( $_SERVER['DOCUMENT_ROOT'].'/content/shop/finance/get_pay_system_parameters.php' );


if($paysystem_parameters["test_mode"] == 1)
{
	$test = "1";
}
else
{
	$test = "0";
}

$mnt_id_my = $paysystem_parameters["mnt_id"];
$mnt_signature_my = $paysystem_parameters["mnt_signature"];

//Принято от payanyway
$MNT_ID = $_POST['MNT_ID'];
$MNT_TRANSACTION_ID = $_POST['MNT_TRANSACTION_ID'];
$MNT_OPERATION_ID = $_POST['MNT_OPERATION_ID'];
$MNT_AMOUNT = $_POST['MNT_AMOUNT'];
$MNT_CURRENCY_CODE = $_POST['MNT_CURRENCY_CODE'];
$MNT_SUBSCRIBER_ID = $_POST['MNT_SUBSCRIBER_ID'];
$MNT_TEST_MODE = $_POST['MNT_TEST_MODE'];
$MNT_SIGNATURE = $_POST['MNT_SIGNATURE'];// Проверочный код присланный системой

// Формируем проверочный код
$key_signature = MD5($mnt_id_my . $MNT_TRANSACTION_ID . $MNT_OPERATION_ID . $MNT_AMOUNT . $MNT_CURRENCY_CODE . $MNT_SUBSCRIBER_ID . $test . $mnt_signature_my);

//Код проверки не подходит
if($key_signature !== $MNT_SIGNATURE)
{
	exit('SUCCESS');
}

//Активируем операцию:
$update_query = $db_link->prepare('UPDATE `shop_users_accounting` SET `active` = 1 WHERE `id` = ? AND `user_id` =? AND `amount` = ?;');
$update_query->execute( array($MNT_TRANSACTION_ID, $MNT_SUBSCRIBER_ID, $MNT_AMOUNT) );	


// -----
//Уведомление менеджерам магазинов
$operation_id = $MNT_TRANSACTION_ID;
$amount = $MNT_AMOUNT;
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_notify.php");
// -----


// -----
//Вызов протокола оплаты заказа, если в операцию был вписан номер заказа
$operation_id = $MNT_TRANSACTION_ID;
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_for_order.php");
// -----



// Сообщаем системе что мы приняли ее запрос
echo 'SUCCESS';
?>