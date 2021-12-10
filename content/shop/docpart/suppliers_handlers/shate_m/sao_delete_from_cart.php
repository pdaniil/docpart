<?php
/**
SAO
Действие: Удалить из корзины

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

ob_start();

$pathError		= $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/shate_m/errors_DELET_FROM_CART.log";


// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

// --------------------------------------------------------------------------------------
//1. Авторизуемся

//Учетные данныее
$login = $connection_options["login"];
$password = $connection_options["password"];
$api_key = $connection_options["api_key"];

//В зависимости от страны - разные адреса
$api_path = "";
switch($connection_options["country"])
{
	case "ru":
		$api_path = "https://api.shate-m.ru/";
		break;
	case "by":
		$api_path = "https://api.shate-m.com/";
		break;
	case "kz":
		$api_path = "http://svkzastsa0003:8989/";
		break;
	default: $api_path = "https://api.shate-m.ru/";
}


$url = $api_path . 'login/'; 

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5');
curl_setopt($ch, CURLOPT_USERPWD, $login.":".$password); 

curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_NOBODY, 1);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "ApiKey=".$api_key);

// this function is called by curl for each header received
curl_setopt( $ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use ( & $headers_answer ) {
	
	$len = strlen($header);
	
	$header = explode(':', $header, 2);
	
	if ( count( $header ) < 2 ) // ignore invalid headers
	  return $len;

	$name = strtolower( trim( $header[0] ) );
	
	if ( ! array_key_exists( $name, $headers_answer ) )
		$headers_answer[$name] = array( trim( $header[1] ) );
	else
		$headers_answer[$name][] = trim( $header[1] );

	return $len;
	
});

$result = curl_exec( $ch ); 

// Читаем token
$token = $headers_answer['token'][0];


// Если токена нет то выходим
if ( ! isset( $token ) )
{
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка авторизации в API Шате-М";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$sao_message = "Ошибка авторизации при добавлении в корзину: ".date("d.m.Y H:i:s", time());
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = ? WHERE `id` = ?;');
	$update_query->execute(array($sao_message, $order_item_id));
}
else//Работаем дальше - добавляем в корзину
{
	//Идентификатор берем из объекта ответа на запрос "Добавить в корзину"
	$sao_state_object = json_decode($order_item["sao_state_object"], true);

	if( $sao_state_object == null )
	{
		$sao_result["status"] = false;
		$sao_result["message"] = "В sao_state_object не оказалось параметров для выполнения команды";
		
		//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
		$sao_message = "Ошибка удаления из корзины: ".date("d.m.Y H:i:s", time());
		
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object` = ?, `sao_message` = ? WHERE `id` = ?;');
		$update_query->execute(array(json_encode($result), $sao_message, $order_item_id));

	}
	else//Есть параметры
	{
		$PartID = $sao_state_object["AddCartItemWebApiRezult"][0]["PartID"];
	
		$url = $api_path . 'api/cart/RemoveItemCart/'.$PartID;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
		curl_setopt($ch, CURLOPT_HEADER, 0);

		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5');

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 
			"Token: {$token}"                                                                       
		)); 

		$result = curl_exec($ch);
		
		$result = json_decode($result, true);

	
		if( $result == "OK" )//ПОЛНЫЙ УСПЕХ
		{
			//После данного действия SAO-состояние данной позиции должно получить id=7 (Удалено из корзины)
			$new_sao_state = 7;
			
			//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
			$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;');
			$new_status_query->execute(array($new_sao_state));
			$new_status_record = $new_status_query->fetch();
			$new_status = $new_status_record["status_id"];

			if($new_status > 0)
			{
				//Отправляем запрос на изменение статуса позиции
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".$DP_Config->tech_key);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
			}
						
			$sao_result["status"] = true;
			$sao_result["message"] = "Ок";
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$sao_message = "Удалено из корзины: ".date("d.m.Y H:i:s", time()) . "PartID: $PartID";

			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = ?, `sao_state` = ?, `sao_robot` = ? WHERE `id` = ?;');
			$update_query->execute(array($sao_message, $new_sao_state, 0, $order_item_id));

		}
		else//Ошибка удаления товара из корзины
		{
			$sao_result["status"] = false;
			$sao_result["message"] = "Код ответа: ".$result["StatusCode"].", текст ответа: ".$result["ReasonPhrase"];
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$sao_message = "Ошибка удаления из корзины: ".date("d.m.Y H:i:s", time());

			$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = ? WHERE `id` = ?;');
			$update_query->execute(array($sao_message, $order_item_id));
		}
	}
}
// --------------------------------------------------------------------------------------

//Лог
$dump = ob_get_contents();
file_put_contents($pathError, $dump, FILE_APPEND);
ob_end_clean();
?>