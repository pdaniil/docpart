<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class partkom_enclosure
{
	public $status = 0;//Флаг ответа
	public $ProductsManufacturers = array(); //Массив брендов
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//данные авторизации
		$login			= $storage_options['login'];
		$password		= $storage_options['password'];
		$under_domain	= $storage_options['under_domain'];
		
		$url 			= "http://{$under_domain}.part-kom.ru/engine/api/v3/search/brands?number={$article}";//URL
		
		
		$authStr 		= base64_encode("{$login}:{$password}");//Строка авторизации
		
		// var_dump($url);
		
		$arrayHeaders	= array('Content-Type: application/json', 'Accept: application/json', "Authorization: Basic {$authStr}");//Посылаемые заголовки
		
		//запрос к парткому(REST V3)
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeaders);

		$curlResult = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url."<br>Заголовки:<br>Content-Type: application/json, Accept: application/json, Authorization: Basic base64_encode($login:$password)", $curlResult, print_r(json_decode($curlResult, true), true) );
		}
		
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		$manufacturers = json_decode($curlResult, true);
		
		//Обработка ошибок json(Можно записать в лог)
		if(json_last_error())
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка парсинга JSON", print_r(json_last_error(), true) );
			}
			
			return;
		}
		
		//Создаём объект DocpartManufacturer;
		foreach($manufacturers as $manufacturer)
		{
			$DocpartManufacturer = new DocpartManufacturer($manufacturer["name"],
				$manufacturer['id'],
				'',//Название детали
				$storage_options['office_id'],
				$storage_options['storage_id'],
				true, //Для синонима только 1 запрос
				array()//Дополнительные параметры
			);
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new partkom_enclosure($_POST['article'], $storage_options);
exit(json_encode($ob));
?>