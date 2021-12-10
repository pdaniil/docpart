<?php
// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );
require_once( "kd.php" );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class kolesadarom_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct( $article, $storage_options ) {
		
		$api_key = trim( $storage_options['api_key'] );
		
		$params_action = array (
			"kod_proizvoditelya" => array( $article )
		);
		
		try {
			
			$api = new kd();
		
		
		
			// Logger::addLog( 'res_action', $res_action );
			
			$res_action_json = $api->search( $api_key, $params_action );
			$res_action = json_decode( $res_action_json, true );
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
				$debug = DocpartSuppliersAPI_Debug::getInstance();
				$debug->log_before_api_request( 'Параметры авторизации', print_r( $api_key, true ) );
				$debug->log_before_api_request( 'Параметры запроса позиций', print_r( $params_action, true ) );
				$debug->log_after_api_request( 'Ответ поставщика', print_r( $res_action_json, true ), print_r( $res_action, true ) );
			
			}
			
			if ( json_last_error() ) {
				
				throw new Exception( 'Ошибка json: '. json_last_error() );
				
			}
			
			foreach ( $res_action as $item ) {
				
				$article 					= $item['kod_proizvoditelya'];
				$manufacturer 				= $item['maker'];
				$name 						= $item['name'];
				$price_purchase			= $item['price_rozn'];
				$exist 					= $item['quantity'];
				
				$time_to_exe				= $storage_options['additional_time'];
				$time_to_exe_guaranteed	= $storage_options['additional_time'];
				$storage 					= 0;
				$min_order					= $item['min_quantity'];
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
				
				$dp = new DocpartProduct( 
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
					$json_params = '',
					$rest_params 
				);
				
				if ( $dp->valid ) {
					
					$this->Products[] = $dp;
					
				}
				
				// Logger::addLog( '$dp', $dp );
				
			} // ~! foreach ( $res_action as $item )
			
			$this->result = 1;
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
				$debug->log_supplier_handler_result( "DocpartProducts", print_r( $this->Products, true ) );
		
			}
		} 
		catch ( Exception $e ) {
			
			$this->result = 0;
			$this->Products = array();
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
				$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
			}
			
		}
		
	}
	
}


$storage_options = json_decode( $_POST["storage_options"], true  );

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	//Настройки подключения к складу
	//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
	$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
	//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
	$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL_HTTP") );
	//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
	$DocpartSuppliersAPI_Debug->start_log();
}

$ob =  new kolesadarom_enclosure (
	$_POST["article"],  
	$storage_options
);

$json = json_encode($ob);


exit($json);
?>