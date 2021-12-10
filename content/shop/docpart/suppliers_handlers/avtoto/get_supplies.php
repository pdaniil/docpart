<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


$pathClasses = $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/avtoto/classes/";

set_include_path(get_include_path(). PATH_SEPARATOR .$pathClasses);

spl_autoload_register(function($class){
	require_once($class.".php");
});


class avtoto_enclosure
{
	public $result = 0;
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Логи запросов к API расставлены непосредственно в библиотеке поставщика avtoto/classes/avtoto_parts.php");
		
		//*********************************************************************
			//Инициализируем переменные
		$wsdl = "http://www.avtoto.ru/services/search/soap.wsdl";
		
		$params = array();//Параметры запроса
		
		$params["user_id"] 		= $storage_options["customer_id"];
		$params["user_login"]		= $storage_options["login"];
		$params["user_password"]	= $storage_options["password"];

		$analogs = 'on';
		$brand = $manufacturers[0]["manufacturer"];
		
		$result = array(); //Результат запроса
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Параметры для создания объекта avtoto_parts: ".print_r($params, true) );
		
		//Клиент avtoto
		$avtoto = new avtoto_parts($params);
		// Максимальное время выполнения 5 сек
		$avtoto->set_search_extension_time_limit(5); 
		
		//********************************************************************
			
		//Выполняем запрос
		$data = $avtoto->get_parts_brand($article, $brand, $limit, $analogs);
		$errors = $avtoto->get_errors();


		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("После обращения к поставщику. data: ".print_r($data,true) );
		}
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("После обращения к поставщику. errors: ".print_r($errors,true) );
		}
		
		$search_id = 0;

		if(!$errors && $data) 
		{
			if(isset($data['Info']['Errors']) && $data['Info']['Errors']) 
			{ 
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($data, true) );
				}
				
				return;
			}

			if(isset($data['Info']['SearchId'])) 
			{ 
				//ID поиска на сервере AvtoTO
				$search_id = $data['Info']['SearchId'];	
			}

			//Обрабатываем данные
			if(isset($data['Parts']) && $data['Parts']) 
			{ 
				// $this->writeDump($data['Parts'], 'parts.log');	
				foreach($data['Parts'] as $part)
				{
					if($part["MaxCount"] < 1)continue;


					$price = (float)$part["Price"];

					//Обработка времени доставки:
					$time = explode("-", $part["Delivery"]);
					
					if( count($time) == 2) 
					{    
						$timeToExe = $time[0];
						$timeToExeG = $time[1];
					}
					else if( count($time) == 1) 
					{
						$timeToExe = $time[0];
						$timeToExeG = $timeToExe;
					}
					else
					{
						$timeToExe = 1;
						$timeToExeG = $timeToExe;
					}

					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$DocpartProduct = new DocpartProduct($part["Manuf"],
						$part["Code"],
						$part["Name"],
						$part["MaxCount"],
						$price+$price*$markup,
						$timeToExe + $storage_options["additional_time"],
						$timeToExeG + $storage_options["additional_time"],
						$part["Storage"],
						$part["BaseCount"],
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2,
						0,
						0,
						'',
						json_encode(array(
							"SearchId" => $search_id,
							"RemoteID" => uniqid(date('YdmHi') . '_'),
							"PartId" => $part['AvtotoData']['PartId'],
						)),
						array("rate"=>$storage_options["rate"])
					);
					
					if($DocpartProduct->valid)
					{
						array_push($this->Products, $DocpartProduct);
					}
					
				}
				
				$this->result = 1;
			}

		}
		else 
		{
			if($errors) 
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($errors, true) );
				}
			} 
			else
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Ответ не получен" );
				}
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	} //~ __construct($article, $manufacturers, $storage_options)
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP с библиотекой avtoto_parts.php") );



$ob = new avtoto_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>