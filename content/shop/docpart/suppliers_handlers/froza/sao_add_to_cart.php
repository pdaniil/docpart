<?php
require_once( 'FrozaApi.php' );


$login 		= $connection_options['login'];
$password		= $connection_options['password'];
$wsdl 			= "http://www.froza.ru/webservice/basket.php?WSDL";

$api_options	= array (
	"login" => $login,
	"password" => $password,
	"wsdl" => $wsdl
);

// Параметры запроса
$action 						= "addToBasket";
$item_params 					= json_decode( $order_item["t2_json_params"], true );
$item_params['quant']			= $order_item['count_need'];
$item_params['delivery_type']	= 'AFL';
$item_params['reference']		= $order_item['id'];

$params_action = array (
	'posList' => array( $item_params )
);

//Иницализируем обработчик
$api = new FrozaApi( $api_options );

$api->getAction( $action );
$api->getParamsAction( $params_action );

try {
	
	$res_action = $api->execAction();
	
	if ( is_object( $res_action ) ) {
		
		$FromBasket = $res_action->addToBasketResult->FromBasket;
		
		if ( $FromBasket->global_id == 0 ) {
			
			throw new Exception( 'Ошибка добавления в корзину: ' .$FromBasket->comment );
			
		}
		//ID Заказа в сис-ме поставщика
		$global_id = $FromBasket->global_id;
		
		$sao_object = array();
		$sao_object['global_id'] = $global_id;
		
		$json_sao_object = json_encode( $sao_object );
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$sql = "SELECT `status_id` FROM `shop_sao_states` WHERE `id` = '6';";
		$new_status = $db_link->query( $sql, PDO::FETCH_COLUMN, 0 );
	
		//Отправляем запрос на изменение статуса позиции
		if ( $new_status > 0 ) {
			
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
			
		}
		
		$data = date("d-m-Y H:i:s");
		$sao_message = "Товар добавлен в корзину<br/> {$data} <br /> GlobalID: {$global_id}";
		
		$UPDATE_ORDERITEM = "
		UPDATE 
			`shop_orders_items`
		SET
			`sao_state` = 6,
			`sao_state_object` = ?,
			`sao_message` = ?,
			`sao_robot` = 0
		WHERE
			`id` = ?
		;";
		
		$binds = array();
		$binds[] = $json_sao_object;
		$binds[] = $sao_message;
		$binds[] = $order_item["id"];
		
		$stmt = $db_link->prepare( $UPDATE_ORDERITEM );
		$res_exec = $stmt->execute( $binds );
		
		if ( ! $res_exec ) {
			
			$error_message = "Товар добавлен в корщину поставщика, но произошла ошибка обновления позиции!";	
			throw new Exception( $error_message );
			
		}
		
		$sao_result["status"] = true;
		
	} 
	else {
		
		throw new Exception( 'Ошибка выполения запроса: ' . $res_action['error_message'] );
		
	}
	
}
	catch ( Exception $e ) {
		
		$sao_result["status"] = false;
		$sao_result["message"] = $e->getMessage();
		
	}
?>