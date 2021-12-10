<?php
//Скрипт для метода получения данных одного заказа
defined('DOCPART_MOBILE_API') or die('No access');


//Общая информация по заказам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];
$order_id = (int)$params["order_id"];

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
	//Сессия есть - проверяем, его ли заказ
	$check_order_query = $db_link->prepare('SELECT `user_id` FROM `shop_orders` WHERE `id` = ?;');
	$check_order_query->execute( array($order_id) );
	$check_order_record = $check_order_query->fetch();
	if( $check_order_record == false )
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Order not found";
		exit(json_encode($answer));
	}
	if( $check_order_record["user_id"] != $user_id )
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Owner error";
		exit(json_encode($answer));
	}
	
	
	//Заказ есть и это его заказ - выдаем данные
	$order = array();
	
	//Заказ
	$order_query = $db_link->prepare("SELECT *, (SELECT `name` FROM `shop_orders_statuses_ref` WHERE `id` = `shop_orders`.`status`) AS `status_name` FROM `shop_orders` WHERE `id` = ?");
	$order_query->execute( array($order_id) );
	$order_record = $order_query->fetch();
	$order["id"] = $order_record["id"];
	$order["time"] = $order_record["time"];
	$order["time_str"] = date("G:i", $order_record["time"]);
	$order["date_str"] = date("d.m.Y", $order_record["time"]);
	$order["status"] = $order_record["status"];
	$order["status_name"] = $order_record["status_name"];
	$order["paid"] = $order_record["paid"];
	$order["paid_name"] = $order_record["paid"] ? "Оплачен":"Не оплачен";
	$order["office"] = array();
	
	
	//Способ получения
	$order["obtaining_details"] = array();//Набор виджетов для отображения способов получения
	$how_get = json_decode($order_record["how_get_json"], true);
	$obtaining_handler_query = $db_link->prepare('SELECT * FROM `shop_obtaining_modes` WHERE `id` = ?');
	$obtaining_handler_query->execute( array($how_get["mode"]) );
	$obtaining_handler_record = $obtaining_handler_query->fetch();
	$order["obtaining_mode_name"] = $obtaining_handler_record["caption"];
	require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/obtaining_modes/".$obtaining_handler_record["handler"]."/get_details_widgets.php");
	
	
	//Офис
	$office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
	$office_query->execute( array($order_record["office_id"]) );
	$office_record = $office_query->fetch();
	$order["office"]["id"] = $office_record["id"];
	$order["office"]["caption"] = $office_record["caption"];
	$order["office"]["country"] = $office_record["country"];
	$order["office"]["region"] = $office_record["region"];
	$order["office"]["city"] = $office_record["city"];
	$order["office"]["address"] = $office_record["address"];
	$order["office"]["phone"] = $office_record["phone"];
	$order["office"]["email"] = $office_record["email"];
	$order["office"]["coordinates"] = $office_record["coordinates"];
	$order["office"]["description"] = $office_record["description"];
	$order["office"]["timetable"] = $office_record["timetable"];
	
	
	//Позиции заказа
	$order_items = array();
	$items_counter = 0;//Счетчик позиций
    //ПОЛЯ ИТОГО ПО ЗАКАЗУ
    $count_need_total = 0;//Итого количество
    $price_sum_total = 0;//Итого сумма
    //ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
    //Запрос наименований
    $SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
    $SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
    $SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
    //Сумма позиции
    $SELECT_item_price_sum = "`price`*`count_need`";
    //СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
    $SELECT_ORDER_ITEMS = "SELECT *, $SELECT_product_name AS `product_name`, $SELECT_item_price_sum AS `price_sum` FROM `shop_orders_items` WHERE `order_id` = ?;";
	$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
	$order_items_query->execute( array($order_id) );
	
	while( $order_item = $order_items_query->fetch() )
    {
		$item = array();
		
        $item["id"] = $order_item["id"];
        $item["status"] = $order_item["status"];
        $item["count_need"] = $order_item["count_need"];
        $item["price"] = $order_item["price"];
        $item["price_sum"] = $order_item["price_sum"];
        $item["product_type"] = $order_item["product_type"];
        $item["product_id"] = $order_item["product_id"];
        $item["product_name"] = $order_item["product_name"];
		$item["t2_time_to_exe"] = $order_item["t2_time_to_exe"];
		$item["t2_time_to_exe_guaranteed"] = $order_item["t2_time_to_exe_guaranteed"];
		$item["status_name"] = $orders_items_statuses[$order_item["status"]]["name"];
		$item["status_color"] = $orders_items_statuses[$order_item["status"]]["color"];
		
		//Срок доставки для продуктов типа 2
		if($item["t2_time_to_exe"] < $item["t2_time_to_exe_guaranteed"])
		{
			$item["t2_time_to_exe"] = $item["t2_time_to_exe"]." - ".$item["t2_time_to_exe_guaranteed"];
		}
		$item["t2_time_to_exe"] = $item["t2_time_to_exe"]." дн.";
		if($item["product_type"] == 1)
		{
			$item["t2_time_to_exe"] = "";
		}

        //Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
        if( array_search($item["status"], $orders_items_statuses_not_count) === false)
        {
            $count_need_total += $item_count_need;
	        $price_sum_total += $item["price_sum"];
        }
        
		array_push($order_items, $item);
       
        $items_counter++;
    }//while - по позициям заказа
	
	
	$order["count_need_total"] = $count_need_total;
	$order["price_sum_total"] = $price_sum_total;
	$order["items"] = $order_items;
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "My order data";
	$answer["order"] = $order;
	exit(json_encode($answer));
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No session";
	exit(json_encode($answer));
}
?>