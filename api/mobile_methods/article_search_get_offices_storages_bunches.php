<?php
//ПОИСК ПО АРТИКУЛУ. Получение связок офис-склад
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];

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
	//Сессия есть - Выполняем действие
	//Формирум объект описания точек выдачи и складов
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");//Получили $customer_offices

	$office_storage_bunches = array();//Список всех связок всех офисов обслуживания со своими складами. По этому списку будет осуществляться опрос складов

	$office_storage_bunches_prices = array();//Такой же точно массив, только для складов типа Docpart-Price - для возможности их одновременного запроса

	//Для каждого магазина получить список складов (не Treelax складов) и опросить каждый склад
	for($i=0; $i < count($customer_offices); $i++)
	{
		$offices_storages_map[$customer_offices[$i]] = array();//ID точки обслуживания => список складов
		
		//Получаем список складов для данной точки обслуживания у которых product_type = 2 (т.е. автозапчасти)
		$SQL_SELECT_office_storages = "SELECT DISTINCT(storage_id) AS storage_id, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`) ) AS `handler_folder` FROM shop_offices_storages_map WHERE office_id = ? AND storage_id IN (SELECT id FROM shop_storages WHERE interface_type > 1);";
		
		$storages_query = $db_link->prepare($SQL_SELECT_office_storages);
		$storages_query->execute( array($customer_offices[$i]) );
		
		while( $storage = $storages_query->fetch() )
		{
			//Определяем версию протокола (1 шаг/2 шага)
			$protocol_version = 1;//По умолчанию
			//Если в папке обработчика присутствует скрипт get_manufacturers.php, значит версия протокола - 2 шаговый
			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/".$storage["handler_folder"]."/get_manufacturers.php") )
			{
				$protocol_version = 2;
			}
			
			
			//Добавляем связку только, если склад не прайсовый
			if($storage["handler_folder"] != "prices")
			{
				//API-поставщиков добавляем в основной список
				array_push($office_storage_bunches, array("office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0));
			}
			else
			{
				//Прайсовых поставщиков добавляем во вспомогательный список
				array_push($office_storage_bunches_prices, array("office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0));
			}
		}
		
		//После наполнения списка связок, вспомогательный список для прайсовых поствщиков добавляем первым элементом в основной список - для того, чтобы сначала опросить прайс-листы
		/*
		Версия протокола - ставим 3
		Флаг опроса поставщиков - 1 (т.е. как бы уже опросили)
		Добавляем еще один параметр office_storage_bunches - используется на сервере для понимания, какие связки складов и машазинов опросить
		*/
		if( count($office_storage_bunches_prices) > 0 )
		{
			array_unshift($office_storage_bunches, array("office_id"=>0, "storage_id"=>0, "sent" => 0, "protocol_version"=>3, "manufacturers_sent" => 1, "office_storage_bunches"=>$office_storage_bunches_prices) );
			$office_storage_bunches_prices = array();
		}
		
	}
	
	
	
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "Offices_storages_bunches Ok!";
	$answer["office_storage_bunches"] = $office_storage_bunches;
	$answer["customer_offices"] = $customer_offices;
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