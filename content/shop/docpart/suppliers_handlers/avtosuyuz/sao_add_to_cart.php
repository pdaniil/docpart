<?php
/**
	* Добавление позиции в корзину поставщика
*/
$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/avtosuyuz";
ob_start();
$sao_result = array(
	"status" => false,
	"message" => ""
);
$login 		= $connection_options["login"];
$password	= $connection_options["password"];
$base_hash	= base64_encode($login . ":" . $password);
$json_options		= json_decode($order_item["t2_json_params"], true);
$article 			= $order_item["t2_article"];
$brand 				= $order_item["t2_manufacturer"];
$supplierName		= $json_options["supplierName"];
$costSale 			= $order_item["t2_price_purchase"];
$quantity 			= $order_item["count_need"];
$supplierTimeMin	= $json_options["supplierTimeMin"];
$supplierTimeMax	= $json_options["supplierTimeMax"];

/**
	* GET-параметры:
		SearchService/AddToBasket
		?article={$}
		&brand={$}
		&supplierName={$}
		&costSale={$}
		&quantity={$}
		&supplierTimeMin={$}
		&supplierTimeMax={$}
*/

$supplier_data = http_build_query(
	array(
		"article" => $article,
		"brand" => $brand,
		"supplierName" => $supplierName,
		"costSale" => $costSale,
		"quantity" => $quantity,
		"supplierTimeMin" => $supplierTimeMin,
		"supplierTimeMax" => $supplierTimeMax
	)
);

$url = "http://xn----7sbgfs5baxh7jc.xn--p1ai";
$url .= "/SearchService/AddToBasket?{$supplier_data}";
$headers = array(
	"Authorization:  Basic {$base_hash}",
	"Accept: application/json",
	"Content-type: application/json"
);		

var_dump($url);

try {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$exec = curl_exec($ch);
	if(curl_errno($ch)) {
		var_dump(curl_errno($ch));
		curl_close($ch);
		throw new Exception("Ошибка запроса к сервису!\n" . curl_errno($ch));
	}
	curl_close($ch);
	
	if ( ! $decode = json_decode($exec, true) ) {
		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
	}
	var_dump($decode);
	if ($decode == "Ok") {
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 6;');		
		if ( ! $new_status_query->execute() ) {
			throw new Exception( "Товар заказн у поставщика, но произошёл сбой при смене статуса позиции!" );
		}
		$new_status_record	= $new_status_query->fetch();
		$new_status 		= $new_status_record["status_id"];
		if($new_status > 0) {
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[{$order_item["id"]}]&status={$new_status}&key=".urlencode($DP_Config->tech_key);
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$execute = curl_exec( $ch );
			curl_close( $ch );
		}
		$date_time = date("d-m-Y H:i:s");
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 6);
		$update_query->bindValue(':sao_state_object', '');
		$update_query->bindValue(':sao_message', 'Товар добавлен в корзину<br/> '.$date_time);
		$update_query->bindValue(':id', $order_item["id"]);
		
		
		if( ! $update_query->execute() ) {
			throw new QueryException( "Товар заказан у поставщика, но произошёл сбой при смене SAO-Состояния!" );
		}
		//---------Успешно---------//
		$sao_result["status"] = true;
		$sao_result["message"] = "";
		//---------Успешно---------//
	} else {
		throw new Exception("Ошибка добавления в корзину!");
	}
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/add_cart.log", ob_get_clean());
?>