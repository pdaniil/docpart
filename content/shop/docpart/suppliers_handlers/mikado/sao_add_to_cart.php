<?php
/**
SAO
Действие: Добавить к корзину

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

ob_start();

// --------------------------------------------------------------------------------------
//1. Отправлем позицию в корзину

$login = $connection_options["user"];
$password = $connection_options["password"];


//Параметры склада из позиции:
$t2_json_params = json_decode($order_item["t2_json_params"], true);


//Отправляем запрос
$ch = curl_init("http://www.mikado-parts.ru/ws1/basket.asmx/Basket_Add?ZakazCode=".$t2_json_params["ZakazCode"]."&QTY=".$order_item["count_need"]."&DeliveryType=0&Notes=0&ClientID=$login&Password=$password&ExpressID=0&StockID=".$t2_json_params["StockID"]."");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
$curl_result = curl_exec($ch);
curl_close($ch);


$xml = simplexml_load_string($curl_result);
$json = json_encode($xml);
$curl_result_api = json_decode($json, true);

/*
Успех: {"Message":"OK","ID":"72445435"}
Ошибка: {"Message":"\u041d\u0435\u0434\u043e\u0441\u0442\u0430\u0442\u043e\u0447\u043d\u043e\u0435 \u043a\u043e\u043b\u0438\u0447\u0435\u0441\u0442\u0432\u043e \u043d\u0430 \u0441\u043a\u043b\u0430\u0434\u0435! \u0417\u0430\u043a\u0430\u0437\u0430\u043d\u043e 0\u0448\u0442","ID":"0"}
*/

var_dump($curl_result_api);

if( $curl_result_api["Message"] == "OK" )
{
	//После данного действия SAO-состояние данной позиции должно получить id=6 (В корзине)
	$new_sao_state = 6;
	
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
	$update_query->bindValue(':sao_message', 'Добавлено в корзину: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':sao_state_object', json_encode($curl_result_api));
	$update_query->bindValue(':sao_robot', 0);
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else
{
	$sao_result["status"] = false;
	$sao_result["message"] = $curl_result_api["Message"];
	
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', 'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':sao_state_object', json_encode($curl_result_api));
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}

file_put_contents("mikado_add_to_cart.log", ob_get_clean());

// --------------------------------------------------------------------------------------
?>