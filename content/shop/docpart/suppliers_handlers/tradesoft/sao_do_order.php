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

// --------------------------------------------------------------------------------------
//1. Получаем настройкт подключения к API поставщика

$user_tradesoft = $connection_options["user_tradesoft"];//Логин на сайте tradesoft.ru
$password_tradesoft = $connection_options["password_tradesoft"];//Пароль  на сайте tradesoft.ru
$provider = $connection_options["provider"];//Уникальный идентификатор поставщика (клиент tradesoft)
$user_provider = $connection_options["user_provider"];//Логин и на сайте поставщика
$password_provider = $connection_options["password_provider"];//Пароль и на сайте поставщика


// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику

//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

//2.1 Запрос id позиции. Т.е. ее нужно уточнять дополнительно
$request = array(
	'service'	=> 'provider',
	'action'	=> 'PreOrderSearch',
	'user'		=> $user_tradesoft,
	'password'	=> $password_tradesoft,
	"timeLimit"	=> '10',
	'container'	=> array(
		array(
			'provider'	=> $provider,
			'login'		=> $user_provider,
			'password'	=> $password_provider,
			'code'		=> $t2_json_params["code"],
			'producer'	=> $t2_json_params["producer"],
			'itemHash'	=> $t2_json_params["itemHash"]
		)
	)
);
$post = json_encode($request);

$ch = curl_init('https://service.tradesoft.ru/3/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$curl_result = curl_exec($ch);
$curl_result_str = $curl_result;//Для записи строки ответа в БД
curl_close($ch);

$curl_result = json_decode($curl_result, true);


if( $curl_result["error"] != "" )
{
	//ОШИБКА КОМАНДЫ
	$sao_result["status"] = false;
	$sao_result["message"] = "API поставщика не нашел id позиции. ".$curl_result["error"];
	
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':sao_state_object', $curl_result_str);
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else
{
	$itemId = $curl_result["container"][0]["items"][0]["itemId"];
	
	/*
	$log = fopen("log.txt", "w");
	fwrite($log, $curl_result_str);
	fclose($log);
	*/
	
	
	//2.2 Запрос на создание заказа
	$request = array(
		'service'	=> 'provider',
		'action'	=> 'makeOrderOffline',
		'user'		=> $user_tradesoft,
		'password'	=> $password_tradesoft,
		'param'	=> array(
			array(
				'provider'	=> $provider,
				'login'		=> $user_provider,
				'password'	=> $password_provider,
				'comment'	=> 'Заказ '.$order_item["order_id"].", позиция ".$order_item["id"],
				'clientOrderNumber'	=> $order_item["order_id"],
				'items' => array(
							array(
								'itemId' =>$itemId,
								'quantity' => $order_item["count_need"],
								'reference' => $order_item["id"],
								'comment' => 'Позиция '.$order_item["id"]
							)
					)
			)
		)
	);
	$post = json_encode($request);

	$ch = curl_init('https://service.tradesoft.ru/3/');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$curl_result = curl_exec($ch);
	$curl_result_str = $curl_result;//Для записи строки ответа в БД
	curl_close($ch);

	$curl_result = json_decode($curl_result, true);


	//--------------

	//3. Анализируем результат

	if($curl_result["error"] != "")
	{
		//ОШИБКА КОМАНДЫ
		$sao_result["status"] = false;
		$sao_result["message"] = "API поставщика вернул ошибку ".$curl_result["error"];
		
		
		//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
		$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
		$update_query->bindValue(':sao_state_object', $curl_result_str);
		$update_query->bindValue(':id', $order_item_id);
		$update_query->execute();
	}
	else
	{
		//Проверяем позицию
		$item = $curl_result["result"][0]["items"][0];
		
		if($item["error"] != "")
		{
			//ОШИБКА КОМАНДЫ
			$sao_result["status"] = false;
			$sao_result["message"] = "API поставщика вернул ошибку на уровне позиции ".$item["error"].". Был указан itemId: ".$itemId;
			
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
			$update_query->bindValue(':sao_message', 'Ошибка заказа: '.date("d.m.Y H:i:s", time()));
			$update_query->bindValue(':sao_state_object', $curl_result_str);
			$update_query->bindValue(':id', $order_item_id);
			$update_query->execute();
		}
		else//Ошибок на уровне позиции нет - считаем, что команда на создание заказа выполнена успешно
		{
			//ПОЛНЫЙ УСПЕХ
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
			$new_status_query->bindValue(':id', 2);
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
			
			
			//Состояние позиции, Запись технических параметоров SAO, Отображаемые комментарий
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
			$update_query->bindValue(':sao_state', 2);
			$update_query->bindValue(':sao_robot', 0);
			$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()));
			$update_query->bindValue(':sao_state_object', $curl_result_str);
			$update_query->bindValue(':id', $order_item_id);
			$update_query->execute();
			
			
			//5. Указываем задание роботу (при необходимости)
			//... нет заданий роботу
			
			//6. Формируем ответ
			$sao_result["status"] = true;
			$sao_result["message"] = "ok";
		}
	}
	
}
?>