<?php
// http://api.demo.abcp.ru/orders/instant
ob_start();

$supplier_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart/suppliers_handlers/abcp';

require_once( $supplier_dir . "/ABCPSAO.php" ); 
require_once( $supplier_dir . "/ABPCOrder.php" ); 

$sao_result = array(); //Результат выполнения действия

$login = $connection_options["login"];
$password = md5( $connection_options["password"] );
$subdomain = $connection_options["subdomain"];

$t2_json_params = json_decode($order_item["t2_json_params"], true); //Специальные параметры

try {
	
	$abcp_sao = new ABCPSAO ( $login, $password, $subdomain );
	
	$abcp_order = new ABCPOrder ( $abcp_sao ); //Сформировали данные заказа без описания позиции
	
	$position = array( 'brand' => $t2_json_params["brand"],
						'number' => $t2_json_params['number'],
						'itemKey' => $t2_json_params['itemKey'],
						'supplierCode' => $t2_json_params['supplierCode'], 
						'quantity' => $order_item['count_need'] ); //описание объекта, добавляемого в корзину.
	
	$abcp_order->addPosition( $position ); //Добавили позцию
	
	echo "createOrder: \n";
	
	$result_order = $abcp_sao->createOrder( $abcp_order ); //Создаём заказ
	
	var_dump( $result_order );
	
	echo "\n";
	
	$status_order = $result_order['status'];
	
	if ( $status_order ) {
		
		$orders = $result_order['orders'];
		
		//Получаем ключи заказов
		$order_ids = array_keys($orders);
		
		//Берём самый первый, т.к заказ по идее один.
		$item_status = $orders[$order_ids[0]]['status'];
		
		//Обновляем статус позиции и т.д 
		//Меняем SAO-Состояние
		$new_sao_state = 2; //Состояние "Заказано"
		$sao_obj = json_encode( array( "order_id" => $order_ids[0] ) );
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare( 'SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;' );
		$new_status_query->bindValue( ':id', $new_sao_state );
		$new_status_query->execute();
		$new_status_record = $new_status_query->fetch();
		
		$new_status = $new_status_record["status_id"];
		
		if($new_status > 0) {
			
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
		}
		
		$sao_result["status"] = true;

		$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа:<br/>{$order_ids[0]}<br />Статус: {$item_status}";
		//Обновляем SAO-статус
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $new_sao_state);
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':sao_robot', 0);
		$update_query->bindValue(':sao_state_object', $sao_obj);
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
		
	} else {
		
		$error_message = $result_order['errorMessage'];
		
		throw new Exception( "Ошбика создания заказа: " . $error_message );
		
	}
	
} catch ( Exception $e ) {
	
	echo $e->getMessage();
	
	$sao_result['status'] = false;
	$sao_result['message'] = $e->getMessage();
	
}
 
file_put_contents( $supplier_dir . "/dump_do_order.log", ob_get_clean() );
?>