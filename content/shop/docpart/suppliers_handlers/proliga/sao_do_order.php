<?php
ob_start();

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/proliga";

$sao_result = array();
$sao_result["status"] = false;

set_include_path( get_include_path() . PATH_SEPARATOR . $path_script . "/classes/" );

spl_autoload_register( function( $class ) {
	require_once( $class . ".php" );
} );

$data 			= date("d-m-Y H:i");
$json_params 	= json_decode( $order_item["t2_json_params"], true );

$delivery_type	= $json_params["delivery_type"];
$payment_type	= $json_params["payment_type"];
$point_code		= $json_params["point_code"];
$point_adderss	= $json_params["adderss"];

$secret 		= $connection_options["api_key"];
$url_api 		= "https://pr-lg.ru";
$uriCreateOrder = "/api/cart/order";

try {
	
	//Массив отправляемых данных.
	$post_data_get_params = array(
		"secret"=>$secret,
		"method"=>$delivery_type,
		"payment"=>$payment_type,
		"point"=>$point_code,
		"address"=>$point_adderss
	);
	
	foreach( $post_data_get_params as $key => $v ) { 
		
		if( $key == "comment" )
			continue;
		
		if( $v == "" || $v == null )
			throw new DataException("Отсутствует параметр {$key}");
	}

	$post_data = http_build_query( $post_data_get_params );
	
	$url_request = $url_api . $uriCreateOrder;
	
	// var_dump( $url_request );
	
	$ch = curl_init();
	
	curl_setopt( $ch, CURLOPT_URL, $url_request );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
	
	$execute = curl_exec( $ch );
	
	curl_close( $ch );
	
	if( ! $execute )
		throw new CurlException( "Ошибка запроса!" . curl_error( $ch ) );
	
	$responce = json_decode( $execute, true );
	if( ! $responce )
		throw new JsonException( "Ошибка разбора ответа от API! error: " . json_last_error() );

	$status = $responce["status"];
	
	if ( $status == "success" ) {
		
		
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', 2);

		if ( ! $new_status_query->execute() )
			throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене статуса позиции!" );
		
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		
		if( $new_status > 0 ) {
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			
			$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items={$order_item["id"]}&status={$new_status}&key=".urlencode($DP_Config->tech_key);
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$execute = curl_exec( $ch );
			curl_close( $ch );
		}
		
		$orders_ids = $responce["orders"];
		
		$orders_ids_str = "";
		
		if( ! empty ($orders_ids) ) {
			$orders_ids_str = "ID заказа: {$orders_ids[0]}";
		}
		
		$state_object = json_encode( $orders_ids );
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 2);
		$update_query->bindValue(':sao_message', 'Товар заказан <br/> '.$data.' <br/> '.$orders_ids_str);
		$update_query->bindValue(':sao_state_object', $state_object);
		$update_query->bindValue(':id', $order_item["id"]);
		
		if( ! $update_query->execute() ) {
			throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
		}
		
		$sao_result["status"] = true;
		$sao_result["message"] = "";
	}
	else if ( $status == "error" ) {
		throw new AddToCartException( $responce["err"] );
	}
}
catch( DataException $e ) {
	$sao_result["message"] = $e->getMessage();
}
catch( CurlException $e ) {
	$sao_result["message"] = $e->getMessage();
}
catch( JsonException $e ) {
	$sao_result["message"] = $e->getMessage();
}
catch( AddToCartException $e ) {
	$sao_result["message"] = $e->getMessage();
}
catch( QueryException $e ) {
	
}
catch( Exception $e ) {
	$sao_result["message"] = $e->getMessage();
}


file_put_contents( $path_script . "/dump_doOrder.txt", ob_get_clean() );
?>