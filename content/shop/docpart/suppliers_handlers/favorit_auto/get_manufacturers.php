<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class favorit_auto_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $key = urlencode($storage_options["key"]);
		/*****Учетные данные*****/
        
		//СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&showname=on");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $curl_result = curl_exec($ch);
        
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&showname=on", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка CURL", print_r(curl_error($ch), true) );
			}
		}
		
		
		curl_close($ch);
		
		
		
		if(empty($curl_result))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Позвоните менеджеру поставщика и попросите его включить доступ к API, указав ему IP-адрес Вашего сайта" );
			}
			return;
		}
		
		$curl_result = json_decode($curl_result, true);
		
		
		$curl_result_brend = $curl_result["goods"];
		
		//По каждому производителю
		for($i = 0; $i < count($curl_result_brend); $i++)
		{
			$DocpartManufacturer = new DocpartManufacturer($curl_result_brend[$i]['brand'],
			   0,
				$curl_result_brend[$i]['name'],
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
};//~class favorit_auto_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new favorit_auto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>