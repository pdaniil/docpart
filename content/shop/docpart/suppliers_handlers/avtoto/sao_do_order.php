<?php
ob_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/avtoto";

require_once($path_script . "/classes/avtoto_parts.curl.json.class.php");


$sao_result = array();
$sao_result["status"] = false;

/**
	* parts - Список запчастей для добавления в заказы (тип: индексированный массив):
	* InnerID* - ID записи в корзине AvtoTO (тип: целое)
	* RemoteID - ID запчасти в Вашей системе (тип: целое)
	* Укажите RemoteID = test, чтобы совершить добавление в тестовом режиме.
	* Count - количество для добавления (необязательный параметр, тип: целое)
	* [*] — данные, сохраненные в результате добавления в корзину
	*/

$login_params = array();

if(!$login_params) {
	$login_params = array(
		'user_id' =>  $connection_options["customer_id"],
		'user_login' => $connection_options["login"],
		'user_password' => $connection_options["password"]
	);
}

$avtoto = new avtoto_parts_curl_json($login_params);

// Запчасти для удаления из корзины
$sao_object = json_decode( $order_item["sao_state_object"], true );

//Объедияем запчасти в один массив
$parts = $sao_object;

//Совершить тестовый заказ
// $parts = array(
// 		0 => array(
// 						'RemoteID' => 'test',
// 						'InnerID' => 67403182
// 				)

// 		);

// Удаление из корзины
$data = $avtoto->add_to_orders_from_basket($parts);
$errors = $avtoto->get_errors();


if(!$errors && $data) {
	//Обрабатываем данные
	if(isset($data['DoneInnerId']) && $data['DoneInnerId']) {
		//Обрабатываем список предложений	
		
		foreach($data['DoneInnerId'] as $order_part) {
			
			//Сохраяем ID в корзине на сервере AvtoTO в своей системе
			$part_order_id = $order_part['RemoteID'];
			$avtoto_id = $order_part['InnerID'];
			
			//После данного действия SAO-состояние данной позиции должно получить id=2 (Заказано)
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
			
			$json_state_object = json_encode($data['DoneInnerId']);

      $SQL = "UPDATE 
          `shop_orders_items` 
      SET 
          `sao_state_object` = ?,
          `sao_message` = ?, 
          `sao_state` = ?, 
          `sao_robot` = ? 
      WHERE 
					`id` = ?;";
					
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			if (!$db_link->prepare($SQL)->execute( array($json_state_object, "Заказано: ".date("d.m.Y H:i:s", time()), $new_sao_state, '2', $order_item_id) ) ) 
			{
        $error = "Позиция заказана, но произошёл сбой при обновлении sao-статуса!";
        throw new Exception($error);
			}
			
	    $sao_result["status"] = true;
	    $sao_result['order_items'] = array($order_item_id);
		
		}
	}
	
	if(isset($data['Errors']) && $data['Errors']) {
		$error_message = '';
		if(is_array($data['Errors'])) {
			foreach ($data['Errors'] as $error_arr) {
				if(isset($error_arr['Errors']) && $error_arr['Errors']) {
					if(is_array($error_arr['Errors'])) {
						foreach ($error_arr['Errors'] as $error) {
							$error_message .= ' '. $error;
						}
					}
				}
			}
		}
		$sao_result["message"] = "Ошибки." . " " . $error_message;
	}

} else {
	if($errors) { 
		//Ответ не получен
		$sao_result["status"] = false;
		$error_message = '';
		if(is_array($errors)) {
			foreach ($errors as $error) {
				$error_message .= ' '. $error['error'];
			}
		}
		$sao_result["message"] = "Ошибки." . " " . $error_message;
	} else { 
		//Ответ не получен
		$sao_result["status"] = false;
		$sao_result["message"] = "Ошибки. Ответ не получен";
	}
}

file_put_contents( $path_script . "/dump_doOrder.txt", ob_get_clean() );
?>