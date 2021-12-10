<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class gpi24_enclosure
{
	public $status = 0;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		/*****Учетные данные*****/
		$key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
		//Запрос товаров по артикулу и производителю
        // инициализация сеанса
        $ch = curl_init();
		
		$service = "https://api.gpi24.ru/v1/webservice/search/";
		$action = "groups";
		$params_action = array(
			"search" => $article
		);
		
		$url_request = $service . $action . "?" . http_build_query( $params_action );
	
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$headers = array(     
			"accept: application/json",  
			"Authorization: Bearer $key",
			"X-CSRF-TOKEN: "
		); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url_request, $exec, print_r(json_decode($exec, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		
        curl_close($ch);
        
		$decode = json_decode($exec, true);
		
		if ( json_last_error() ) 
		{	
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка парсинга JSON", print_r(json_last_error(), true) );
			}
		}
		
		$status = $decode['code'];
		
		if ($status == '200') 
		{
			
			$brands = $decode['data'];
			
			foreach ( $brands as $brand ) {
				
				$manufacturer 				= $brand['brand'];
				$manufacturer_id			= 0;
				$name 						= $brand['article'];
				$office_id 				= $storage_options['office_id'];
				$storage_id 				= $storage_options['storage_id'];
				$synonyms_single_query	= true;
				$params 					= array();
				
				if ( $name == '' ) {
					
					$name = "Не указано поставщиком";
					
				}
				
				$dm = new DocpartManufacturer(
					$manufacturer,
					$manufacturer_id,
					$name,
					$office_id,
					$storage_id,
					$synonyms_single_query,
					$params
				);	
				
				//Раскоментировать условие для актуальной версии
				if ( $dm->valid == true ) 
				{
					$this->ProductsManufacturers[] = $dm;
				}
				
			} // ~!foreach ( $brands as $brand )
			
		}

		$this->status = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	}//~function __construct($article)
	
};//~class gpi24_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new gpi24_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>