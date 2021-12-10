<?php
// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );

require_once( 'FrozaApi.php' );


$login 		= $connection_options['login'];
$password		= $connection_options['password'];
$wsdl 			= "http://www.froza.ru/webservice/orders.php?WSDL";

$item_params = json_decode( $order_item['sao_state_object'], true );
$supp_order_id = $item_params['global_id'];

$order_data = array();

$sql_order = "
SELECT 
	`time` 
FROM 
	`shop_orders`
WHERE
	`id` = '{$order_item['order_id']}'
";

$mysqli_res = mysqli_query( $db_link, $sql_order );
$order_data = mysqli_fetch_assoc( $mysqli_res );

$time_for_order = $order_data['time'];

$order_date 		= date( 'd.m.y', $time_for_order );
$now_date 			= date( 'd.m.y' );
$now_date_time	= date( 'd.m.y H:i:s' );

$api_options	= array (
	"login" => $login,
	"password" => $password,
	"wsdl" => $wsdl
);

$action = "getClientOrderDetails";

$params_action = array (
	'date_start' => $order_date,
	'date_end' => '',
	'status' => '',
	'archive' => '1',
	'search' => $order_item['t2_article'],
	'type_search' => '3',
	'date_st' => '0'
);


$api = new FrozaApi( $api_options );

$api->getAction( $action );
$api->getParamsAction( $params_action );

try {
	
	$res_action = $api->execAction();
	
	if ( is_object( $res_action ) ) {
		
		// Logger::addLog( '$res_action', $res_action );
		// Logger::addLog( '$params_action', $params_action );
		
		$ClientOrderDetails = $res_action->getClientOrderDetailsResult->ClientOrderDetails;
		
		//Позиций может быть несколько, нужно найти конкретную ( UPD: поиск по referense должен исправить эту проблему )
		if ( is_array( $ClientOrderDetails ) ) {
			
			$ClientOrderDetails = $api->searchOrderItemByOrderId( $ClientOrderDetails, $supp_order_id );
			
			if ( ! is_object( $ClientOrderDetails ) ) {
				
				$error_message = "Ошибка: в списке заказов не найден заказ с параметром GlobalID: {$supp_order_id}<br /> {$now_date_time}";
				
				$UPDATE_ORDERITEM = "UPDATE `shop_order_items` SET 'sao_message' = ? WHERE `id` = ?;";
				
				$binds = array();
				$binds[] = $error_message;
				$binds[] = $order_item["id"];
				
				$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
				$res_exec = $stmt->execute( $binds );
				
				if ( ! $res_exec ) {
					
					$error_message = "Ошибка обновления состояния";	
					throw new Exception( $error_message );
					
				}
				
			}
		
		}
		
		$cancel_status_names = array (
			'Отказ'
		);
		
		//Отменить выполение задания для робота
		$stop_robot_action_statuses = array (
			'Выдано'
		);
		
		$state_name = $ClientOrderDetails->status_name;
		
		// Logger::addLog( '$ClientOrderDetails', $ClientOrderDetails );
		
		// Отказ
		if ( in_array( $state_name, $cancel_status_names ) ) {
			
			$need_sao_state_id = 5; //Отказ поставщика
		
			//Получаем статус позиции, для sao-состояния
			$sql = "SELECT `status_id` FROM `shop_sao_states` WHERE `id` = '{$need_sao_state_id}';";
			$new_status = $db_link->query( $sql, PDO::FETCH_COLUMN, 0 );
			
			$service = "{$DP_Config->domain_path}content/shop/protocol/set_order_item_status.php";
			
			$params_action = array (
				'initiator' => 2,
				'orders_items' => json_encode( array( $order_item["id"] ) ),
				'status' => $new_status,
				'key' => $DP_Config->tech_key
			);
			
			$url_request = $service . http_build_query( $params_action );
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url_request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
			$sao_message = "Отказ поставщика <br/>GlobalID: {$supp_order_id}<br/>{$now_date_time}";
			$UPDATE_ORDERITEM = "
			UPDATE 
				`shop_orders_items` 
			SET 
				`sao_state` = ?,
				`sao_robot` = '0',
				`sao_message` = ?
			WHERE 
				`id` = ?;";
				
			$binds = array();
			$binds[] = $need_sao_state_id;
			$binds[] = $sao_message;
			$binds[] = $order_item["id"];
			
			$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
			$res_exec = $stmt->execute( $binds );
			
			if ( ! $res_exec ) {
				
				$error_message = "Ошибка обновления состояния";
				throw new Exception( $error_message );
				
			}
			
			
		} // ~!if ( in_array( $state_name, $cancel_status_names ) )
		else {
			
			$need_sao_state_id 		= 2;
			
			$status_id 				= $ClientOrderDetails->status_id;
			$price_client_primary 	= $ClientOrderDetails->price_client_primary;
			$price_client_final 		= $ClientOrderDetails->price_client_final;
			
			$sao_message = "GlobalID: $supp_order_id<br/>";
			$sao_message .= "Статус заказа: $state_name<br/>";
			$sao_message .= "ID статуса: $status_id<br/>";
			$sao_message .= "Цена оптовика при заказе: $price_client_primary<br/>";
			$sao_message .= "Цена оптовика конечная: $price_client_final<br/>";
			$sao_message .= "{$now_date_time}";
			
			$sao_robot = 0; //2 Для робота ( Обновить состояние )
			
			if ( in_array( $state_name, $stop_robot_action_statuses ) ) {
				
				$sao_robot = 0;
				
			}
			
			$UPDATE_ORDERITEM = "
			UPDATE 
				`shop_orders_items` 
			SET 
				`sao_state` = ?,
				`sao_robot` = ?,
				`sao_message` = ?
			WHERE 
				`id` = ?;";
			
			$binds = array();
			$binds[] = $need_sao_state_id;
			$binds[] = $sao_robot;
			$binds[] = $sao_message;
			$binds[] = $order_item["id"];
			
			$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
			$res_exec = $stmt->execute( $binds );
			
			if ( ! $res_exec ) {
				
				$error_message = "Ошибка обновления состояния";
				throw new Exception( $error_message );
				
			}
			
		}
		
		$sao_result["status"] = true; //Успех

	} // ~! if ( is_object( $res_action ) )
	else {
		
		$error_message = "Ошибка получения статуса заказа: {$res_action['error_message']}<br/>GlobalID: {$supp_order_id}<br /> {$now_date_time}";
		$UPDATE_ORDERITEM = "
		UPDATE 
			`shop_orders_items` 
		SET 
			`sao_message` = ?
		WHERE 
			`id` = ?;";
			
		$binds = array();
		$binds[] = $error_message;
		$binds[] = $order_item["id"];
		
		$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
		$res_exec = $stmt->execute( $binds );
		
		if ( ! $res_exec ) {
			
			$error_message = "Ошибка обновления состояния";
			throw new Exception( $error_message );
			
		}
		
	}
	
} 
catch ( Exception $e ) {
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

// Logger::writeLog( __DIR__, 'dump_sao_update.log' );
?>