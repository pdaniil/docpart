<?php

ob_start();

$sao_result = array();

//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

$login 				= $connection_options["login"];
$key				= $connection_options["key"];
$delivery_option	= $connection_options["delivery_option"]; //Способ достаки


$url_request = "http://portal.moskvorechie.ru/portal.api?l={$login}&p={$key}&act=make_order&d=0&dt={$delivery_option}&cs=utf8";//Запрос на добавление в коризну


try {
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	$exec = curl_exec($ch);//Выполняем
	
	var_dump($exec);
	
	if ( ! $exec ) {
		
		var_dump($url_request);
		throw new Exception("Ошибка culr: " . curl_errno($ch));
		
	}
	
	curl_close($ch);
	
	
	
	$decode = json_decode($exec, true);
	
	if ( ! $decode ) {
		
		throw new Exception("Ошибка json: " . json_last_error());
		
	}
	
	$action_result	= $decode['result'];
	
	$action_status	= $action_result['status'];
	$action_msg 	= $action_result['msg'];
	
	var_dump($action_result);
	
	//Анализируем ответ
	if ($action_status != 0) {
		
		$error = "";
		$error .= "Ошибка от сервера: {$action_msg} Статус: {$action_status} \n";

		var_dump($url_request);
		var_dump($decode);
		
		throw new Exception($error);
		
	}
	
	$new_sao_state = 2;
	
	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
	$new_status_query->execute( array($new_sao_state) );
	$new_status_record = $new_status_query->fetch();
	$new_status = $new_status_record["status_id"];
	if($new_status > 0)
	{
		//Отправляем запрос на изменение статуса позиции
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".$DP_Config->tech_key);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_result = curl_exec($ch);
		curl_close($ch);
	}
	
	$SQL = "
	UPDATE 
		`shop_orders_items` 
	SET 
		`sao_state_object` = ?,
		`sao_message` = ?, 
		`sao_state` = ?, 
		`sao_robot` = '0' 
	WHERE 
		`id` = ?;
	";

	$action_result_id = $action_result[0]['id'];
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	if ( ! $db_link->prepare($SQL)->execute( array($exec, "Заказаоно у поставщика: ".date("d.m.Y H:i:s", time())." <br/> ID : {$action_result_id}", $new_sao_state, $order_item_id) ) )
	{	
		$error = "Позиция заказана, но произошёл сбой при обновлении sao-статуса!";
		throw new Exception($error);	
	}
	
	$sao_result["status"] = true;
	
} catch (Exception $e) {
	
	echo  "\n" . $e->getMessage() . "\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

$bufer = ob_get_contents();
ob_end_clean();

$f = fopen($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/moskvorechie/sao_do_order.log", 'w');
fwrite($f, $bufer."\n");
fclose($f);
?>