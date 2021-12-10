<?php
ob_start();
$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/proliga";
$sao_result = array();
$sao_result["status"] = false;
set_include_path( get_include_path() . PATH_SEPARATOR . $path_script . "/classes/" );
spl_autoload_register( function( $class ) {
	require_once( $class . ".php" );
} );
$json_params 	= json_decode( $order_item["t2_json_params"], true );
$secret 		= $connection_options["api_key"];
$id_api 		= ( int )$json_params["id"];
$warehouse_api	= ( int )$json_params["warehouse"];
$quantity		= ( int )$order_item["count_need"];
$code_str 		= $json_params["code"];

//Массив отправляемых данных.
$post_data_arr = array(
	"secret"=>$secret,
	"id"=>$id_api,
	"warehouse"=>$warehouse_api,
	"quantity"=>$quantity,
	"code"=>$code_str,
	"comment"=>""
);
// var_dump( $post_data_arr );
try
{
	//Проверка параметров
	foreach( $post_data_arr as $key => $v ) 
	{ 
		if( $key == "comment" )
			continue;
		if( $v == "" || $v == null )
			throw new DataException("Отсутствует параметр {$key}");
	}
	$ch 		= curl_init();
	$url 		= "https://pr-lg.ru/api/cart/add";
	$verbose	= fopen( $path_script . "/culr_cart.log", "w" );
	$post_data  = http_build_query( $post_data_arr );
	// var_dump( $url );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
	curl_setopt( $ch, CURLOPT_STDERR, $verbose );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
	$execute = curl_exec( $ch );
	//заглушка
	// $execute = false;
	curl_close( $ch );
	if( ! $execute )
		throw new CurlException( "Ошибка запроса!" . curl_error( $ch ) );
	$responce = json_decode( $execute, true );
	if( ! $responce )
		throw new JsonException( "Ошибка разбора ответа от API! error: " . json_last_error() );
	$status_resp = $responce["status"];
	if( $status_resp == "success" ) 
	{
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', 6);
		
		if ( ! $new_status_query->execute() )
			throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене статуса позиции!" );
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		if( $new_status > 0 ) 
		{
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items={$order_item["id"]}&status={$new_status}&key=".urlencode($DP_Config->tech_key);
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$execute = curl_exec( $ch );
			curl_close( $ch );
		}

		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 6);
		$update_query->bindValue(':sao_message', 'Товар добавлен в корзину<br/> '.$data);
		$update_query->bindValue(':sao_state_object', '');
		$update_query->bindValue(':id', $order_item["id"]);
			
		if( ! $update_query->execute() ) 
		{
			throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
		}
		$sao_result["status"] = true;
		$sao_result["message"] = "";
	}
	else { throw new AddToCartException( "Ошибка добавления в корзину: " . $status_resp . "!" ); }
}
catch( DataException $e ) 
{
	$sao_result["message"] = $e->getMessage();
}
catch( CurlException $e ) 
{
	$sao_result["message"] = $e->getMessage();
}
catch( JsonException $e ) 
{
	$sao_result["message"] = $e->getMessage();
}
catch( AddToCartException $e ) 
{
	$sao_result["message"] = $e->getMessage();
}
catch( QueryException $e ) 
{
}
catch( Exception $e ) 
{
	$sao_result["message"] = $e->getMessage();
}
file_put_contents( $path_script . "/dump_addToCart.txt", ob_get_clean() );
?>