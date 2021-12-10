<?php
/**
 * Скрипт для очередного шага общего алгоритма загрузки прайс-листа "Завершение работы"
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




//Получаем конфигурацию прайс-листа
$price_id = $_GET["price_id"];




if( $db_link->prepare("UPDATE `shop_docpart_prices` SET `last_updated` = ? WHERE `id` = ?;")->execute( array(time(), $price_id) ) != true)
{
    $answer = array();
    $answer["result"] = 0;
    $answer["message"] = "Ошибка записи времени обновления таблицы";
    exit(json_encode($answer));
}





$answer = array();
$answer["result"] = 1;
exit(json_encode($answer));
?>