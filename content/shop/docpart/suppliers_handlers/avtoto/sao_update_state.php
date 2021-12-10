<?php
ob_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/avtoto";

require_once($path_script . "/classes/avtoto_parts.curl.json.class.php");

$sao_result = array();
$sao_result["status"] = false;

/**
	* parts - Список запчастей для добавления в заказы (тип: индексированный массив):
	* InnerID* - ID записи в заказах AvtoTO (тип: целое)
	* RemoteID - ID записи в Вашей системе (тип: целое)
	* [*] — данные, сохраненные в результате добавления в заказы
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

// Удаление из корзины
$data = $avtoto->get_orders_status($parts);
$errors = $avtoto->get_errors();


if(!$errors && $data) {
	//Обрабатываем данные
	if(isset($data['OrdersInfo']) && $data['OrdersInfo']) {
		//Обрабатываем список предложений	
		
		foreach($data['OrdersInfo'] as $order_info) {
			
			//Сохраяем ID в корзине на сервере AvtoTO в своей системе
			$order_info_id = $order_info['RemoteID'];
			$avtoto_id = $order_info['InnerID'];

			$detail = $order_info['Info'];

			$status_id = $detail['progress'];
			$status_text = $detail['progress_text'];


			/**
			 * '2' => 'Ожидает оплаты',
			 * '1' => 'Ожидает обработки',
			 * '3' => 'Заказано',
			 * '4' => 'Закуплено',
			 * '5' => 'В пути',
			 * '6' => 'На складе',
			 * '7' => 'Выдано',
			 * '8' => 'Нет в наличии'
			 */

			// switch($status_id) {
			// 	case :
			// 		break;
			// 	default: 
			// }

			if($status_text !== "")
      {

	      $SQL = "UPDATE 
	          `shop_orders_items`
	      SET 
	          `sao_message` = ?
	      WHERE 
						`id` = ?;";

				//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
				if (!$db_link->prepare($SQL)->execute( array("$status_text: ".date("d.m.Y H:i:s", time()), $order_item_id) ) ) 
				{
					$error = "Произошёл сбой при обновлении sao-статуса!";
					throw new Exception($error);
				}

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

file_put_contents( $path_script . "/dump_updateStatus.txt", ob_get_clean() );
?>