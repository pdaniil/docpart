<?php
//Серверный скрипт для выполнения SAO-действий
header('Content-Type: application/json;charset=utf-8;');

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

//-----------------------------------------------------------------------------------------------

//Получаем исходные данные:
$order_item_id = $_GET["order_item_id"];
$sao_action_id = $_GET["sao_action_id"];
$key = $_GET["key"];

//-----------------------------------------------------------------------------------------------

//Проверяем право на запуск:
if( $key != $DP_Config->tech_key )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Wrong key";
	exit(json_encode($answer));
}

//-----------------------------------------------------------------------------------------------

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

//-----------------------------------------------------------------------------------------------

//Выполняем действие
//1. Получаем имя скрипта действия и необходимые настройки для его выполнения
$script_query = $db_link->prepare("SELECT `script`, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = (SELECT `t2_storage_id` FROM `shop_orders_items` WHERE `id` = ? ) ) ) AS `handler_folder`, (SELECT `connection_options` FROM `shop_storages` WHERE `id` = (SELECT `t2_storage_id` FROM `shop_orders_items` WHERE `id` = ?) ) AS `connection_options` FROM `shop_sao_actions` WHERE `id` = ?;");
$script_query->execute( array($order_item_id, $order_item_id, $sao_action_id) );
$script_record = $script_query->fetch();
$script_path = "/content/shop/docpart/suppliers_handlers/".$script_record["handler_folder"]."/".$script_record["script"];
$connection_options = json_decode($script_record["connection_options"], true);//Настройки подключения к поставщику

// -----

//2. Получаем данные позиции заказа
$order_item_query = $db_link->prepare("SELECT * FROM `shop_orders_items` WHERE `id` = ?;");
$order_item_query->execute( array($order_item_id) );
$order_item = $order_item_query->fetch();

// -----

//3. Вызываем скрипт действия
require_once($_SERVER["DOCUMENT_ROOT"].$script_path);

// -----

//4. Анализируем результат его выполнения и отвечаем инициатору
if($sao_result["status"])
{
	$answer = array();
	$answer["status"] = true;
	$answer["order_item_id"] = $order_item_id;
	//$answer["sao_action_id"] = $sao_action_id;
	//$answer["script_path"] = $script_path;
	//$answer["sao_action_message"] = $sao_result["message"];
	exit(json_encode($answer));
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["order_item_id"] = $order_item_id;
	//$answer["sao_action_id"] = $sao_action_id;
	//$answer["script_path"] = $script_path;
	$answer["sao_action_message"] = $sao_result["message"];
	exit(json_encode($answer));
}
?>