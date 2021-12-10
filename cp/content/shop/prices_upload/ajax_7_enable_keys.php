<?php
/**
 * Скрипт для включения индексов в таблице прайс-листов. Этот скрипт необходимо выполнить, если импорт CSV не успел выполниться (timeout php)
*/
header('Content-Type: application/json;charset=utf-8;');

//Конфигурация Treelax
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
    $answer["result"] = 0;
    $answer["message"] = "Не ошибка подключния к основной БД";
    exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");




if( $db_link->prepare("ALTER TABLE `shop_docpart_prices_data` ENABLE KEYS;")->execute() != true)
{
    $answer = array();
    $answer["result"] = 0;
    $answer["message"] = "Ошибка включения индексов";
    exit(json_encode($answer));
}





$answer = array();
$answer["result"] = 1;
exit(json_encode($answer));
?>