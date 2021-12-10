<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/trinity_parts/TrinityPartsWS.php");

class trinity_parts_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("API-запросы логируются непосредственно в методе TrinityPartsWS::query()");
		}
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$ClientCode = $storage_options["сlient_сode"];
		/*****Учетные данные*****/
        
		$ws = new \TrinityPartsWS($ClientCode); 
		
		if( !empty($ws->error) )
		{
			return;
		}
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Получаем список брендов по артикулу ".$article);
		}
		
		//Получаем список брендов по артикулу
		$brands = $ws->searchBrands($article);
		
		if($brands["count"] == 0 )
		{
			return;
		}
		
		
		for($b=0; $b < count($brands["data"]); $b++)
		{
			$DocpartManufacturer = new DocpartManufacturer($brands["data"][$b]["producer"],
					0,
					$brands["data"][$b]["caption"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					null
				);
				

			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}//~function __construct($article)
};//~class trinity_parts_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"file_get_contents() в библиотеке поставщика TrinityPartsWS.php") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new trinity_parts_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>