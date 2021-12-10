<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

// require_once( $_SERVER['DOCUMENT_ROOT'] . "/Logger.php" );

require_once( 'FrozaApi.php' );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart/";
$debug_class_file = "{$base_dir}DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class froza_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) {
		
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
			"find_subs" => 1,
			"make_logo" => '',
			"sort" => '',
			"currency" => ''
		);
		
		
		$api->getAction( $action );
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$message = 'Цикл по производителям';
			$debug->log_simple_message( $message );

		}
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
				
			$debug->log_before_api_request( "Список производителей", print_r( $manufacturers, true ) );
				
		}
		
		foreach ( $manufacturers as $m ) {
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
				
				$debug->log_before_api_request( "Параметры метода {$action}", print_r( $params_action, true ) );
				
			}
			
			$params_action['make_logo'] = $m['manufacturer_id'];
			
			$api->getParamsAction( $params_action );
			
			$res = $api->execAction();
			
			if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
				
				$debug->log_after_api_request( "Параметры метода {$action}", null ,print_r( $params_action, true ) );
				
			}
			
			if ( ! is_object( $res ) ) {
				
				continue;
				
			}
			
			$FindByDetail = $res->getFindByDetailResult->FindByDetail;
			
			if ( is_array( $FindByDetail ) ) {
				
				foreach ( $FindByDetail as $item_data ) {
					
					$dp = $this->getDPProduct( $item_data, $storage_options );
					
					if ( $dp != false ) {
						
						$this->Products[] = $dp;
						
					}
					
				}
				
			} 
			else if ( is_object( $FindByDetail ) ) {
				
				$dp = $this->getDPProduct( $FindByDetail, $storage_options );
					
				if ( $dp != false ) {
					
					$this->Products[] = $dp;
					
				}
				
			}
			
		} // ~! foreach ( $manufacturers as $m )
		
		$this->result = 1;
		
	} // ~! __construct()
	
	private function getDPProduct ( $supp_data, $storage_options ) {
		
		// Logger::addLog( '$supp_data', $supp_data );
		
		$manufacturer 				= $supp_data->make_name;
        $article 					= $supp_data->detail_num_space;
        $name 						= $supp_data->description_rus;
        $exist 					= $supp_data->quantity;
        $time_to_exe 				= $supp_data->delivery_time;
        $time_to_exe_guaranteed	= $supp_data->delivery_time_guar;
        $storage 					= $supp_data->supplier;
        $min_order 				= $supp_data->quantity_lot;
        $probability				= (int) $supp_data->stats_success;
        $office_id 				= $storage_options['office_id'];
        $storage_id 				= $storage_options['storage_id'];
        $office_caption 			= $storage_options['office_caption'];
        $color 					= $storage_options['color'];
        $storage_caption 			= $storage_options['storage_caption'];
        $price_purchase		 	= (float) $supp_data->price;
        $markup					= 0;
        $product_type 				= 2;
        $product_id 				= 0;
        $storage_record_id 		= 0;
        $url 						= '';
		$json_params 				=  json_encode( array ( 
			'make_logo' => $supp_data->make_logo,
			'detail_num' => $supp_data->detail_num,
			'subs_detail_num' => '',
			'descr' => '',
			'supplier' => $supp_data->supplier_code,
			'quant' => '',
			'delivery_type' => '',
			'reference' => '',
			'price_compare' => '',
			'delivery_days_ad' => ''
		), JSON_UNESCAPED_UNICODE );
		
						
		$rest_params 				= array( "rate" => $storage_options["rate"] );
		
		$markup = $storage_options["markups"][(int)$price_purchase];
		//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		if ( $markup == NULL ) {
			
			$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			
		}
		
		$price_for_customer = $price_purchase + $price_purchase * $markup;
		
		$time_to_exe += $storage_options['additional_time'];
		$time_to_exe_guaranteed += $storage_options['additional_time'];
		
		$dp = new DocpartProduct (
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
			$office_id,
			$storage_id,
			$office_caption,
			$color,
			$storage_caption,
			$price_purchase,
			$markup,
			$product_type,
			$product_id,
			$storage_record_id,
			$url,
			$json_params,
			$rest_params
		);
		
		if ( $dp->valid == true ) {
			
			return $dp;
			
		} else {
			
			return false;
			
		}
		
	} // ~! getDPProduct ( $supp_data, $storage_options )
	
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
	
	$ob = new froza_enclosure (
		$_POST["article"], 
		json_decode( $_POST["manufacturers"], true ),
		$storage_options
	);
	
	$json = json_encode($ob, JSON_UNESCAPED_UNICODE);
	
	if ( ! $json ) {
		
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
		
	}
	
} 
catch ( Exception $e ) {
	
	if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
		
		$debug->log_exception( $e->getMessage(), print_r( $e, true ) );
		
	}
	
}

// Logger::writeLog( __DIR__, 'dump_supp.log' );

exit($json);
?>