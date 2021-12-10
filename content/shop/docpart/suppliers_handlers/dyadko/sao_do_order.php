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
//1. Получаем логин и пароль

$login = $connection_options["login"];
$password = $connection_options["password"];

// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику

//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);

//XML-данные для запроса списка брэндов для артикула
$xml='<?xml version="1.0" encoding="UTF-8" ?>
<message>
<param>
<action>make_orders</action>
<login>'.$login.'</login>
<password>'.$password.'</password>
</param>
<basket>
<b_id>'.$t2_json_params["b_id"].'</b_id>
<name_parts>'.$order_item["t2_name"].'</name_parts>
<count>'.$order_item["count_need"].'</count>
<cost_sale>'.((int)$order_item["t2_price_purchase"] + 1).'</cost_sale>
<primech>-</primech>
<vid_dostavki>1</vid_dostavki>
</basket>
</message>';



$data = array('xml' => $xml);
$address="https://dyadko.ru/pricedetals2.php";//Адрес для запроса
$ch = curl_init($address);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$result=curl_exec($ch);//Получаем рузультат в виде xml




$xml = simplexml_load_string($result);
$json = json_encode($xml);

/*
$log = fopen($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/adeo/log.txt", "w");
fwrite($log, $result."\n\n".$json);
fclose($log);
*/

/*
{"detail":{"orderid":"6957407","msg":"\u0417\u0430\u043a\u0430\u0437 \u0440\u0430\u0437\u043c\u0435\u0449\u0435\u043d."}}
*/


$result = json_decode($json, true);


if( $result["detail"]["orderid"] > 0 )
{
	//Новое состояние будет 2 - Заказано
	$new_state_id = 2;
	
	//4. Вносим изменения в своей базе
	
	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
	$new_status_query->bindValue(':id', $new_state_id);
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
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_robot` = :sao_robot, `sao_state_object`= :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
	$update_query->bindValue(':sao_state', 2);
	$update_query->bindValue(':sao_robot', 0);
	$update_query->bindValue(':sao_state_object', $json);
	$update_query->bindValue(':sao_message', 'Заказано: '.date("d.m.Y H:i:s", time()).'<br>ID заказа: '.$result["detail"]["orderid"]);
	$update_query->bindValue(':id', $order_item['id']);
	$update_query->execute();

	
	//5. Указываем задание роботу (при необходимости)
	//... У Adeo нет заданий роботу
	
	//6. Формируем ответ
	$sao_result["status"] = true;
	$sao_result["message"] = "ok";
}
else//Ошибка размещения заказа
{
	$sao_result["status"] = false;
	$sao_result["message"] = $result["detail"]["msg"];
}
?>