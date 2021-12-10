<?php
/**
	* Добавление позиции в корзину поставщика
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

$json_options		= json_decode($order_item["t2_json_params"], true);

$Code 			    = $json_options["Code"];
$ProdId 			= $json_options["ProdId"];
$SupCode     		= $json_options["SupCode"];
$Qty    			= $order_item["count_need"];
$Comment        	= '';
$Reference      	= $json_options["Code"];


// -------------------------------------------------------------------------------------------------

// Пример
// http://tehnomir.com.ua/ws/xml.php?act=BasketAddPos&usr_login=LOGIN&usr_passwd=PASSWORD&ProdId=579&SupCode=STOK&Code=9091901235&Qty=1&Comment=%B2%EF%F0%E8%E2%E5%F2&Reference=10004231

try {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=BasketAddPos&usr_login={$login}&usr_passwd={$password}&ProdId={$ProdId}&Code={$Code}&SupCode={$SupCode}&Qty={$Qty}&Reference={$Reference}");
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
        )
    
    */
	

	if ( ! $xml_result = json_decode($json, true) ) {
		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
	}
	
	var_dump($xml_result);
	

	//Читаем статусы
	$supplier_statuses = array(
	    100 => "Позиция добавлена",
	    200 => "Не передано производителя",
	    201 => "Невозможно найти производителя. Плохой параметр ProdId",
	    202 => "Невозможно найти производителя. Плохой параметр ProdStr",
	    203 => "Не передано поставщика",
	    204 => "Невозможно найти пост. Плохой параметр SupCode",
	    205 => "Не передано Qty или неверное значение (мин = 1, макс = 200)",
	    206 => "Не передан код детали (> 1 сим, только из 0-9 и A-Z)",
	    207 => "Не найдено позиции в прайсах"
	    );
	    


	if (isset($xml_result['Status']['Code'])) {
	    
	    $status_code = (int)$xml_result['Status']['Code'];
	    
	    if($status_code == 100) {
	        
    		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
    		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 6;');
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
    		$date_time = date("d-m-Y H:i:s");
    		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
    		$update_query->bindValue(':sao_state', 6);
    		$update_query->bindValue(':sao_state_object', '');
    		$update_query->bindValue(':sao_message', 'Позиция добавлена в корзину<br/> '.$date_time);
    		$update_query->bindValue(':id', $order_item["id"]);
    		
    		
    		if( ! $update_query->execute() ) {
    			throw new QueryException( "Позиция добавлена в корзину поставщика, но произошёл сбой при смене SAO-Состояния!" );
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
file_put_contents($path_script . "/add_cart.log", ob_get_clean());
?>