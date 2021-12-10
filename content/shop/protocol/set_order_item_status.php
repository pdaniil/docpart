<?php 
if($_GET["initiator"] != 1 && $_GET["initiator"] != 2)
{
	exit();
}
?>
<?php
/**
 * Серверный скрипт для выставления статуса отдельной позиции
 * 
 * 
 * Инициаторы:
 * 1 - менеджер
 * 2 - Скрипт, например SAO, робот
 * 
 * 
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
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


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );


$result = array();//Результат работы



//Входные данные:
$initiator = $_GET["initiator"];
$orders_items = json_decode($_GET["orders_items"], true);
$status = $_GET["status"];
$key = null;
if( isset($_GET["key"]) )
{
	$key = $_GET["key"];
}



//ПРОВЕРКА ПРАВ
if($initiator == 1)
{
    //Проверяем право менеджера
    if( ! DP_User::isAdmin())
    {
        $result["status"] = false;
        $result["message"] = "Forbidden";
        $result["code"] = 501;
        exit(json_encode($result));//Вообще не является администратором бэкенда
    }
}
if($initiator == 2)
{
	if( $key != $DP_Config->tech_key )
	{
		$result["status"] = false;
        $result["message"] = "Forbidden";
        $result["code"] = 501;
        exit(json_encode($result));
	}
}


//ДАЛЕЕ САМ АЛГОРИТМ
// -----------------------------------------------------------------------------------------------------------
//0 Получаем список заказов по данным позициям:
$SQL_IN = "";
$binding_values = array();
for($i=0; $i < count($orders_items); $i++)
{
    if($i > 0) $SQL_IN .= ",";
    $SQL_IN .= '?';
	
	array_push($binding_values, $orders_items[$i]);
}
$SQL_IN = '('.$SQL_IN.')';
$orders_query = $db_link->prepare('SELECT DISTINCT(`order_id`), `id` FROM `shop_orders_items` WHERE `id` IN '.$SQL_IN.';');
$orders_query->execute($binding_values);
$orders = array();
while( $order = $orders_query->fetch() )
{
    array_push($orders, $order["order_id"]);
}
// -----------------------------------------------------------------------------------------------------------
//Проверка состояния оплаты заказов. Если заказ имеет состояние "Оплачен" или "Частично оплачен", то его позициям нельзя устанавливать статус, исключающий эти позиции из подсчета суммы заказов.
//Если идет установка статуса позиций, исключающего их из суммы заказа
if( array_search($status, $orders_items_statuses_not_count) !== false )
{
	//Проверка наличия заказов, по которым есть платежи
	$check_paid_query = $db_link->prepare( 'SELECT COUNT(*) FROM `shop_orders` WHERE `paid` != ? AND `id` IN ('.str_repeat('?,', count($orders)-1).'?);' );
	$check_paid_query->execute( array_merge( array(0), $orders ) );
	if( $check_paid_query->fetchColumn() > 0 )
	{	
		$result = array();
		$result["status"] = false;
		$result["message"] = "Данный статус нельзя назначать позициям заказов, которые Оплачены, либо Частично оплачены";
		exit(json_encode($result));
	}
}
// -----------------------------------------------------------------------------------------------------------
//1. Массив покупателей и менеджеров по заказам
$orders_data = array();//Ассоциативный массив Заказ=>[покупатель, офис]
for($i=0; $i < count($orders); $i++)
{
    //1. Получаем информацию по заказам:
	$order_query = $db_link->prepare('SELECT `user_id` AS `customer`, `email_not_auth`, `phone_not_auth`, (SELECT `users` FROM `shop_offices` WHERE `id`=`shop_orders`.`office_id`) AS `managers` FROM `shop_orders` WHERE `id`= ?;');
	$order_query->execute( array($orders[$i]) );
    $order_record = $order_query->fetch();
    $orders_data[$orders[$i]] = array("customer"=>$order_record["customer"], "managers"=>json_decode($order_record["managers"], true), "email_not_auth"=>$order_record["email_not_auth"], "phone_not_auth"=>$order_record["phone_not_auth"]);    
}

// -----------------------------------------------------------------------------------------------------------



//2.1 Операции с товаром (возврат на склад / списание со склада) ТОЛЬКО ДЛЯ ТОВАРОВ С ТИПОМ 1
//Сначала определяем - нужно ли делать выдачу товара или отмену позиции
$status_flags_query = $db_link->prepare('SELECT `count_flag`, `issue_flag` FROM `shop_orders_items_statuses_ref` WHERE `id` = ?;');
$status_flags_query->execute( array($status) );
$status_flags_record = $status_flags_query->fetch();
$count_flag = $status_flags_record["count_flag"];//Флаг - нужно ли учитывать товар при расчете суммы заказа
$issue_flag = $status_flags_record["issue_flag"];//Флаг - товар выдать покупателю

//Далее проверяем тип товаров в нужных позициях.
for($i=0; $i < count($orders_items); $i++)
{
	//Получаем данные по позиции
	$product_type_query = $db_link->prepare('SELECT `product_type`, (SELECT `count_flag` FROM `shop_orders_items_statuses_ref` WHERE `id` = `shop_orders_items`.`status`) AS `count_flag_current`, (SELECT SUM(`count_issued`) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`) AS `previously_issued` FROM `shop_orders_items` WHERE `id` = ?;');
	$product_type_query->execute( array($orders_items[$i]) );
	$product_type_record = $product_type_query->fetch();
	
	
	//Актуально только для типа продукта = 1
	if( $product_type_record["product_type"] == 1 )
	{
		//Определяем флаг "Позиция была отмене ранее"
		if($product_type_record["count_flag_current"] == 1)
		{
			$previously_canceled = 0;//Позицию не отменяли
		}
		else
		{
			$previously_canceled = 1;//Позиция уже отменена
		}
		
		//Определяем флаг "Товар уже был выдан покупателю" (точне не флаг, а количество товара, которое уже было отпущено)
		$previously_issued = $product_type_record["previously_issued"];
		
		
		//Получаем перечень детализированных записей позиции
		$details_records = array();
		$details_records_query = $db_link->prepare('SELECT `id` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;');
		$details_records_query->execute( array($orders_items[$i]) );
		while( $detail_record = $details_records_query->fetch() )
		{
			array_push($details_records, $detail_record["id"]);
		}
		
		
		//Далее идут действия:
		
		//Нужно выполнить действие - Выдать товар покупателю со склада
		if($issue_flag)
		{
			//Позиция не была ранее отменена
			if( ! $previously_canceled )
			{
				//Товар еще не был выдан (OK - GOOD)
				if($previously_issued == 0) 
				{
					//ВЫДАЕМ
					//Склады - количество из "Зарезервировано" перетекает в "Отпущено"
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_storages_data` SET `issued` = `issued` + (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `id`=?), `reserved` = `reserved` - (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `id`=?)')->execute( array($details_records[$d], $details_records[$d],$details_records[$d]) );
					}
					//Детальные записи заказа - из "Зарезервировано" перетекает в "Отпущено"
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_issued` = `count_reserved` WHERE `id` = ?')->execute( array($details_records[$d]) );
						
						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_reserved` = 0 WHERE `id` = ?')->execute( array($details_records[$d]) );
					}
				}
				else//Товар уже выдан
				{
					//Ничего не делаем
				}
			}
			else//Позиция была отменена (OK - GOOD)
			{
				//ВЫДАЕМ
				//Склады - количество из "Наличие" перетекает в "Отпущено"
				for( $d=0; $d < count($details_records); $d++ )
				{
					$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` - (SELECT `count_canceled` FROM `shop_orders_items_details` WHERE `id`=?), `issued` = `issued` + (SELECT `count_canceled` FROM `shop_orders_items_details` WHERE `id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `id`=?)')->execute( array($details_records[$d], $details_records[$d], $details_records[$d]) );
				}
				//Детальные записи заказа - колонка "Отпущено" инициализируется количеством, а количество "Отменено" становится равным 0
				for( $d=0; $d < count($details_records); $d++ )
				{
					$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_issued` = `count_canceled` WHERE `id` = ?')->execute( array($details_records[$d]) );
					
					$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_canceled` = 0 WHERE `id` = ?')->execute( array($details_records[$d]) );
				}
			}
		}
		//Нужно выполнить действие - Вернуть товар на склад
		else if( ! $count_flag )
		{
			//Позиция не была ранее отменена
			if( ! $previously_canceled )
			{
				//Товар еще не был выдан (OK - GOOD)
				if($previously_issued == 0)
				{
					//Возвращаем товар на склад (снимаем с резервирования)
					//Склады - количество из "Зарезервировано" перетекает в "Наличие"
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` + (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `id`=?), `reserved` = `reserved` - (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `id`=?)')->execute( array($details_records[$d], $details_records[$d], $details_records[$d]) );
					}
					//Детальные записи заказа - "Зарезервировано" ставим 0, а "Отменено" - указываем количество
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_canceled` = `count_reserved` WHERE `id` = ?')->execute( array($details_records[$d]) );

						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_reserved` = 0 WHERE `id` = ?')->execute( array($details_records[$d]) );
					}
				}
				else//Товар был выдан (OK - GOOD)
				{
					//Возвращаем товар на склад
					//Склады - количество из "Отпущено" перетекает в "Наличие"
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` + (SELECT `count_issued` FROM `shop_orders_items_details` WHERE `id`=?), `issued` = `issued` - (SELECT `count_issued` FROM `shop_orders_items_details` WHERE `id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `id`=?)')->execute( array($details_records[$d], $details_records[$d],$details_records[$d]) );
					}
					//Детальные записи заказа - "Отпущено" ставим 0, а "Отменено" - указываем количество
					for( $d=0; $d < count($details_records); $d++ )
					{
						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_canceled` = `count_issued` WHERE `id` = ?')->execute( array($details_records[$d]) );
						
						$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_issued` = 0 WHERE `id` = ?')->execute( array($details_records[$d]) );
					}
				}
			}
			else//Позиция уже была отменена
			{
				//Ничего не делаем
			}
		}
		else if( $count_flag )//Менеджер установил статус, при котором товар должен быть зарезервирован
		{
			//Этот блок выполняется только в том случае, если позиция в данный момент числится, как отмененная
			if( $previously_canceled )
			{
				//Склады - количество из "Наличие" перетекает в "Зарезервирован"
				for( $d=0; $d < count($details_records); $d++ )
				{
					$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` - (SELECT `count_canceled` FROM `shop_orders_items_details` WHERE `id`=?), `reserved` = `reserved` + (SELECT `count_canceled` FROM `shop_orders_items_details` WHERE `id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `id`=?)')->execute( array($details_records[$d],$details_records[$d],$details_records[$d]) );
				}
				//Детальные записи заказа - "Отменено" ставим 0, а "Зарезервирован" - указываем количество
				for( $d=0; $d < count($details_records); $d++ )
				{
					$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_reserved` = `count_canceled` WHERE `id` = ?')->execute( array($details_records[$d]) );
					
					$db_link->prepare('UPDATE `shop_orders_items_details` SET `count_canceled` = 0 WHERE `id` = ?')->execute( array($details_records[$d]) );
				}
			}
		}
	}
}

// -----------------------------------------------------------------------------------------------------------

//3. Меняем статус позиции

array_unshift($binding_values, $status);
if( $db_link->prepare("UPDATE `shop_orders_items` SET `status` = ? WHERE `id` IN $SQL_IN;")->execute( $binding_values ) != true)
{
    $result["status"] = false;
    $result["message"] = "SQL error";
    $result["code"] = 701;
    exit(json_encode($result));
}

// -----------------------------------------------------------------------------------------------------------
//4. Уведомления
foreach($orders_data as $order_id=>$data)
{
    //3.1 ДЛЯ МЕНЕДЖЕРОВ
	$persons = array();
    for($i=0; $i < count($data["managers"]); $i++)
    {
		$persons[] = array('type'=>'user_id', 'user_id'=>$data["managers"][$i]);
    }
	//Переменные для уведомления
	$notify_vars = array();
	$notify_vars['order_id'] = $order_id;
	$notify_vars['status_name'] = $orders_items_statuses[$status]["name"];
	$notify_vars['status_ref'] = $orders_items_statuses[$status];//Этой переменной нет в спецификации уведомления. Но, она используется для учета настроек отправки по разным статусам
	//Отправляем уведомление (БЕЗ обработки результата)
	send_notify('order_item_status_to_manager', $notify_vars, $persons);
	
	
    
    //3.2 Для покупателя
	$persons = array();
    if( $data["customer"] > 0 )
    {
        $persons[] = array( 'type'=>'user_id', 'user_id'=>$data["customer"] );
    }
	else
	{
		$persons[] = array(
			'type'=>'direct_contact',
			'contacts'=>array(
					'email'=>array('value'=>$data["email_not_auth"]),
					'phone'=>array('value'=>$data["phone_not_auth"])
				)
			);
	}
	//Отправляем уведомление (БЕЗ обработки результата)
	send_notify('order_item_status_to_customer', $notify_vars, $persons);
}

// -----------------------------------------------------------------------------------------------------------

//ЗАПИСЬ ИСТОРИИ ДЕЙСТВИЙ С ЗАКАЗАМИ
if($initiator == 2)
{
	$is_manager = 0;
	$user_id = 0;
	$is_robot = 1;
}
else if($initiator == 1)
{
	$is_manager = 1;
	$is_robot = 0;
	$user_id = DP_User::getAdminId();
}
else 
{
	$is_manager = 0;
	$is_robot = 0;
	$user_id = DP_User::getUserId();
}
$orders_items_to_order_id_array = array();
for($i=0; $i < count($orders_items); $i++)
{
	$order_id_query = $db_link->prepare('SELECT `order_id` FROM `shop_orders_items` WHERE `id`= ?;');
	$order_id_query->execute( array($orders_items[$i]) );
	$order_id_record = $order_id_query->fetch();
	
	$order_id = $order_id_record["order_id"];
	
	//Пишем лог заказа
	$db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`, `is_robot`) VALUES (?, ?, ?, ?, ?, ?);')->execute( array($order_id,time(),$user_id,$is_manager,'Позиции '.$orders_items[$i].' присвоен статус <b>'.$orders_items_statuses[$status]["name"].'</b>',$is_robot) );
}


// -----------------------------------------------------------------------------------------------------------

//5. Выдаем ответ (JSON)
$result["status"] = true;
exit(json_encode($result));
?>