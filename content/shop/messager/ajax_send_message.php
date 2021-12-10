<?php
/*
Серверный срипт для отправки сообщений
*/


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
//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

//Входные данные:
$order_id = (int)$_GET["order_id"];
$text = $_GET["text"];
$is_customer = 1;

//Проверяем права на запуск
if( !empty($_GET["manager"]) )//Запрос от менеджера
{
	$is_customer = 0;
	//Проверяем право менеджера
    if( ! DP_User::isAdmin())
    {
        $result["status"] = false;
        $result["message"] = "Forbidden";
        $result["code"] = 501;
        exit(json_encode($result));//Вообще не является администратором бэкенда
    }
	
	$order_sql = "SELECT * FROM `shop_orders` WHERE `id` = ?;";
	$order_query = $db_link->prepare($order_sql);
	$order_query->execute( array($order_id) );
}
else//Запрос от пользователя
{
	$user_id = DP_User::getUserId();
	$order_sql = "SELECT * FROM `shop_orders` WHERE `id` = ? AND `user_id` = ?;";
	$order_query = $db_link->prepare($order_sql);
	$order_query->execute( array($order_id, $user_id) );
}


$order = $order_query->fetch();
if($order == false)
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	exit(json_encode($result));
}

$office_id = $order["office_id"];
$user_id = $order["user_id"];


if($db_link->prepare('INSERT INTO `shop_orders_messages` (`order_id`, `is_customer`, `text`, `time`) VALUES (?,?,?,?);')->execute( array($order_id, $is_customer, htmlentities($text), time()) ) != true)
{
	echo "false";
}
else
{
	//Отправляем уведомление
	if($is_customer)
	{
		//Отправляем сообщение менеджерам
		
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
		$notify_vars['order_id'] = $order_id;
		//Отправляем уведомление (БЕЗ обработки результата)
		send_notify('order_message_to_manager', $notify_vars, $persons);
	}
	else
	{
		//Отправляем сообщение покупателю
		//Для покупателя
		//Значение переменных для уведомления
		$notify_vars = array();
		$notify_vars['order_id'] = $order_id;
		//Получатель
		$persons = array();
		if( $user_id > 0 )
		{
			$persons[] = array('type'=>'user_id', 'user_id'=>$user_id);
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
		send_notify('order_message_to_customer', $notify_vars, $persons);
	}
	
	
	
	echo "true";
}
?>