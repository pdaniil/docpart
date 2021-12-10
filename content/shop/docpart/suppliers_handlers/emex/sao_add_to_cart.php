<?php

require_once("classes/lib.php");

//Конфигурация
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
//Соединение с основной БД
$DP_Config = new DP_Config;//Конфигурация CMS

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

//Инициализируем переменные
$login			= $connection_options["login"];//Логин Emex
$password		= $connection_options["password"];//Пароль Emex
$addParams		= json_decode($order_item["t2_json_params"], true);//Параметры для SAO

$order_item["MakeLogo"]			= $addParams["MakeLogo"];
$order_item["PriceLogo"]			= $addParams["PriceLogo"];
$order_item["DestinationLogo"]		= $addParams["DestinationLogo"];
$order_item["DeliveryRegionType"]		= $addParams["DeliveryRegionType"];


$sao_result	= array();//Результат работы API
$sao_result["status"] = false;	

$sao_object = array(); //Тех параметры для SAO
$data = ""; //Время создания заказа

//******************************************************************************************************************************************************
try
{

	$wsdl_service	= "http://ws.emex.ru/EmEx_Basket.asmx?wsdl"; //Адрес сервиса
	
	$soap_options = array(
		'soap_version' => SOAP_1_2,
		'exceptions' => true
	);
	
	$api 		= new EmexService( $wsdl_service, $soap_options );
	
	//Уникальная последовательность
	$basket_ref = md5 ( 
		$order_item['MakeLogo'] .
		$order_item['PriceLogo'] .
		$order_item['t2_article']. 
		microtime() 
	);
				
	//Описание позиции emex'a
	$emx_basket_item = array ( 
		"Num" => 1,
		"MLogo" => $order_item['MakeLogo'],
		"DNum" => $order_item['t2_article'],
		"Name" => $order_item['t2_name'],
		"Quan" => $order_item['count_need'],
		"Price" => $order_item['t2_price_purchase'],
		"PLogo" => $order_item['PriceLogo'],
		"DLogo" => $order_item['DestinationLogo'],
		"Ref" => $basket_ref,
		"DeliveryRegionType" => $order_item['DeliveryRegionType'],
		"Notc" => 'true',
		"Com" => ""
	);
				
	$Request = array (
		"login"=> $login,
		"password"=> $password, 
		"ePrices" => array( $emx_basket_item ) //Список позиций
	);
	
	$api->getRequest( $Request ); //Устанавливаем данные запроса
	
	$res = $api->addToBacket(); //Кладём в корзину
	
	if ( isset( $res['GlobalId'] ) ) {
		
		//Особое пожелание первого заказчика. Эти параметры необходимо отображать в ПУ
		$sao_object["Num"]						= $res['Num'];
		$sao_object["GlobalId"]			    	= $res['GlobalId'];
		$sao_object["Reference"]				= $emx_basket_item['Ref'];
		$sao_object["MLogo"]					= $order_item["MakeLogo"];
		$sao_object["PLogo"]					= $order_item["PriceLogo"];
		$sao_object["DeliveryRegionType"]		= $order_item["DestinationLogo"];

		$json_sao_object = json_encode($sao_object);
		$data = date("d-m-Y H:i:s");
		
		//Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния
		$new_sao_state = 6;

		$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
		$new_status_query->execute( array($new_sao_state) );
		$new_status_record = $new_status_query->fetch();
		$new_status = $new_status_record["status_id"];
		
		//Отправляем запрос на изменение статуса позиции
		if ( $new_status > 0 ) {
						
			$orders_items_list = array( $order_item["id"] );
			$order_items_json = json_encode( $orders_items_list );
			
			$url_service = $DP_Config->domain_path;
			$action_service = "content/shop/protocol/set_order_item_status.php";
			
			$params_action = array(
				"initiator" => 2,
				"orders_items" => $order_items_json,
				"status" => $new_status,
				"key" => $DP_Config->tech_key
			);
			
			$url_request = $url_service . $action_service . "?" . http_build_query( $params_action );
			
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url_request );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
		}
		
		$UPDATE_ORDERITEM = "
			UPDATE 
				`shop_orders_items`
			SET
				`sao_state` = ?,
				`sao_state_object` = ?,
				`sao_message` = ?
			WHERE
				`id` = ?
		;";
		
		
		if ( !$db_link->prepare($UPDATE_ORDERITEM)->execute( array($new_sao_state, $json_sao_object, 'Товар добавлен в корзину<br/> '.$data,$order_item["id"])) ) {
			
			$message = "Позиция отправлена в корзину, но произошла ошбика обновления записи";
			throw new Exception( $message );
			
		}
		
		$sao_result['status'] = true; //Успех операции
		
	} else {
		
		//Обрабатываем ошибку
		$message = $res['error'];
		throw new Exception( "Ошибка отправки позиции в корзину: " . $message );
		
	}
	

} catch ( SoapFault $e ) {
	
	$sao_result["message"] = "Невозможно подключиться к сервису: " . $e->getMessage();
	
} catch ( Exception $e ) {
	
	$sao_result["message"] = $e->getMessage();
	
}
?>