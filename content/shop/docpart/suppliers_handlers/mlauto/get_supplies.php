<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
// require_once( $_SERVER["DOCUMENT_ROOT"] . "/Logger.php" );

require_once( "MlAutoSupplierApi.php" );

class ml_auto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) {
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			
		}
		
		$api_options = array(
			"login" => $storage_options['login'],
			"password" => $storage_options['password']
		);
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Параметры авторизации";
			$debug->log_before_api_request( $debug_message, print_r( $api_options, true ) );
			
			$debug_message = "Бренды, учавствующие в запросе";
			$debug->log_before_api_request( $debug_message, print_r( $manufacturers, true ) );
			
		}
		
		$api = new MlAutoSupplierApi( $api_options );
		
		$params_action = array(
			"ARTICLE" => $article,
			"BRAND" => "",
			"SEARCH_TYPE" => "1"
		);

		$api->getParamsAction( $params_action );
		
		$time_now = time();//Время сейчас
		
		foreach ( $manufacturers as $m ) {
			
			$api->getParamsAction( 'BRAND', $m['manufacturer'] );
			
			if ( isset( $debug ) ) {
			
				$debug_message = "Получение остатков {$article} {$m['manufacturer']}";
				$debug->log_simple_message( $debug_message );
				
				$debug_message = "Параметры запроса";
				$debug->log_before_api_request( $debug_message, print_r( $api->getParamsAction(), true ) );
			
			}
			
			try {
				
				$items_result = $api->getSupplierItems();
				
				if ( isset( $debug ) ) {
					
					$debug_message = "Ответ поставщика";
					$debug->log_after_api_request( $debug_message, null, print_r( $items_result, true ) );
			
				}
				
				if ( $items_result['STATUS'] == 200 ) {
					
					$suppliers_items = $items_result['RESPONSE'];
					
					foreach ( $suppliers_items as $item ) {
						
						$article 					= $item['PIN'];
						$manufacturer 				= $item['BRAND'];
						$name 						= $item['NAME'];
						$price_purchase			= $item['PRICE'];
						$exist 					= (int)$item['QUANTITY'];
						
						//Срок доставки
						$time_to_exe = 0;
						
						$time_arrive = strtotime($item["DATE"]);//Время поступления
						
						if ( $time_arrive > $time_now ) {
							
							$time_to_exe = $time_arrive - $time_now;//Срок доставки в секундах
							$time_to_exe = (int)($time_to_exe/86400);//Срок доставки в днях
							
						}
						
						$time_to_exe_guaranteed	= $time_to_exe;
						$storage 					= $item['STORAGE_CODE'];
						$min_order					= $item['MIN'];
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
						
						$DocpartProduct = new DocpartProduct(
							$manufacturer, 
							$article,
							$name,
							$exist,
							$price_for_customer,
							$time_to_exe + $storage_options["additional_time"],
							$time_to_exe_guaranteed + $storage_options["additional_time"],
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
							$rest_params 
						);
						
						if ( $DocpartProduct->valid ) {
							
							array_push($this->Products, $DocpartProduct);
							
						}
						
					} // ~! foreach ( $suppliers_items as $item )
				
				} // ~! if ( $items_result['STATUS'] == 200 )
				
			} 
			catch ( Exception $e ) {
				
				if ( isset( $debug ) ) {
		
					$debug->log_error( $e->getMessage(), print_r( $e, true ) );
		
				}

			}
			
			
		} // ~! foreach ( $manufacturers as $m )
		
		$this->result = 1;
		
	} // ~! __construct($article, $manufacturers, $storage_options)
	
}

$storage_options = json_decode( $_POST["storage_options"], true );
$api_type = 'CURL_HTTP';

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	
	$debug = DocpartSuppliersAPI_Debug::getInstance();
	
	$init_options = array();
	$init_options['api_type'] = $api_type;
	$init_options['storage_id'] = $storage_options['storage_id'];
	$init_options['api_script_name'] = __FILE__;
	
	$debug->init( $init_options );
	$debug->start_log();
	
}

$ob =  new ml_auto_enclosure (
	$_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);

$json = json_encode( $ob );

if ( isset( $debug ) ) {
	
	$debug->log_supplier_handler_result( 'DocpartProducts', print_r( $json, true ) );
	
}

echo $json;
?>