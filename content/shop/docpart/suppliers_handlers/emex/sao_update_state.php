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

ob_start();

//Инициализируем переменные
$sao_object 			= array(); //Тех параметры для SAO
$sao_result			= array();//Результат работы API
$sao_result["status"]	= false;	

$data = date("d-m-Y H:i:s");

$login				= $connection_options["login"];//Логин Emex
$password			= $connection_options["password"];//Пароль Emex

$sao_state_object	= json_decode( $order_item["sao_state_object"], true );//Параметры для SAO

$GlobalId = array( "long" => $sao_state_object['GlobalId'] );
$Reference = $sao_state_object['Reference'];

		 
//Выполняем запрос
try {
	
	$url_motion = "http://ws.emex.ru/EmExInmotion.asmx?wsdl"; //Сервис движений
	$url_basket = "http://ws.emex.ru/EmEx_Basket.asmx?wsdl"; //Сервис корзины
	
	$soap_options = array( 'soap_version' => SOAP_1_2 , 'trace' => true, 'exceptions' => true );
	
	//Получаем текущий объект
	$emx = new EmexService( $url_motion, $soap_options );
	
	$Request = array( 
		"login"=> $login,
		"password"=> $password,
		"greaterThenGlobalId"=> null,
		"globalIds"=> $GlobalId,
		"reference"=> $Reference 
	);
	
	$emx->getRequest( $Request );
	
	$emxData = $emx->GetConsumerInmotion3( array( "activeOnly" => false ) );
	
	//В заказе нет, ищем в корзине
	if ( $emxData == false ) {
		
		$emx = new EmexService( $url_basket, $soap_options );
		$emx->getRequest( $Request );
		$emxData = $emx->GetBasket( array( "basketPart" => "all" ) );
		
		//Какие-то проблемы...
		if ( $emxData == false ) {
			
			throw new Exception( "Не удалось получить объект emex" );
			
		}
		
	} 
	
	// /*
	$cancel_state_ids = array( 117 );
	//Получаем состояние
	$current_state = $emxData->getState();
	
	// var_dump( $emxData);
	// var_dump( $current_state );
	
	$state_id_em = $current_state['state_id'];
	
	$current_date = date( "d-m-Y H:i:s" );
	
	//Отмена
	if ( in_array( $state_id_em, $cancel_state_ids ) ) {
		
		//Получаем текущий статус и sao-состояние
		$current_item_status = $item['status'];
		
		$need_sao_state_id = 5; //Отказ поставщика
		
		//Получаем статус позиции, для sao-состояния
		$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
		$new_status_query->execute( array($need_sao_state_id) );
		$new_status_record = $new_status_query->fetch();
		$need_item_status = $new_status_record["status_id"];

		
		/*
		
		if ( $current_item_status != $need_item_status ) {
			
			$ch = curl_init();
			
			$params_url = http_build_query( array( "initiator" => 2,
													 "orders_items" => json_encode( array( (int) $order_item["id"] )  ),
													 "status" => $need_item_status,
													 "key" => $DP_Config->tech_key ) );
			
			$url_update_status = "{$DP_Config->domain_path}content/shop/protocol/set_order_item_status.php?{$params_url}";
			
			var_dump( $url_update_status );
			
			curl_setopt( $ch, CURLOPT_URL, $url_update_status );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$curl_result = curl_exec( $ch );
			$error = curl_error( $ch );
			curl_close( $ch );
			
		}
		
		 */
		 
		$sao_message = "ID состояния : {$state_id_em}<br />Состояние: {$current_state['state']} <br /> Комментарий: {$current_state['comment']}<br /> Обновлено: {$current_date}";
		
		$upd_sao_info = "
		UPDATE 
			`shop_orders_items`
		SET
			`sao_state` = ?,
			`sao_message` = ?,
			`sao_robot` = ?
		WHERE
			`id` = ? 
		";

		if( ! $db_link->prepare($upd_sao_info)->execute( array($need_sao_state_id, $sao_message, 0, $order_item["id"]))) {
					
			$error = "Ошибка обновления базы данных при обновлении sao статуса позиции";
			throw new Exception( $error );
			
		}
			
	} else {
		
		//Любое другое.
		
		$sao_message = "ID состояния : {$state_id_em}<br />Состояние: {$current_state['state']} <br /> Комментарий: {$current_state['comment']}<br /> Обновлено: {$current_date}";
		
		$upd_sao_info = "
		UPDATE 
			`shop_orders_items`
		SET
			`sao_message` = ?
		WHERE
			`id` = ? 
		";

		if( ! $db_link->prepare($upd_sao_info)->execute( array($sao_message, $order_item["id"]))) {
					
			$error = "Ошибка обновления базы данных при обновлении sao статуса позиции";
			throw new Exception( $error );
			
		}

	}
	
	$sao_result["status"] = true;
	$sao_result["message"] = "Статус обновлён";
	
	
} catch( SoapFault $e ) {
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
} catch( Exception $e ) {
	
	$sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
	
}

file_put_contents( __DIR__ . "/dump_update.log", ob_get_clean() );

?>