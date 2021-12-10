<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/*
	Поиск происходит по артикулу, учета брендов нет в их API
	Должен быть доступ для ip
*/

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class carreta_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
		
		
		$ch = curl_init("http://api.carreta.ru/v1/search/?api_key=$api_key&q=$article");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result=curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://api.carreta.ru/v1/search/?api_key=$api_key&q=$article", $result, print_r(json_decode($result, true), true) );
		}
		
		if( $result == '{"error": "Unauthorized."}' )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка Unauthorized.", "1. Проверьте ключ API<br>2. Укажите поставщику правильный IP-адрес Вашего сайта" );
			}
		}
		
		$result = json_decode($result, true);

		$data = $result["objects"];
		
		for($i=0; $i < count($data); $i++)
		{
			$price = $data[$i]["price"];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($data[$i]["maker"],
				$data[$i]["code"],
				$data[$i]["name"],
				$data[$i]["qty"],
				$price + $price*$markup,
				$data[$i]["period_min"] + $storage_options["additional_time"],
				$data[$i]["period_max"] + $storage_options["additional_time"],
				NULL,
				$data[$i]["min_qty"],
				$data[$i]["stat"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price,
				$markup,
				2,0,0,'',null,array("rate"=>$storage_options["rate"])
				);
			
			if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class carreta_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new carreta_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>