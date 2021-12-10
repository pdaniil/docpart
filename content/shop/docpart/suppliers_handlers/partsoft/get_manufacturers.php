<?php
$docpart_dir = $_SERVER['DOCUMENT_ROOT'] . '/content/shop/docpart';

// require_once( $_SERVER['DOCUMENT_ROOT'] . '/Logger.php' );
require_once( $docpart_dir . '/DocpartManufacturer.php' );

require_once( 'PartSoftApi.php' );


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class part_soft_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) {
		
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$api_key = $storage_options['api_key'];
		$site = $storage_options['site'];
		
		$api_options = array (
			'base' => $site,
			'api_key' => $api_key
		);
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием объекта PartSoftApi с аргументом ".print_r($api_options, true) );
		}
		
		$api = new PartSoftApi( $api_options );
		
		$action = "api/v1/search/get_brands_by_oem"; //Получени списка брендов
		$params_action = array( 'oem' => $article );
		
		//Устанавливаем параметры запроса
		$api->setAction( $action );
		$api->setParamsAction( $params_action );
		$api->exec();
		$response_json = $api->getResponse(); //Получаем ответ поставщика в json
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "Метод setAction(".$action."). Параметры ".print_r($params_action,true), $response_json, print_r(json_decode($response_json, true), true) );
		}
		
		$response_arr = json_decode( $response_json, true );
		
		if ( $response_arr['result'] == 'ok'
			&& is_array( $response_arr['data'] )
		) {
			
			foreach ( $response_arr['data'] as $item ) {
				
				$manufacturer = trim( $item['brand'] );
				$manufacturer_id = 0;
				$name = trim( $item['des_text'] );
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
			
		}
		else if (  $response_arr['result'] == 'error' ) {
			
			$error_message = "Поставщик вернул ошибку: {$response_arr['error']}";
			throw new Exception( $error_message );
			
		}
		
		$this->status = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	}
	
	public function __toString() {
		
		return json_encode( $this );
		
	}
	
}

$article = $_POST['article'];

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON (библиотека поставщика)") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



try {
	
	$enclosure = new part_soft_enclosure( $article, $storage_options );
	echo $enclosure;
		
}
catch ( Exception $e ) {
	
	// Logger::addLog( 'Exception', $e->getMessage() );
	
	//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", $e->getMessage() );
	}
	
}

// Logger::writeLog( __DIR__, 'dump_m.log' );

?>