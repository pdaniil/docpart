<?php
$path_handler = $_SERVER["DOCUMENT_ROOT"] ."/content/shop/docpart/suppliers_handlers/forum_auto";

ob_start();

$sao_result 		= array(); //Результат выполнения
$sao_state_object	= array(); //Запись тех параметров после выполнения действия

$sao_result["status"]	= false;

$sao_data	= json_decode($order_item["t2_json_params"], true);

$login		= $connection_options["login"];
$password	= $connection_options["password"];
$tid 		= $sao_data["gid"];
$num 		= $order_item["count_need"];
// login	Логин
// pass		Пароль
// tid		ИД товара в нашей системе
// num		Кол-во товара
// eid		ИД товарной строки заказа в вашей системе. Если не передали, будет сгенерен автоматически.

try
{
	$client = new SoapClient("http://api.forum-auto.ru/wsdl", array('soap_version' => SOAP_1_2, 'exceptions' => true));
	
	$do_order_result = $client->addGoodsToOrder($login, $password, $tid, $num);	
	
	if( ! empty($do_order_result[0]["did"]) )
	{
		$sao_state_object["did"] = $do_order_result[0]["did"];
		
		$sao_message = "Заказ размещён<br/>".$do_order_result[0]["dtc"]."<br/>";
		$sao_message .= "ID заказа у поставщика: ".$do_order_result[0]["did"];
		
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
			throw new Exception("Заказ создан, но произошла ошибка смены SAO-Состояния");
		}

		$sao_result["status"] = true;
		$sao_result["message"] = "Заказ создан";
	}
	
}
catch(SoapFault $e)
{
	var_dump($e);
	$sao_result["status"]	= false;
	$sao_result["message"] = "Ошибка запроса";
}
catch(Exception $e)
{
	$sao_result["status"]	= false;
	$sao_result["message"] = $e->getMessage();
}

file_put_contents($path_handler ."/dump_do_order.log", ob_get_clean());
?>