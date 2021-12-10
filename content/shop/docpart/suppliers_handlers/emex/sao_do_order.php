<?php
require_once("classes/lib.php");

//Конфигурация
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

//Инициализируем переменные
$sao_object 			= array(); //Тех параметры для SAO
$sao_result			= array();//Результат работы API
$sao_result["status"]	= false;	

$data = date("d-m-Y H:i:s");

$login					= $connection_options["login"];//Логин Emex
$password				= $connection_options["password"];//Пароль Emex
$sao_state_object		= json_decode($order_item["sao_state_object"], true);//Параметры для SAO

try {
	
	$wsdl_service = "http://ws.emex.ru/EmEx_Basket.asmx?wsdl";
	$soap_options = array(
		'soap_version' => SOAP_1_2
	);
	
	$api = new EmexService( $wsdl_service, $soap_options );
	
	$Request = array (
		"login"=> $login,
		"password"=> $password
	);
	
	$addParams = array(
		"basketPart" => 'Basket',
		"reference" => $sao_state_object["Reference"]
	);
	
	$api->getRequest( $Request );
	$BasketData = $api->GetBasket( $addParams ); //Получаем данные корзины
	
	if ( is_object( $BasketData ) ) {
		
		$baket_state = $BasketData->getState();
		
		//Получили 10-й статус(Товар в корзине)
		if ( $baket_state['state_id'] == 10 ) {
			
			if ( $BasketData->getPricePotrDiffRUR() == 0 ) { //Цена не изменилась
				
				//Команда смены статуса
				$basketChanging = array(
					"Id" =>  $BasketData->data->GlobalId,
					"Timestamp" => $BasketData->data->timestamp,
					"cmd" => 'InOrder'
				);
				
				$addParams = array(
					"queryList" => array( $basketChanging )
				);
				
				//Меняем состояние позиции в корзине( Отправляем в заказ )
				$res = $api->createOrder( $addParams );
				
				//Обрабатываем результат
				if ( $res !== false ) {
					
					//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
            		$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;");
                	$new_status_query->execute();
                	$new_status_record = $new_status_query->fetch();
            		$new_status = $new_status_record["status_id"];
					
					if ( $new_status > 0 ) {
						
						//Отправляем запрос на изменение статуса позиции
						$orders_items_list = array( $order_item["id"] );
						$order_items_json = json_encode( $orders_items_list );
						
						$url_service = $DP_Config->domain_path;
						$action_service = "content/shop/protocol/set_order_item_status.php";
						
						$params_action = array(
							"initiator" => 2,
							"orders_items" => $order_items_json,
							"status" => $new_status,
							"key" => $DP_Config->tech_key
						);
						
						$url_request = $url_service . $action_service . "?" . http_build_query( $params_action );
						
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_URL, $url_request );
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						$curl_result = curl_exec($ch);
						curl_close($ch);
						
					}

					$new_sao_state = 2; //Состояние "Заказано"
					
					//Обновляем статус позиции в docpart
					$UPDATE_ORDERITEM = "
						UPDATE 
							`shop_orders_items`
						SET
							`sao_state` = ?,
							`sao_message` = ?,
							`sao_robot` = ?
						WHERE
							`id` = ?
					;";
			
					if( ! $db_link->prepare($UPDATE_ORDERITEM)->execute( array($new_sao_state, 'Заказ в обработке<br/> '.$data, 0, $order_item["id"])) ) {
						
						$error = "Позиция заказана, но произла ошибка обновления записи";
						throw new Exception( $error );
						
					}
						
					$sao_result["status"] = true; //Успех операции
					
				} else {
					
					//Ошибка добавления в заказ
					throw new Exception( "Ошибка создания заказа" );
					
				}
				
			//end if ($basket->PricePotrDiffRUR == 0)
			} else {
				
				/*
					//** Если нужно отменять позиции при изменении цены, раскоментить
				 
				//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния (Отказ поставщика)
				$new_status_query = mysqli_query($db_link, "SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 5;");
				$new_status_record = mysqli_fetch_array($new_status_query);
				$new_status = $new_status_record["status_id"];
				
				if ( $new_status > 0 ) {
					
					//Отправляем запрос на изменение статуса позиции
					$orders_items_list = array( $order_item["id"] );
					$order_items_json = json_encode( $orders_items_list );
					
					$url_service = $DP_Config->domain_path;
					$action_service = "content/shop/protocol/set_order_item_status.php";
					
					$params_action = array(
						"initiator" => 2,
						"orders_items" => $order_items_json,
						"status" => $new_status,
						"key" => $DP_Config->tech_key
					);
					
					$url_request = $url_service . $action_service . "?" . http_build_query( $params_action );
					
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $url_request );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					$curl_result = curl_exec($ch);
					curl_close($ch);
					
				}
				*/
				//Обновляем статус позиции в docpart
				$UPDATE_ORDERITEM = "
					UPDATE 
						`shop_orders_items`
					SET
						`sao_state` = ?,
						`sao_message` = ?,
						`sao_robot` = ?
					WHERE
						`id` = ?
				;";
		
				if( ! $db_link->prepare($UPDATE_ORDERITEM)->execute( array(6, 'Изменение цены<br/> '.$data, 0, $order_item["id"]))) {
					
					$error = "Изменение цены";
					throw new Exception( $error );
					
				}
				
			}
			
		//end if($basket->StatusCode == 10)
		} else {
			
			//Ошибка  статуса отличнного от 10
			$error = "Позиция не в корзине: " . $baket_state['comment'];
			throw new Exception( $error );
			
		} 
		
	} else {
		
		$error = "Ошбика получения корзины";
		throw new Exception( $error );
		
	}
	
	
} catch ( SoapFault $e ) {
	
	$sao_result["message"] = $e->getMessage();
	
} catch ( Exception $e ) {
	
	$sao_result["message"] = $e->getMessage();

}

?>