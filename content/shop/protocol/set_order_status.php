<?php
header('Content-Type: application/json;charset=utf-8;');
if($_GET["initiator"] != 1 && $_GET["initiator"] != 4)
{
	exit();
} 
?>
<?php
/**
 * Серверный скрипт для изменения статуса заказа
 * 
 * //Инициаторы:
 * 1 - менеджер
 * 4 - скрипт (например, при оплате заказа)
 * 
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

//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

$result = array();//Результат работы



//Входные данные:
$initiator = $_GET["initiator"];
$orders = json_decode($_GET["orders"], true);
$status = $_GET["status"];


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
else if($initiator == 4)
{
    if($_GET["key"] != $DP_Config->tech_key)
    {
        $result["status"] = false;
        $result["message"] = "Wrong key";
        $result["code"] = 503;
        exit(json_encode($result));
    }
}




//ДАЛЕЕ САМ АЛГОРИТМ
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

//2. Меняем статус
$binding_values = array();
array_push($binding_values, $status);
$SQL_UPDATE_STATUS = "UPDATE `shop_orders` SET `status`=? WHERE ";
for($i=0; $i < count($orders); $i++)
{
    if($i > 0)$SQL_UPDATE_STATUS .=" OR ";
    $SQL_UPDATE_STATUS .= "`id`=?";
	
	array_push($binding_values, $orders[$i]);
}
$SQL_UPDATE_STATUS .=";";
if( $db_link->prepare($SQL_UPDATE_STATUS)->execute( $binding_values ) != true )
{
    $result["status"] = false;
    $result["message"] = "SQL error";
    $result["code"] = 701;
    exit(json_encode($result));
}

// -----------------------------------------------------------------------------------------------------------

//3. Уведомления

$subject = urlencode("Смена статуса заказа");

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
	$notify_vars['status_name'] = $orders_statuses[$status]["name"];
	$notify_vars['status_ref'] = $orders_statuses[$status];//Этой переменной нет в спецификации уведомления. Но, она используется для учета настроек отправки по разным статусам
	//Отправляем уведомление (БЕЗ обработки результата)
	send_notify('order_status_to_manager', $notify_vars, $persons);
	
	
	
    
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
	send_notify('order_status_to_customer', $notify_vars, $persons);
}

// -----------------------------------------------------------------------------------------------------------


//ЗАПИСЬ ИСТОРИИ ДЕЙСТВИЙ С ЗАКАЗАМИ
if($initiator == 1) 
{
	$is_manager = 1;
	$user_id = DP_User::getAdminId();
	$is_robot = 0;
}
else if( $initiator == 4 )
{
	$is_manager = 0;
	$user_id = 0;
	$is_robot = 1;
}
else 
{
	$is_manager = 0;
	$user_id = DP_User::getUserId();
	$is_robot = 0;
}
for($i=0; $i < count($orders); $i++)
{
	$order_id = $orders[$i];
	
	//Пишем лог заказа
	$db_link->prepare('INSERT INTO `shop_orders_logs` (`order_id`,`time`,`user_id`,`is_manager`,`text`, `is_robot`) VALUES (?, ?, ?, ?, ?, ?);')->execute( array($order_id, time(), $user_id, $is_manager,'Заказу присвоен статус <b>'.$orders_statuses[$status]["name"].'</b>', $is_robot) );
}

// -----------------------------------------------------------------------------------------------------------


//4. Выдаем ответ (JSON)
$result["status"] = true;
exit(json_encode($result));

?>