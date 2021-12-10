<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autopiter_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список
	
	public $client = null;
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$this->connect($storage_options["user"], $storage_options["password"]);//Соединяемся с сервером SOAP
		$soap_result = $this->getManufacturers($article);//Выполняем процедуру получения производителей
		
		if($soap_result == false)
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", '$soap_result после попытки соединения и запроса производителей равен false. Запрос не удался' );
			}
			
			$this->status = 0;
			return;
		}
		
		//Формируем массив брэндов:
        foreach ($soap_result as &$value) 
		{
			$DocpartManufacturer = new DocpartManufacturer($value["brand"],
			    $value["id"],
				$value["text"],
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
		
        $this->status = 1;
	}//~function __construct($article)
	
	
	
	//Метод соединения с SOAP - сервером
	public function connect ($user, $password) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SoapClient. Для авторизации используем<br>Логин: '$user'<br>Пароль: '$password'");
		
		$this->client = new SoapClient('http://service.autopiter.ru/price.asmx?WSDL', 
								 array('soap_version' => SOAP_1_2, 
									   'encoding'=>'UTF-8')); 
		$result = $this->client->IsAuthorization(); 
		
		// Авторизуемся 
		if (!$result->IsAuthorizationResult) 
		{ 
			$result = $this->client->Authorization(array( 
						   'UserID' => $user, 
						   'Password' => $password, 
						   'Save' => true 
						   )); 
		} 
	}//~public function connect() 
	
	
	//Выполнение процедуры SOAP
	public function getManufacturers($detailNum) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед SOAP вызовом метода FindCatalog");
		
		try
		{
			// Загружаем каталоги с деталями 
			$catalogObj = $this->client->FindCatalog(array('ShortNumberDetail' => $detailNum));
		}
		catch(Exception $e)
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода FindCatalog", print_r($e, true) , $e->getMessage() );
			}
			
			return false;
		}
		
		 
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$detailNum, "SOAP-вызов метода FindCatalog с указанием параметра 'ShortNumberDetail' = '$detailNum'", "См. ответ API после обработки", print_r($catalogObj, true) );
		}
		
		
		if (!$catalogObj->FindCatalogResult) 
		{
			return false; 
		} 

		$itemCatalog = $catalogObj->FindCatalogResult->SearchedTheCatalog; 

		
		$result = array();
		if(is_array($itemCatalog))
		{
			for($i=0; $i < count($itemCatalog); $i++)
			{
				$item = $itemCatalog[$i];
				$result[] = array('id' =>$item -> id, 'brand' => $item -> Name, 'text' => $item -> NameDetail);
			}
		}
		else
		{
			$item = $itemCatalog;
			$result[] = array('id' =>$item -> id, 'brand' => $item -> Name, 'text' => $item -> NameDetail);
		}
		return $result;
	}
};//~class autopiter_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autopiter_enclosure($_POST["article"], $storage_options);
$ob->client = 0;
exit(json_encode($ob));
?>