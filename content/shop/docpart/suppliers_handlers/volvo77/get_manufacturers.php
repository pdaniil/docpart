<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
require_once( 'Volvo77Api.php' );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart";
$debug_class_file = "{$base_dir}/DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class volvo77_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			
		}
		
		
        $api_options = array(
            'api_id' => $storage_options['api_id']
        );
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Параметры авторизации";
			$debug->log_before_api_request( $debug_message, print_r( $api_options ,true ) );
			
		}
		
        $api = new Volvo77Api( $api_options );

        $params_action = array ( 
            'ACTION' => 'getSuppliers',
            'ARTICUL' => $article
        );
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Получение брендов {$article}";
			$debug->log_simple_message( $debug_message );
			$debug->log_before_api_request( 'Параметры запроса', print_r( $params_action ,true ) );
			
		}
		
        $res_action = $api->getBrands( $params_action );
		
		if ( isset( $debug ) ) {
			
			$debug_message = "Ответ поставщика";
			$debug->log_after_api_request( $debug_message, null, print_r( $res_action ,true ) );
			
		}
		
		foreach ( $res_action['result'] as $item ) {
            
            $manufacturer = $item['supplier'];
            $manufacturer_id = $item['supplier_id'];
            $name = 'Не указано поставщиком';
            $synonyms_single_query = true;
            $params = array();

			$dm = new DocpartManufacturer (
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
    
    public function getEncode() {

        $enc = json_encode( $this );

        if ( json_last_error() ) { 

            $error_message = "Ошибка преобразования объекта в json. Код: " . json_last_error();
            throw new Exception( $error_message );
        }

        return $enc;
    }
}
//-----------------------------------------------------------------------------------------------

$storage_options = json_decode($_POST["storage_options"], true);

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	//Настройки подключения к складу
	//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
	$debug = DocpartSuppliersAPI_Debug::getInstance();
	//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
	$debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL_HTTP") );
	//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
	$debug->start_log();
}

try {
	
	$ob = new volvo77_enclosure(
        $_POST["article"], 
        json_decode( $_POST["storage_options"], true) 
    );
	$enc = $ob->getEncode();
	
	if ( isset( $debug ) ) {
		
		$debug->log_supplier_handler_result( 'DocpartManufacturers', print_r( $enc, true ) );
		
	}
	
    echo $enc;

} 
catch (Exception $e) {
	
	if ( isset( $debug ) ) {
		
		$debug->log_exception( "Exception", print_r( $e, true ), $e->getMessage() );
		
	}
    
}
?>