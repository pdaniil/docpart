<?php
//Скрипт для метода получения списка заказов
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];
$filter = json_decode($params["filter"], true);

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
	//Сессия есть - выполняем действие
	$orders = array();
	
	$binding_values = array();
	
	//Подстрока с условиями фильтрования заказов
	$WHERE_CONDITIONS = " WHERE `user_id` = ?";
	array_push($binding_values, $user_id);
	
	//Фильтр:
	//1. Время с
	$WHERE_CONDITIONS .= " AND `time` > ?";
	array_push($binding_values, $filter["time_from"]);
	
	//2. Время по
	$WHERE_CONDITIONS .= " AND `time` < ?";
	array_push($binding_values, $filter["time_to"]);
	
	//3. Номер заказа
	if( $filter["order_id"] != "" && $filter["order_id"] != 0 )
	{
		$WHERE_CONDITIONS .= " AND `id` = ?";
		array_push($binding_values, $filter["order_id"]);
	}
	
	
	//4. Статус заказа
	if( $filter["status"] != 0 )
	{
		$WHERE_CONDITIONS .= " AND `status` = ?";
		array_push($binding_values, $filter["status"]);
	}
	
	
	//5. Оплата
	if($filter["paid"] != -1)
	{
		$WHERE_CONDITIONS .= " AND `paid` = ?";
		array_push($binding_values, $filter["paid"]);
	}
	
	
	//Защита от SQL-инъекций
	$sort_field = 'id';
	if( array_search( $filter["sort_field"], array('id', 'time', 'obtain_caption', 'paid', 'status', 'status_name', 'status_color', 'price_sum') ) !== false )
	{
		$sort_field = $filter["sort_field"];
	}
	
	$sort_asc_desc = 'asc';
	if( strtolower($filter["sort_asc_desc"]) == 'asc' )
	{
		$sort_asc_desc = 'asc';
	}
	else
	{
		$sort_asc_desc = 'desc';
	}
	
	
	$SQL_SELECT_ORDERS = "SELECT *, `shop_orders`.`id` AS `id`, ";
	$SQL_SELECT_ORDERS .= "`shop_orders`.`time` AS `time`, ";
	$SQL_SELECT_ORDERS .= "(SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get` ) AS `obtain_caption`, ";
	$SQL_SELECT_ORDERS .= "`shop_orders`.`paid` AS `paid`, ";
	$SQL_SELECT_ORDERS .= "`shop_orders`.`status` AS `status`, ";
	
	$SQL_SELECT_ORDERS .= " (SELECT `name` FROM `shop_orders_statuses_ref` WHERE `id` = `shop_orders`.`status`) AS `status_name`, ";
	
	$SQL_SELECT_ORDERS .= " (SELECT `color` FROM `shop_orders_statuses_ref` WHERE `id` = `shop_orders`.`status`) AS `status_color`, ";
	
	$SQL_SELECT_ORDERS .= " CAST((SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(8,2)) AS `price_sum` ";//Сумма заказа
	$SQL_SELECT_ORDERS .= " FROM `shop_orders` $WHERE_CONDITIONS ORDER BY `".$sort_field."` ".$sort_asc_desc;
	
	
	$elements_query = $db_link->prepare( $SQL_SELECT_ORDERS );
	$elements_query->execute($binding_values);
	
	while($element_record = $elements_query->fetch() )
	{
		//Простая текстовая строка:
		$item_text = $element_record["id"].", оформлен ".date("d.m.Y", $element_record["time"])." в ".date("G:i", $element_record["time"]).". Статус: ".$element_record["status_name"];
		
		
		array_push($orders, array("id"=>$element_record["id"], "time"=>$element_record["time"], "price_sum"=>$element_record["price_sum"], "item_text"=>$item_text, "paid"=>$element_record["paid"], "status"=>$element_record["status"], "obtain_caption"=>$element_record["obtain_caption"], "office_id"=>$element_record["office_id"], "status_color"=>$element_record["status_color"], "status_name"=>$element_record["status_name"], "paid_name"=>($paid ? "Оплачен":"Не оплачен"), "office_name"=>$element_record["office_name"], "date_str"=>date("d.m.Y", $element_record["time"]), "time_str"=>date("G:i", $element_record["time"])) );
	}
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "My orders list";
	$answer["orders"] = $orders;
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