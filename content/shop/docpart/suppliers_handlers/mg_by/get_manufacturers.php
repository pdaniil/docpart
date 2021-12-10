<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mg_by_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров

	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
        //Создание объекта клиента
		try
		{
			$objClient = new SoapClient("http://api.mg.by/ClientApi/SearchService.svc?wsdl", array('soap_version' => SOAP_1_1));//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		//Запускаем SOAP-процедуру (Получение производителей)
		try
		{
			$params = array("login"=>$login, "password"=>$password, "article"=>$article);
			
			$soap_am_result=$objClient->SearchCatalogs($params);//Запускаем SOAP-процедуру и получаем результат ее выполнения
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-вызов метода SearchCatalogs() с параметрами ".print_r($params,true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода SearchCatalogs()", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		//Получаем массив с объектами производителей:
        $soap_am_result = $soap_am_result->SearchCatalogsResult->Catalogs->SearchCatalog;
		
		if(is_array($soap_am_result))
		{
			foreach($soap_am_result as $catalog)
			{
				$DocpartManufacturer = new DocpartManufacturer($catalog->CatalogName,
					0,
					$catalog->Description,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class mg_by_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new mg_by_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>