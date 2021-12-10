<?php
// Пути подключения файлов
$pathExceptions = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/autopiter/exceptions/";
set_include_path(get_include_path() . PATH_SEPARATOR . $pathExceptions);

// Ф-ция автозагрузки
spl_autoload_register(function($class)
{
	require_once("{$class}.php");
});

ob_start();

$sao_result = array(); //Результат выполнения
$sao_result["status"] = false;
$orderAutopiterInfo = json_decode($order_item["sao_state_object"], true); //Данные для SAO
try
{
	//Создаём клиент и авторизируемся
	$AutopiterClient = new SoapClient("http://service.autopiter.ru/price.asmx?WSDL");
	if(! $AutopiterClient->IsAuthorization()->IsAuthorizationResult) 
	{
		$AutopiterClient->Authorization(array("UserID"=>"{$connection_options["user"]}", "Password"=>"{$connection_options["password"]}", "Save"=> "true"));
	}
	
	$deleteResult = $AutopiterClient->ClearBasket()->ClearBasketResult;//Очищаем корзину.
	
	if($deleteResult)
	{
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_state_object` = :sao_state_object, `sao_message` = :sao_message WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', 1);
		$update_query->bindValue(':sao_state_object', '');
		$update_query->bindValue(':sao_message', 'Удалено из корзины');
		$update_query->bindValue(':id', $order_item["id"]);
	
		if( ! $update_query->execute() )
			throw new MysqliException("Ошибка изменнения SAO-состояния!");
		
		$sao_result["status"] = true;
	}
	else
	{
		$sao_result["message"]  = "Ошибка очистки корзины!";
	}
	
	
}
catch(SoapFault $e)
{
	echo $e;
	$sao_result["message"]  = "Ошибка соединения с сервисом!";
}
catch(MysqliException $e)
{
	$sao_result["message"] = $e->getMessage();
}

$buffer = ob_get_contents();
ob_end_clean();
file_put_contents("dump_delete_cart.log", $buffer);
?>