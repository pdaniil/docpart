<?php
/**
	* Удаление позиции из корзины поставщика
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
// Получаем все позиции в корзине
// http://tehnomir.com.ua/ws/xml.php?act=BasketList&usr_login=LOGIN&usr_passwd=PASSWORD
// Удаляем одну позицию из корзины
// http://tehnomir.com.ua/ws/xml.php?act=BasketDeletePos&usr_login=LOGIN&usr_passwd=PASSWORD&BasId=2544074

try {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=BasketList&usr_login={$login}&usr_passwd={$password}");
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
            [Positions] => Array
            (
                [Position] => 
            )
        )
    
    */
	

	if ( ! $xml_result = json_decode($json, true) ) {
		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
	}
	

	if (isset($xml_result['Positions'])) {
	    
	    $positions = $xml_result['Positions'];
	    
	    if(!empty($positions)) {
	        
	        $BasId = null;
	        
	        foreach($positions as $position) {
	            
	            //Ищем нашу позицию
                if($Code == $position["Code"] &&
                    $ProdId == $position["ProdId"] &&
                    $SupCode == $position["SupCode"] &&
                    $Qty == $position["Qty"]) {
                        
                        $BasId = $position["BasId"];
                        
                    }
	        }
	        
	        
	        if(!empty($BasId)) {
	            
	                $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=BasketDeletePos&usr_login={$login}&usr_passwd={$password}&BasId={$BasId}");
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
                    
                    if ( ! $xml_result = json_decode($json, true) ) {
                		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
                	}
                	
                
                	if (isset($xml_result['BasId'])) {
	            
    	                //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
                		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = 7;');
                		$new_status_query->execute();
                 		$new_status_record	= $new_status_query->fetch();
                		$new_status 		= (int)$new_status_record["status_id"];
                
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
                		$update_query->bindValue(':sao_state', 7);
                		$update_query->bindValue(':sao_state_object', '');
                		$update_query->bindValue(':sao_message', 'Позиция удалены из корзины<br/> '.$date_time);
                		$update_query->bindValue(':id', $order_item["id"]);
                		
                		
                		if( ! $update_query->execute() ) {
                			throw new QueryException( "Позиция удалена из корзины поставщика, но произошёл сбой при смене SAO-Состояния!" );
                		}
                		//---------Успешно---------//
                		$sao_result["status"] = true;
                		$sao_result["message"] = "";
                		//---------Успешно---------//
                		
                	} else {
                	    
                	    throw new Exception("Ошибка исполнения запроса на стороне поставщика.");
                	}
	            
	        } else {
	            
	            throw new Exception("Данная позиция не найдена в корзине поставщика.");
	        }

	    } else {
	        throw new Exception("Отсутствуют позиции в корзине.");
	    }
	} else {
		throw new Exception("Ошибка ответа поставщика. Требуется проверить параметры ответа.");
	}
	
} catch(Exception $e) {
	echo $e->getMessage();
	$sao_result["message"] = $e->getMessage();
}		
file_put_contents($path_script . "/delete_cart.log", ob_get_clean());
?>