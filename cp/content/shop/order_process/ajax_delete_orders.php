<?php
/**
Серверный скрипт удаления заказов
*/
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	$result = array();
    $result["status"] = false;
	$result["message"] = "DB connect error";
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	$result = array();
	$result["status"] = false;
	$result["message"] = "Forbidden";
	exit(json_encode($result));//Вообще не является администратором бэкенда
}


//Получаем список заказов
$orders_list = json_decode($_POST["orders_list"], true);
$orders_list_str = "";
$binding_values = array();
for($i=0;$i < count($orders_list); $i++)
{
	if( $i>0 )
	{
		$orders_list_str = $orders_list_str.",";
	}
	$orders_list_str = $orders_list_str."?";
	
	array_push($binding_values, $orders_list[$i]);
}

$orders_list_str = "(".$orders_list_str.")";



try
{
	//Старт транзакции
	if( ! $db_link->beginTransaction()  )
	{
		throw new Exception("Не удалось стартовать транзакцию");
	}
	
	
	
	//Проверка наличия заказов, по которым есть платежи. Т.е. нельзя удалять заказы, которые оплачены или частично оплачены. Удалять можно только заказы, у которых paid == 0 (Не оплачен)
	$check_paid_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_orders` WHERE `paid` != ? AND `id` IN '.$orders_list_str);
	$check_paid_query->execute( array_merge( array(0), $binding_values ) );
	if( $check_paid_query->fetchColumn() > 0 )
	{
		throw new Exception("Нельзя удалять заказы, которые полностью оплачены или частично оплачены.");
	}
	
	
	//Удаляем заказы и данные по ним
	if( ! $db_link->prepare("DELETE FROM `shop_orders` WHERE `id` IN ".$orders_list_str)->execute($binding_values) )
	{
		throw new Exception("Ошибка удаления учетных записей заказов из shop_orders");
	}
	if( ! $db_link->prepare("DELETE FROM `shop_orders_items` WHERE `order_id` IN ".$orders_list_str)->execute($binding_values) )
	{		
		throw new Exception("Ошибка удаления учетных записей позиций заказов из shop_orders_items");
	}
	if( ! $db_link->prepare("DELETE FROM `shop_orders_items_details` WHERE `order_id` IN ".$orders_list_str)->execute($binding_values) )
	{		
		throw new Exception("Ошибка удаления учетных записей детализации позиций заказов из shop_orders_items_details");
	}
	if( ! $db_link->prepare("DELETE FROM `shop_orders_logs` WHERE `order_id` IN ".$orders_list_str)->execute($binding_values) )
	{	
		throw new Exception("Ошибка удаления журнала действий заказов из shop_orders_logs");
	}
	if( ! $db_link->prepare("DELETE FROM `shop_orders_messages` WHERE `order_id` IN ".$orders_list_str)->execute($binding_values) )
	{		
		throw new Exception("Ошибка удаления чата заказов из shop_orders_messages");
	}
	if( ! $db_link->prepare("DELETE FROM `shop_orders_viewed` WHERE `order_id` IN ".$orders_list_str)->execute($binding_values) )
	{
		throw new Exception("Ошибка удаления записей просмотра заказов из shop_orders_viewed");
	}
	
}
catch (Exception $e)
{
	//Откатываем все изменения
	$db_link->rollBack();

	$answer = array();
	$answer["status"] = false;
	$answer["message"] = $e->getMessage();
	exit( json_encode($answer) );
}

//Дошли до сюда, значит выполнено ОК
$db_link->commit();//Коммитим все изменения и закрываем транзакцию

$answer = array();
$answer["status"] = true;
exit( json_encode($answer) );
?>