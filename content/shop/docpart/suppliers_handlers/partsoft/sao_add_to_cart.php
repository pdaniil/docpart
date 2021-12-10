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

$json_options = json_decode($order_item["t2_json_params"], true); //Данные для SAO

/*
oem              - номер заказываемой детали
make_name        - производитель заказываемой детали
detail_name      - название заказываемой детали
qnt              - количество в заказ
comment          - комментарий к позиции
min_delivery_day - минимальный срок доставки
max_delivery_day - максимальный срок доставки
api_hash         - hash полученный из проценки
*/

$action = "api/v1/baskets";
$params_action = array();
$params_action['oem'] = $order_item["t2_article_show"];
$params_action['make_name'] = $order_item["t2_manufacturer"];
$params_action['detail_name'] = $order_item["t2_name"];
$params_action['qnt'] = $order_item["count_need"];
$params_action['comment'] = '';
$params_action['min_delivery_day'] = $json_options["min_delivery_day"];
$params_action['max_delivery_day'] = $json_options["max_delivery_day"];
$params_action['api_hash'] = $json_options["system_hash"];

$api->setAction( $action );
$api->setParamsAction( $params_action );

try {
	
	$api->exec(true);
	$response_json = $api->getResponse();
	
	$response_arr = json_decode( $response_json, true );
	
	$update_query_complete = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object`=?, `sao_message` = ?, `sao_state` = ?, `sao_robot` = ? WHERE `id` = ?;');
	
	$update_query_others = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object`=?, `sao_message` = ? WHERE `id` = ?;');

	if ( $response_arr['result'] == 'ok' ) {
		
		//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
		$update_query_complete->execute( array(json_encode($response_arr['data']),'Добавлено в корзину: '.date("d.m.Y H:i:s", time()),6,0,$order_item["id"]) );
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = 'SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;';
		$stmt = $db_link->prepare( $new_status_query );
		$res_exec = $stmt->execute( array( 6 ) ); //Товар в корзине
		
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
		$data = date( 'd-m-Y H:i:s' );
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 6);
		$update_query->bindValue( ':sao_message', 'Товар в корзине <br/> ' . $data );
		$update_query->bindValue(':id', $order_item["id"]);
			
		if ( ! $update_query->execute() ) {
			
			throw new Exception( "Товар в корзине поставщика, но произошёл сбой при смене SAO-Состояния!" );
			
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
// Logger::writeLog( __DIR__, 'dump_del_from_cart.log' );
?>