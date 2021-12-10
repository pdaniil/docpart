<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once( 'Volvo77Api.php' );

$base_dir = "{$_SERVER["DOCUMENT_ROOT"]}/content/shop/docpart";
$debug_class_file = "{$base_dir}/DocpartSuppliersAPI_Debug.php";

if ( file_exists( $debug_class_file ) ) { require_once( $debug_class_file ); } 

class volvo77_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct( $article, $manufacturers, $storage_options ) {
		
		if ( class_exists( 'DocpartSuppliersAPI_Debug', false ) ) {
			
			$debug = DocpartSuppliersAPI_Debug::getInstance();
			
		}
		
        $api_options = array( 'api_id' => $storage_options['api_id'] );
        
		if ( isset( $debug ) ) {
			
			$debug_message = "Параметры авторизации";
			$debug->log_before_api_request( $debug_message, print_r( $api_options ,true ) );
			
		}
		
        $api = new Volvo77Api( $api_options );
        
		if ( isset( $debug ) ) {
			
			$debug_message = "Список брендов, участвующие в запросе";
			$debug->log_before_api_request( $debug_message, print_r( $manufacturers ,true ) );
			
		}
		
        foreach ( $manufacturers as $m ) {

            $params_action = array ( 
                'ACTION' => 'getPrice',
                'SUPPLIER_ID' => $m['manufacturer_id'],
                'ARTICUL' => $article
            );
			
			if ( isset( $debug ) ) {
			
				$debug_message = "Получение остатков {$article} {$m['manufacturer_id']}";
				$debug->log_simple_message( $debug_message );
				$debug->log_before_api_request( 'Параметры запроса', $params_action );
			}
			
            $res_action = $api->getSupplies( $params_action );
			
			if ( isset( $debug ) ) {
			
				$debug_message = "Ответ поставщика";
				$debug->log_before_api_request( $debug_message, null, print_r( $res_action, true ) );
				
			}
			
            foreach ( $res_action['result'] as $item ) {
                
                $article 				= $item['articul'];
                $manufacturer 			= $item['supplier'];
                $name 				    = $item['title'];
                $price_purchase			= $item['price'];
                $exist 					= (int) $item['qty'];
                
                $time_to_exe			= $storage_options['additional_time'];
                $time_to_exe_guaranteed = $storage_options['additional_time'];
                $storage 				= 0;
                $min_order				= 1;
                $probability			= $storage_options['probability'];
                $product_type			= 2;
                $product_id				= 0;
                $storage_record_id		= 0;
                $url				    = '';
                $json_params			= '';
                $rest_params			= array( "rate"=>$storage_options["rate"] );
                
                $delivery_days               =  $item['days'];

                $time_to_exe += $delivery_days;
                $time_to_exe_guaranteed += $delivery_days;

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
                
                if( $dp->valid ) {
                    
                    $this->Products[] =  $dp;
                    
                }
            }
        }
		
		$this->result = 1;
		
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

    $ob =  new volvo77_enclosure (
        $_POST["article"], 
        json_decode($_POST["manufacturers"], true), 
        json_decode($_POST["storage_options"], true)
    );
    
    $enc = $ob->getEncode();
	
	if ( isset( $debug ) ) {
		
		$debug->log_supplier_handler_result( 'DocpartProducts', print_r( $enc, true ) );
		
	}
	
	echo $enc;
}
catch ( Exception $e ) {
	
	if ( isset( $debug ) ) {
		
		$debug->log_exception( "Exception", print_r( $e, true ), $e->getMessage() );
		
	}
	
}
?>