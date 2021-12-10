<?php
/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

ob_start();

// --------------------------------------------------------------------------------------
//1. Получаем логин и пароль

$login = $connection_options["login"];
$password = $connection_options["password"];
$sao_test_mode = $connection_options["sao_test_mode"];

// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику


//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

//Параметры запроса на создание заказа
$fields = array(
	'format' => "json",
	'VKORG' => $t2_json_params["VKORG"],
	'KUNRG' => $t2_json_params["KUNNR_RG"],
	'ITEMS' => array(),
	'DBTYP' => 3
	
);

$fields['ITEMS'][0] = array(
	'PIN'=>$order_item["t2_article"],
	'BRAND'=>$order_item["t2_manufacturer"],
	'KWMENG'=>$order_item["count_need"],
	'KEYZAK'=>$t2_json_params["KEYZAK"],
	'PRICEMAX'=>$order_item["price"],
	'DATEMAX'=>$t2_json_params["DLVDT"]
	);
/*
Для для фиксированного тестового запроса
$fields['ITEMS'][0] = array(
	'PIN'=>'oc47',
	'BRAND'=>'KNECHT',
	'KWMENG'=>'1'
	);
*/
$field_string = http_build_query($fields);
//Запрос на создание заказа
$ch = curl_init();
if($sao_test_mode)
{
	curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_order/createTestOrder");//Тестовый заказ
}
else
{
	curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_order/createOrder");//Боевой заказ
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
$curl_result = curl_exec($ch);
curl_close($ch);
$curl_result_str = $curl_result;//Для записи строки ответа в БД
$curl_result = json_decode($curl_result, true);


var_dump($curl_result);

//3. Анализируем результат
if($curl_result["STATUS"] != 200)
{
	//ОШИБКА КОМАНДЫ
	$sao_result["status"] = false;
	$sao_result["message"] = "API поставщика вернул код ".$curl_result["STATUS"].". Сообщения Армтек: ";
	for($i=0; $i < count($curl_result["MESSAGES"]); $i++)
	{
		$sao_result["message"] .= " ".$curl_result["MESSAGES"][$i]["TEXT"];
	}
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
	$update_query->bindValue(':sao_state_object', $curl_result_str);
	$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else
{
	$ERROR = $curl_result["RESP"]["ITEMS"][0]["ERROR"];
	$ERROR_MESSAGE = $curl_result["RESP"]["ITEMS"][0]["ERROR_MESSAGE"];
	
	if( $ERROR == 0 )//ОШИБОК ЗАПРОСА НЕТ
	{
		if( count($curl_result["MESSAGES"]) != 0  )//НО ЗАКАЗ НЕ ОФОРМЛЕН
		{
			$sao_result["status"] = false;
			$sao_result["message"] = "Запрос выполнен, однако API поставщика ответил: ";
			for($i=0; $i < count($curl_result["MESSAGES"]); $i++)
			{
				if($i > 0)
				{
					$sao_result["message"] = $sao_result["message"].",";
				}
				$sao_result["message"] = $sao_result["message"]." ".$curl_result["MESSAGES"][$i]["TEXT"];
			}
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
			$update_query->bindValue(':sao_state_object', $curl_result_str);
			$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
			$update_query->bindValue(':id', $order_item_id);
			$update_query->execute();
		}
		else//УСПЕХ - ЗАКАЗ ОФОРМЛЕН В АРМТЕК
		{
			//Новое состояние будет 2 - Заказано
		
			//4. Вносим изменения в своей базе
			
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
			$new_status_query->execute();
			$new_status_record = $new_status_query->fetch();
			$new_status = $new_status_record["status_id"];
			if($new_status > 0)
			{
				//Отправляем запрос на изменение статуса позиции
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
			}
			
			//4.1. Состояние позиции, //4.1. Запись технических параметоров SAO, // 4.3. Отображаемые комментарий
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = 2, `sao_robot` = 0, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
			$update_query->bindValue(':sao_state_object', $curl_result_str);
			$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()));
			$update_query->bindValue(':id', $order_item_id);
			$update_query->execute();
			
			//5. Указываем задание роботу (при необходимости)
			//... У Армтек нет заданий роботу
			
			//6. Формируем ответ
			$sao_result["status"] = true;
			$sao_result["message"] = "ok";
		}
	}
	else//ЗАКАЗ НЕ ОФОРМЛЕН - ВЫДАЕМ СООБЩЕНИЕ
	{
		$sao_result["status"] = false;
		$sao_result["message"] = "Запрос выполнен, однако API поставщика ответил: ".$ERROR_MESSAGE;
		
		//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state_object', $curl_result_str);
		$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
		$update_query->bindValue(':id', $order_item_id);
		$update_query->execute();
	}
}

file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/armtek/sao.log", ob_get_clean());
?>