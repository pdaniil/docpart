<?php

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/


/***********************************************************************
* SAO
* Действие: Проверить состояние

* Данный скрипт выполняется в контексте:
	** либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
	** либо в контексте скрипта робота
	
*************************************************************************/
ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/rossko/error_sao/errors_UPD_STATE.log";

//Структура результата 
$sao_result = array();

//*************************************************************************

$state_object = json_decode($order_item["sao_state_object"], true);

$connect = array(
    'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetOrders',
    'options' => array(
        'connection_timeout' => 1,
        'trace' => true
    )
);

/************************************
	Разобраться с SAO - статусом
*************************************/
$current_sao_state = $order_item["sao_state"];

/*****Учетные данные*****/
$KEY1    = isset($connection_options["key1"]) ? $connection_options["key1"] : null;
$KEY2    = isset($connection_options["key2"]) ? $connection_options["key2"] : null;
$orderId = isset($state_object["orderId"]) ? $state_object["orderId"] : null;
/*****Учетные данные*****/

$sao_info = "";
try
{
    if(empty($KEY1)) {
	    throw new Exception("Отсутствует key1 для подключения к Rossko.");
	}
	
	if(empty($KEY2)) {
	    throw new Exception("Отсутствует key2 для подключения к Rossko.");
	}
	
	if(empty($orderId)) {
	    throw new Exception("Отсутствуют данные заказа ЛК Rossko.");
	}
	
	$param = array(
        'KEY1' => $KEY1,
        'KEY2' => $KEY2,
        'order_ids' => array($orderId)
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
	
	
    $soap_result = $objClient->GetOrders($param);
    

	if(isset($soap_result->OrdersResult)) {
        $order = $soap_result->OrdersResult;
    
    	if($order->success)
    	{
    	    $supplier_statuses = array(
        	    0 => "ждёт подтверждения",
                1 => "комплектуется",
                2 => "отгружено",
                3 => "готово к отгрузке",
                5 => "ожидаем поступление",
                6 => "на складе филиала",
                7 => "нет в наличии",
                8 => "отменён клиентом",
                9 => "просрочен",
                31 => "ожидаем товар на складе",
                32 => "возврат на согласовании",
                33 => "товар на экспертизе",
                34 => "возврат отклонён",
                35 => "возврат частично отклонён",
                36 => "товар возвращён"
            );
                
    	    $status_order_item = isset($order->OrdersList->Order->parts->part->status) ? $order->OrdersList->Order->parts->part->status : null;
    	    $status_order_item_text = isset($supplier_statuses[$status_order_item]) ? $supplier_statuses[$status_order_item] : 'Не определен';
    	    
    		$sao_info .= "ID заказа: {$order->OrdersList->Order->id}<br/>";
    		$sao_info .= "Заказ создан: {$order->OrdersList->Order->created_date}<br/>";
    		$sao_info .= "Статус оплаты: {$order->OrdersList->Order->payment_status}<br/>";
    		$sao_info .= "Дата поставки: {$order->OrdersList->Order->delivery_date}<br/>";		
    		$sao_info .= "Статус позиции: {$status_order_item_text}<br/>";	
    		
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			/*
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
			$new_status_query->bindValue(':id', $docpartState);
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
				$error = curl_error($ch);
				curl_close($ch);
			}
			*/
			
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
 			$update_query->bindValue(':sao_message', $sao_info);
			$update_query->bindValue(':id', $order_item["id"]);

    		
    		if( !$update_query->execute() )
    		{	
    			echo date("d-m-Y H:i:s")."\n";
    			echo "Ошибка  mysql: \n";
    			echo mysqli_error($db_link)."\n";
    			echo $UPD_STATUS."\n";
    			echo "============================================================\n";
    			
    			$sao_result["status"] = false;
    			$sao_result["message"] = "Не удалось обновить состояние, обратитесь к инженеру Docpart";
    		}
    		else
    		{
    			$sao_result["status"] = true;
    		}
    		
    	}
    	else
    	{
    		$error_message = isset($order->message) ? $order->message : "Ошибка при получении заказа";
		    throw new Exception($error_message);
    	}
	
	} else {
	    throw new Exception("Ошибка получения данных после запроса GetOrders. Проверьте корзину в личном кабинете Росско.");
	}

}
catch(SoapFault $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка SOAP: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка подключения в сервису, обратитесь к инженеру Docpart";
}
catch(Exception $e)
{
	echo date("d-m-Y H:i:s")."\n";
	echo "Ошибка: \n";
	echo $e->getMessage()."\n";
	echo "============================================================\n";
	
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка подключения в сервису, обратитесь к инженеру Docpart";
}

//Лог
$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>