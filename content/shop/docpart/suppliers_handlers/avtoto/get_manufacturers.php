<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");



$pathClasses = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/avtoto/classes/";

set_include_path(get_include_path(). PATH_SEPARATOR .$pathClasses);

spl_autoload_register(function($class){
	require_once($class.".php");
});



class avtoto_enclosure
{
	public $status = 0; //Статус ответа @bool
	public $ProductsManufacturers = array();//Список производителей
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Логи запросов к API расставлены непосредственно в библиотеке поставщика avtoto/classes/avtoto_parts.php");
		
		
		//*********************************************************************
			//Инициализируем переменные
		$wsdl = "http://www.avtoto.ru/services/search/soap.wsdl";
		
		$params = array();//Параметры запроса
		
		$params["user_id"] 		= $storage_options["customer_id"];
		$params["user_login"]		= $storage_options["login"];
		$params["user_password"]	= $storage_options["password"];
		
		$office_id = $storage_options["office_id"];
		$storage_id = $storage_options["storage_id"];
		
		$result = array(); //Результат запроса
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Параметры для создания объекта avtoto_parts: ".print_r($params, true) );
		
		
		$avtoto = new avtoto_parts($params);
		// Максимальное время выполнения 5 сек
		$avtoto->set_search_extension_time_limit(5); 
		
		// ********************************************************************
		
		$data = $avtoto->get_brands_by_code($article);
		$errors = $avtoto->get_errors();
		
		
		
		
		if( ! empty($data["Info"]["Errors"]))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($data["Info"]["Errors"], true) );
			}
			return;
		}
		
		
		if($data)
		{
			foreach($data["Brands"] as $brand)
			{
				$Manufacturer = new DocpartManufacturer($brand["Manuf"], 0, $brand["Name"], $office_id, $storage_id, true, '');
				array_push($this->ProductsManufacturers, $Manufacturer);
			}
			
			$this->status = 1;
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	} // ~__construct($article, $storage_options)
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP с библиотекой avtoto_parts.php") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new avtoto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>