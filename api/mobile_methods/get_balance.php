<?php
//Скрипт для метода получения баланса
defined('DOCPART_MOBILE_API') or die('No access');


//Общая информация по заказам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

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
	//Подстрока с условиями фильтрования
	$WHERE_CONDITIONS = " WHERE `user_id` = ? AND `active` = 1 ";
	
	$binding_values = array();
	array_push($binding_values, $user_id);
	
	//Фильтр:
	//1. Время с
	$WHERE_CONDITIONS .= " AND `time` > ?";
	array_push($binding_values, $filter["time_from"]);
	
	//2. Время по
	$WHERE_CONDITIONS .= " AND `time` < ?";
	array_push($binding_values, $filter["time_to"]);
	
	//3. Направление
	if( $filter["income"] != -1 )
	{
		$WHERE_CONDITIONS .= " AND `income` = ?";
		array_push($binding_values, $filter["income"]);
	}
	
	//4. Код операции
	if($filter["accounting_code"] != -1)
	{
		$WHERE_CONDITIONS .= " AND `operation_code` = ?";
		array_push($binding_values, $filter["accounting_code"]);
	}
	
	//Защита от SQL-инъекций
	$sort_field = 'id';
	if( array_search( $filter["sort_field"], array('operation_name','id','user_id','time','income','amount','operation_code','active') ) !== false )
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
	
	
	$records = array();//Записи операций
	$records_balance = 0;//Баланс по операциям
	$balance_query = $db_link->prepare("SELECT *,
	(SELECT `name` FROM `shop_accounting_codes` WHERE `id` = `shop_users_accounting`.`operation_code`) AS `operation_name`
	FROM `shop_users_accounting` ".$WHERE_CONDITIONS." ORDER BY `".$sort_field."` ".$sort_asc_desc);
	$balance_query->execute($binding_values);
	
	
	while( $balance_record = $balance_query->fetch() )
	{
		array_push($records, array("id"=>$balance_record["id"], "time"=>$balance_record["time"], "income"=>$balance_record["income"], "amount"=>$balance_record["amount"], "operation_code"=>$balance_record["operation_code"], "operation_name"=>$balance_record["operation_name"], "date_str"=>date("d.m.Y", $balance_record["time"]), "time_str"=>date("G:i", $balance_record["time"])) );
		
		if($balance_record["income"] == 1)
		{
			$records_balance = $records_balance + $balance_record["amount"];
		}
		else
		{
			$records_balance = $records_balance - $balance_record["amount"];
		}
	}
	
	
	//Полный баланс без фильтров
	$total_balance_plus_query = $db_link->prepare('SELECT SUM(`amount`) AS `amount` FROM `shop_users_accounting` WHERE `user_id` = ? AND `active` = 1 AND `income` = 1;');
	$total_balance_plus_query->execute( array($user_id) );
	$total_balance_plus_record = $total_balance_plus_query->fetch();
	$total_balance_plus = $total_balance_plus_record["amount"];
	
	$total_balance_minus_query = $db_link->prepare('SELECT SUM(`amount`) AS `amount` FROM `shop_users_accounting` WHERE `user_id` = ? AND `active` = 1 AND `income` = 0;');
	$total_balance_minus_query->execute( array($user_id) );
	$total_balance_minus_record = $total_balance_minus_query->fetch();
	$total_balance_minus = $total_balance_minus_record["amount"];
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "Balance ok!";
	$answer["records"] = $records;
	$answer["records_balance"] = $records_balance;
	$answer["total_balance"] = $total_balance_plus - $total_balance_minus;
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