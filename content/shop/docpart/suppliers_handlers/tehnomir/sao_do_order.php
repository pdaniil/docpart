<?php
/**
	* Оформление заказ из корзины поставщика
*/

$path_script = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/tehnomir";

ob_start();

$sao_result = array(
	"status" => false,
	"message" => ""
);

/*****Учетные данные*****/
$login = $connection_options["login"];
$password = $connection_options["password"];
/*****Учетные данные*****/

$OrderNum    			= $order_item["order_id"];



// -------------------------------------------------------------------------------------------------

// Пример
// http://tehnomir.com.ua/ws/xml.php?act=BasketMakeOrder&usr_login=LOGIN&usr_passwd=PASSWORD&OrderNum=1147

try {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=BasketMakeOrder&usr_login={$login}&usr_passwd={$password}&OrderNum={$OrderNum}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $curl_result = curl_exec($ch);
    
	if(curl_errno($ch)) {
		var_dump(curl_errno($ch));
		curl_close($ch);
		throw new Exception("Ошибка запроса к сервису!\n" . curl_errno($ch));
	}
	
    curl_close($ch);
    
    $xml = simplexml_load_string($curl_result, "SimpleXMLElement", LIBXML_NOCDATA); 
    $json = json_encode($xml);

    //Формат ответа
    /*
        (
            [Status] => Array
            (
                [Code] => 100
                [Msg] => Позиция добавлена
            )
            <OrderId>932850</OrderId>
            <OrderStatus>ACTIVE</OrderStatus>
        )
    
    */
	

	if ( ! $xml_result = json_decode($json, true) ) {
		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
	}
	
	var_dump($xml_result);
	

	//Читаем статусы
	$supplier_statuses = array(
	    100 => "Заказ принят",
	    200 => "Нет позиций в корзине",
	    );
	    


	if (isset($xml_result['Status']['Code'])) {
	    
	    $status_code = (int)$xml_result['Status']['Code'];
	    
	    if($status_code == 100) {
	        
    		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
    		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 2;');
    		$new_status_query->execute();
     		$new_status_record	= $new_status_query->fetch();
    		$new_status 		= (int)$new_status_record["status_id"];
    		
    		var_dump($new_status_record);
    
    		if($new_status > 0) {
    			//Отправляем запрос на изменение статуса позиции
    			$ch = curl_init();
    			$url = $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[{$order_item["id"]}]&status={$new_status}&key=".urlencode($DP_Config->tech_key);
    
    			curl_setopt( $ch, CURLOPT_URL, $url );
    			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    			curl_setopt( $ch, CURLOPT_HEADER, 0 );
    			$execute = curl_exec( $ch );
    			
    			var_dump($execute);
    			
    			curl_close( $ch );
    		}
    		
            $sao_order_id = $xml_result['OrderId'];
            $sao_order_status = $xml_result['OrderStatus'];
            
            $sao_obj = json_encode( array( "order_id" => $sao_order_id ) );
            
    		$date_time = date("d-m-Y H:i:s");
    		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
    		$update_query->bindValue(':sao_state', 2);
    		$update_query->bindValue(':sao_state_object', $sao_obj);
    		$update_query->bindValue(':sao_message', 'Оформлен заказ '.$sao_order_id.'. Статус заказа: '.$sao_order_status.'<br/> '.$date_time);
    		$update_query->bindValue(':id', $order_item["id"]);
    		
    		
    		if( ! $update_query->execute() ) {
    			throw new QueryException( "Заказ оформлен, но произошёл сбой при смене SAO-Состояния!" );
    		}
    		//---------Успешно---------//
    		$sao_result["status"] = true;
    		$sao_result["message"] = "";
    		//---------Успешно---------//

	    } else {
	        $error_message = isset($supplier_statuses[$status_code]) ? $supplier_statuses[$status_code] : "Ошибка. Неизвестный статус ответа поставщика.";
	        throw new Exception($error_message);
	    }
	} else {
		throw new Exception("Ошибка ответа поставщика. Требуется проверить параметры ответа.");
	}
	
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/make_order_cart.log", ob_get_clean());
?>