<?php
include( 'lib.inc.php' );

class AutoCode_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) {
		
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
		
		foreach ( $manufacturers as $m ) {
			
			try {
				
				$params_action = array();
				$params_action[] = $m['manufacturer'];
				$params_action[] = $article;
				
				if ( isset( $debug ) ) {
					
					$debug_message = "Запрос позиции {$article} {$m['manufacturer']}";
					$debug->log_before_api_request( $debug_message, print_r( $params_action, true ) );
					
				}
				
				$res_action = $api->getSupplies( $params_action );
				
				if ( isset( $debug ) ) {
					
					$debug_message = "Ответ поставщика";
					$debug->log_after_api_request( $debug_message, '', print_r( $res_action, true ) );
					
				}
				
				foreach ( $res_action as $item ) {
					
					$article_supp 				= $item['Art'];
					$manufacturer 				= $item['UrlBrand'];
					$name 						= $item['Name'];
					$price_purchase			= $item['Price'];
					$exist 					= $item['Qty'];
					
					if ( $exist == 'null' ) {
						
						$exist = 1;
						
					}
					
					$time_to_exe				= $item['Days'] + $storage_options['additional_time'];
					$time_to_exe_guaranteed	= $item['Days'] + $storage_options['additional_time'];
					$storage 					= 0;
					$min_order					= $item['MinQty'];
					// $probability				= $storage_options['probability'];
					$probability				= 100 - $item['PerRej'];
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
						$article_supp,
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
						$json_params,
						$rest_params 
					);
					
					if ( isset( $debug ) ) {
					
						/* $debug_message = "Единичное предложение";
						$debug->log_after_api_request( $debug_message, '', print_r( $DocpartProduct, true ) ); */
					
					}	
					
					if($DocpartProduct->valid) {
						
						array_push($this->Products, $DocpartProduct);
						
					}
					
				}
				
			}
			catch ( Exception $e ) {
				
				if ( isset( $debug ) ) {
					
					$debug_message = "Исключение";
					$debug->log_exception( $debug_message, print_r( $e, true ), $e->getMessage() );
					
				}
				
			}
			
		}
		
		$this->result = 1;
		
		if ( isset( $debug ) ) {
			
			$debug_message = 'Результирующий объект:';
			$debug->log_supplier_handler_result( $debug_message, print_r( $this, true ) );
			
		}
		
	}
	
}

$article = $_POST['article'];
$manufacturers = json_decode($_POST["manufacturers"], true);
$storage_options = json_decode( $_POST["storage_options"], true );

if ( file_exists( $class_dir . 'DocpartSuppliersAPI_Debug.php' ) ) {

	$debug = DocpartSuppliersAPI_Debug::getInstance();

	$init_options = array();
	$init_options['api_type'] = 'CURL_HTTP';
	$init_options['storage_id'] = $storage_options['storage_id'];
	$init_options['api_script_name'] = __FILE__;

	$debug->init_object( $init_options );
	
}

$ob =  new AutoCode_enclosure( $article, $manufacturers, $storage_options );

exit( json_encode($ob) );
?>