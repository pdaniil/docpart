<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class favorit_auto_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
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
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");
		}
		
		for($b=0; $b < count($curl_result_brend); $b++)
		{
			$manufacturer = urlencode($curl_result_brend[$b]["brand"]);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&brand=$manufacturer&showname=on&analogues=on");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&brand=$manufacturer&showname=on&analogues=on", $curl_result, print_r(json_decode($curl_result, true), true) );
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
			
			$curl_result = json_decode($curl_result, true);
			
			$curl_result = $curl_result["goods"];
			
			for($i=0; $i < count($curl_result); $i++)
			{
				//Данные на весь блок
				$manufacturer = $curl_result[$i]["brand"];
				$number = $curl_result[$i]["number"];
				$count = $curl_result[$i]["count"];
				$name = $curl_result[$i]["name"];
				$price = $curl_result[$i]["price"];
				$min_order = $curl_result[$i]["rate"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($manufacturer,
					$number,
					$name,
					$count,
					$price + $price*$markup,
					$storage_options["additional_time"],
					$storage_options["additional_time"],
					NULL,
					$min_order,
					$storage_options["probability"],
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
				
				
				// аналоги
				$analogues = $curl_result[$i]["analogues"];
				if(!empty($analogues)){
					foreach($analogues as $analog){
						
						//Данные на весь блок
						$manufacturer = $analog["brand"];
						$number = $analog["number"];
						$count = $analog["count"];
						$name = $analog["name"];
						$price = $analog["price"];
						$min_order = $analog["rate"];
						
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($manufacturer,
							$number,
							$name,
							$count,
							$price + $price*$markup,
							$storage_options["additional_time"],
							$storage_options["additional_time"],
							NULL,
							$min_order,
							$storage_options["probability"],
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
				}
				
			}
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
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