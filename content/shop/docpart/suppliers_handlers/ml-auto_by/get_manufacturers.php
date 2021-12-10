<?php
/*
https://www.ml-auto.by/webservice/info/
*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class ml_auto_by_enclosure
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
        $pass = $storage_options["pass"];
		/*****Учетные данные*****/
        
        
        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ: /webservice/ArticleSearch/
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "https://www.ml-auto.by/webservice/ArticleSearch/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "LOGIN=$login&PASSWORD=$pass&ARTICLE=$article");
        // загрузка страницы и выдача её браузеру
        $curl_result = trim(curl_exec($ch));
		
		
		// Вырезаем BOM
		if(substr($curl_result, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)){
			$curl_result = substr($curl_result, 3);
		}
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://www.ml-auto.by/webservice/ArticleSearch/<br>Метод POST<br>Поля "."LOGIN=$login&PASSWORD=$pass&ARTICLE=$article", $curl_result, print_r(json_decode($curl_result, true), true) );
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
        
		
		
        
		
		
        $curl_result = json_decode($curl_result, true);
		
		
		if($curl_result["STATUS"] == '200'){
			
			foreach ($curl_result['RESPONSE'] as $value) 
			{
				if(empty($value["BRAND"])){
					continue;
				}
				
				if(empty($value["NAME"])){
					$value["NAME"] = 'Наименование не указано поставщиком';
				}
				
				$DocpartManufacturer = new DocpartManufacturer($value["BRAND"],
					0,
					$value["NAME"],
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
};//~class ml_auto_by_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new ml_auto_by_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>