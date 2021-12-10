<?php
/*
Скрипт для реализации первого шага протокола проценки

1. Путь к WSDL файлу: "http://avtobat.com.ua/price.php?wsdl".
2. Метод авторизации "getUniqId". В него передается логин и пароль, а возвращает код авторизации. Имейте в виду что каждый запуск метода getUniqId меняет ключ!!!
3. Метод получения прайса "getPrice". В него передается код авторизации, который вернулся в пункте 2, код товара, бренд товара.
Здесь обмен реализован используя SOAP. То есть, что бы пользоваться с другого сайта, нужно подключаться с помощью SOAP. Это можно сделать на любом языке программирования в Вашем проэкте, примеры, как это сделать есть в Интернете. Тестировать можно на online-тестере, например
http://soapclient.com/soapclient?template=%2Fclientform.html
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class avtobat_enclosure
{
	public $status;
    public $ProductsManufacturers = array();//Список
    
    public $client = null;
	
	public function __construct($article, $storage_options)
	{

        $login    = $storage_options["login"];
        $password = $storage_options["password"];


        $this->status = 0;//По умолчанию
        
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SoapClient. Для авторизации используем<br>Логин: '$login'<br>Пароль: '$password'");

		$this->client = new SoapClient('https://avtobat.com.ua/price.php?wsdl', 
								 array('soap_version' => SOAP_1_2, 
                                       'encoding'=>'UTF-8'));                                  
        
        try
		{
			// Загружаем каталоги с деталями 
			$client_id = $this->client->getUniqId($login, $password);

			// print_r($client_id);
		}
		catch(Exception $e)
		{
            
   			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода getUniqId. Неверные логин или пароль.", print_r($e, true) , $e->getMessage() );
			}
			
			return false;
		}
     

        try
		{
			// Загружаем каталоги с деталями 
			$xml_products = $this->client->getPrice($client_id, $article);
		}
		catch(Exception $e)
		{

			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода getPrice", print_r($e, true) , $e->getMessage() );
			}
			
			return false;
		}

        if (empty($xml_products)) {

            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Пустой ответ", 'Запрос поставщиков вернул пустой результат. Артикул - '.$article. '. Результат - '.print_r($xml_products, true) );
			}
			
			$this->status = 0;
			return false;

        }
		
		//Формируем массив брэндов:
        foreach ($xml_products as $value) 
		{

			$DocpartManufacturer = new DocpartManufacturer($value["brand"],
			    0,
				$value["name"],
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
}
	
	


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new avtobat_enclosure($_POST["article"], $storage_options);
$ob->client = 0;
exit(json_encode($ob));
?>