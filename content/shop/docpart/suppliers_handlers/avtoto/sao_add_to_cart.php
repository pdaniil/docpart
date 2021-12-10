<?php
ob_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/avtoto";

require_once($path_script . "/classes/avtoto_parts.curl.json.class.php");

$sao_result = array();
$sao_result["status"] = false;


$login_params = array();

if(!$login_params) {
	$login_params = array(
		'user_id' =>  $connection_options["customer_id"],
		'user_login' => $connection_options["login"],
		'user_password' => $connection_options["password"]
	);
}


//Элементы для удаления из корзины
$elements_for_delete_from_basket = array();
//Элементы для оформления заказа
$elements_for_add_to_orders = array();
//Элементы для проверки статуса
$elements_for_get_status = array();

/**
	*Входные параметры: В качестве входного параметра необходим массив со следующей структурой:
	*
	* parts - Список запчастей для добавления в корзину (тип: индексированный массив):
	* Code - Код детали
	* Manuf - Производитель
	* Name - Название (тип: строка)
	* Price - Цена (тип: вещест.)
	* Storage* - Склад (тип: строка)
	* Delivery* - Срок доставки (тип: строка)
	* Count - Кол-во для покупки (тип: целое)
	* PartId* - Номер запчасти в списке результата поиска (тип: целое)
	* SearchID* - Номер поиска (тип: целое)
	* RemoteID - Id запчасти в Вашей системе(тип: целое)
	* Comment - Ваш комментарий к запчасти (тип: строка) [необязательный параметр]
	* [*] — данные, сохраненные в результате поиска
	* Необходимо, чтобы количество для покупки Count не превышало максимальное количество MaxCount, и соответствовало кратности заказа BaseCount
	*/

$avtoto = new avtoto_parts_curl_json($login_params);


$json_params 	= json_decode( $order_item["t2_json_params"], true );

//Данные одной позиции
$work_part1 = array();

//Наполняем данными
$work_part1['Code'] = $order_item['t2_article'];
$work_part1['Manuf'] = $order_item['t2_manufacturer'];
$work_part1['Name'] = $order_item['t2_name'];
$work_part1['Price'] = $order_item['t2_price_purchase'];
$work_part1['Storage'] = $order_item['t2_storage'];
$work_part1['Delivery'] = $order_item['t2_time_to_exe'];
$work_part1['Count'] = $order_item['count_need'];

$work_part1['PartId'] = $json_params['PartId'];
$work_part1['SearchID'] = $json_params['SearchId'];
$work_part1['RemoteID'] = $json_params['RemoteID'];

//Объедияем запчасти в один массив
$parts = array ($work_part1);


//Добавление в корзину
$data = $avtoto->add_to_basket($parts);
$errors = $avtoto->get_errors();




if(!$errors && $data) {
	//Обрабатываем данные
	if(isset($data['DoneInnerId']) && $data['DoneInnerId']) {
		//Обрабатываем список предложений	
		
		foreach($data['DoneInnerId'] as $added_part) {
			
			//Сохраяем ID в корзине на сервере AvtoTO в своей системе
			$part_cart_id = $added_part['RemoteID'];
			$avtoto_id = $added_part['InnerID'];
			
			//Сохраняем, чтобы в дальнейшем могли удалить из корзины или оформить заказ
			//После данного действия SAO-состояние данной позиции должно получить id=6 (Ва корзине)
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
			if (!$db_link->prepare($SQL)->execute( array($json_state_object, "В корзине: ".date("d.m.Y H:i:s", time()), $new_sao_state, '1', $order_item_id) ) ) 
			{
        $error = "Позиция добавлена, но произошёл сбой при обновлении sao-статуса!";
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


file_put_contents( $path_script . "/dump_addToCart.txt", ob_get_clean() );
?>