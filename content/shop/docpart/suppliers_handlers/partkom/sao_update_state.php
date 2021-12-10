<?php
/***********************************************************************
* SAO
* Действие: Проверить состояние

* Данный скрипт выполняется в контексте:
	** либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
	** либо в контексте скрипта робота
	
*************************************************************************/

//Структура результата - Если массив останется пустой, то позиция заказана, Иначе вернётся позиция с сообщением об ошибке.
$sao_result = array();

/**********************************************************************
	* Настройки подключения
***********************************************************************/
 
$login			= $connection_options["login"];		//Логин Партком
$password		= $connection_options["password"];	//Пароль Партком
$under_domain	= $connection_options["under_domain"];

$reference = json_decode($order_item["sao_state_object"], true)["reference"];

/***********************************************
	* CURL - запрос
***********************************************/

$url = "http://{$under_domain}.part-kom.ru/engine/api/v3/motion/{$reference}";

$array_headers = array(
	'Authorization: Basic '.base64_encode("{$login}:{$password}"),
	'Accept: application/json',
	'Content-type: application/json'
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $array_headers);

$execResult = curl_exec($ch);

ob_start();

var_dump($url);
// var_dump($execResult);

curl_close($ch);

if($infoForItems = json_decode($execResult))
{
	// var_dump($infoForItems);
	$docpartState; //Приведённый SAO-статус Парткома к Docpart
	$apiMessage = ""; //Стандартно
	foreach($infoForItems as $items)
	{
		switch($items->state)
		{
			case 29:
			 case 43:
			 case 45:
			 case 47:
			 case 49:
			 case 50:
			 case 37:
			 case 38:
			 case 49:
			 case 32:
			 case 33:
			 case 30:
			 case 27:
			 case 18:
			 case 0:
			 case 1:
			 case 5:
			 case 6:
			 case 8:
			 case 9:
			 case 10:
			 case 12:
			 case 14:
			 case 15:
			 case 16:
				$docpartState = 3;
				$apiMessage = $items->stateTxt .":";
				$apiMessage .= "<br/>".$items->orderDate;
				break;
			 case 51:
			 case 52:
			 case 53:
			 case 54:
			 case 55:
			 case 56:
			 case 57:
			 case 58:
			 case 59:
			 case 60:
			 case 61:
			 case 62:
			 case 63:
			 case 44:
			 case 42:
			 case 41:
			 case 34:
			 case 35:
			 case 28:
			 case 26:
			 case 25:
			 case 24:
			 case 4:
			 case 2:
				$docpartState = 4;
				$apiMessage = $items->stateTxt;
				break;
			 case 46:
			 case 36:
			 case 31:
			 case 22:
			 case 21:
			 case 20:
			 case 19:
			 case 3:
				$docpartState = 5;
				$apiMessage = $items->stateTxt;
				break;
			 
			 case 7:
			 case 23:
				$docpartState = 1;
				$apiMessage = $items->stateTxt;
				break;
			 default:
				$docpartState = 3;
		}
	}
	//Определяем текущий SAO-статус позиции заказа
	$sqlRes = $db_link->prepare('SELECT `sao_state` FROM `shop_orders_items` WHERE `id` = :id;');
	$sqlRes->bindValue(':id', $order_item["id"]);
	$sqlRes->execute();
	$sqlFetch 		= $sqlRes->fetch();
	$currentState	= $sqlFetch["sao_state"];
	//Если полученный статус не равен текущему, обновляем текущий
	if($docpartState != $currentState)
	{
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_status_query = $db_link->prepare('SELECT `status_id` FROM `shop_sao_states` WHERE `id` = :id;');
		$new_status_query->bindValue(':id', $docpartState);
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
			$error = curl_error($ch);
			curl_close($ch);
		}


		//Ставим статус заказано.(МБ ПОСТАВИТЬ СТАТУС В РАБОТЕ?)
		$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state` = :sao_state, `sao_message` = :sao_message, `sao_robot` = :sao_robot WHERE `id` = :id;');
		$update_query->bindValue(':sao_state', $docpartState);
		$update_query->bindValue(':sao_message', $apiMessage);
		$update_query->bindValue(':sao_robot', 0);
		$update_query->bindValue(':id', $order_item["id"]);

		if( ! $update_query->execute() )
		{
			$sao_result["status"] = false;
			$sao_result["message"] = "Ошибка обновления SAO-статуса";
		}
		else
		{
			$sao_result["status"] = true;
			$sao_result["message"] = "Статус обновлён";
		}
	} // ~if($docpartState != $currentState)
	else
	{
		$sao_result["status"] = true;
		$sao_result["message"] = "SAO-состояние не изменилось";
	}
} // ~if($infoForItems = json_decode($execResult)) 
else
{
	$sao_result["status"] = false;
	$sao_result["message"] = "Ошибка запроса к  API";
}

file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/suppliers_handlers/partkom/upd.log", ob_get_clean());
?>