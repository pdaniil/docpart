<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/soap_transport.php");
/**
SAO
Действие: Добавить к корзину

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
$t2_json_params = json_decode($order_item["t2_json_params"], true);



$data['session_id'] = $session_id;
$data['session_guid']='';
$data['session_login']=$login;
$data['session_password']=$passwd;



$data['Reference'] = $order_item["id"];
$data['AnalogueCodeAsIs'] = $order_item["t2_article"];
$data['AnalogueManufacturerName'] = $order_item["t2_manufacturer"];
$data['OfferName'] = $t2_json_params['OfferName'];
$data['LotBase'] = $t2_json_params['LotBase'];
$data['LotType'] = $t2_json_params['LotType'];
$data['PriceListDiscountCode'] = $t2_json_params['PriceListDiscountCode'];//?????
$data['Price'] = $order_item["t2_price_purchase"];//!!! В пакете заменить на t2_price_purchase
$data['Quantity'] = $order_item["count_need"];
$data['PeriodMin'] = $order_item["t2_time_to_exe"];
$data['ConstraintPriceUp'] = '-1';
$data['ConstraintPeriodMinUp'] = '-1';
$data['SelfNote'] = '';
$data['SupplierNote'] = '';
$data['ClientNote'] = '';

/*
$log = fopen($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/log.txt", "a");
fwrite($log, var_export($data, true) );
fclose($log);
*/

$SOAP = new soap_transport();
$requestXMLstring = createSearchRequestXML($data);
$errors=array();
$responceXML = $SOAP->query('AddBasket', array('AddBasketXml' => $requestXMLstring), $errors);




//Разбор данных ответа
if ($responceXML)
{
	$basket_result = parseAddBasketResponseXML($responceXML);
}


if( ! is_array($basket_result) )
{
	$sao_result["status"] = false;
	$sao_result["message"] = "Техническая неполадка";
	
	//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
	$update_query = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object`=:sao_state_object, `sao_message` = :sao_message WHERE `id` =:id;');
	$update_query->bindValue(':sao_state_object', var_export($basket_result, true));
	$update_query->bindValue(':sao_message', 'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()));
	$update_query->bindValue(':id', $order_item_id);
	$update_query->execute();
}
else//Запрос выполнен - проверяем результат
{
	$update_query_complete = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object`=?, `sao_message` = ?, `sao_state` = ?, `sao_robot` = ? WHERE `id` = ?;');
	
	$update_query_others = $db_link->prepare('UPDATE `shop_orders_items` SET `sao_state_object`=?, `sao_message` = ? WHERE `id` = ?;');
	
	switch($basket_result[0]["Status"])
	{
		case "Complete":
			$sao_result["status"] = true;
			$sao_result["message"] = "Добавлено. ".var_export($basket_result, true);
			
			$new_sao_state = 6;//В корзине
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_complete->execute( array(json_encode($basket_result), 'Добавлено в корзину: '.date("d.m.Y H:i:s", time()), $new_sao_state, 0, $order_item_id) );
			break;
		case "NotFound":
			$sao_result["status"] = false;
			$sao_result["message"] = "Такое предложение не найдено. ".var_export($basket_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array(var_export($basket_result, true), 'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()).'. Предложене не найдено', $order_item_id) );
			break;
		case "InvalidConstraint":
			$sao_result["status"] = false;
			$sao_result["message"] = "Позиция не размещена, т.к. не выполнены условия размещения. ".var_export($basket_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array(var_export($basket_result, true),'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()).'. Позиция не размещена, т.к. не выполнены условия размещения.',$order_item_id) );
			break;
		case "InvalidQuantity":
			$sao_result["status"] = false;
			$sao_result["message"] = "Позиция не соответствует условиям кратности партии или минимального заказа. ".var_export($basket_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array(var_export($basket_result, true),'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()).'. Позиция не соответствует условиям кратности партии или минимального заказа.',$order_item_id) );
			break;
		default:
			$sao_result["status"] = false;
			$sao_result["message"] = "Статус не определен. ".var_export($basket_result, true);
			
			//Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
			$update_query_others->execute( array(var_export($basket_result, true),'Ошибка добавления в корзину: '.date("d.m.Y H:i:s", time()).'. Статус не определен.',$order_item_id) );
			break;
	}
}


// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------
//ОПРЕДЕЛЕНИЯ ФУНКЦИЙ
function createSearchRequestXML($data) 
{
	
	$session_info = $data['session_guid'] ? 
		'SessionGUID="'.$data['session_guid'].'"' : 
		'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';
	
	$xml = '<root>
			  <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.'/>
			  <rows>
				<row>
					<Reference>'.$data['Reference'].'</Reference>
					<AnalogueCodeAsIs>'.$data['AnalogueCodeAsIs'].'</AnalogueCodeAsIs>
					<AnalogueManufacturerName>'.$data['AnalogueManufacturerName'].'</AnalogueManufacturerName>
					<OfferName>'.$data['OfferName'].'</OfferName>
					<LotBase>'.$data['LotBase'].'</LotBase>
					<LotType>'.$data['LotType'].'</LotType>
					<PriceListDiscountCode>'.$data['PriceListDiscountCode'].'</PriceListDiscountCode>
					<Price>'.$data['Price'].'</Price>
					<Quantity>'.$data['Quantity'].'</Quantity>
					<PeriodMin>'.$data['PeriodMin'].'</PeriodMin>
					<ConstraintPriceUp>'.$data['ConstraintPriceUp'].'</ConstraintPriceUp>
					<ConstraintPeriodMinUp>'.$data['ConstraintPeriodMinUp'].'</ConstraintPeriodMinUp>
					<SelfNote>'.$data['SelfNote'].'</SelfNote>
					<SupplierNote>'.$data['SupplierNote'].'</SupplierNote>
					<ClientNote>'.$data['ClientNote'].'</ClientNote>
				</row>
			  </rows>
			</root>';
	return $xml;
}
// --------------------------------------------------------------------------------------
function parseAddBasketResponseXML($xml) 
{
	$data = array();
	foreach($xml->rows->row as $row) 
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