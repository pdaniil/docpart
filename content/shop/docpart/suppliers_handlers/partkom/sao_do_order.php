<?php
/**
SAO
Действие: Заказать

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/
class MysqlException extends Exception{}
// --------------------------------------------------------------------------------------
//0. Структура результата
$sao_result = array(); // - Если массив останется пустой, то позиция заказана, Иначе вернётся позиция с сообщением об ошибке.
ob_start();

// Настройки подключения
$login			= $connection_options["login"];
$password		= $connection_options["password"];
$flagTest		= $connection_options["flagTest"];
$under_domain	= $connection_options["under_domain"];
//Уникальные для ПАРТКОМ
$sao_options 	= json_decode($order_item["t2_json_params"], true);
// --------------------------------------------------------------------------------------

// Формирование запроса к API
$url 		= "http://{$under_domain}.part-kom.ru/engine/api/v3/order"; // URL запроса

//Уникальное значение, по нему будет отслеживать заказ
$reference = md5(
	$order_item["t2_article"] . 
	$sao_options["providerId"] . 
	$order_item["id"] . 
	time()
); 

//Создаём объект описания детали
$orderItem  = new stdClass();

$orderItem->detailNum	= $order_item["t2_article"];	// Артикул
$orderItem->makerId		= $sao_options["makerId"];		// ID производителя в сис-ме 'Партком' 
$orderItem->description	= $order_item["t2_name"];		// Описание детали
$orderItem->price		= (int)$order_item["price"];	// Цена в руб. без копеечной части
$orderItem->providerId	= $sao_options["providerId"];	// ID постовщика в сис-ме 'Партком'
$orderItem->quantity	= $order_item["count_need"];	// Количество
$orderItem->comment		= ""; 							// Коммент к заказу

$orderItem->reference  =$reference;

/*****************************************************************************************
$requestObject - Объект запроса к API. Содержит:
	@orderItems - коллекция объектов описания детали
	@flagTest, флаг теста. Если не указан, то API выполнит запрос в тестовом режиме.
		т.е боевой режим - @flagTest = false.		
*****************************************************************************************/
//Объект запроса к API: 
$requestObject = new stdClass();

if ($flagTest == 1) {
	
	$flagTest = true;
	
} else {
	
	$flagTest = false;
	
}


$requestObject->flagTest	 = $flagTest;
$requestObject->orderItems[] = $orderItem;
// $requestObject->generateReference = true;


$jsonRequest = json_encode($requestObject);
//Запрос к API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic '.base64_encode("{$login}:{$password}"),'Accept: application/json','Content-type: application/json'));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);

$execResult = curl_exec($ch);

curl_close($ch);

$errorItems = json_decode($execResult, true);

var_dump($errorItems);


/****************************************************
	* Если ответ содержит пустую коллекцию
		 -заказ ушёл в обработку. 
	* Если ответ содержит  коллекцию объектов
		-ошибка заказа, 
*****************************************************/
try
{
	if(empty($errorItems))
	{
		//Статус заказано();
		$new_sao_state = 2;
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', $new_sao_state);
		$new_status_query->execute();
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		if($new_status > 0)
		{
			//Отправляем запрос на изменение статуса позиции
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".urlencode($DP_Config->tech_key) );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
		}
		
		$sao_message = "Заказ в обработке. ".date("d-m-Y H:i:s");
		
		$sao_state_object = json_encode(
			array(
				"reference" => $reference
			)
		);
		
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_state_object` = :sao_state_object WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $new_sao_state);
		$update_query->bindValue(':sao_message', $sao_message);
		$update_query->bindValue(':sao_state_object', $sao_state_object);
		$update_query->bindValue(':id', $order_item["id"]);
		if( ! $update_query->execute())
		{
			echo mysqli_error($db_link)."\n";
			throw new MysqlException("Заказ создан, но произошла ошибка смены SAO-Состояния");
		}
		
		//результат
		$sao_result["status"] = true;
		$sao_result["message"] = "Заказ создан";
	}
	else
	{
		foreach($errorItems as $item)
		{
			$SET_ERROR = "
				UPDATE 
					`shop_orders_items`
				SET
					`sao_message` = 'Ошибка создания заказа.<br/> Код ошибки: {$item["errorCode"]}'
				WHERE
					`id` = {$order_item["id"]}
			;";
			
			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
			$update_query->bindValue(':sao_message', 'Ошибка создания заказа.<br/> Код ошибки: '.$item["errorCode"]);
			$update_query->bindValue(':id', $order_item["id"]);
			$update_query->execute();
		}
		//результат
		$sao_result["status"] = false;
		$sao_result["message"] = "Ошибка создания заказа";
	}
}
catch(MysqlException $e)
{
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage;
}



file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/partkom/order.log", ob_get_clean());
?>