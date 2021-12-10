<?php
$path_handler = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/ixora";

set_include_path(get_include_path() . PATH_SEPARATOR . $path_handler . "/classes");

spl_autoload_register(function($class){
	require_once($class.".php");
});

ob_start();

$sao_result = array(); //Результат выполнения
$sao_result["status"] = false;
$sao_state_object = array();
$orders = array();

$sao_data = json_decode($order_item["t2_json_params"], true);

$AuthCode = $connection_options["authcode"];
$orderReference = $sao_data["orderreference"];

$id_result = 0; 


$order = new Order($order_item);


array_push($orders, $order);
try
{
	$client = new SoapClient("http://ws.ixora-auto.ru/soap/ApiService.asmx?wsdl", array('soap_version'   => SOAP_1_2));	
	
	var_dump($order);
	
	$basketInsertResult = $client->BasketInsertOrders(array("AuthCode" => $AuthCode, "Orders" => $order));
	
	var_dump($basketInsertResult);
	
	if($basketInsertResult->BasketInsertOrdersResult->Warning->Code == 0)//Успешно
	{
		if($basketInsertResult->BasketInsertOrdersResult->Data->Order->Error == "")//Успешно в заказе
		{
			$id_result = $basketInsertResult->BasketInsertOrdersResult->Data->Order->Id;//Сохраняем результат
			
			$sao_state_object["order_id"] = $id_result;
			$sao_message = "Заказ размещён и ожидает подтверждения<br/>".date("Y-m-d H:i");
			
			$sao_state_object_json = json_encode($sao_state_object);
			//Статус заказано();
			$new_sao_state = 2;
		
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
			$new_status_query->bindValue(':id', $new_sao_state);
			$new_status_query->execute();
			$new_status_record = $new_status_query->fetch();
			$new_status = $new_status_record["status_id"];
			if($new_status > 0)
			{
				//Отправляем запрос на изменение статуса позиции
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
			}

			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
			$update_query->bindValue(':sao_state', $new_sao_state);
			$update_query->bindValue(':sao_message', $sao_message);
			$update_query->bindValue(':sao_state_object', $sao_state_object_json);
			$update_query->bindValue(':id', $order_item["id"]);

			if( ! $update_query->execute() )
			{
				echo mysqli_error($db_link)."\n";
				throw new MysqlException("Заказ создан, но произошла ошибка смены SAO-Состояния");
			}

			$sao_result["status"] = true;
			$sao_result["message"] = "Заказ создан";
			
		}
	}
	
}
catch(SoapFault $e)
{
	$sao_result["message"] = "Ошибка соединения с API";
}
catch(MysqlException $e)
{
	$sao_result["message"] = $e->getMessage();
}

file_put_contents($path_handler."/dump_do_order.log", ob_get_clean());
?>