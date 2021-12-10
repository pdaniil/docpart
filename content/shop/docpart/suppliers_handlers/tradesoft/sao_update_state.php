<?php
/**
SAO
Действие: Обновить статус

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

$sao_state_object = json_decode($order_item["sao_state_object"], true);

$request = array(
	'service'	=> 'provider',
	'action'	=> 'getItemsStatus',
	'user'		=> $user_tradesoft,
	'password'	=> $password_tradesoft,
	'container'	=> array(
		array(
			'provider'	=> $provider,
			'login'		=> $user_provider,
			'password'	=> $password_provider,
			'items' => array($sao_state_object["result"][0]["items"][0]["orderItemId"])
		)
	)
);
$post = json_encode($request);

$ch = curl_init('https://service.tradesoft.ru/3/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$curl_result = curl_exec($ch);
$message = $curl_result;
curl_close($ch);

$curl_result = json_decode($curl_result, true);


if( $curl_result["error"] != "" )
{
	//ОШИБКА КОМАНДЫ
	$sao_result["status"] = false;
	$sao_result["message"] = "API поставщика вернул ошибку команды. ".$curl_result["error"];
}
else//Получаем текущий статус
{
	$item_state = $curl_result["container"][0]["items"][0];
	
	$item_state_id = $item_state["stateId"];
	$item_state_name = $item_state["stateName"];
	$item_state_error = $item_state["error"];
	
	//$new_sao_state - берем id из таблицы shop_sao_states. !!! НЕОБХОДИМО СВЕРЯТЬ ЭТОТ ПАРАМЕТР С ТАБЛИЦЕЙ
	
	//Далее определяем, какой статус поставить у себя
	$new_sao_state = 0;
	if( $item_state_error != "" )
	{
		$new_sao_state = 9;//Позиция не найдена
	}
	
	if($item_state_id == 1)
	{
		$new_sao_state = 2;//Заказано
	}
	
	if( $item_state_id == 3 )
	{
		$new_sao_state = 8;//Отказано
	}
	
	if( $item_state_id == 5 )
	{
		$new_sao_state = 10;//Заказано поставщику
	}
	
	
	if($new_sao_state == 0)
	{
		$sao_result["status"] = false;
		$sao_result["message"] = "Неопределено состояние на стороне поставщика $message";
	}
	else
	{
		//ПОЛНЫЙ УСПЕХ
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
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
		}
		
		
		//Состояние позиции, Запись технических параметоров SAO, Отображаемые комментарий
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_robot` = :sao_robot WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $new_sao_state);
		$update_query->bindValue(':sao_robot', 0);
		$update_query->bindValue(':sao_message', 'Обновлено состояние: '.date("d.m.Y H:i:s", time()));
		$update_query->bindValue(':id', $order_item_id);
		$update_query->execute();
		
		
		//5. Указываем задание роботу (при необходимости)
		//... нет заданий роботу
		
		//6. Формируем ответ
		$sao_result["status"] = true;
		$sao_result["message"] = "ok";
	}
}
?>