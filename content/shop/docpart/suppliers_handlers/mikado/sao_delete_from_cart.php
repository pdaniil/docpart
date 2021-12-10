<?php
/**
SAO
Действие: Удаление из корзины

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

// --------------------------------------------------------------------------------------
//1. Отправлем позицию в корзину

$login = $connection_options["user"];
$password = $connection_options["password"];


//Параметры позиции:
$sao_state_object = json_decode($order_item["sao_state_object"], true);

ob_start();

//Отправляем запрос
$ch = curl_init("http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Delete?ItemID=".$sao_state_object["ID"]."&ClientID=$login&Password=$password");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
$curl_result = curl_exec($ch);
curl_close($ch);

$xml = simplexml_load_string($curl_result);

if( $xml == "OK" )
{
	//После данного действия SAO-состояние данной позиции должно получить id=6 (В корзине)
	$new_sao_state = 7;//Удалено из корзины
	
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
	
	
	
	$sao_result["status"] = true;
	$sao_result["message"] = "Ок";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
	$update_query->bindValue(':sao_state', $new_sao_state);
	$update_query->bindValue(':sao_message', 'Удалено из корзины: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':sao_state_object', json_encode($curl_result));
	$update_query->bindValue(':sao_robot', 0);
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else
{
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка удаления из корзины";
	
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', 'Ошибка удаления из корзины: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
	
	
	var_dump($xml);
	var_dump($curl_result);
	
}

file_put_contents("mikado_dump_delete_cart.log", ob_get_clean());
?>