<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
// require_once( $_SERVER["DOCUMENT_ROOT"]."/Logger.php" );
require_once( 'LekopartsApi.php' );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class supplier_enclosure
{
	public $result = 0; 
	public $Products = array();
	private $storage_options;
	
	public function __construct($article, $manufacturers, $storage_options) {
		
		$this->storage_options = $storage_options;
		
		$api_options = array (
            'user' => $storage_options['user'],
            'pass' => $storage_options['pass']
        );

        $api = new LekopartsApi( $api_options );
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			$debug->log_before_api_request( 'API OPTIONS', print_r( $api_options, true ) );
			$debug->log_before_api_request( "Список брнедов",  print_r( $manufacturers, true ) );
			
		}
		
        foreach ( $manufacturers as $m ) {
			
            $params_action = array (
                'number' => $article,
                'make' => $m['manufacturer']
            );
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
				
				$debug->log_simple_message( "Запрос {$article} {$m['manufacturer']}" );
							
			}
			
			try {
				
				$res_action = $api->getSupplies( $params_action );
				
				if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
					
					$debug->log_after_api_request( "Список позиций", null, print_r( $res_action ,true ) );
								
				}
				
				$found 		= $res_action['found'];
				$possible 		= $res_action['possible'];
				$aftermarket	= $res_action['aftermarket'];
				
				foreach ( $found as $supp_item ) { $this->getDocpartProduct( $supp_item ); }
				foreach ( $possible as $supp_item ) { $this->getDocpartProduct( $supp_item ); }
				foreach ( $aftermarket as $supp_item ){ $this->getDocpartProduct( $supp_item ); }
				
				
			}
			catch ( Excetion $e ) {
				
				if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
					$debug->log_exception( "Exception", print_r( $e, true ), $e->getMessage() );
		
				}
				
			}
			
        }
		
		$this->result = 1;
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			$debug->log_supplier_handler_result( 'Ответ от склада', print_r( $this, true ) );
		}
		
    }
	
	private function getDocpartProduct( $item ) {
		
		$storage_options = $this->storage_options;
	
		$int_pattern = "/[\d]+/";
		
		$article 					= $item['number'];
		$manufacturer 				= $item['make'];
		$name 						= $item['name'];
		$price_purchase			= (float) $item['price'];
		
		$exist 					= $item['quantity'];
		preg_match( $int_pattern, $exist, $matches );
		if ( ! empty( $matches ) ) { $exist = $matches[0]; }
		
		
		$time_to_exe				= $storage_options['additional_time'];
		$time_to_exe_guaranteed	= $storage_options['additional_time'];
		
		$dev_supp = 				$item['status'];
		
		if ( $dev_supp != 'наличие' ) {
			
			preg_match( $int_pattern, $dev_supp, $matches );
			if ( ! empty( $matches ) ) { 
			
				$time_to_exe += $matches[0];
				$time_to_exe_guaranteed += $matches[0];
			}
			
		}
		
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
		
		$pd = new DocpartProduct( 
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
		
		if ( $pd->valid ) {
			
			$this->Products[] = $pd;
			
		}
		
	}
	
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);

if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
	
	//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
	$debug = DocpartSuppliersAPI_Debug::getInstance();
	//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
	$debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
	//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
	$debug->start_log();
}



try {
	
	$ob =  new supplier_enclosure(
		$_POST["article"], 
		json_decode($_POST["manufacturers"], true), 
		$storage_options
	);
	$json = json_encode($ob);
	
	if ( ! $json ) {
		
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
		
	}
	
	if ( isset( $debug ) ) {
		
		$debug->log_supplier_handler_result( 'DocpartProducts', print_r( $ob, true ) );
		
	}
	
} 
catch ( Exception $e ) {
	
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
	}
	
}


// Logger::writeLog( __DIR__, 'dump_s.log' );

exit( json_encode($ob) );
?>