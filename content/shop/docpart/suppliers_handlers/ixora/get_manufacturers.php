<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class ixora_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$AuthCode = $storage_options["authcode"];
		/*****Учетные данные*****/
        
		
		//Запрос товаров по артикулу и производителю
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://ws.ixora-auto.ru/soap/ApiService.asmx/GetMakersXML?AuthCode=".$AuthCode."&Number=$article");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($curl_result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://ws.ixora-auto.ru/soap/ApiService.asmx/GetMakersXML?AuthCode=".$AuthCode."&Number=$article", htmlspecialchars($curl_result, ENT_QUOTES, "UTF-8"), print_r($php_object, true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
        
        $curl_result = simplexml_load_string($curl_result);
        $curl_result = json_encode($curl_result);
        $curl_result = json_decode($curl_result, true);
        
        $MakerInfo = $curl_result["MakerInfo"];
        
		if( !empty($MakerInfo["id"]) )
		{
			$MakerInfo = array($MakerInfo);
		}
		
		foreach ($MakerInfo as $value) 
		{
			$DocpartManufacturer = new DocpartManufacturer($value["name"],
			    $value["id"],
				"Наименование не указано",
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
};//~class ixora_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new ixora_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>