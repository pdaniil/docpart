<?php
/*

Скрипт вставляется в конеч скрипта \content\shop\finance\pay_for_order.php
Создает чек после совершения онлайн платежа

*/

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

if(empty($db_link)){
	//Подключение к БД
	try
	{
		$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
	}
	catch (PDOException $e) 
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = 'Не соединения с БД. Чек не создан';
		exit(json_encode($answer));
	}
	$db_link->query("SET NAMES utf8;");
}

if(!empty($operation_id)){
	// Подключаем массив отмененных статусов позиций - $orders_items_statuses_not_count
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

	// Получаем данные финансовой операции
	$operation_query = $db_link->prepare("SELECT * FROM `shop_users_accounting` WHERE `id` = $operation_id AND `active` = 1;");
	$operation_query->execute();
	$operation = $operation_query->fetch();

	if(!empty($operation)){
		// Операция существует
		
		$amount = (float) $operation["amount"];// Сумма платежа
		$pay_orders = (int) $operation["pay_orders"];// id заказа, оплата которого была этой операцией
		
		// Получаем почту или телефон клиента на которую будет отправлен электронный чек
		$email = '';
		//$email = 'info@intask.pro';
		if(empty($email)){
			if($operation["user_id"] > 0){
				// Клиент зарегистрирован
				$main_field_query = $db_link->prepare("SELECT `email`, `phone` FROM `users` WHERE `user_id` = ".$operation["user_id"]);
				$main_field_query->execute();
				$main_field_record = $main_field_query->fetch();
				
				$main_field = '';
				if(!empty($main_field_record["email"])){
					$main_field = trim($main_field_record["email"]);
				}else{
					if(!empty($main_field_record["phone"])){
						$main_field = trim($main_field_record["phone"]);
					}
				}
				
				if(!empty($main_field)){
					if(strpos($main_field, '@') === false){
						$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $main_field);
						if(strlen($phone) == 11){
							$phone = substr($phone, 1);
						}
						$phone = '+7'.$phone;
					}else{
						$email = $main_field;
					}
				}
			}else{
				// Клиент без регистрации, возьмем данные для уведомления из заказа
				if(!empty($operation["pay_orders"])){
					
					$order_id_tmp = (int) trim($operation["pay_orders"]);
					
					$order_data_query = $db_link->prepare("SELECT `how_get_json`, `phone_not_auth`, `email_not_auth` FROM `shop_orders` WHERE `id` = $order_id_tmp;");
					$order_data_query->execute();
					$order_data_record = $order_data_query->fetch();
					
					$notify_settings = '';
					if(!empty($order_data_record["email_not_auth"])){
						$notify_settings = trim($order_data_record["email_not_auth"]);
					}else{
						if(!empty($order_data_record["phone_not_auth"])){
							$notify_settings = trim($order_data_record["phone_not_auth"]);
						}
					}
					
					if(!empty($notify_settings)){
						if(strpos($notify_settings, '@') === false){
							$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $notify_settings);
							if(strlen($phone) == 11){
								$phone = substr($phone, 1);
							}
							$phone = '+7'.$phone;
						}else{
							$email = $notify_settings;
						}
					}else{
						$how_get_json = json_decode($order_data_record["how_get_json"], true);
						if(!empty($how_get_json['phone_not_auth'])){
							$phone = $how_get_json['phone_not_auth'];
							$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $phone);
							if(strlen($phone) == 11){
								$phone = substr($phone, 1);
							}
							$phone = '+7'.$phone;
						}else{
							if(!empty($how_get_json['phone'])){
								$phone = $how_get_json['phone'];
								$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $phone);
								if(strlen($phone) == 11){
									$phone = substr($phone, 1);
								}
								$phone = '+7'.$phone;
							}
						}
					}
				}
			}
		}
		
		// Настройки параметров чека
		$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 1 LIMIT 1;");
		$shop_kkt_default_setting_query->execute();
		$shop_kkt_default_setting = $shop_kkt_default_setting_query->fetch();
		
		// Объект чека
		$check_object = array();
		$check_object['check_type'] = 'usual';//Обычный чек (т.е. не чек коррекции)
		$check_object['products'] = array();
		$check_object['payments'] = array();
		
		// Тип чека
		$check_object['tag_1054'] = array();
		$check_object['tag_1054']['value'] = 1;
		$check_object['tag_1054']['for_print'] = 'Приход';
		
		// Налогообложение
		$ref_tag_1055_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1055` WHERE `value` = ". $shop_kkt_default_setting['taxationSystem']);
		$ref_tag_1055_query->execute();
		$ref_tag_1055 = $ref_tag_1055_query->fetch();
		$check_object['tag_1055'] = array();
		$check_object['tag_1055']['value'] = $ref_tag_1055["value"];
		$check_object['tag_1055']['for_print'] = $ref_tag_1055["for_print"];
		
		// Касса
		$ref_kkt_devices_query = $db_link->prepare("SELECT * FROM `shop_kkt_devices` WHERE `id` = ". $shop_kkt_default_setting['kkt_device_id']);
		$ref_kkt_devices_query->execute();
		$ref_kkt_devices = $ref_kkt_devices_query->fetch();
		$check_object['kkt'] = array();
		$check_object['kkt']['id'] = $ref_kkt_devices["id"];
		$check_object['kkt']['for_print'] = $ref_kkt_devices["name"];
		
		// Контакт покупателя
		if(!empty($email)){
			$check_object['customer_contact'] = $email;
		}else if(!empty($phone)){
			$check_object['customer_contact'] = $phone;
		}
		
		// Формируем позиции чека
		$products = array();
		if(!empty($pay_orders)){
			$order_items_query = $db_link->prepare("SELECT * FROM `shop_orders_items` WHERE `order_id` = $pay_orders;");
			$order_items_query->execute();
			$count_need_total = 0;//Итого количество
			$price_sum_total = 0;//Итого сумма
			while($order_items_record = $order_items_query->fetch()){
				$item_status 		= $order_items_record["status"];
				$item_count_need    = $order_items_record["count_need"];
				$item_price         = $order_items_record["price"];
				
				// Если цена 0 то пропускаем, что бы не было ошибки
				if($item_price <= 0){
					continue;
				}
				
				//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
				if( array_search($item_status, $orders_items_statuses_not_count) === false)
				{
					$count_need_total += $item_count_need;
					$price_sum_total  += $item_price * $item_count_need;
				}else{
					// Если статус позиции - отмена то пропускаем позицию
					continue;
				}
				
				// Получаем наименование товара
				$name = '';
				if($order_items_record['product_type'] == 1){
					$product_id = (int) $order_items_record['product_id'];
					$product_query = $db_link->prepare("SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = $product_id;");
					$product_query->execute();
					$caption = $product_query->fetch();
					$name = $caption['caption'];
				}else{
					$name = $order_items_record['t2_manufacturer'] .' - '. $order_items_record['t2_article'] .' - '. $order_items_record['t2_name'];
				}
				
				$name = str_replace(array("'", '"', "\n", "\r", "\t", "\\"), '', $name);
				$name = trim($name);
				$name = mb_substr($name, 0, 255, 'UTF-8');
				if($name == ''){$name = 'Автозапчасть';}
				
				$products[] = array(
					"local_id" => count($products)+1,
					"name" => $name,
					"price" => (float) number_format($order_items_record['price'],2,'.',''),
					"count" => (float) $order_items_record['count_need'],
					
					"tag_1199" => $shop_kkt_default_setting['check_product_tax'],//Ставка НДС
					"tag_1214" => $shop_kkt_default_setting['check_product_paymentMethodType'],//Признак способа расчета
					"tag_1212" => $shop_kkt_default_setting['check_product_paymentSubjectType'],//Признак предмета расчета
					
					"order_item_id" => $order_items_record['id']
				);
			}
		}else{
			$products[] = array(
				"local_id" => count($products)+1,
				"name" => "Авансовый платеж",
				"price" => (float) number_format($amount,2,'.',''),
				"count" => 1,
				
				"tag_1199" => 6,//Ставка НДС - НДС НЕ ОБЛАГАЕТСЯ
				"tag_1214" => 4,//Полная оплата, в том числе с учетом аванса (предварительной оплаты) в момент передачи предмета расчета
				"tag_1212" => 10,//Признак предмета расчета - ПЛАТЕЖ
				
				"order_item_id" => 0
			);
		}
		$check_object['products'] = $products;
		
		// Формируем платежи чека
		$products = array();
		$payments[] = array(
						"local_id" => 1,
						"type_tag" => $shop_kkt_default_setting['check_payment_type'],
						"amount" => (float) number_format($amount,2,'.','')
		);
		$check_object['payments'] = $payments;
		
		$check_object_json = json_encode($check_object);
		
		if(!empty($check_object_json)){
			$postdata = http_build_query(
				array(
					'initiator' => 'php',
					'check_object' => $check_object_json,
					'key' => $DP_Config->tech_key
				)
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path.$DP_Config->backend_dir."/content/shop/kkt/ajax_create_check.php");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
			$curl_result = json_decode($curl_result, true);
			
			if($curl_result['status'] !== true){
				$message = $curl_result['message'];
			}
		}
	}
}
?>