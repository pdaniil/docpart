<?php


// require_once( $_SERVER["DOCUMENT_ROOT"]."/Logger.php" );

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
require_once( "OptipartApi.php" );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class optipar_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
		$connection_options = array(
			'apikey' => trim( $storage_options['apikey'] ),
			'tecdoc' => $storage_options['tecdoc']
		);
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			$debug->log_before_api_request( 'API OPTIONS', print_r( $connection_options, true ) );
			$debug->log_simple_message( "Запрос брендов {$article}" );
			
		}
		
		$handler = new OptipartApi( $connection_options );
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_simple_message( "Запрос брендов {$article}" );
			
		}
		
		$dom = $handler->getBrands( $article );
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_after_api_request( "Список брендов {$article}", null, print_r( $handler->getAnswer(), true ) );
			
		}
		
		
		$el_collection = $dom->getElementsByTagName( 'e' );
		
		foreach ( $el_collection as $el ) {
			
			$attributes = $el->attributes;
			
			$brand_data = array();
			
			foreach ( $attributes as $attr ) {
				
				 if ( $attr->name == 'bnd' ) {
					
					$brand_data['name'] = $attr->value;
					
				}
				else if ( $attr->name == 'nam' ) {
					
					$brand_data['description'] = $attr->value;
					
				} 
				
			}
			
			 $dm = new DocpartManufacturer(
				$brand_data['name'],
				0,
				$brand_data['description'],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true,
				array()
			);
			
			// Logger::addLog( '$dm', $dm );
			
			$this->ProductsManufacturers[] =  $dm;
		 
		}
		
		$this->status = 1;
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_supplier_handler_result( "Результат", print_r( $this, true ) );
			
		}
		
	}
	
}

$storage_options = json_decode($_POST["storage_options"], true);
$api_type = 'CURL_HTTP';

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	
	$debug = DocpartSuppliersAPI_Debug::getInstance();
	
	$init_options = array();
	$init_options['api_type'] = $api_type;
	$init_options['storage_id'] = $storage_options['storage_id'];
	$init_options['api_script_name'] = __FILE__;
	
	$debug->init_object( $init_options );
	$debug->start_log();
	
}

try {
	
	$ob = new optipar_enclosure($_POST["article"], $storage_options);
	$json = json_encode($ob);
	
	if ( ! $json ) { throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error()); }
	
} 
catch ( Exception $e ) { 

	// Logger::addLog( 'Exception', $e->getMessage() );
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
	}
	
 }

// Logger::writeLog( __DIR__, 'dump_m.log' );

exit($json);
?>