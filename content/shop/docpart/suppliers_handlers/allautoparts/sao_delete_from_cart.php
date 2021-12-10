<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/soap_transport.php");
/**
SAO
Действие: Удалить из корзины

Данный скрипт выполняется в контексте:
- либо ajax_exec_action.php (выполнение действия по нажатию кнопки)
- либо в контексте скрипта робота
*/

// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

// --------------------------------------------------------------------------------------
//1. Авторизуемся

/*****Учетные данные*****/
$login = $connection_options["login"];
$passwd = $connection_options["password"];
$session_id = $connection_options["session_id"];
/*****Учетные данные*****/



// --------------------------------------------------------------------------------------
//2. Выполняем сам запрос

//Специальные параметры
$sao_state_object = json_decode($order_item["sao_state_object"], true);


$data['session_id'] = $session_id;
$data['session_guid']='';
$data['session_login']=$login;
$data['session_password']=$passwd;
$data['RowID'] = $sao_state_object[0]["OrderID"];



$SOAP = new soap_transport('https://allautoparts.ru/WEBService/BasketService.svc/wsdl?wsdl');
$requestXMLstring = createRequestXML($data);
$errors=array();
$Response_result = $SOAP->query('DeleteBasketDetails', array('ParametersXml' => $requestXMLstring), $errors);


//Разбор данных ответа
if ($Response_result)
{
	$Response_result = parseResponseXML($Response_result);
}


if( ! is_array($Response_result) )
{
	$sao_result["status"] = false;
	$sao_result["message"] = "Техническая неполадка";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = :sao_message WHERE `id` = :id;');
	$update_query->bindValue(':sao_message', 'Ошибка удаления из корзины: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else
{
	$update_query_ok = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = ?, `sao_state` = ?, `sao_robot` = ? WHERE `id` = ?;');
	
	
	$update_query_others = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_message` = ? WHERE `id` = ?;');
	
	switch($Response_result[0]["State"])
	{
		case "Ok":
			$sao_result["status"] = true;
			$sao_result["message"] = "Удалено. ".var_export($Response_result, true);
			
			$new_sao_state = 7;//Удалено из корзины
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_ok->execute( array('Удалено из корзины: '.date("d.m.Y H:i:s", time()), $new_sao_state, 0, $order_item_id) );
			break;
		case "Error":
			$sao_result["status"] = false;
			$sao_result["message"] = "Ошибка удаления из корзины. ".var_export($Response_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array('Ошибка удаления из корзины: '.date("d.m.Y H:i:s", time()),$order_item_id) );
			break;
		default:
			$sao_result["status"] = false;
			$sao_result["message"] = "Статус не определен. ".var_export($Response_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array('Ошибка удаления из корзины: '.date("d.m.Y H:i:s", time()).'. Статус не определен.', $order_item_id) );
			break;
	}
}





// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
//ОПРЕДЕЛЕНИЯ ФУНКЦИЙ
function createRequestXML($data) 
{
	
	$session_info = $data['session_guid'] ? 
		'SessionGUID="'.$data['session_guid'].'"' : 
		'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';
	
	$xml = '<root>
			  <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.'/>
			  <Details>
				<Detail>
					<RowID>'.$data['RowID'].'</RowID>
				</Detail>
			  </Details>
			</root>';
	return $xml;
}
// --------------------------------------------------------------------------------------
function parseResponseXML($xml) 
{
	$data = array();
	foreach($xml->Details->Detail as $row) 
	{
		$_row = array();
		foreach($row as $key => $field) 
		{
			$_row[(string)$key] = (string)$field;
		}
		$data[] = $_row;
	}
	return $data;
}
?>