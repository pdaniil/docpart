<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

include('CrimeaApi.php');


class crimea_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$api_key = trim( $storage_options['api_key'] );
			
		$api = new CrimeaApi( $api_key );
		
		$searchResutl_str = $api->Search( $article, '', 1 );
		
		//ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Получение остатков по артикулу ".$article, $searchResutl_str, print_r( json_decode( $searchResutl_str , true ), true ) );
		}
		
		$searchResutl = json_decode( $searchResutl_str , true );
		
		if ( json_last_error() ) {
			
			throw new Exception( 'Ошибка json: ' . json_last_error() );
			
		}
		
		if ( $searchResutl['info']['status'] == false ) {
			
			throw new Exception( 'Сервис вернул ошибку: ' . $searchResutl['info']['mes'] );
			
		}
		
		foreach ( $searchResutl['data'] as $item ) {
			
			$article 					= $item['article'];
			$manufacturer 				= $item['brand'];
			$name 						= $item['desc'];
			$price_purchase			= $item['price'];
			$exist 					= $item['amount'];
			
			$time_to_exe				= $storage_options['additional_time'];
			$time_to_exe_guaranteed	= $storage_options['additional_time'];
			$storage 					= 0;
			$min_order					= 1;
			$probability				= $storage_options['probability'];
			$product_type				= 2;
			$product_id				= 0;
			$storage_record_id		= 0;
			$url						= '';
			$json_params				= '';
			$rest_params				= array( "rate"=>$storage_options["rate"] );
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price_purchase];
			//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			if( $markup == NULL ) {
				
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				
			}
			
			$price_for_customer = $price_purchase + $price_purchase * $markup;
			
			$DocpartProduct = new DocpartProduct( $manufacturer, 
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
													$json_params = '',
													$rest_params );
			
			if($DocpartProduct->valid) {
				
				array_push($this->Products, $DocpartProduct);
				
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
	}
	
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON в классе CrimeaApi (CrimeaApi.php)") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


try {
	
	$ob =  new crimea_enclosure($_POST["article"], 
		json_decode($_POST["manufacturers"], true), 
		$storage_options
	);
	
	$json = json_encode($ob);
	
} 
catch ( Exception $e ) 
{
	$ob->result = 0;
	$ob->Products = array();
	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit($json);
?>