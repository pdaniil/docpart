<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once( 'PartSoftApi.php' );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class part_soft_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct( $article, $manufacturers, $storage_options ) {
		
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$api_key = $storage_options['api_key'];
		$site = $storage_options['site'];
		
		$api_options = array (
			'base' => $site,
			'api_key' => $api_key
		);
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием объекта PartSoftApi с аргументом ".print_r($api_options, true) );
		}
		
		$api = new PartSoftApi( $api_options );
		
		$action = "api/v1/search/get_offers_by_oem_and_make_name"; //Получени позиций с уточнением бренда
		$api->setAction( $action );
		
		$params_action = array ( 
			'oem' => $article
		);
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Перед циклом foreach по каждому поставщику. Метод setAction(".$action.")");
		}
		
		//По каждому синониму//
		foreach ( $manufacturers as $m ) {
			
			$params_action['make_name'] = $m['manufacturer'];
			
			//Устанавливаем параметры запроса
			$api->setParamsAction( $params_action );
			
			try {
				
				$api->exec();
				$response_json = $api->getResponse(); //Получаем ответ поставщика в json
				
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение цен по артикулу ".$article." и бренду ".$m['manufacturer'], "Метод setAction(".$action."). Параметры ".print_r($params_action,true), $response_json, print_r(json_decode($response_json, true), true) );
				}
				
				
				$response_arr = json_decode( $response_json, true );
				
				if ( $response_arr['result'] == 'ok'
					&& is_array( $response_arr['data'] )
				) {
				
					foreach ( $response_arr['data'] as $item ) {
						
						$article 					= $item['oem'];
						$manufacturer 				= $item['make_name'];
						$name 						= trim( $item['detail_name'] );
						$price_purchase			= $item['cost'];
						$exist 					= $item['qnt'];
						
						if ( $exist == -1 ) { $exist = 1; } //Точно наличие неивестно, но оно есть.
						
						
						
						$time_to_exe				= $item['min_delivery_day'] + $storage_options['additional_time'];
						$time_to_exe_guaranteed	= $item['max_delivery_day'] + $storage_options['additional_time'];
						$storage 					= 0;
						$min_order					= $item['min_qnt'];
						$probability				= $item['stat_group'];
						$product_type				= 2;
						$product_id				= 0;
						$storage_record_id		= 0;
						$url						= '';
						$json_params				= json_encode(
														array( 
															'system_hash' => $item['system_hash'],
															'min_delivery_day' => $item['min_delivery_day'],
															'max_delivery_day' => $item['max_delivery_day']
													) );
						$rest_params				= array( "rate"=>$storage_options["rate"] );
						
						//Наценка
						$markup = $storage_options["markups"][(int)$price_purchase];
						//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						if( $markup == NULL ) {
							
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							
						}
						
						$price_for_customer = $price_purchase + $price_purchase * $markup;
						
						$DocpartProduct = new DocpartProduct( 
							$manufacturer, 
							$article,
							$name,
							$exist,
							$price_for_customer,
							$time_to_exe,
							$time_to_exe_guaranteed,
							$storage,
							$min_order,
							$probability,
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price_purchase,
							$markup,
							$product_type,
							$product_id,
							$storage_record_id,
							$url,
							$json_params,
							$rest_params 
						);
						
						if ( $DocpartProduct->valid ) {
							
							array_push( $this->Products, $DocpartProduct );
							
						}
						
					} // END foreach ( $response_arr['data'] as $item )
				}
				else if (  $response_arr['result'] == 'error' ) {
			
					$error_message = "Поставщик вернул ошибку: {$response_arr['error']}";
					throw new Exception( $error_message );
			
				}
			}
			catch ( Exception $e ) {
			
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", $e->getMessage() );
				}
				
			}
			
		} // END foreach ( $manufacturers as $m )
	
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	} // END public function __construct( $article, $manufacturers, $storage_options )
	
	public function __toString () {
		
		return json_encode( $this );
		
	}
	
}

$article = $_POST["article"];
$manufacturers = json_decode( $_POST["manufacturers"], true );

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON (библиотека поставщика)") );


try { 

	$enclosure = new part_soft_enclosure (
		$article,
		$manufacturers,
		$storage_options
	);
	
	echo $enclosure;

}
catch ( Exception $e ) {
	
	//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", $e->getMessage() );
	}
	
}
?>