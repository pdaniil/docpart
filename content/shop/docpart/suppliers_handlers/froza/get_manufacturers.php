<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );

require_once( 'FrozaApi.php' );


class froza_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options) {
		
		$login = $storage_options['login'];
		$password = $storage_options['password'];
		
		$wsdl = "https://www.froza.ru/webservice/search.php?WSDL";
		
		$api_options = array (
			"login" => $login,
			"password" => $password,
			"wsdl" => $wsdl
		);
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			$debug->log_before_api_request( 'API OPTIONS', print_r( $api_options, true ) );
			
		}
		
		$api = new FrozaApi( $api_options );
		$action = "getFindByDetail";
		
		$params_action = array (
			"detail_num" => $article,
			"find_subs" => 0,
			"make_logo" => '',
			"sort" => '',
			"currency" => ''
		);
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_before_api_request( "Параметры метода {$action}", print_r( $params_action, true ) );
			
		}
		
		$api->getAction( $action );
		$api->getParamsAction( $params_action );
		
		
		$res = $api->execAction();
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug->log_after_api_request( "Результат выполнения метода {$action}", null ,print_r( $res, true ) );
			
		}
		
		// Logger::addLog( '$res', $res );
		
		$FindByDetail = $res->getFindByDetailResult->FindByDetail;
		
		if ( is_array( $FindByDetail ) ) {
			
			foreach ( $FindByDetail as $item_data ) {
				
				$this->ProductsManufacturers[] = $this->getDPManufacturer( $item_data, $storage_options );
				
			}
			
		} 
		else if ( is_object( $FindByDetail ) ) {
			
			$this->ProductsManufacturers[] = $this->getDPManufacturer( $FindByDetail, $storage_options );
			
		}
		
		$this->status = 1;
		
	}
	
	private function getDPManufacturer ( $supp_data, $storage_options ) {
		
		// Logger::addLog( '$supp_data', $supp_data );
		
		$manufacturer 				= $supp_data->make_name;
        $manufacturer_id 			= $supp_data->make_logo;
        $name						= $supp_data->description_rus;
		$office_id					= $storage_options["office_id"];
        $storage_id				= $storage_options["storage_id"];
		$synonyms_single_query	= true;
		$params 					= array();
		
		$dm = new DocpartManufacturer (
			$manufacturer,
			$manufacturer_id,
			$name,
			$office_id,
			$storage_id,
			$synonyms_single_query,
			$params
		);
		
		return $dm;
		
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
	
	$ob = new froza_enclosure( $_POST["article"], $storage_options );
	$json = json_encode($ob);
	
	if ( ! $json ) {
		
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
		
	}
	
} 
catch ( Exception $e ) {
	
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
	}
	
}

// Logger::writeLog( __DIR__, 'dump_manuf.log' );

exit($json);
?>