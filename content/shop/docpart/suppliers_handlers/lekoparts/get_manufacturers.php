<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
// require_once( $_SERVER["DOCUMENT_ROOT"]."/Logger.php" );
require_once('LekopartsApi.php' );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 


class supplier_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
        $api_options = array(
            'user' => $storage_options['user'],
            'pass' => $storage_options['pass']
        );

        $api = new LekopartsApi( $api_options );

        $params_action = array (
            'number' => $article
        );
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			$debug->log_before_api_request( 'Параметры авторизации', print_r( $api_options, true ) );
			$debug->log_simple_message( "Запрос брендов {$article}" );
			
		}
		
        $res_action = $api->getBrands( $params_action );
		
        if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_after_api_request( 'Ответ поставщика', null, print_r( $res_action, true ) );
			
		}
		
		foreach ( $res_action as $item ) {
			
			$manufacturer = $item['make'];
			$manufacturer_id = 0;
			$name  = 'Не указано поставщиком';
			$synonyms_single_query  = true;
			$params = array();
			
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
		
	}
	
}

$storage_options = json_decode($_POST["storage_options"], true);

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	//Настройки подключения к складу
	//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
	$debug = DocpartSuppliersAPI_Debug::getInstance();
	//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
	$debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
	//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
	$debug->start_log();
}

try {
	
	$ob = new supplier_enclosure($_POST["article"], $storage_options);
	$json = json_encode($ob);
	
	if ( ! $json ) {
		
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
		
	}
	
	if ( isset( $debug ) ) {
		
		$debug->log_supplier_handler_result( 'DocpartManufacturers', print_r( $this->ProductsManufacturers, true ) );
		
	}
	
} 
catch ( Exception $e ) {
	
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
	}
	
}

// Logger::writeLog( __DIR__, 'dump_m.log' );
exit($json);
?>