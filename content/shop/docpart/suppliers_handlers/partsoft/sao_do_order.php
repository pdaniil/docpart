<?php
require_once( $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/partsoft/PartSoftApi.php' );
// require_once( $_SERVER['DOCUMENT_ROOT'] . '/Logger.php' );


$sao_result = array();
$sao_result['status'] = false;
$sao_result['message'] = '';


$api_key = $connection_options['api_key'];
$site = $connection_options['site'];

$api_options = array (
	'base' => $site,
	'api_key' => $api_key
);
		
$api = new PartSoftApi( $api_options );

$action = "api/v1/baskets/order";

$api->setAction( $action );

try {
	
	$api->exec( true );
	$response_json = $api->getResponse();
	$response_arr = json_decode( $response_json, true );
	
	// Logger::addLog( '$response_arr', $response_arr );
	
	if ( $response_arr['result'] == 'ok' ) {
		
		//Теперь нужно запросить список заказов, что бы получить id заказанной позиции...
		$action = "/api/v1/order_items";
		$params_action = array();
		$params_action['page'] = 1; //Первая страница с позициями
		
		$api->setAction( $action );
		$api->setParamsAction( $params_action );
		
		$api->exec();
		$response_json = $api->getResponse();
		$response_arr = json_decode( $response_json, true );
		
		// Logger::addLog( '$response_arr_2', $response_arr );
		
		if ( $response_arr['result'] == 'ok' 
			&& ! empty( $response_arr['data'] )
		) {
			
			$supp_order_item_id = 0;
			
			$order_data =  $response_arr['data'];
			foreach ( $order_data as $supp_item ) {
				
				if ( $supp_item['oem'] == $order_item['t2_article']
					&& $supp_item['make_name'] == $order_item['t2_manufacturer']
				) {
					
					$supp_order_item_id = $supp_item['id'];
					break;
				}
				
			}
			
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = 'SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;';
			$stmt = $db_link->prepare( $new_status_query );
			$res_exec = $stmt->execute( array( 2 ) ); //Товар в заказан у поставщика
			
			if ( ! $res_exec ) {}

			$new_status_record = $stmt->fetch( PDO::FETCH_ASSOC );
			$new_status = $new_status_record["status_id"];
			
			//Отправляем запрос на изменение статуса позиции
			if ( $new_status > 0 ) {

				$dp_action = "{$DP_Config->domain_path}content/shop/protocol/set_order_item_status.php";
				
				$params_action = array();
				$params_action['initiator'] = '2';
				$params_action['orders_items'] = json_encode( array( $order_item["id"] ) );
				$params_action['status'] = $new_status;
				$params_action['key'] = $DP_Config->tech_key;
				
				$build = http_build_query( $params_action );
				$url_req = $dp_action . "?" . $build;
				
				$ch = curl_init();

				curl_setopt( $ch, CURLOPT_URL, $url_req );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_HEADER, 0 );
				$execute = curl_exec( $ch );
				curl_close( $ch );
				
			}

			//Записываем инфомацию о sao-действии
			$data = date( 'd-m-Y', 'H:i:s' );
			
			$current_sao_object = json_decode( $order_item['sao_state_object'], true );
			$current_sao_object['order_item_id'] = $supp_order_item_id;
			$new_sao_object = json_encode( $current_sao_object );
			
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
			$update_query->bindValue(':sao_state', 2);
			$update_query->bindValue(':sao_message', 'Товар заказан у поставщика<br/> '.$data);
			$update_query->bindValue( ':sao_state_object', $new_sao_object );
			$update_query->bindValue( ':id', $order_item["id"] );
				
			if( ! $update_query->execute() ) 
			{
				throw new Exception( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
			}

			$sao_result["status"] = true;
			$sao_result["message"] = "";
			
			
		}
		else {
			
			//Записываем инфомацию о sao-действии
			$data = date( 'd-m-Y', 'H:i:s' );
			
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
			$update_query->bindValue(':sao_state', 2);
			$update_query->bindValue(':sao_message', 'Товар заказан у поставщика но не удалось найти позицию в списке заказов. Обновление состояния невозможно<br/> '.$data);
			$update_query->bindValue( ':id', $order_item["id"] );
			
			if( ! $update_query->execute() ) 
			{
				throw new Exception( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
			}

			$sao_result["status"] = true;
			$sao_result["message"] = "";
			
		}
		
	}
	else if (  $response_arr['result'] == 'error' ) {
	
		$error_message = "Поставщик вернул ошибку: {$response_arr['error']}";
		throw new Exception( $error_message );
	
	}
	
}
catch( Exception $e ) {
	
	$sao_result['message'] = $e->getMessage();
	
}

// Logger::writeLog( __DIR__, 'dump_do_order.log' );
?>