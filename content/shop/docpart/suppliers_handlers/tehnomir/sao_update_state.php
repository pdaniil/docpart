<?php
/**
	* Проверить состояние заказа
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
$sao_state          = json_decode( $order_item['sao_state_object'], true );

$Code 			    = $json_options["Code"];
$order_id 			= $sao_state["order_id"];
$SupCode     		= $json_options["SupCode"];
$Qty    			= $order_item["count_need"];


// -------------------------------------------------------------------------------------------------

// Пример
// Получаем все позиции в заказе
// http://tehnomir.com.ua/ws/xml.php?act=GetOrderPositions&usr_login=LOGIN&usr_passwd=PASSWORD&order_id=1332555

try {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=GetOrderPositions&usr_login={$login}&usr_passwd={$password}&order_id={$order_id}");
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
            <Position>
                <GlobalId>5100200</GlobalId>
                <Producer>MITSUBISHI</Producer>
                <PartNumber>MN100250</PartNumber>
                <PartNumberNew></PartNumberNew>
                <Quantity>10</Quantity>
                <Description>BUSH</Description>
                <Price>2.54</Price>
                <Currency>USD</Currency>
                <StateId>12</StateId>
                <StateName>Выдано</StateName>
                <StateChangedDate>2016-01-02 03:04:05</StateChangedDate>
                <SupplierCode>STOK</SupplierCode>
                <Reference>12345</Reference>
                <CommentCustomer>warehouse</CommentCustomer>
                <CommentAdmin></CommentAdmin>
            </Position>
        )
    
    */
	

	if ( ! $xml_result = json_decode($json, true) ) {
		throw new Exception("Ошибка разбора ответа от сервиса!\n" . json_last_error());
	}
	
	var_dump($xml_result);
	

	if (isset($xml_result['PositionsList'])) {
	    
	    $positions = $xml_result['PositionsList'];
	    
	    if(!empty($positions)) {
	        
	        $StateId = null;
	        $StateName = null;
	        $StateChangedDate = null;
	        
	        foreach($positions as $position) {
	            
	            //Ищем нашу позицию
                if($Code == $position["PartNumber"] &&
                    $Qty == $position["Quantity"]) {
                        
                        $StateId = $position["StateId"];
                        $StateName = $position["StateName"];
                        $StateChangedDate = !empty($position["StateChangedDate"]) ? $position["StateChangedDate"] : 'Нет данных';
                        
                    }
	        }
	        
	        
	        if(!empty($StateId)) {
	            
	            $supplier_statuses = array(
	                	1 => array("action" => "2", "name" => "На обработке"),
                        2 => array("action" => "2", "name" => "Приостановлено"),
                        3 => array("action" => "2", "name" => "Превышение цены"),
                        4 => array("action" => "2", "name" => "Снято"),
                        5 => array("action" => "2", "name" => "В заказе"),
                        6 => array("action" => "5", "name" => "Отказ поставщика"),
                        7 => array("action" => "2", "name" => "Выкуплено"),
                        8 => array("action" => "2", "name" => "В пути"),
                        9 => array("action" => "2", "name" => "Пришло ОД"),
                        10 => array("action" => "2", "name" => "Повреждено"),
                        11 => array("action" => "4", "name" => "Выдано"),
                        12 => array("action" => "8", "name" => "Отказ клиента"),
                        13 => array("action" => "2", "name" => "К выдаче"),
                        14 => array("action" => "3", "name" => "Возврат клиентом")
	                );
	                
	            if(isset($supplier_statuses[$StateId])) {
	                
	                $action_status_data = $supplier_statuses[$StateId];
	                $action_status = $action_status_data['action'];
	                $action_name = $action_status_data['name'];
	                
                    //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
            		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;');
            		$new_status_query->execute(array($action_status));
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
                    $update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
            		$update_query->bindValue(':sao_state', $action_status);
            		$update_query->bindValue(':sao_message', 'Статус "'.$action_name.'" обновлен поставщиком: '.$StateChangedDate.'<br/>Обновлено в последний раз: '.$date_time);
            		
            		$update_query->bindValue(':id', $order_item["id"]);

            		if( ! $update_query->execute() ) {
            			throw new QueryException( "Позиция удалена из корзины поставщика, но произошёл сбой при смене SAO-Состояния!" );
            		}
            		//---------Успешно---------//
            		$sao_result["status"] = true;
            		$sao_result["message"] = "";
            		//---------Успешно---------//
        		
        		
	            } else {
	                
	                throw new Exception("Текущий статус позиции не обнаружен в документации поставщика.");
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
file_put_contents($path_script . "/update_state_cart.log", ob_get_clean());
?>