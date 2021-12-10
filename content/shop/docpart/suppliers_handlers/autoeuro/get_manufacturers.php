<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

// подключаем класс клиента
include('ae_client/cli_main.php');

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autoeuro_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = false;
		
		//РЕАЛИЗАЦИЯ ПРОТОКОЛА
		$config = array (
				'server' => 'http://online.autoeuro.ru/ae_server/srv_main.php',
				'client_name' => $storage_options["client_name"],
				'client_pwd' => $storage_options["client_pwd"],
			);
			
		// создаем экземпляр класса
		$aeClient = new AutoeuroClient($config);
		$data1 = $aeClient->getData( 'Search_By_Code', array($article,1) );//Выполняем процедуру получения товаров по артикулу
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "Метод Search_By_Code", print_r($data1, true), "Преобразование результата не требуется" );
		}
		
		
		//Формируем массив брэндов:
		foreach ($data1 as $value) 
		{
			$maker	= iconv( "windows-1251", "utf-8", $value["maker"] );
			$name	= iconv( "windows-1251", "utf-8", $value["name"] );
			
			$DocpartManufacturer = new DocpartManufacturer($maker,
			    0,
				$name,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = true;
	}//~function __construct($article)
};//~class autoeuro_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"Библиотека от поставщика ae_client") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autoeuro_enclosure($_POST["article"], $storage_options);
exit( json_encode( $ob ) );
?>