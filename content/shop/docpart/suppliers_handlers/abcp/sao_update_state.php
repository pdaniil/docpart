<?php
ob_start();
$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/abcp';

$sao_result = array(); //Результат выполнения действия

$login = $connection_options["login"];
$password = md5( $connection_options["password"] );
$subdomain = $connection_options["subdomain"];


$sao_state = json_decode( $order_item['sao_state_object'], true );

$abcp_order_id = $sao_state['order_id'];

$auth_params = http_build_query( array( 'userlogin' => $login, 
											'userpsw' => $password ));

$param_order = urlencode("orders[0]");

$url_request = "http://{$subdomain}.public.api.abcp.ru/orders/list?{$auth_params}&{$param_order}={$abcp_order_id}";
/* 
var_dump( $url_request );

echo "\n";
 */
$ch = curl_init();
curl_setopt( $ch, CURLOPT_HEADER, false );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_URL, $url_request );

try {
	
	$exec = curl_exec($ch);

	if ( curl_errno( $ch ) ) {
		
		throw new Exception ( "Ошибка curl: " . curl_errno( $ch ) );
		
	}

	$decode = json_decode ( $exec, true );
	//DEBUG
	/*

		var_dump($decode);

	// */

	if ( is_null( $decode ) ) {
		
		throw new Exception ( "Ошибка json: " . json_last_error() );
		
	}

	if ( isset ( $decode['errorCode'] ) ) {
		
		throw new Exception ( "Ошибка от поставщика: " . $decode['errorMessage'] );
		
	}
	
	$order_data = $decode[$abcp_order_id];
	$order_status = $order_data['status'];
	$order_status_code = $order_data['statusCode'];
	
	$new_sao_state = 2; //Заказано
	
	if ( $order_status_code == 19 ) { //Отказ клиента
		
		$new_sao_state = 8;
		
	}
	
	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
	$new_status_query->bindValue(':id', $new_sao_state);
	$new_status_query->execute();
	$new_status_record = $new_status_query->fetch();
	
	$new_status = $new_status_record["status_id"];
	
	if( $new_status > 0 && $order_item['status'] != $new_status ) {
		
		//Отправляем запрос на изменение статуса позиции
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_result = curl_exec($ch);
		curl_close($ch);
		
	}

	$sao_result["status"] = true;
			
	$sao_message = "Обновление ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>{$abcp_order_id}<br />Статус: {$order_status}";
	//Обновляем SAO-статус
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message  WHERE `id` = :id;');
	$update_query->bindValue(':sao_state', $new_sao_state);
	$update_query->bindValue(':sao_message', $sao_message);
	$update_query->bindValue(':id', $order_item["id"]);
	
	if( $update_query->execute() ) {
			
			
	} else {
		
		echo date("d-m-Y H:i:s")."\n";
		echo "Ошибка обновления SAO-статуса\n";
		echo mysqli_error($db_link)."\n";
		echo $UPDATE_SAO_STATUS."\n";
		echo "=======================================================\n";
		
		$sao_result["status"] = false;
		$sao_result["message"] = "Заказ создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддрежки";
		
	}
	
} catch ( Exception $e ) {
	
	$sao_result['status'] = false;
	$sao_result['message'] = $e->getMessage();
	
}

file_put_contents( $supplier_dir . "/dump_update.log", ob_get_clean() );
?>