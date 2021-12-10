<?php
require_once( $_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartManufacturer.php" );
// require_once( $_SERVER["DOCUMENT_ROOT"] . "/Logger.php" );

require_once( "MlAutoSupplierApi.php" );


class ml_auto_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
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
			
		}
		
		$api = new MlAutoSupplierApi( $api_options );
		
		$params_action = array(
			"ARTICLE" => $article
		);
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Получение брнендов {$article}";
			$debug->log_simple_message( $debug_message );
			$debug->log_before_api_request( 'Параметры запроса', print_r( $params_action, true ) );
			
		}
		
		$api->getParamsAction( $params_action );
		
		try {
			
			$brands_result = $api->getBrands();
			
			if ( isset( $debug ) ) {
			
				$debug_message = "Ответ поставщика";
				$debug->log_after_api_request( $debug_message, null, print_r( $brands_result, true ) );
			
			}
			
			if ( $brands_result['STATUS'] == 200 ) {
				
				$response = $brands_result['RESPONSE'];
				
				foreach ( $response as $brand_data ) {
					
					$brand_name = $brand_data['BRAND'];
					$brand_id = 0;
					$item_name = $brand_data['NAME'];
					
					if ( $item_name == '' ) {
						
						$item_name = 'Не указано поставщиком';
						
					}
					
					$synonyms_single_query = true;
					$params = array();
					
					$DocpartManufacturer = new DocpartManufacturer(
						$brand_name,
						$brand_id,
						$item_name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$synonyms_single_query,
						$params
					);
					
					$this->ProductsManufacturers[] = $DocpartManufacturer;
					
				}
				
				$this->status = 1;
				
			}
			
			
		} 
		catch ( Exception $e ) {
			
			if ( isset( $debug ) ) {
		
				$debug->log_error( $e->getMessage(), print_r( $e, true ) );
		
			}
			
		}
		
	}
	
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


$ob = new ml_auto_enclosure( $_POST["article"], $storage_options );
$json = json_encode( $ob );

if ( isset( $debug ) ) {
	
	$debug->log_supplier_handler_result( 'DocpartManufacturers', print_r( $json, true ) );
	
}

echo $json;
?>