<?php
include( 'lib.inc.php' );

class AutoCode_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
		if ( class_exists(  'DocpartSuppliersAPI_Debug', false ) ) {

			$debug = DocpartSuppliersAPI_Debug::getInstance();

		}
		
		$api_options = array();
		$api_options['login'] = $storage_options['login'];
		$api_options['password'] = $storage_options['password'];
		
		if ( isset( $debug ) ) {
			
			
			$debug_message = 'Параметры авторизации';
			$debug->log_before_api_request( $debug_message, print_r( $api_options, true ) );
			
		}
		
		$api = new AutoCodeApi( $api_options );

		$params_action = array();
		$params_action[] = $article;
		$res_action = $api->getBrands( $params_action );
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Запрос брендов {$article}";
			$debug->log_after_api_request( $debug_message, '', print_r( $res_action, true ) );
			
		}

		foreach ( $res_action as $item ) {
			
			$manufacturer = $item['UrlBrand'];
			$manufacturer_id = $item['Id'];
			$name = $item['Name'];
			$synonyms_single_query = true;
			$params = '';
			
			$dm = new DocpartManufacturer(
				$manufacturer,
				$manufacturer_id,
				$name,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$synonyms_single_query,
				$params
			);
			
			$this->ProductsManufacturers[] = $dm;
			
		}
		
		$this->status = 1;
		
		if ( isset( $debug ) ) {
			
			$debug_message = 'Результирующий объект:';
			$debug->log_supplier_handler_result( $debug_message, print_r( $this, true ) );
			
		}
	}
}

/* 
$storage_options = array();
$storage_options['login'] = '896';
$storage_options['password'] = '67236954059'; 
*/

$storage_options = json_decode( $_POST["storage_options"], true );
$article = $_POST['article'];

if ( file_exists( $class_dir . 'DocpartSuppliersAPI_Debug.php' ) ) {

	$debug = DocpartSuppliersAPI_Debug::getInstance();

	$init_options = array();
	$init_options['api_type'] = 'CURL_HTTP';
	$init_options['storage_id'] = $storage_options['storage_id'];
	$init_options['api_script_name'] = __FILE__;

	$debug->init_object( $init_options );
	$debug->start_log();
	
}

try {
	
	$ob = new AutoCode_enclosure( $article, $storage_options );
	$json = json_encode( $ob );
	
	if ( ! $json ) {
		
		throw new Exception( "Ошибка преобразования DocpartManufacturer в json: " . json_last_error() );
		
	}
	
	exit( $json );
} 
catch ( Exception $e ) {
	
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug = DocpartSuppliersAPI_Debug::getInstance();
		$debug_message = 'Исключение';
		$debug->log_exception( $debug_message, print_r( $e,true ), $e->getMessage() );
		
	}
}
?>
