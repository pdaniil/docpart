<?php
// Пути подключения файлов
$pathExceptions = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/autopiter/exceptions/";
$log = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/autopiter/dump_order.log";

set_include_path(get_include_path() . PATH_SEPARATOR . $pathExceptions);

// Ф-ция автозагрузки
spl_autoload_register(function($class)
{
	require_once("{$class}.php");
});

ob_start();

$sao_result = array(); //Результат выполнения
$sao_result["status"] = false;
$ItemCart = json_decode($order_item["sao_state_object"], true); //Данные для SAO

$item_cart_h = array();
try
{

	if( ! $ItemCart ) {
		
		throw new Exception("Ошибка разбора json: " . json_last_error());
		
	}
	
	foreach ($ItemCart as $k=>$v) {
		
		if ($v == "") {
			
			$v = null;
			
		}
		
		$item_cart_h[$k] = $v;
		
	}
	
	//Создаём клиент и авторизируемся
	$client = new SoapClient("http://service.autopiter.ru/price.asmx?WSDL", array("soap_version" => SOAP_1_2 ,"trace"=>true));
	
	/*
		Получаем сохранённый из корзины объект  ($json_options)
		Кладём позицию в заказ MakeOrderByItems()
		Проверяем результат, если успешно пишем данные в базу, меняем sao состояние
	*/
	$arrayItemsCart["ItemCart"] = $item_cart_h;

	$auth = $client->IsAuthorization();
	
	if( ! $auth->IsAuthorizationResult) {
		
		$auth_res = $client->Authorization(array("UserID"=>"{$connection_options["user"]}", "Password"=>"{$connection_options["password"]}", "Save"=> "true"));
		
	}
	
	$orderResult = $client->MakeOrderByItems(array("items"=>$arrayItemsCart)); //Создаём заказ
	
	$numberInvoce = $orderResult->MakeOrderByItemsResult->NumberInvoice;

	if($numberInvoce) {
		
		$json_obj["NumberInvoice"] = $numberInvoce;
		$json_obj = json_encode($json_obj);
		$date = date("d-m-Y");
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 2);
		$update_query->bindValue(':sao_state_object', $json_obj);
		$update_query->bindValue(':sao_message', 'Заказ оформлен<br/>'.$date.'<br/>ID заказа: '.$numberInvoce);
		$update_query->bindValue(':id', $order_item["id"]);
		
		if( ! $update_query->execute() )
			throw new MysqliException("Ошибка изменнения SAO-состояния!");
		
		$client->ClearBasket();//Очищаем корзину.
		$sao_result["status"] = true;
		
	} else {
		
		$sao_result["message"] = "Ошибка создания заказа!";
		
	}
	
} catch(SoapFault $e) {
	
	echo $e->getMessage() . "\n";
	
	echo $e->getTraceAsString() . "\n";
	
	$sao_result["message"]  = "Ошибка соединения с сервисом! \n {$e->getMessage()}";
	
} catch(MysqliException $e) {
	
	$sao_result["message"] = $e->getMessage();
	
} catch (Exception $e) {
	
	$sao_result["message"] = $e->getMessage();
	
}

file_put_contents($log, ob_get_clean());
?>