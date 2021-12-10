<?php


ob_start();

$sao_result = array();

//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

$login = $connection_options["login"];
$key = $connection_options["key"];

$gid = $t2_json_params["gid"]; //ID из прайса поставщика
$quantity = $order_item["count_need"]; //Кол-во

$url_request = "http://portal.moskvorechie.ru/portal.api?l={$login}&p={$key}&act=to_basket&gid={$gid}&q={$quantity}";//Запрос на добавление в коризну

try {
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	$exec = curl_exec($ch);//Выполняем

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
	
	//Анализируем ответ
	if ($action_status != 0) {
		
		$error = "";
		$error .= "Ошибка от сервера: {$action_msg} Статус: {$action_status} \n";

		var_dump($url_request);
		var_dump($decode);
		
		throw new Exception($error);
		
	}
	
	//После данного действия SAO-состояние данной позиции должно получить id=6 (В корзине)
	$new_sao_state = 6;
	
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
		`sao_message` = 'Добавлено в корзину: ".date("d.m.Y H:i:s", time())."', 
		`sao_state` = ?, 
		`sao_robot` = '0' 
	WHERE 
		`id` = ?;
	";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	if ( ! $db_link->prepare($SQL)->execute( array($exec, $new_sao_state, $order_item_id) ) ) 
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

$f = fopen($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/moskvorechie/sao.log", 'w');
fwrite($f, $bufer."\n");
fclose($f);

?>