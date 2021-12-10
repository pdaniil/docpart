<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class moskvorechie_enclosure
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
        $key = $storage_options["key"];
		/*****Учетные данные*****/
        
        
        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://portal.moskvorechie.ru/portal.api?l={$login}&p={$key}&act=brand_by_nr&nr={$article}&cs=utf8&name");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
        
		
		//ЛОГ API-запроса
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article,"http://portal.moskvorechie.ru/portal.api?l={$login}&p={$key}&act=brand_by_nr&nr={$article}&cs=utf8&name",$curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		
		
		
        $curl_result = json_decode($curl_result, true);
        $curl_result = $curl_result["result"];
        
        
        //Формируем массив брэндов:
        foreach ($curl_result as &$value) 
		{
			$DocpartManufacturer = new DocpartManufacturer($value["brand"],
			    0,
				$value['name'],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);
			

			array_push($this->ProductsManufacturers, $DocpartManufacturer);
        }
        
		
		//ЛОГ результата запроса
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		
        $this->status = 1;
	}//~function __construct($article)
};//~class moskvorechie_enclosure



$storage_options = json_decode($_POST["storage_options"], true);


//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new moskvorechie_enclosure($_POST["article"], $storage_options );
exit(json_encode($ob));
?>