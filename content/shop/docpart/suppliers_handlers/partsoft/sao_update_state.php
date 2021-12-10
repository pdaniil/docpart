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

$codes_descriptions = array(); //Описание кодов статусов( из описания API  )
$codes_descriptions['processing'] = 'Обрабатывается менеджером';
$codes_descriptions['commit'] = 'подтвержден менеджером';
$codes_descriptions['v-zakaze'] = 'отправлен в заказ';
$codes_descriptions['supplier-commit'] = 'подтвержден поставщиком';
$codes_descriptions['transit'] = 'в пути';
$codes_descriptions['supplier-accept'] = 'ожидает приемки на склад';
$codes_descriptions['prishlo'] = 'пришло на склад';
$codes_descriptions['vydano'] = 'выдано';
$codes_descriptions['otkaz'] = 'отказ поставки';
$codes_descriptions['snyat'] = 'клиентом или поставщиком';

$api = new PartSoftApi( $api_options );

$sao_state_object = json_decode( $order_item['sao_state_object'], true );
$supplier_cart_item_id = $sao_state_object['order_item_id'];

$action = "/api/v1/order_items";
$params_action = array();
$params_action['search'] = array( 'id_eq' => $supplier_cart_item_id ); //По ID товара в системе поставщика
// $params_action['page'] = 1;

$api->setAction( $action );
$api->setParamsAction( $params_action );

try {
	
	$api->exec();
	$response_json = $api->getResponse();
	
	$response_arr = json_decode( $response_json, true );
	
	// Logger::addLog( '$response_arr', $response_arr );

	if ( $response_arr['result'] == 'ok'
		&& ! empty( $response_arr['data'] )
	) {
		
		
		$supp_order_item = $response_arr['data'][0];
		
		$status = $supp_order_item['status'];
		$status_code = $supp_order_item['status_code'];
		$status_code_description = $codes_descriptions[$status_code];
		
		$sao_message = "Заказ в обработке";
		 $sao_message .= "<br/>";
		$sao_message .= "Статус: {$status}";
		$sao_message .= "<br/>";
		$sao_message .= "Код статуса: {$status_code}";
		$sao_message .= "<br/>";
		$sao_message .= "Описание кода: {$status_code_description}";
		$sao_message .= "<br/>";
		$sao_message .= date( 'd-m-Y H:i:s' );
		
		$current_sao_status = $order_item['sao_state'];
		
		if ( $status_code == 'otkaz' ) {
			
			$current_sao_status = 5;
			
		}
		else if ( $status_code == 'snyat' ) {
			
			$current_sao_status = 8;
			
		}
		else if ( $status_code == 'vydano' ) {
			
			$current_sao_status = 4;
			
		}
		
		if ( $current_sao_status != $order_item['sao_state'] ) {
			
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = 'SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;';
			$stmt = $db_link->prepare( $new_status_query );
			$res_exec = $stmt->execute( array( $new_status ) ); //Товар удалён из корзины
			
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
			
		}

		//Записываем инфомацию о sao-действии
		$data = date( 'd-m-Y H:i:s' );
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $current_sao_status);
		$update_query->bindValue( ':sao_message', $sao_message );
		$update_query->bindValue(':id', $order_item["id"]);
			
		if ( ! $update_query->execute() ) {
			
			throw new Exception( "Товар удалён из корзины поставщика, но произошёл сбой при смене SAO-Состояния!" );
			
		}

		$sao_result["status"] = true;
		$sao_result["message"] = ""; 
		
		
	}
	else if (  $response_arr['result'] == 'error' ) {
	
		$error_message = "Поставщик вернул ошибку: {$response_arr['error']}";
		throw new Exception( $error_message );
	
	}
	
}
catch ( Exception $e ) {
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage(); 
	
}
// Logger::writeLog( __DIR__, 'dump_update_state.log' );
?>