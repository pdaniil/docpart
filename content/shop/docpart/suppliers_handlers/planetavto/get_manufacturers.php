<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );
// require_once( $_SERVER["DOCUMENT_ROOT"]."/Logger.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


require_once( "PlanetavtoApi.php" );

class planetavto_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct( $article, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$api_options = array(
			"login" => $login,
			"password" => $password
		);
		
		$api = new PlanetavtoApi( $api_options );
		
		$action = "/getSuppliers/";
		$params_action = array();
		
		$api->getAction( $action );
		$api->getParamsAction( $params_action );
		$suppliers_data = $api->getSuppliers(); //Получаем список доступных поставщиков
		
		$suppliers_ids = array_keys( $suppliers_data['children'] );
		
		$action = "/getProducts/articleList/";
		$params_action = array(
			"articles" => array( $article ),
			"suppliers" => $suppliers_ids
		);
		
		$api->getAction( $action );
		$api->getParamsAction( $params_action );
		
		$brands_data = $api->getBrands(); //Получаем список позиций
		
		
		//** Достаём массив с описанием позиций
		$supp_data = $brands_data['children'][0]['children'];
		
		// Logger::addLog( '$supp_items', $supp_items );
		
		foreach ( $supp_data as $children ) {
			
			$supp_items = $children['children'];
			
			foreach ( $supp_items as $item ) {
				
				// Logger::addLog( '$item', $item );
				
				$info = $item['info'];
				
				$manufacturer = trim( $info['brandTitle'] );
				$manufacturer_id = 0;
				$name = trim( $info['name'] );
				$synonyms_single_query = true;
				$params = array();
				
				$DM = new DocpartManufacturer(
					$manufacturer,
					$manufacturer_id,
					$name,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$synonyms_single_query,
					$params
				);
				
				$this->ProductsManufacturers[] = $DM;
				
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON в библиотеке PlanetavtoApi.php") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();




try 
{	
	$ob = new planetavto_enclosure( 
		$_POST["article"], 
		$storage_options 
	);
	
	$json = json_encode($ob);
	
	if ( ! $json ) 
	{	
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());	
	}
	
} 
catch ( Exception $e ) 
{
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit($json);
?>