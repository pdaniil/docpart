<?php
//Серверный скрипт для отражения возврата оплаты клиенту по заказу. Может вызываться только продавцом.
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
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );




//Все делаем через тразакцию
try
{
	//Старт транзакции
	if( ! $db_link->beginTransaction()  )
	{
		throw new Exception("Не удалось стартовать транзакцию");
	}
	
	
	//Проверяем, что работает пользователь ПУ
	if( ! DP_User::isAdmin())
	{
		throw new Exception("Forbidden");
	}
	
	
	//Входные данные
	if( !isset($_POST['order_id']) || !isset($_POST['direct_refund']) )
	{
		throw new Exception("Forbidden");
	}
	$order_id = $_POST['order_id'];
	$direct_refund = $_POST['direct_refund'];
	
	
	//Получаем данные заказа
	//Сколько уже оплачено по заказу
	$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
	$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?),0)";
	//Баланс клиента
	$INCOME_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `user_id` = `shop_orders`.`user_id` ), 0)";
	$ISSUE_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `user_id` = `shop_orders`.`user_id` ),0)";
	$order_query = $db_link->prepare("SELECT *, ( $ISSUE_SQL - $INCOME_SQL ) AS `paid_sum`, ($INCOME_USER_SQL - $ISSUE_USER_SQL) AS `customer_balance` FROM `shop_orders` WHERE `id` = ?;");
	$order_query->execute( array($order_id, $order_id, $order_id) );
	$order = $order_query->fetch();
	if( $order == false )
	{
		throw new Exception("Forbidden");
	}
	
	
	
	//Получаем сумму заказа:
	$binding_values = array();
	array_push($binding_values, $order_id);
	$WHERE_COUNT_STATUS = "";
	for($i=0; $i < count($orders_items_statuses_not_count); $i++)
	{
		$WHERE_COUNT_STATUS .= " AND `status` != ?";
		
		array_push($binding_values, $orders_items_statuses_not_count[$i]);
	}
	$order_sum_query = $db_link->prepare('SELECT SUM(`price`*`count_need`) AS `order_sum` FROM `shop_orders_items` WHERE `order_id` = ? '.$WHERE_COUNT_STATUS.';');
	$order_sum_query->execute( $binding_values );
	$order_sum_record = $order_sum_query->fetch();
	if( $order_sum_record == false )
	{
		throw new Exception("Forbidden");
	}
	$order_sum = $order_sum_record["order_sum"];
	
	
	
	//Проверяем, что пользователь ПУ имеет право работать с этим заказом
	//Теперь проверяем право работать с заказами данного офиса обслуживания
	$manager_id = DP_User::getAdminId();
	$office_id = $order["office_id"];
	$office_access_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_offices` WHERE `id` = ? AND `users` LIKE ?;');
	$office_access_query->execute( array($office_id, '%"'.$manager_id.'"%') );
	if( $office_access_query->fetchColumn() == 0)
	{
		throw new Exception("Forbidden");
	}
	
	
	//Если клиент по заказу, не зарегистрирован, проверяем флаг direct_refund (при user_id==0, direct_refund может быть равен только 1)
	if( $order['user_id'] == 0 )
	{
		if( $direct_refund != 1 )
		{
			throw new Exception("Forbidden");
		}
	}
	
	
	//Проверяем, есть ли, что возвращать по данному заказу (paid должен быть 1 или 2)
	if( $order['paid'] == 0 )
	{
		throw new Exception("Forbidden");
	}
	
	
	//Делаем возврат (добавляем приходную операцию с заданным order_id)
	if( ! $db_link->prepare('INSERT INTO `shop_users_accounting` (`user_id`, `time`, `income`, `amount`, `operation_code`, `active`, `order_id`, `office_id`) VALUES (?,?,?,?, (SELECT `id` FROM `shop_accounting_codes` WHERE `key` = ? LIMIT 1) ,?,?, (SELECT `office_id` FROM `shop_orders` WHERE `id` = ? LIMIT 1) );')->execute( array($order['user_id'], time(), 1, $order['paid_sum'], '5_refund_from_order_to_balance', 1, $order['id'], $order['id']) ) )
	{
		throw new Exception("Ошибка добавления приходной операции на баланс клиента");
	}
	
	
	//Меняем paid в заказе на 0 (Не оплачен)
	if( ! $db_link->prepare('UPDATE `shop_orders` SET `paid`=? WHERE `id` = ?;')->execute( array(0, $order_id) ) )
	{
		throw new Exception("Ошибка записи состояния оплаты заказа");
	}
	
	
	//Если direct_refund==1, то добавляем расходную операцию на баланс клиента (выдача денег с баланса)
	if( $direct_refund )
	{
		if( ! $db_link->prepare('INSERT INTO `shop_users_accounting` (`user_id`, `time`, `income`, `amount`, `operation_code`, `active`, `order_id`, `office_id`) VALUES (?,?,?,?, (SELECT `id` FROM `shop_accounting_codes` WHERE `key` = ? LIMIT 1) ,?,?, (SELECT `office_id` FROM `shop_orders` WHERE `id` = ? LIMIT 1) );')->execute( array($order['user_id'], time(), 0, $order['paid_sum'], '6_refund_from_balance', 1, 0, $order['id']) ) )
		{
			throw new Exception("Ошибка добавления расходной операции на баланс клиента");
		}
	}
	
	//Пишем лог заказа (произведен возврат)
	$refund_type = 'на баланс клиента';//Для уведомлений
	$log_text = 'Возврат оплаты по заказу в сумме '.$order['paid_sum'].' (на баланс клиента)';
	if( $direct_refund )
	{
		$refund_type = 'прямой возврат клиенту';
		$log_text = 'Возврат оплаты по заказу в сумме '.$order['paid_sum'].' (прямой возврат)';
	}
	if( !$db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`) VALUES (?, ?, ?, ?, ?);')->execute( array($order['id'], time(), $manager_id, 1, $log_text) ) )
	{
		throw new Exception("Ошибка записи в лог");
	}
	
	
	//Необходимые записи возврата сделаны. Далее коммитим
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	
	
	// --------------------------------------------------------------------------------------------------------------
	//Отправляем уведомления

	//Для менеджера
	//Получаем список менеджеров офиса
	$managers_query = $db_link->prepare('SELECT `users` FROM `shop_offices` WHERE `id` = ?;');
	$managers_query->execute( array($office_id) );
	$managers_record = $managers_query->fetch();
	$managers = json_decode($managers_record["users"], true);
	$persons = array();
	for($i=0; $i < count($managers); $i++)
	{
		$persons[] = array('type'=>'user_id', 'user_id'=>(int)$managers[$i]);
	}
	//Значение переменных для уведомления
	$notify_vars = array();
	$notify_vars['order_id'] = $order['id'];
	$notify_vars['refund_sum'] = $order['paid_sum'];
	$notify_vars['refund_type'] = $refund_type;
	$notify_vars['order_sum'] = $order_sum;
	
	//Отправляем уведомление (БЕЗ обработки результата)
	send_notify('order_pay_refund_to_manager', $notify_vars, $persons);
	
	
	
	//Для покупателя
	//Значение переменных для уведомления
	$notify_vars = array();
	$notify_vars['order_id'] = $order['id'];
	$notify_vars['refund_sum'] = $order['paid_sum'];
	$notify_vars['refund_type'] = $refund_type;
	$notify_vars['order_sum'] = $order_sum;
	//Получатель
	$persons = array();
	if( $order["user_id"] > 0 )
	{
		$persons[] = array('type'=>'user_id', 'user_id'=>$order["user_id"]);
	}
	else
	{
		$persons[] = array(
			'type'=>'direct_contact',
			'contacts'=>array(
					'email'=>array('value'=>$order["email_not_auth"]),
					'phone'=>array('value'=>$order["phone_not_auth"])
				)
			);
	}
	//Отправляем уведомление (БЕЗ обработки результата)
	send_notify('order_pay_refund_to_customer', $notify_vars, $persons);
	
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


$answer = array();
$answer["status"] = true;
$answer["message"] = '';
exit( json_encode($answer) );
?>