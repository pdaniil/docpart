<?php
/*
Скрипт для реализации второго шага протокола проценки
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class partkom_enclosure
{
	public $result = 0;
	public $Products = array();
	
	public function __construct($arcticle, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//данные авторизации
		$login			= $storage_options['login'];
		$password		= $storage_options['password'];
		$under_domain	= $storage_options['under_domain'];
		$maker_id 		= $manufacturers[0]["manufacturer_id"];
		
		// var_dump($manufacturers);
		
		$url 			= "http://{$under_domain}.part-kom.ru/engine/api/v3/search/parts?number={$arcticle}&maker_id={$maker_id}&find_substitutes=1";//URL
		$authStr 		= base64_encode("{$login}:{$password}");//Строка авторизации
		
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
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$arcticle." и ID производителя ".$manufacturers[0]["manufacturer_id"]." (".$manufacturers[0]["manufacturer"].")", $url."<br>Заголовки:<br>Content-Type: application/json, Accept: application/json, Authorization: Basic base64_encode($login:$password)", $curlResult, print_r(json_decode($curlResult, true), true) );
		}
		
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		$products = json_decode($curlResult, true);
		
		
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
		
		
		foreach($products as $product)
		{
			$price = $product["price"];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			$data_for_sao["providerId"] = $product["providerId"];
			$data_for_sao["makerId"] = $product["makerId"];
			$json = json_encode($data_for_sao);
			
			
			$DocpartProduct = new DocpartProduct($product["maker"],//Производитель
				$product["number"],//Артикул
				$product["description"],//Наименование
				$product["quantity"],//Кол-во 
				$price+$price*$markup,//Цена
				$product["expectedDays"]+$storage_options["additional_time"],//Срок поставки
				$product["guaranteedDays"]+$storage_options["additional_time"],//Гарантированный срок поставки
				$product["placement"],//Склад поставщика
				$product["minQuantity"],//Минимальный заказ
				$storage_options["probability"],//Вероятность поставки
				$storage_options["office_id"],//ID магазина (Docpart)
				$storage_options["storage_id"],//ID Склада (Docpart)
				$storage_options["office_caption"],//Название точки обслуживания
				$storage_options["color"],//Цвет
				$storage_options["storage_caption"],//Название склада
				$product["price"],//Закупочная цена
				$markup,//Наценка
				2,//Тип продутка(Trelax/Docpart)
				0, //ID продукта в каталоге
				0, //ID записи поставки на складе
				'', //URL продукта
				$json, //Json параметры для SAO
				array("rate"=>$storage_options["rate"]) //Дополнительный аргумент
			);
			
			// var_dump($DocpartProduct);
			if($DocpartProduct->valid)
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


$ob = new partkom_enclosure($_POST['article'], json_decode($_POST['manufacturers'], true), $storage_options);
exit(json_encode($ob));
?>