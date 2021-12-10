<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота

ВЫВОД ДАМПА БУФФЕРИЗИРУЕТСЯ И ПИШЕТСЯ В ФАЙЛ $pathError
*/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/rossko/error_sao/errors_DO_ORDER.log";

// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array();

/*********************************************************
	* Выполнение действия 
**********************************************************/

$connect = array(
    'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetCheckout',
    'options' => array(
        'connection_timeout' => 1,
        'trace' => true
    )
);


/*****Учетные данные*****/
$KEY1           = isset($connection_options["key1"]) ? $connection_options["key1"] : null;
$KEY2           = isset($connection_options["key2"]) ? $connection_options["key2"] : null;
$delivery_id    = isset($connection_options["delivery_id"]) ? $connection_options["delivery_id"] : null;
$address_id     = isset($connection_options["address_id"]) ? $connection_options["address_id"] : null;
$payment_id     = isset($connection_options["payment_id"]) ? $connection_options["payment_id"] : null;
$requisite_id   = isset($connection_options["requisite_id"]) ? $connection_options["requisite_id"] : null;
$name           = isset($connection_options["delivery_name"]) ? $connection_options["delivery_name"] : '';
$phone          = isset($connection_options["delivery_phone"]) ? $connection_options["delivery_phone"] : '';
$comment        = isset($connection_options["delivery_comment"]) ? $connection_options["delivery_comment"] : '';
$delivery_parts = (isset($connection_options["delivery_parts"]) && $connection_options["delivery_parts"] == 1) ? true : false;
/*****Учетные данные*****/

try
{
	//Выполняем запрос на получение информации для создания заказа.
	
	if(empty($KEY1)) {
	    throw new Exception("Отсутствует key1 для подключения к Rossko.");
	}
	
	if(empty($KEY2)) {
	    throw new Exception("Отсутствует key2 для подключения к Rossko.");
	}
	
	if(empty($delivery_id) || empty($address_id) || empty($payment_id) || empty($requisite_id)) {
	    throw new Exception("Отсутствуют данные из ЛК Rossko.");
	}
	
	if(empty($name) || empty($phone)) {
	    throw new Exception("Отсутствуют контакнтные данные для оформления заказа.");
	}
	

	$json_params = json_decode($order_item["t2_json_params"], true);
	
	$partnumber = $order_item["t2_article"];
	$brand      = $order_item["t2_manufacturer"];
	$stock      = $json_params["id"];
	$count      = $order_item["count_need"];
	
	
	if(empty($partnumber) || empty($brand) || empty($stock) || empty($count)) {
	    throw new Exception("Отсутствуют данные позиции при оформлении заказа.");
	}
	
    $param = array(
        'KEY1' => $KEY1,
        'KEY2' => $KEY2,
        'delivery' => array(
            'delivery_id' => $delivery_id,
            'address_id'  => $address_id
        ),
        'payment' => array(
            'payment_id'        => $payment_id,
            'requisite_id'      => $requisite_id
        ),
        'contact' => array(
            'name'    => $name,
            'phone'   => $phone,
            'comment' => $comment
        ),
        'delivery_parts' => $delivery_parts,
        'PARTS' => array(
            array(
                'partnumber' => $partnumber,
                'brand'      => $brand,
                'stock'      => $stock,
                'count'      => $count,
                'comment'    => ''
            )
        )
    );
    
    
    
    //Создание объекта клиента
	try
	{
		$objClient = new SoapClient($connect['wsdl'], $connect['options']);//Создаем SOAP-клиент
	}
	catch (SoapFault $e)//Не можем создать клиент SOAP
	{
		throw new Exception("Ошибка Soap: " . $e->getMessage());
	}
    
    //Обрабатываем результат
    $soap_result = $objClient->GetCheckout($param);

    if(isset($soap_result->CheckoutResult)) {
        $query_result = $soap_result->CheckoutResult;
        
        /*Формат ответа
            (
                [success] => 1
                [OrderIDS] => stdClass Object
                    (
                        [id] => 33600727
                    )
            
                [DeliveryCost] => stdClass Object
                    (
                        [cost] => 0
                    )
            
                [comment] => 
                [ItemsList] => stdClass Object
                    (
                        [Item] => stdClass Object
                            (
                                [partnumber] => AG 335 CF
                                [brand] => GoodWill
                                [count] => 1
                                [price] => 273
                                [total_price] => 273
                                [stock] => HST156
                                [delivery] => 0
                                [deliveryStart] => 2021-06-21T15:55:00
                                [deliveryEnd] => 2021-06-21T17:25:00
                                [comment] => 
                                [order_id] => 33600727
                                [extra] => 0
                                [description] => г. Волгоград, ул. Венецианова 2Б
                                [stock_address] => г. Волгоград, ул. Венецианова 2Б
                            )
            
                    )
            
            )
        */

    	if($query_result->success)
    	{
    		if( !empty($query_result->ItemsErrorList))
    		{
    			$error_message = isset($query_result->ItemsErrorList->ItemError->message) ? $query_result->ItemsErrorList->ItemError->message : "Ошибка позиции при оформлении заказа";
    		    throw new Exception($error_message);
    		}
    		else //Если деталь заказана, то и заказ оформлен
    		{
    			//Заказ создан, получен его id в росско.
            	$orderRosskoId = isset($query_result->OrderIDS->id) ? $query_result->OrderIDS->id : null;
            	$sao_obj["orderId"] = $orderRosskoId;
            	$sao_obj = json_encode($sao_obj);
            	
            	//Меняем SAO-Состояние
            	$new_sao_state = 2; //Состояние "Заказано"
            	
            	//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
            	$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
            	$new_status_query->bindValue(':id', $new_sao_state);
            	$new_status_query->execute();
            	$new_status_record = $new_status_query->fetch();
            	$new_status = $new_status_record["status_id"];
            	if((int)$new_status > 0)
            	{
            		//Отправляем запрос на изменение статуса позиции
            		$ch = curl_init();
            		curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
            		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            		curl_setopt($ch, CURLOPT_HEADER, 0);
            		$curl_result = curl_exec($ch);
            		curl_close($ch);
            	}
            
            	$sao_message = "Заказ создан ". date("d-m-Y H:i:s") ."<br/>Номер заказа в Росско:<br/>{$orderRosskoId}";
            	//Обновляем SAO-статус
            	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object, `sao_robot` = :sao_robot WHERE `id` = :id;');
            	$update_query->bindValue(':sao_state', $new_sao_state);
            	$update_query->bindValue(':sao_message', $sao_message);
            	$update_query->bindValue(':sao_robot', 0);
            	$update_query->bindValue(':sao_state_object', $sao_obj);
            	$update_query->bindValue(':id', $order_item["id"]);
            	
            	
            	if( ! $update_query->execute() )
            	{
            		echo date("d-m-Y H:i:s")."\n";
            		echo "Ошибка обновления SAO-статуса\n";
            		echo mysqli_error($db_link)."\n";
            		echo $UPDATE_SAO_STATUS."\n";
            		echo "=======================================================\n";
            		
            		$sao_result["status"] = false;
            		$sao_result["message"] = "Заказ создан, но при обновлении SAO-состояния возникли ошибки, обратитесь к инженеру поддрежки";
            	}
            	else
            	{
            		$sao_result["status"] = true;
            	}
    		}
    	} // ~if($result->success)
    	else
    	{
    	    $error_message = isset($query_result->message) ? $query_result->message : "Ошибка при оформлении заказа";
    		throw new Exception($error_message);
    	}

    } else {
        throw new Exception("Ошибка получения данных после запроса GetCheckout. Проверьте корзину в личном кабинете Росско.");
    }
	
}
catch(Exception $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}

$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>