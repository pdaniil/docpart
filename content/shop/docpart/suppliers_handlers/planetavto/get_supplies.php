<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

require_once( "PlanetavtoApi.php" );

class planetavto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct ( $article, $manufacturers, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$api_options = array(
			"login" => $login,
			"password" => $password
		);
		
		$api = new PlanetavtoApi( $api_options ); //Инициализируем интерфейс поставщика
		
		$action = "/getSuppliers/";
		$params_action = array();
		
		$api->getAction( $action );
		$api->getParamsAction( $params_action );
		$suppliers_data = $api->getSuppliers(); //Получаем список доступных поставщиков
		
		$suppliers_ids = array_keys( $suppliers_data['children'] );
		
		$action = "/getProducts/";
		$params_action = array(
			"article" => $article,
			"getAnalogs" => 1,
			"suppliers" => $suppliers_ids
		);
		
		$api->getParamsAction( $params_action );
		
		foreach ( $manufacturers as $brand ) {
			
			$api->getParamsAction( 'brand', $brand['manufacturer'] );
			
			$api->getAction( $action );
			
			$suppliers_items = $api->getSupplierItems(); //Получаем позиции
			
			$searchGroups = $suppliers_items['children']; //Все позиции
			
			//Вытаскиваем описание позиций
			foreach ( $searchGroups as $group ) {
				
				$children_group = $group['children'];
				
				foreach ( $children_group as $item_info ) {
					
					$info = $item_info['info']; //Описание позиции
					
					$article 					= $info['article'];
					$manufacturer 				= $info['brandTitle'];
					$name 						= $info['name'];
					$price_purchase				= $info['price'];
					$exist 						= $info['quantity'];
					
					$time_to_exe				= $storage_options['additional_time'];
					$time_to_exe_guaranteed		= $storage_options['additional_time'];
					$storage 					= 0;
					$min_order					= $info['measureRatio'];
					$probability				= $storage_options['probability'];
					$product_type				= 2;
					$product_id					= 0;
					$storage_record_id			= 0;
					$url						= '';
					$json_params				= '';
					$rest_params				= array( "rate"=>$storage_options["rate"] );
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price_purchase];
					//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					
					if ( $markup == NULL ) {
						
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						
					}
					
					$price_for_customer = $price_purchase + $price_purchase * $markup;
					
					$DocpartProduct = new DocpartProduct(
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
					
					if ( $DocpartProduct->valid ) {
						
						array_push($this->Products, $DocpartProduct);
						
					}

				} // ~! foreach ( $children_group as $item_info )
				
			} // ~! foreach ( $searchGroups as $group )
			
		} // ~! foreach ( $manufacturers as $brand )
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	} // ~! __construct ( $article, $manufacturers, $storage_options )
	
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON в библиотеке PlanetavtoApi.php") );


try 
{	
	$ob =  new planetavto_enclosure(
		$_POST["article"], 
		json_decode( $_POST["manufacturers"], true ), 
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
exit( $json );
?>