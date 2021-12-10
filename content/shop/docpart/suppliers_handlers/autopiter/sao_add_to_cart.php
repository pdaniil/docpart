<?php
// Пути подключения файлов
$pathExceptions = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/autopiter/exceptions/";
$log = $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/autopiter/dump_cart.log";
set_include_path(get_include_path() . PATH_SEPARATOR . $pathExceptions);

// Ф-ция автозагрузки
spl_autoload_register(function($class)
{
	require_once("{$class}.php");
});

ob_start();

$sao_result = array(); //Результат выполнения
$sao_result["status"] = false;
$json_options = json_decode($order_item["t2_json_params"], true); //Данные для SAO

// var_dump($json_options);
try
{
	//Создаём клиент и авторизируемся
	$AutopiterClient = new SoapClient("http://service.autopiter.ru/price.asmx?WSDL");
	if(! $AutopiterClient->IsAuthorization()->IsAuthorizationResult) 
	{
		$AutopiterClient->Authorization(array("UserID"=>"{$connection_options["user"]}", "Password"=>"{$connection_options["password"]}", "Save"=> "true"));
	}

	// $AutopiterClient->ClearBasket();
	
	// Кладём позицию в корзину
	$insertResult = $AutopiterClient->InsertToBasket(
		array("items"=> array(
			array(
				"Cost"=>$json_options["Cost"],
				"IdArticleDetail"=>$json_options["IdArticleDetail"],
				"Quantity"=>$order_item["count_need"]
			)
		))
	);
	
	// $basket = $AutopiterClient->GetBasket();
	// var_dump($insertResult);
	// var_dump($basket);
	// /*
		// После успшного добавления товара в корзину (ResponseCode == 0)
		// Сохраняем данные позиции в json объект
		// Ставим SAO статус "в корзине".
	// */
	
	$code = $insertResult->InsertToBasketResult->ResponseCodeItem->Code->ResponseCode;//Код ответа 0-успешно
	
	if($code == 0)
	{
		$itemCart	= $AutopiterClient->GetBasket()->GetBasketResult->ItemCart; //Получаем созданную позицию
		
		$item_cart_trim = array();
		
		foreach($itemCart as $k => $v) {
			
			$replace = str_replace("\"", "", $v);
			
			$item_cart_trim[$k] = trim($replace);
			
		}
		
		$jsonItem	= json_encode($item_cart_trim);
		$date 		= date("d-m-Y");
		

		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object` = :sao_state_object, `sao_state` = :sao_state, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state_object', $jsonItem);
		$update_query->bindValue(':sao_state', 6);
		$update_query->bindValue(':sao_message', 'Добавлено в корзину<br/>'.$date);
		$update_query->bindValue(':id', $order_item["id"]);
		if( ! $update_query->execute() )
			throw new MysqliException("Ошибка изменения SAO-Состояния!");
		
		$sao_result["status"] = true;
	}
	elseif($code == 4)
	{
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_message', 'Цена изменилась, необходимо переоценить позицию!');
		$update_query->bindValue(':id', $order_item["id"]);
		
		if( ! $update_query->execute() )
			throw new MysqliException("Ошибка создания записи в БД!");
		
		$sao_result["status"] = true;
	}
	else
	{
		$sao_result["message"] = "Ошибка добавления позиции в корзину!";
	}	
}
catch(SoapFault $e)
{
	echo $e->getMessage();
	$sao_result["message"]  = "Ошибка соединения с сервисом!";
}
catch(MysqliException $e)
{
	$AutopiterClient->ClearBasket();//Очищаем корзину
	$sao_result["message"] = $e->getMessage();
}

file_put_contents($log, ob_get_clean());
?>